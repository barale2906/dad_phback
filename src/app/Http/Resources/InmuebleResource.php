<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InmuebleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nomenclatura' => $this->nomenclatura,
            'coeficiente' => (float) $this->coeficiente,
            'tipo' => $this->tipo,
            'propietario_documento' => $this->propietario_documento,
            'propietario_nombre' => $this->propietario_nombre,
            'telefono' => $this->telefono,
            'email' => $this->email,
            'activo' => (bool) $this->activo,
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
            'deleted_at' => optional($this->deleted_at)?->toIso8601String(),
        ];
    }
}
