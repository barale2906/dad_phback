<?php

namespace App\Http\Requests\Ph;

use App\Models\Ph;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePhRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $phId = Ph::query()->value('id');

        return [
            'nit' => ['required', 'string', 'max:50', Rule::unique('phs', 'nit')->ignore($phId)],
            'nombre' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'estado' => ['required', 'in:activo,inactivo'],
        ];
    }
}
