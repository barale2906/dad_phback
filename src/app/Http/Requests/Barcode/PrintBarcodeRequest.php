<?php

namespace App\Http\Requests\Barcode;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Payload para generar una hoja de códigos de barras en PDF.
 *
 * Los parámetros de secuencia (`inicio`, `cantidad`, `repeticiones`) definen qué
 * números se imprimen y cuántas veces. Los parámetros de layout (`papel`,
 * `orientacion`, márgenes y dimensiones del rótulo) controlan cómo se distribuyen
 * físicamente en la hoja para que coincidan con el rótulo autoadhesivo real.
 */
class PrintBarcodeRequest extends FormRequest
{
    public const DEFAULT_PAPEL         = 'A4';
    public const DEFAULT_ORIENTACION   = 'portrait';
    public const DEFAULT_MARGEN_MM     = 10;
    public const DEFAULT_TIPO_CODIGO   = 'C128';

    public const PAPELES_VALIDOS       = ['A4', 'Letter', 'Legal'];
    public const ORIENTACIONES_VALIDAS = ['portrait', 'landscape'];
    public const TIPOS_CODIGO_VALIDOS  = ['C128', 'C39', 'EAN13', 'EAN8', 'UPCA'];

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'inicio'        => ['required', 'integer', 'min:1'],
            'cantidad'      => ['required', 'integer', 'min:1'],
            'repeticiones'  => ['required', 'integer', 'min:1'],
            'rotulo_ancho'  => ['required', 'numeric', 'min:10'],
            'rotulo_alto'   => ['required', 'numeric', 'min:10'],
            'papel'         => ['sometimes', 'string', 'in:' . implode(',', self::PAPELES_VALIDOS)],
            'orientacion'   => ['sometimes', 'string', 'in:' . implode(',', self::ORIENTACIONES_VALIDAS)],
            'margen_top'    => ['sometimes', 'numeric', 'min:0'],
            'margen_bottom' => ['sometimes', 'numeric', 'min:0'],
            'margen_left'   => ['sometimes', 'numeric', 'min:0'],
            'margen_right'  => ['sometimes', 'numeric', 'min:0'],
            'tipo_codigo'   => ['sometimes', 'string', 'in:' . implode(',', self::TIPOS_CODIGO_VALIDOS)],
        ];
    }

    public function messages(): array
    {
        $papelesLista     = implode(', ', self::PAPELES_VALIDOS);
        $orientacionLista = implode(', ', self::ORIENTACIONES_VALIDAS);
        $tiposLista       = implode(', ', self::TIPOS_CODIGO_VALIDOS);

        return [
            'rotulo_ancho.required' => 'El ancho del rótulo es obligatorio.',
            'rotulo_ancho.min'      => 'El ancho del rótulo debe ser al menos 10 mm.',
            'rotulo_alto.required'  => 'El alto del rótulo es obligatorio.',
            'rotulo_alto.min'       => 'El alto del rótulo debe ser al menos 10 mm.',
            'papel.in'              => "El tamaño de papel debe ser uno de: {$papelesLista}.",
            'orientacion.in'        => "La orientación debe ser: {$orientacionLista}.",
            'tipo_codigo.in'        => "El tipo de código debe ser uno de: {$tiposLista}.",
        ];
    }
}
