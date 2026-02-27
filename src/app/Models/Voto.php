<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voto extends Model
{
    use HasFactory;

    protected $table = 'votos';

    protected $fillable = [
        'pregunta_id',
        'inmueble_id',
        'opcion_id',
        'asistente_id',
        'coeficiente',
        'telefono',
        'votado_at',
    ];

    protected $casts = [
        'coeficiente' => 'float',
        'votado_at' => 'datetime',
    ];

    public function pregunta()
    {
        return $this->belongsTo(Pregunta::class);
    }

    public function opcion()
    {
        return $this->belongsTo(Opcion::class);
    }

    public function inmueble()
    {
        return $this->belongsTo(Inmueble::class);
    }

    public function asistente()
    {
        return $this->belongsTo(Asistente::class);
    }
}

