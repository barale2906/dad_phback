<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inmueble extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'inmuebles';

    protected $fillable = [
        'nomenclatura',
        'coeficiente',
        'tipo',
        'propietario_documento',
        'propietario_nombre',
        'telefono',
        'email',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'coeficiente' => 'decimal:6',
            'activo' => 'boolean',
        ];
    }

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'usuario_inmueble', 'inmueble_id', 'usuario_id')
            ->withPivot(['relacion', 'es_principal', 'fecha_inicio', 'fecha_fin'])
            ->withTimestamps();
    }

    public function asistentes(): BelongsToMany
    {
        return $this->belongsToMany(Asistente::class, 'asistente_inmueble', 'inmueble_id', 'asistente_id')
            ->withPivot(['coeficiente', 'poder_url'])
            ->withTimestamps();
    }

    public function votos(): HasMany
    {
        return $this->hasMany(Voto::class, 'inmueble_id');
    }
}
