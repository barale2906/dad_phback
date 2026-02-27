<?php

namespace App\Http\Requests\OrdenDia;

use Illuminate\Foundation\Http\FormRequest;

class MarcarEjecutadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'ejecutado' => ['sometimes', 'boolean'],
        ];
    }
}
