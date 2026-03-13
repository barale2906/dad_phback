<?php

namespace App\Services;

use App\Models\Inmueble;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InmuebleService
{
    public function softDelete(Inmueble $inmueble): void
    {
        $inmueble->delete();
    }

    public function validarCoeficientes(): array
    {
        $total = (float) Inmueble::query()
            ->where('activo', true)
            ->sum('coeficiente');

        $faltante = max(0, round(100 - $total, 6));
        $exceso = max(0, round($total - 100, 6));
        $completo = abs($total - 100.0) < 0.000001;

        return [
            'total_coeficientes' => round($total, 6),
            'completo' => $completo,
            'estado' => $completo ? 'completo' : 'incompleto',
            'faltante' => $faltante,
            'exceso' => $exceso,
        ];
    }

    public function cargaMasiva(UploadedFile $archivo): array
    {
        $raw = file_get_contents($archivo->getRealPath());
        $lines = preg_split('/\r\n|\r|\n/', (string) $raw);
        $lines = array_values(array_filter($lines, fn (?string $line): bool => trim((string) $line) !== ''));

        if (count($lines) < 2) {
            return [
                'creados' => 0,
                'actualizados' => 0,
                'errores' => ['archivo' => ['El archivo no contiene filas de datos.']],
            ];
        }

        $firstLine = (string) array_shift($lines);
        $delimiter = str_contains($firstLine, ';') ? ';' : ',';

        $headers = str_getcsv($firstLine, $delimiter);
        $headerMap = [];
        foreach ($headers as $idx => $header) {
            $headerMap[Str::lower(trim($header))] = $idx;
        }

        $required = ['nomenclatura', 'coeficiente', 'tipo'];
        foreach ($required as $column) {
            if (! array_key_exists($column, $headerMap)) {
                return [
                    'creados' => 0,
                    'actualizados' => 0,
                    'errores' => ['archivo' => ["Falta la columna obligatoria: {$column}."]],
                ];
            }
        }

        $creados = 0;
        $actualizados = 0;
        $errores = [];

        DB::transaction(function () use ($lines, $headerMap, $delimiter, &$creados, &$actualizados, &$errores): void {
            foreach ($lines as $lineNumber => $line) {
                $rowNumber = $lineNumber + 2;
                $row = str_getcsv($line, $delimiter);

                $nomenclatura = trim((string) ($row[$headerMap['nomenclatura']] ?? ''));
                $coeficienteRaw = str_replace(',', '.', trim((string) ($row[$headerMap['coeficiente']] ?? '')));
                $tipo = trim((string) ($row[$headerMap['tipo']] ?? ''));

                if ($nomenclatura === '' || $coeficienteRaw === '' || $tipo === '') {
                    $errores[$rowNumber][] = 'Las columnas nomenclatura, coeficiente y tipo son obligatorias.';
                    continue;
                }

                if (! is_numeric($coeficienteRaw)) {
                    $errores[$rowNumber][] = 'El coeficiente debe ser numerico.';
                    continue;
                }

                $payload = [
                    'coeficiente' => (float) $coeficienteRaw,
                    'tipo' => $tipo,
                    'propietario_documento' => $this->getOptionalCsvValue($row, $headerMap, 'propietario_documento'),
                    'propietario_nombre' => $this->getOptionalCsvValue($row, $headerMap, 'propietario_nombre'),
                    'telefono' => $this->getOptionalCsvValue($row, $headerMap, 'telefono'),
                    'email' => $this->getOptionalCsvValue($row, $headerMap, 'email'),
                    'activo' => $this->toBool($this->getOptionalCsvValue($row, $headerMap, 'activo'), true),
                ];

                $inmueble = Inmueble::query()->withTrashed()->where('nomenclatura', $nomenclatura)->first();

                if ($inmueble) {
                    if ($inmueble->trashed()) {
                        $inmueble->restore();
                    }
                    $inmueble->update($payload);
                    $actualizados++;
                } else {
                    Inmueble::query()->create(array_merge(['nomenclatura' => $nomenclatura], $payload));
                    $creados++;
                }
            }
        });

        return [
            'creados' => $creados,
            'actualizados' => $actualizados,
            'errores' => $errores,
        ];
    }

    private function getOptionalCsvValue(array $row, array $headerMap, string $column): ?string
    {
        if (! array_key_exists($column, $headerMap)) {
            return null;
        }

        $value = trim((string) ($row[$headerMap[$column]] ?? ''));

        return $value === '' ? null : $value;
    }

    private function toBool(?string $value, bool $default): bool
    {
        if ($value === null) {
            return $default;
        }

        return in_array(Str::lower($value), ['1', 'true', 'si', 'sí', 'activo'], true);
    }
}
