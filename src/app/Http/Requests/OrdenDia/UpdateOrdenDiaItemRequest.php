<?php

namespace App\Http\Requests\OrdenDia;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrdenDiaItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'orden' => ['required', 'integer', 'min:1'],
            'ejecutado' => ['sometimes', 'boolean'],
        ];
    }
}
