<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Symfony\Component\HttpFoundation\Response;

/**
 * Genera hojas de códigos de barras en PDF ajustadas a rótulos autoadhesivos.
 *
 * Calcula automáticamente cuántos rótulos caben por fila a partir de las
 * dimensiones del rótulo físico y el área útil de impresión (papel menos márgenes).
 * Cada rótulo contiene el código de barras como imagen PNG embebida en base64
 * y el número impreso debajo, centrado horizontalmente.
 * El PDF se retorna en línea sin persistir ningún archivo en disco.
 */
class BarcodeService
{
    /** Dimensiones en mm [ancho, alto] de cada tamaño de papel en orientación portrait. */
    private const PAPER_SIZES_MM = [
        'A4'     => [210.0, 297.0],
        'Letter' => [215.9, 279.4],
        'Legal'  => [215.9, 355.6],
    ];

    /** Mapa del tipo de código (clave de request) al tipo interno de picqer. */
    private const BARCODE_TYPES = [
        'C128'  => BarcodeGeneratorPNG::TYPE_CODE_128,
        'C39'   => BarcodeGeneratorPNG::TYPE_CODE_39,
        'EAN13' => BarcodeGeneratorPNG::TYPE_EAN_13,
        'EAN8'  => BarcodeGeneratorPNG::TYPE_EAN_8,
        'UPCA'  => BarcodeGeneratorPNG::TYPE_UPC_A,
    ];

    /** Espacio interior reservado en cada lado del rótulo en mm. */
    private const PADDING_MM = 1.5;

    /** Altura reservada para imprimir el número debajo del barcode en mm. */
    private const LABEL_TEXT_HEIGHT_MM = 5.0;

    /**
     * Factor de conversión mm → px a 96 DPI.
     * Derivado de: 96 px/in ÷ 25.4 mm/in = 3.7795 px/mm.
     */
    private const MM_TO_PX = 3.7795;

    /**
     * Genera el PDF con los rótulos y lo devuelve como respuesta HTTP binaria.
     *
     * Calcula cuántas columnas de rótulos caben en el área útil, genera la imagen
     * PNG de cada código de barras embebida en base64, y renderiza la vista Blade
     * con dimensiones CSS absolutas en milímetros para que DomPDF las respete.
     *
     * @param  string  $papel        Tamaño del papel: 'A4', 'Letter' o 'Legal'.
     * @param  string  $orientacion  'portrait' o 'landscape'.
     * @param  float   $margenTop    Margen superior en mm.
     * @param  float   $margenBottom Margen inferior en mm.
     * @param  float   $margenLeft   Margen izquierdo en mm.
     * @param  float   $margenRight  Margen derecho en mm.
     * @param  float   $rotuloAncho  Ancho del rótulo físico en mm.
     * @param  float   $rotuloAlto   Alto del rótulo físico en mm.
     * @param  string  $tipoCodigo   Clave del tipo de código: 'C128', 'C39', etc.
     */
    public function print(
        int $inicio,
        int $cantidad,
        int $repeticiones,
        string $papel,
        string $orientacion,
        float $margenTop,
        float $margenBottom,
        float $margenLeft,
        float $margenRight,
        float $rotuloAncho,
        float $rotuloAlto,
        string $tipoCodigo
    ): Response {
        [$anchoHoja] = $this->resolverDimensionesPapel($papel, $orientacion);

        $anchoUtil = $anchoHoja - $margenLeft - $margenRight;
        $columnas  = max(1, (int) floor($anchoUtil / $rotuloAncho));

        $items = $this->generarItems($inicio, $cantidad, $repeticiones, $rotuloAncho, $rotuloAlto, $tipoCodigo);

        $html = view('pdf.barcodes', [
            'items'        => $items,
            'columnas'     => $columnas,
            'rotuloAncho'  => $rotuloAncho,
            'rotuloAlto'   => $rotuloAlto,
            'margenTop'    => $margenTop,
            'margenBottom' => $margenBottom,
            'margenLeft'   => $margenLeft,
            'margenRight'  => $margenRight,
        ])->render();

        $pdf = Pdf::loadHTML($html)->setPaper(strtolower($papel), $orientacion);

        return response($pdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="barcodes.pdf"',
        ]);
    }

    /**
     * Retorna [anchoMm, altoMm] para el papel y orientación indicados.
     *
     * Si el papel no está en el mapa conocido, se usa A4 como fallback.
     *
     * @return float[] Par [ancho, alto] en mm.
     */
    private function resolverDimensionesPapel(string $papel, string $orientacion): array
    {
        [$ancho, $alto] = self::PAPER_SIZES_MM[$papel] ?? self::PAPER_SIZES_MM['A4'];

        return $orientacion === 'landscape' ? [$alto, $ancho] : [$ancho, $alto];
    }

    /**
     * Construye la secuencia de items: número + imagen barcode en base64.
     *
     * La imagen PNG se dimensiona para ocupar el interior del rótulo descontando
     * el padding lateral y el espacio reservado al texto del número.
     * Cada número se genera una sola vez y se reutiliza la misma imagen en cada
     * repetición para minimizar el tiempo de procesamiento.
     *
     * @return array<int, array{numero: int, imagen: string}>
     */
    private function generarItems(
        int $inicio,
        int $cantidad,
        int $repeticiones,
        float $rotuloAncho,
        float $rotuloAlto,
        string $tipoCodigo
    ): array {
        $anchoPx = (int) round(($rotuloAncho - self::PADDING_MM * 2) * self::MM_TO_PX);
        $altoPx  = max(1, (int) round(($rotuloAlto - self::PADDING_MM * 2 - self::LABEL_TEXT_HEIGHT_MM) * self::MM_TO_PX));

        $generator = new BarcodeGeneratorPNG();
        $type      = self::BARCODE_TYPES[$tipoCodigo] ?? BarcodeGeneratorPNG::TYPE_CODE_128;

        $items  = [];
        $actual = $inicio;

        for ($i = 0; $i < $cantidad; $i++) {
            $imagen = 'data:image/png;base64,' . base64_encode(
                $generator->getBarcode((string) $actual, $type, widthFactor: 1, height: $altoPx)
            );

            for ($j = 0; $j < $repeticiones; $j++) {
                $items[] = ['numero' => $actual, 'imagen' => $imagen];
            }

            $actual++;
        }

        return $items;
    }
}
