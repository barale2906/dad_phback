<?php

namespace App\Http\Requests\Asistente;

use Illuminate\Foundation\Http\FormRequest;

class CheckInAsistenteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'reunion_id' => ['nullable', 'integer', 'exists:reuniones,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'reunion_id.exists' => 'La reunión indicada no existe.',
        ];
    }
}
