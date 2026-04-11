<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Barcode\PrintBarcodeRequest;
use App\Models\Asistente;
use App\Services\BarcodeService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * Generación de hojas de códigos de barras para rótulos autoadhesivos.
 *
 * Permite a logística generar un PDF listo para imprimir con los códigos de barras
 * que se asignarán físicamente a los asistentes en el momento del registro.
 * El PDF se ajusta al tamaño exacto del rótulo físico y a las dimensiones de la hoja
 * indicada, incluyendo márgenes configurables por todos los lados.
 * No persiste ningún archivo en disco — el PDF se genera en memoria y se devuelve en línea.
 */
#[Group('Códigos de barras', weight: 5)]
class BarcodeController extends Controller
{
    public function __construct(private readonly BarcodeService $barcodeService)
    {
    }

    /**
     * Generar PDF de códigos de barras.
     *
     * Genera una hoja PDF lista para imprimir sobre rótulos autoadhesivos.
     * Cada rótulo contiene el código de barras visual y el número impreso debajo,
     * centrado horizontalmente. El layout (columnas por fila) se calcula
     * automáticamente según las dimensiones del rótulo, el papel y los márgenes.
     *
     * @authenticated
     *
     * @bodyParam inicio integer required Primer número de la secuencia. Example: 1
     * @bodyParam cantidad integer required Cuántos números distintos generar. Example: 100
     * @bodyParam repeticiones integer required Cuántas etiquetas imprimir por cada número. Example: 1
     * @bodyParam rotulo_ancho number required Ancho del rótulo físico en mm (mínimo 10). Example: 50
     * @bodyParam rotulo_alto number required Alto del rótulo físico en mm (mínimo 10). Example: 25
     * @bodyParam papel string Tamaño del papel: `A4`, `Letter` o `Legal`. Por defecto `A4`. Example: A4
     * @bodyParam orientacion string Orientación de la hoja: `portrait` o `landscape`. Por defecto `portrait`. Example: portrait
     * @bodyParam margen_top number Margen superior en mm. Por defecto 10. Example: 10
     * @bodyParam margen_bottom number Margen inferior en mm. Por defecto 10. Example: 10
     * @bodyParam margen_left number Margen izquierdo en mm. Por defecto 10. Example: 10
     * @bodyParam margen_right number Margen derecho en mm. Por defecto 10. Example: 10
     * @bodyParam tipo_codigo string Tipo de barcode: `C128`, `C39`, `EAN13`, `EAN8`, `UPCA`. Por defecto `C128`. Example: C128
     *
     * @response 200 scenario="PDF generado" {"description": "Archivo PDF binario (Content-Type: application/pdf)"}
     * @response 403 {"message": "This action is unauthorized."}
     * @response 422 {"message": "El ancho del rótulo es obligatorio.", "errors": {"rotulo_ancho": ["El ancho del rótulo es obligatorio."]}}
     */
    public function print(PrintBarcodeRequest $request): Response
    {
        Gate::authorize('create', Asistente::class);

        return $this->barcodeService->print(
            inicio:       (int)   $request->integer('inicio'),
            cantidad:     (int)   $request->integer('cantidad'),
            repeticiones: (int)   $request->integer('repeticiones'),
            papel:               $request->input('papel', PrintBarcodeRequest::DEFAULT_PAPEL),
            orientacion:         $request->input('orientacion', PrintBarcodeRequest::DEFAULT_ORIENTACION),
            margenTop:    (float) $request->input('margen_top', PrintBarcodeRequest::DEFAULT_MARGEN_MM),
            margenBottom: (float) $request->input('margen_bottom', PrintBarcodeRequest::DEFAULT_MARGEN_MM),
            margenLeft:   (float) $request->input('margen_left', PrintBarcodeRequest::DEFAULT_MARGEN_MM),
            margenRight:  (float) $request->input('margen_right', PrintBarcodeRequest::DEFAULT_MARGEN_MM),
            rotuloAncho:  (float) $request->input('rotulo_ancho'),
            rotuloAlto:   (float) $request->input('rotulo_alto'),
            tipoCodigo:          $request->input('tipo_codigo', PrintBarcodeRequest::DEFAULT_TIPO_CODIGO),
        );
    }
}
