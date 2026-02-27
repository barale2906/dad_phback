<?php

namespace App\Services;

use App\Models\Asistente;
use App\Models\Inmueble;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class AsistenteService
{
    public function create(array $data): Asistente
    {
        $this->guardBarcodeEdition($data['barcode_numero'] ?? null);

        $asistente = Asistente::query()->create([
            'usuario_id' => $data['usuario_id'] ?? null,
            'nombre' => $data['nombre'],
            'documento' => $data['documento'] ?? null,
            'telefono' => $data['telefono'] ?? null,
            'codigo_acceso' => $this->generateUniqueCodigoAcceso(),
            'barcode_numero' => $data['barcode_numero'] ?? null,
            'tipo_asistente' => $data['tipo_asistente'],
        ]);

        $this->syncInmuebles($asistente, $data['inmuebles']);

        return $asistente->load('inmuebles');
    }

    public function update(Asistente $asistente, array $data): Asistente
    {
        if (array_key_exists('barcode_numero', $data) && $data['barcode_numero'] !== $asistente->barcode_numero) {
            $this->guardBarcodeEdition($data['barcode_numero']);
        }

        $asistente->update([
            'usuario_id' => $data['usuario_id'] ?? null,
            'nombre' => $data['nombre'],
            'documento' => $data['documento'] ?? null,
            'telefono' => $data['telefono'] ?? null,
            'barcode_numero' => $data['barcode_numero'] ?? null,
            'tipo_asistente' => $data['tipo_asistente'],
        ]);

        $this->syncInmuebles($asistente, $data['inmuebles']);

        return $asistente->load('inmuebles');
    }

    public function delete(Asistente $asistente): void
    {
        $asistente->delete();
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

    private function generateUniqueCodigoAcceso(): string
    {
        do {
            $candidate = Str::upper(Str::random(8));
        } while (Asistente::query()->where('codigo_acceso', $candidate)->exists());

        return $candidate;
    }

    private function guardBarcodeEdition(mixed $barcodeValue): void
    {
        if ($barcodeValue === null) {
            return;
        }

        if (! Schema::hasTable('preguntas')) {
            return;
        }

        $hasOpenQuestion = DB::table('preguntas')->where('estado', 'abierta')->exists();

        if ($hasOpenQuestion) {
            throw new RuntimeException('No se puede editar barcode_numero mientras exista una votacion abierta.');
        }
    }
}
