<?php

namespace App\Http\Requests\OrdenDia;

use Illuminate\Foundation\Http\FormRequest;

class CargaMasivaOrdenDiaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'archivo' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ];
    }
}
