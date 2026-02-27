<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReunionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo,
            'fecha' => optional($this->fecha)?->toDateString(),
            'hora' => $this->hora,
            'modalidad' => $this->modalidad,
            'ente' => $this->ente,
            'estado' => $this->estado,
            'inicio_at' => optional($this->inicio_at)?->toIso8601String(),
            'cierre_at' => optional($this->cierre_at)?->toIso8601String(),
            'zonas_comunes' => ZonaComunResource::collection($this->whenLoaded('zonasComunes')),
            'convocatoria' => new ConvocatoriaResource($this->whenLoaded('convocatoria')),
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
        ];
    }
}
