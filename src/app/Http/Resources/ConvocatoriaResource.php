<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConvocatoriaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reunion_id' => $this->reunion_id,
            'fecha_convocatoria' => optional($this->fecha_convocatoria)?->toDateString(),
            'medio' => $this->medio,
            'contenido' => $this->contenido,
            'orden_dia_snapshot' => $this->orden_dia_snapshot,
            'fecha_limite_legal' => optional($this->fecha_limite_legal)?->toDateString(),
            'estado' => $this->estado,
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
        ];
    }
}
