<?php

namespace App\Http\Requests\Opcion;

use Illuminate\Foundation\Http\FormRequest;

class StoreOpcionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'pregunta_id' => ['required', 'exists:preguntas,id'],
            'texto' => ['required', 'string', 'max:255'],
            'orden' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
