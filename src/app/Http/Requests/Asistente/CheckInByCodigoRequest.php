<?php

namespace App\Http\Requests\Asistente;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Payload para check-in en puerta por código de barras o teléfono.
 * Se debe enviar exactamente uno de los dos: codigo_barras o telefono.
 */
class CheckInByCodigoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'codigo_barras' => ['required_without:telefono', 'nullable', 'integer', 'min:1'],
            'telefono' => ['required_without:codigo_barras', 'nullable', 'string', 'max:20'],
            'reunion_id' => ['nullable', 'integer', 'exists:reuniones,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'codigo_barras.required_without' => 'Debe indicar codigo_barras o telefono.',
            'telefono.required_without' => 'Debe indicar codigo_barras o telefono.',
            'reunion_id.exists' => 'La reunión indicada no existe.',
        ];
    }
}
