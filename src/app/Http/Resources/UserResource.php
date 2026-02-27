<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'rol' => $this->rol,
            'tipo_usuario' => $this->tipo_usuario,
            'documento' => $this->documento,
            'telefono' => $this->telefono,
            'activo' => (bool) $this->activo,
        ];
    }
}
