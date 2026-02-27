<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VotoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pregunta_id' => $this->pregunta_id,
            'inmueble_id' => $this->inmueble_id,
            'opcion_id' => $this->opcion_id,
            'asistente_id' => $this->asistente_id,
            'coeficiente' => $this->coeficiente,
            'telefono' => $this->telefono,
            'votado_at' => $this->votado_at,
            'created_at' => $this->created_at,
        ];
    }
}

