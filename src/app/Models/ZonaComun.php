<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ZonaComun extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'zonas_comunes';

    protected $fillable = [
        'nombre',
        'descripcion',
        'capacidad',
        'tipo',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function reuniones(): BelongsToMany
    {
        return $this->belongsToMany(Reunion::class, 'reunion_zona_comun', 'zona_comun_id', 'reunion_id')
            ->withTimestamps();
    }
}
