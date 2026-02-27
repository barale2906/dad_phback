<?php

namespace App\Http\Requests\Install;

use Illuminate\Foundation\Http\FormRequest;

class InstallRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'admin_password' => ['required', 'string', 'min:8', 'confirmed'],
            'admin_documento' => ['nullable', 'string', 'max:50', 'unique:users,documento'],
            'admin_telefono' => ['nullable', 'string', 'max:20'],
            'ph_nit' => ['required', 'string', 'max:50', 'unique:phs,nit'],
            'ph_nombre' => ['required', 'string', 'max:255'],
            'ph_email' => ['nullable', 'email', 'max:255'],
            'ph_direccion' => ['nullable', 'string', 'max:255'],
            'ph_telefono' => ['nullable', 'string', 'max:20'],
            'ph_estado' => ['sometimes', 'in:activo,inactivo'],
        ];
    }
}
