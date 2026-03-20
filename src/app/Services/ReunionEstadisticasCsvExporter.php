<?php

namespace App\Services;

/**
 * Exporta las estadísticas de reunión a formato CSV.
 *
 * Recibe el array generado por ReunionEstadisticasService y lo transforma
 * en un archivo CSV compatible con Excel (UTF-8 con BOM).
 * Estructura: orden del día, asistencia, votaciones.
 */
class ReunionEstadisticasCsvExporter
{
    private const UTF8_BOM = "\xEF\xBB\xBF";

    private const DELIMITER = ',';

    /**
     * Genera el contenido CSV a partir de las estadísticas.
     *
     * @param  array{reunion_id: int, orden_dia: array, asistencia: array, votaciones: array}  $estadisticas
     */
    public function exportar(array $estadisticas): string
    {
        $lines = [];

        $lines[] = ['Estadísticas de reunión', 'ID: ' . ($estadisticas['reunion_id'] ?? '')];
        $lines[] = [];

        $this->agregarOrdenDia($lines, $estadisticas['orden_dia'] ?? []);
        $lines[] = [];

        $this->agregarAsistencia($lines, $estadisticas['asistencia'] ?? []);
        $lines[] = [];

        $this->agregarVotaciones($lines, $estadisticas['votaciones'] ?? []);

        return self::UTF8_BOM . $this->arrayToCsv($lines);
    }

    /**
     * @param  array<int, array>  $lines
     * @param  array{items: array, total: int, ejecutados: int, nivel_cumplimiento: float}  $ordenDia
     */
    private function agregarOrdenDia(array &$lines, array $ordenDia): void
    {
        $lines[] = ['ORDEN DEL DÍA'];
        $lines[] = [
            'Orden',
            'Título',
            'Descripción',
            'Ejecutado',
        ];

        foreach ($ordenDia['items'] ?? [] as $item) {
            $lines[] = [
                $item['orden'] ?? '',
                $item['titulo'] ?? '',
                $item['descripcion'] ?? '',
                ($item['ejecutado'] ?? false) ? 'Sí' : 'No',
            ];
        }

        $total = $ordenDia['total'] ?? 0;
        $ejecutados = $ordenDia['ejecutados'] ?? 0;
        $nivel = $ordenDia['nivel_cumplimiento'] ?? 0;

        $lines[] = [];
        $lines[] = ['Resumen', "{$ejecutados} de {$total} ejecutados", "Nivel de cumplimiento: {$nivel}%"];
    }

    /**
     * @param  array<int, array>  $lines
     * @param  array{registrados: array, no_registrados: array, total_unidades: int, unidades_registradas: int, unidades_no_registradas: int}  $asistencia
     */
    private function agregarAsistencia(array &$lines, array $asistencia): void
    {
        $lines[] = ['ASISTENCIA'];

        $lines[] = ['Registrados'];
        $lines[] = ['Código barras', 'Teléfono', 'Identificación', 'Inmueble', 'Nomenclatura', 'Coeficiente'];

        foreach ($asistencia['registrados'] ?? [] as $reg) {
            $identificacion = $reg['identificacion'] ?? ($reg['codigo_barras'] ?? $reg['telefono'] ?? '');
            $inmuebles = $reg['inmuebles'] ?? [];

            if (empty($inmuebles)) {
                $lines[] = [
                    $reg['codigo_barras'] ?? '',
                    $reg['telefono'] ?? '',
                    $identificacion,
                    '',
                    '',
                    '',
                ];
            } else {
                foreach ($inmuebles as $idx => $inmueble) {
                    $lines[] = [
                        $idx === 0 ? ($reg['codigo_barras'] ?? '') : '',
                        $idx === 0 ? ($reg['telefono'] ?? '') : '',
                        $idx === 0 ? $identificacion : '',
                        $inmueble['inmueble_id'] ?? '',
                        $inmueble['nomenclatura'] ?? '',
                        $inmueble['coeficiente'] ?? '',
                    ];
                }
            }
        }

        $lines[] = [];
        $lines[] = ['No registrados'];
        $lines[] = ['Inmueble ID', 'Nomenclatura', 'Coeficiente', 'Teléfono'];

        foreach ($asistencia['no_registrados'] ?? [] as $nr) {
            $lines[] = [
                $nr['inmueble_id'] ?? '',
                $nr['nomenclatura'] ?? '',
                $nr['coeficiente'] ?? '',
                $nr['telefono'] ?? '',
            ];
        }

        $lines[] = [];
        $lines[] = [
            'Resumen',
            'Total unidades: ' . ($asistencia['total_unidades'] ?? 0),
            'Registradas: ' . ($asistencia['unidades_registradas'] ?? 0),
            'No registradas: ' . ($asistencia['unidades_no_registradas'] ?? 0),
        ];
    }

    /**
     * @param  array<int, array>  $lines
     * @param  array<int, array>  $votaciones
     */
    private function agregarVotaciones(array &$lines, array $votaciones): void
    {
        $lines[] = ['VOTACIONES'];

        foreach ($votaciones as $v) {
            $lines[] = ['Pregunta ' . ($v['pregunta_id'] ?? '') . ': ' . ($v['pregunta'] ?? '')];
            $lines[] = ['Tipo', $v['tipo'] ?? '', 'Estado', $v['estado'] ?? ''];

            if (! ($v['disponible'] ?? false)) {
                $lines[] = [$v['mensaje'] ?? 'Sin resultados'];
                $lines[] = [];
                continue;
            }

            $resultados = $v['resultados'] ?? [];
            $lines[] = [
                'Asistencia (unidades)',
                $resultados['asistencia_unidades'] ?? '',
                'Votaron',
                $resultados['votaron_unidades'] ?? '',
                'No votaron',
                $resultados['no_votaron_unidades'] ?? '',
            ];
            $lines[] = [
                'Asistencia (coef.)',
                $resultados['asistencia_coeficiente'] ?? '',
                'Votaron (coef.)',
                $resultados['votaron_coeficiente'] ?? '',
                'No votaron (coef.)',
                $resultados['no_votaron_coeficiente'] ?? '',
            ];

            $lines[] = [];
            $lines[] = ['Opciones'];
            $lines[] = ['Opción', 'Texto', 'Votos', 'Coeficiente'];

            foreach ($resultados['opciones'] ?? [] as $opcion) {
                $lines[] = [
                    $opcion['opcion_id'] ?? '',
                    $opcion['texto'] ?? '',
                    $opcion['votos'] ?? '',
                    $opcion['coeficiente'] ?? '',
                ];
            }

            $lines[] = [];
            $lines[] = ['Detalle por inmueble asistente'];
            $lines[] = ['Inmueble ID', 'Nomenclatura', 'Coeficiente', 'Votó', 'Identificación', 'Opción', 'Fecha voto'];

            foreach ($v['inmuebles_asistentes'] ?? [] as $ia) {
                $lines[] = [
                    $ia['inmueble_id'] ?? '',
                    $ia['nomenclatura'] ?? '',
                    $ia['coeficiente'] ?? '',
                    ($ia['votado'] ?? false) ? 'Sí' : 'No',
                    $ia['identificacion'] ?? ($ia['codigo_barras'] ?? $ia['telefono'] ?? ''),
                    $ia['opcion_texto'] ?? '',
                    $ia['votado_at'] ?? '',
                ];
            }

            $lines[] = [];
        }
    }

    /**
     * Convierte un array bidimensional en string CSV con escape correcto.
     *
     * @param  array<int, array>  $rows
     */
    private function arrayToCsv(array $rows): string
    {
        $output = fopen('php://temp', 'r+');

        foreach ($rows as $row) {
            fputcsv($output, $row, self::DELIMITER);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv ?: '';
    }
}
