<?php

namespace App\Services;

use App\Models\Asistente;
use App\Models\Inmueble;
use App\Models\Reunion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class AsistenteService
{
    public function __construct(private readonly VotoService $votoService)
    {
    }

    /**
     * Crea un asistente para la reunión indicada y sincroniza sus inmuebles.
     * El asistente es efímero: existe solo en el contexto de esa reunión.
     */
    public function create(Reunion $reunion, array $data): Asistente
    {
        if (! empty($data['codigo_barras'])) {
            $this->guardBarcodeEdition((int) $data['codigo_barras']);
        }

        $this->guardInmueblesUnicos($reunion, array_column($data['inmuebles'], 'inmueble_id'));

        $asistente = Asistente::query()->create([
            'reunion_id' => $reunion->id,
            'telefono' => $data['telefono'] ?? null,
            'codigo_barras' => $data['codigo_barras'] ?? null,
        ]);

        $this->syncInmuebles($asistente, $data['inmuebles']);

        return $asistente->load('inmuebles');
    }

    public function delete(Asistente $asistente): void
    {
        $asistente->delete();
    }

    /**
     * Registra la presencia (check-in) de un asistente ya identificado.
     *
     * Busca la reunión en curso, localiza la pregunta de quórum abierta con la
     * opción "PRESENTE" y registra el voto por cada inmueble activo del asistente.
     *
     * @return array{ya_registrado: bool, inmuebles_registrados: int}
     *
     * @throws RuntimeException
     */
    public function checkIn(Asistente $asistente, ?int $reunionId = null): array
    {
        $reunionQuery = Reunion::query()->where('estado', 'en_curso');

        if ($reunionId !== null) {
            $reunionQuery->where('id', $reunionId);
        }

        $reunion = $reunionQuery->first();

        if (! $reunion) {
            throw new RuntimeException(
                $reunionId
                    ? 'La reunión indicada no está en curso.'
                    : 'No hay ninguna reunión en curso.'
            );
        }

        $pregunta = $reunion->preguntas()
            ->where('estado', 'abierta')
            ->whereHas('opciones', fn ($q) => $q->whereRaw("UPPER(texto) LIKE '%PRESENTE%'"))
            ->first();

        if (! $pregunta) {
            throw new RuntimeException(
                'No hay una pregunta de quórum abierta en la reunión actual. '.
                'Abra primero la pregunta de verificación de quórum.'
            );
        }

        $opcion = $pregunta->opciones()->whereRaw("UPPER(texto) LIKE '%PRESENTE%'")->first();

        $inmuebles = $asistente->inmuebles()->where('inmuebles.activo', true)->get();

        if ($inmuebles->isEmpty()) {
            throw new RuntimeException('El asistente no tiene inmuebles activos asociados.');
        }

        $registrados = 0;
        $yaRegistrado = false;

        foreach ($inmuebles as $inmueble) {
            $voto = $this->votoService->registrarPorInmueble($pregunta, $opcion, $inmueble, $asistente, null);

            if ($voto !== null) {
                $registrados++;
            } else {
                $yaRegistrado = true;
            }
        }

        return [
            'ya_registrado' => $registrados === 0 && $yaRegistrado,
            'inmuebles_registrados' => $registrados,
        ];
    }

    /**
     * Busca al asistente por codigo_barras o telefono y registra su presencia.
     * Operación de puerta: una sola llamada sin conocer el ID del asistente.
     *
     * @param  array{codigo_barras?: int, telefono?: string, reunion_id?: int}  $data
     * @return array{asistente: Asistente, ya_registrado: bool, inmuebles_registrados: int}
     *
     * @throws RuntimeException
     */
    public function checkInByCodigo(array $data): array
    {
        $asistente = null;

        if (! empty($data['codigo_barras'])) {
            $asistente = Asistente::query()
                ->where('codigo_barras', (int) $data['codigo_barras'])
                ->first();
        }

        if ($asistente === null && ! empty($data['codigo_acceso'])) {
            // compatibilidad con campo legacy en requests transitivos
            $asistente = Asistente::query()
                ->where('codigo_barras', (int) $data['codigo_acceso'])
                ->first();
        }

        if ($asistente === null) {
            throw new RuntimeException('No se encontró ningún asistente con el código indicado.');
        }

        $result = $this->checkIn(
            $asistente,
            isset($data['reunion_id']) ? (int) $data['reunion_id'] : null
        );

        return array_merge($result, ['asistente' => $asistente->load('inmuebles')]);
    }

    private function syncInmuebles(Asistente $asistente, array $inmuebles): void
    {
        $syncData = [];

        foreach ($inmuebles as $item) {
            $inmueble = Inmueble::query()->findOrFail((int) $item['inmueble_id']);

            $syncData[$inmueble->id] = [
                'coeficiente' => isset($item['coeficiente']) ? (float) $item['coeficiente'] : (float) $inmueble->coeficiente,
                'poder_url' => $item['poder_url'] ?? null,
            ];
        }

        $asistente->inmuebles()->sync($syncData);
    }

    /**
     * Verifica que ninguno de los inmuebles indicados esté ya registrado
     * por otro asistente en la misma reunión.
     *
     * @param  int[]  $inmuebleIds
     *
     * @throws RuntimeException
     */
    private function guardInmueblesUnicos(Reunion $reunion, array $inmuebleIds): void
    {
        $ocupados = DB::table('asistente_inmueble')
            ->join('asistentes', 'asistente_inmueble.asistente_id', '=', 'asistentes.id')
            ->where('asistentes.reunion_id', $reunion->id)
            ->whereIn('asistente_inmueble.inmueble_id', $inmuebleIds)
            ->pluck('asistente_inmueble.inmueble_id')
            ->toArray();

        if (! empty($ocupados)) {
            throw new RuntimeException(
                'Los siguientes inmuebles ya están registrados en esta reunión: '.implode(', ', $ocupados).'.'
            );
        }
    }

    /**
     * Bloquea la asignación o cambio del codigo_barras cuando hay una pregunta
     * de tipo VOTACION abierta. Las preguntas QUORUM_CHECK no bloquean, permitiendo
     * registrar asistentes con barcode incluso durante el paso de quórum.
     *
     * @throws RuntimeException
     */
    private function guardBarcodeEdition(int $codigoBarras): void
    {
        if (! Schema::hasTable('preguntas')) {
            return;
        }

        $hasOpenVotacion = DB::table('preguntas')
            ->where('estado', 'abierta')
            ->where('tipo', 'VOTACION')
            ->exists();

        if ($hasOpenVotacion) {
            throw new RuntimeException(
                'No se puede asignar o cambiar el codigo_barras mientras exista una votacion abierta.'
            );
        }
    }
}
