<?php

namespace App\Http\Requests\Opcion;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOpcionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'texto' => ['required', 'string', 'max:255'],
            'orden' => ['required', 'integer', 'min:1'],
        ];
    }
}
