<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AsistenteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reunion_id' => $this->reunion_id,
            'telefono' => $this->telefono,
            'codigo_barras' => $this->codigo_barras,
            'inmuebles' => $this->whenLoaded('inmuebles', function () {
                return $this->inmuebles->map(fn ($inmueble) => [
                    'id' => $inmueble->id,
                    'nomenclatura' => $inmueble->nomenclatura,
                    'coeficiente' => (float) ($inmueble->pivot->coeficiente ?? $inmueble->coeficiente),
                    'poder_url' => $inmueble->pivot->poder_url,
                ]);
            }),
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
        ];
    }
}
