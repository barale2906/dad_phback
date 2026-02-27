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
            'usuario_id' => $this->usuario_id,
            'nombre' => $this->nombre,
            'documento' => $this->documento,
            'telefono' => $this->telefono,
            'codigo_acceso' => $this->codigo_acceso,
            'barcode_numero' => $this->barcode_numero,
            'tipo_asistente' => $this->tipo_asistente,
            'inmuebles' => $this->whenLoaded('inmuebles', function () {
                return $this->inmuebles->map(function ($inmueble): array {
                    return [
                        'id' => $inmueble->id,
                        'nomenclatura' => $inmueble->nomenclatura,
                        'coeficiente' => (float) ($inmueble->pivot->coeficiente ?? $inmueble->coeficiente),
                        'poder_url' => $inmueble->pivot->poder_url,
                    ];
                });
            }),
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
            'deleted_at' => optional($this->deleted_at)?->toIso8601String(),
        ];
    }
}
