<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PreguntaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reunion_id' => $this->reunion_id,
            'pregunta' => $this->pregunta,
            'estado' => $this->estado,
            'apertura_at' => optional($this->apertura_at)?->toIso8601String(),
            'cierre_at' => optional($this->cierre_at)?->toIso8601String(),
            'orden' => $this->orden,
            'opciones' => OpcionResource::collection($this->whenLoaded('opciones')),
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
        ];
    }
}
