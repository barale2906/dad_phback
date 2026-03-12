<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrdenDia\CargaMasivaOrdenDiaRequest;
use App\Http\Requests\OrdenDia\MarcarEjecutadoRequest;
use App\Http\Requests\OrdenDia\ReorderOrdenDiaRequest;
use App\Http\Requests\OrdenDia\StoreOrdenDiaItemRequest;
use App\Http\Requests\OrdenDia\UpdateOrdenDiaItemRequest;
use App\Http\Resources\OrdenDiaItemResource;
use App\Models\OrdenDiaItem;
use App\Models\Reunion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class OrdenDiaController extends Controller
{
    public function index(Reunion $reunion): AnonymousResourceCollection
    {
        Gate::authorize('view', $reunion);

        return OrdenDiaItemResource::collection(
            $reunion->ordenDiaItems()->orderBy('orden')->get()
        );
    }

    public function store(StoreOrdenDiaItemRequest $request, Reunion $reunion): JsonResponse
    {
        Gate::authorize('update', $reunion);

        $maxOrden = (int) $reunion->ordenDiaItems()->max('orden');
        $orden = $request->integer('orden') ?: ($maxOrden + 1);

        $item = OrdenDiaItem::query()->create([
            'reunion_id' => $reunion->id,
            'titulo' => $request->string('titulo')->value(),
            'descripcion' => $request->input('descripcion'),
            'orden' => $orden,
            'ejecutado' => false,
        ]);

        return response()->json([
            'message' => 'Punto del orden del dia creado correctamente.',
            'data' => new OrdenDiaItemResource($item),
        ], 201);
    }

    public function update(UpdateOrdenDiaItemRequest $request, OrdenDiaItem $item): JsonResponse
    {
        Gate::authorize('update', $item);

        $item->update($request->validated());

        return response()->json([
            'message' => 'Punto del orden del dia actualizado correctamente.',
            'data' => new OrdenDiaItemResource($item->fresh()),
        ], 200);
    }

    public function reordenar(ReorderOrdenDiaRequest $request, Reunion $reunion): JsonResponse
    {
        Gate::authorize('update', $reunion);

        DB::transaction(function () use ($request, $reunion): void {
            $items = $request->validated('items');
            $offset = 100000;

            // Primera pasada: valores temporales fuera de rango para evitar
            // colisión del índice único (reunion_id, orden) durante la actualización.
            foreach ($items as $itemData) {
                OrdenDiaItem::query()
                    ->where('reunion_id', $reunion->id)
                    ->where('id', $itemData['id'])
                    ->update(['orden' => $itemData['orden'] + $offset]);
            }

            // Segunda pasada: valores definitivos, ya sin riesgo de colisión.
            foreach ($items as $itemData) {
                OrdenDiaItem::query()
                    ->where('reunion_id', $reunion->id)
                    ->where('id', $itemData['id'])
                    ->update(['orden' => $itemData['orden']]);
            }
        });

        return response()->json([
            'message' => 'Orden del dia reordenado correctamente.',
            'data' => OrdenDiaItemResource::collection(
                $reunion->ordenDiaItems()->orderBy('orden')->get()
            ),
        ], 200);
    }

    public function marcarEjecutado(MarcarEjecutadoRequest $request, OrdenDiaItem $item): JsonResponse
    {
        Gate::authorize('update', $item);

        $item->update([
            'ejecutado' => $request->boolean('ejecutado', true),
        ]);

        return response()->json([
            'message' => 'Estado de ejecucion del punto actualizado correctamente.',
            'data' => new OrdenDiaItemResource($item->fresh()),
        ], 200);
    }

    public function cargaMasiva(CargaMasivaOrdenDiaRequest $request, Reunion $reunion): JsonResponse
    {
        Gate::authorize('update', $reunion);

        $raw   = file_get_contents($request->file('archivo')->getRealPath());
        $lines = preg_split('/\r\n|\r|\n/', (string) $raw);
        $lines = array_values(array_filter($lines, fn (?string $l): bool => trim((string) $l) !== ''));

        if (count($lines) < 2) {
            return response()->json([
                'message' => 'Carga masiva finalizada con errores.',
                'data'    => [
                    'creados' => 0,
                    'errores' => ['archivo' => ['El archivo no contiene filas de datos.']],
                ],
            ], 422);
        }

        $headers   = str_getcsv((string) array_shift($lines));
        $headerMap = [];
        foreach ($headers as $idx => $header) {
            $headerMap[Str::lower(trim($header))] = $idx;
        }

        if (! array_key_exists('titulo', $headerMap)) {
            return response()->json([
                'message' => 'Carga masiva finalizada con errores.',
                'data'    => [
                    'creados' => 0,
                    'errores' => ['archivo' => ['Falta la columna obligatoria: titulo.']],
                ],
            ], 422);
        }

        $creados = 0;
        $errores = [];
        $maxOrden = (int) $reunion->ordenDiaItems()->max('orden');

        DB::transaction(function () use ($lines, $headerMap, $reunion, &$creados, &$errores, &$maxOrden): void {
            foreach ($lines as $lineNumber => $line) {
                $rowNumber = $lineNumber + 2;
                $row       = str_getcsv($line);

                $titulo = trim((string) ($row[$headerMap['titulo']] ?? ''));

                if ($titulo === '') {
                    $errores[$rowNumber][] = 'La columna titulo es obligatoria.';
                    continue;
                }

                if (mb_strlen($titulo) > 255) {
                    $errores[$rowNumber][] = 'El titulo no puede superar 255 caracteres.';
                    continue;
                }

                $descripcion = null;
                if (array_key_exists('descripcion', $headerMap)) {
                    $raw = trim((string) ($row[$headerMap['descripcion']] ?? ''));
                    $descripcion = $raw !== '' ? $raw : null;
                }

                $orden = null;
                if (array_key_exists('orden', $headerMap)) {
                    $ordenRaw = trim((string) ($row[$headerMap['orden']] ?? ''));
                    if ($ordenRaw !== '' && is_numeric($ordenRaw) && (int) $ordenRaw >= 1) {
                        $orden = (int) $ordenRaw;
                    }
                }

                if ($orden === null) {
                    $maxOrden++;
                    $orden = $maxOrden;
                }

                OrdenDiaItem::query()->create([
                    'reunion_id'  => $reunion->id,
                    'titulo'      => $titulo,
                    'descripcion' => $descripcion,
                    'orden'       => $orden,
                    'ejecutado'   => false,
                ]);

                $creados++;
            }
        });

        $hasErrors = ! empty($errores);

        return response()->json([
            'message' => $hasErrors ? 'Carga masiva finalizada con errores.' : 'Carga masiva finalizada correctamente.',
            'data'    => [
                'creados' => $creados,
                'errores' => $errores,
            ],
        ], $hasErrors ? 422 : 200);
    }
}
