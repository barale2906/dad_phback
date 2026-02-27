<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asistente extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'asistentes';

    protected $fillable = [
        'usuario_id',
        'nombre',
        'documento',
        'telefono',
        'codigo_acceso',
        'barcode_numero',
        'tipo_asistente',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function inmuebles(): BelongsToMany
    {
        return $this->belongsToMany(Inmueble::class, 'asistente_inmueble', 'asistente_id', 'inmueble_id')
            ->withPivot(['coeficiente', 'poder_url'])
            ->withTimestamps();
    }

    public function votos(): HasMany
    {
        return $this->hasMany(Voto::class, 'asistente_id');
    }
}
