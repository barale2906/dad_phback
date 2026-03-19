<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Asistente\CheckInAsistenteRequest;
use App\Http\Requests\Asistente\CheckInByCodigoRequest;
use App\Http\Requests\Asistente\RegistroTardioRequest;
use App\Http\Requests\Asistente\StoreAsistenteRequest;
use App\Http\Resources\AsistenteResource;
use App\Models\Asistente;
use App\Models\Reunion;
use App\Services\AsistenteService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

/**
 * Gestión de asistentes a las reuniones de la propiedad horizontal.
 *
 * Un asistente es un registro efímero y por reunión: representa a una persona
 * presente el día de la reunión, identificada por teléfono (WhatsApp) o por
 * el código de barras físico que logística le asigna en el momento.
 * Un asistente puede representar uno o varios inmuebles.
 */
#[Group('Asistentes', weight: 4)]
class AsistenteController extends Controller
{
    public function __construct(private readonly AsistenteService $asistenteService)
    {
    }

    /**
     * Listar asistentes de una reunión.
     *
     * @authenticated
     *
     * @urlParam reunion integer required ID de la reunión. Example: 1
     * @queryParam telefono string Filtra por teléfono (búsqueda parcial). Example: 300
     * @queryParam codigo_barras integer Filtra por número de código de barras exacto. Example: 42
     *
     * @response 200 { "data": [ { "id": 1, "reunion_id": 1, "telefono": "573001234567", "codigo_barras": 42, "inmuebles": [], "created_at": "..." } ] }
     */
    public function index(Reunion $reunion): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Asistente::class);

        $query = $reunion->asistentes()->with('inmuebles');

        if (request()->filled('telefono')) {
            $query->where('telefono', 'like', '%'.request()->query('telefono').'%');
        }

        if (request()->filled('codigo_barras')) {
            $query->where('codigo_barras', (int) request()->query('codigo_barras'));
        }

        return AsistenteResource::collection($query->orderByDesc('created_at')->paginate(20));
    }

    /**
     * Crear asistente en una reunión.
     *
     * Registra la presencia de una persona en la reunión indicada.
     * La reunión debe estar en estado `en_curso`.
     * Se debe indicar al menos `telefono` o `codigo_barras`.
     *
     * @authenticated
     *
     * @urlParam reunion integer required ID de la reunión en curso. Example: 1
     * @bodyParam telefono string nullable Teléfono (formato internacional sin +). Example: 573001234567
     * @bodyParam codigo_barras integer nullable Número del barcode físico asignado por logística. Example: 42
     * @bodyParam inmuebles array required Inmuebles que representa (mínimo 1).
     * @bodyParam inmuebles[].inmueble_id integer required ID del inmueble. Example: 3
     * @bodyParam inmuebles[].coeficiente number nullable Snapshot del coeficiente. Si se omite, toma el del inmueble. Example: 1.234560
     * @bodyParam inmuebles[].poder_url string nullable URL del documento de poder (para apoderados). Example: https://storage.example.com/poder.pdf
     *
     * @response 201 { "message": "Asistente registrado correctamente.", "data": { "id": 1, "reunion_id": 1, "telefono": "573001234567", "codigo_barras": 42, "inmuebles": [] } }
     * @response 422 scenario="Reunión no en curso" { "message": "Solo se pueden registrar asistentes en una reunión en curso." }
     * @response 422 scenario="Quórum cerrado" { "message": "No se pueden registrar asistentes porque la pregunta de quórum ya fue cerrada." }
     * @response 409 scenario="Identificación bloqueada" { "message": "No se puede asignar o cambiar el telefono o codigo_barras mientras exista una votacion abierta." }
     */
    public function store(StoreAsistenteRequest $request, Reunion $reunion): JsonResponse
    {
        Gate::authorize('create', Asistente::class);

        if ($reunion->estado !== 'en_curso') {
            return response()->json([
                'message' => 'Solo se pueden registrar asistentes en una reunión en curso.',
            ], 422);
        }

        try {
            $asistente = $this->asistenteService->create($reunion, $request->validated());
        } catch (RuntimeException $exception) {
            $isQuorumCerrado = str_contains($exception->getMessage(), 'quórum ya fue cerrada');
            $status = $isQuorumCerrado ? 422 : 409;

            return response()->json(['message' => $exception->getMessage()], $status);
        }

        return response()->json([
            'message' => 'Asistente registrado correctamente.',
            'data' => new AsistenteResource($asistente),
        ], 201);
    }

    /**
     * Ver asistente.
     *
     * @authenticated
     *
     * @urlParam reunion integer required ID de la reunión. Example: 1
     * @urlParam asistente integer required ID del asistente. Example: 1
     *
     * @response 200 { "data": { "id": 1, "reunion_id": 1, "telefono": "573001234567", "codigo_barras": 42, "inmuebles": [] } }
     */
    public function show(Reunion $reunion, Asistente $asistente): AsistenteResource
    {
        Gate::authorize('view', $asistente);

        return new AsistenteResource($asistente->load('inmuebles'));
    }

    /**
     * Eliminar asistente.
     *
     * Elimina el registro de presencia. Si se cometió un error al registrar,
     * se elimina y se vuelve a crear.
     *
     * @authenticated
     *
     * @urlParam reunion integer required ID de la reunión. Example: 1
     * @urlParam asistente integer required ID del asistente. Example: 1
     *
     * @response 200 { "message": "Asistente eliminado correctamente." }
     */
    public function destroy(Reunion $reunion, Asistente $asistente): JsonResponse
    {
        Gate::authorize('delete', $asistente);

        $this->asistenteService->delete($asistente);

        return response()->json([
            'message' => 'Asistente eliminado correctamente.',
        ], 200);
    }

    /**
     * Registro tardío — asistente que llega después del cierre del quórum.
     *
     * Permite registrar en la tabla de asistentes a una persona cuya incorporación
     * fue aprobada por la asamblea una vez iniciada la reunión y cerrado el quórum.
     * El asistente queda vinculado a sus inmuebles y recibirá las votaciones
     * activas y futuras por WhatsApp si aportó su número de teléfono.
     * No se registra presencia en la pregunta de quórum (ya está cerrada).
     *
     * @authenticated
     *
     * @urlParam reunion integer required ID de la reunión en curso. Example: 1
     * @bodyParam telefono string nullable Teléfono en formato internacional sin +. Example: 573001234567
     * @bodyParam codigo_barras integer nullable Número del barcode físico. Example: 42
     * @bodyParam inmuebles array required Inmuebles que representa (mínimo 1).
     * @bodyParam inmuebles[].inmueble_id integer required ID del inmueble. Example: 3
     * @bodyParam inmuebles[].coeficiente number nullable Snapshot del coeficiente. Example: 1.234560
     * @bodyParam inmuebles[].poder_url string nullable URL del documento de poder. Example: https://storage.example.com/poder.pdf
     *
     * @response 201 { "message": "Asistente tardío registrado correctamente.", "data": { "id": 5, "reunion_id": 1, "telefono": "573001234567", "codigo_barras": null, "inmuebles": [] } }
     * @response 422 scenario="Reunión no en curso" { "message": "Solo se pueden registrar asistentes en una reunión en curso." }
     * @response 409 scenario="Identificación bloqueada" { "message": "No se puede asignar o cambiar el telefono o codigo_barras mientras exista una votacion abierta." }
     * @response 409 scenario="Inmueble ya registrado" { "message": "Los siguientes inmuebles ya están registrados en esta reunión: 3." }
     */
    public function registroTardio(RegistroTardioRequest $request, Reunion $reunion): JsonResponse
    {
        Gate::authorize('create', Asistente::class);

        if ($reunion->estado !== 'en_curso') {
            return response()->json([
                'message' => 'Solo se pueden registrar asistentes en una reunión en curso.',
            ], 422);
        }

        try {
            $asistente = $this->asistenteService->registroTardio($reunion, $request->validated());
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        }

        return response()->json([
            'message' => 'Asistente tardío registrado correctamente.',
            'data'    => new AsistenteResource($asistente),
        ], 201);
    }

    // -------------------------------------------------------------------------
    // Endpoints de puerta (no scoped bajo reunión — operación directa en puerta)
    // -------------------------------------------------------------------------

    /**
     * Check-in por código de barras (endpoint de puerta).
     *
     * Recibe el `codigo_barras` escaneado físicamente, localiza al asistente en
     * la reunión en curso y registra su presencia (PRESENTE) en la pregunta de
     * quórum abierta. Una sola operación — no requiere conocer el ID del asistente.
     *
     * @authenticated
     *
     * @bodyParam codigo_barras integer Número del código de barras. Requerido si no se envía telefono. Example: 42
     * @bodyParam telefono string Teléfono del asistente. Requerido si no se envía codigo_barras. Example: 573001234567
     * @bodyParam reunion_id integer nullable ID de la reunión. Si se omite, usa la reunión en curso. Example: 1
     *
     * @response 200 { "message": "Asistencia registrada correctamente.", "data": { "asistente": {}, "ya_registrado": false, "inmuebles_registrados": 2 } }
     * @response 404 { "message": "No se encontró ningún asistente con el código indicado." }
     * @response 422 { "message": "No hay ninguna reunión en curso." }
     */
    public function checkInByCodigo(CheckInByCodigoRequest $request): JsonResponse
    {
        Gate::authorize('create', Asistente::class);

        try {
            $result = $this->asistenteService->checkInByCodigo($request->validated());
        } catch (RuntimeException $exception) {
            $isNotFound = str_contains($exception->getMessage(), 'No se encontró');

            return response()->json(['message' => $exception->getMessage()], $isNotFound ? 404 : 422);
        }

        $yaRegistrado = $result['ya_registrado'];

        return response()->json([
            'message' => $yaRegistrado
                ? 'El asistente ya había registrado su asistencia.'
                : 'Asistencia registrada correctamente.',
            'data' => [
                'asistente' => new AsistenteResource($result['asistente']),
                'ya_registrado' => $yaRegistrado,
                'inmuebles_registrados' => $result['inmuebles_registrados'],
            ],
        ], 200);
    }

    /**
     * Check-in manual por ID de asistente.
     *
     * Registra la presencia de un asistente ya identificado en la reunión activa.
     *
     * @authenticated
     *
     * @urlParam asistente integer required ID del asistente. Example: 1
     * @bodyParam reunion_id integer nullable ID de la reunión. Si se omite, usa la reunión en curso. Example: 1
     *
     * @response 200 { "message": "Asistencia registrada correctamente.", "data": { "asistente": {}, "ya_registrado": false, "inmuebles_registrados": 1 } }
     * @response 422 { "message": "No hay una pregunta de quórum abierta en la reunión actual." }
     */
    public function checkIn(CheckInAsistenteRequest $request, Asistente $asistente): JsonResponse
    {
        Gate::authorize('update', $asistente);

        try {
            $result = $this->asistenteService->checkIn(
                $asistente,
                $request->filled('reunion_id') ? $request->integer('reunion_id') : null
            );
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $yaRegistrado = $result['ya_registrado'];

        return response()->json([
            'message' => $yaRegistrado
                ? 'El asistente ya había registrado su asistencia.'
                : 'Asistencia registrada correctamente.',
            'data' => [
                'asistente' => new AsistenteResource($asistente->load('inmuebles')),
                'ya_registrado' => $yaRegistrado,
                'inmuebles_registrados' => $result['inmuebles_registrados'],
            ],
        ], 200);
    }
}
