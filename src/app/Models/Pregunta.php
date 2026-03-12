<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pregunta extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'preguntas';

    protected $fillable = [
        'reunion_id',
        'pregunta',
        'tipo',
        'estado',
        'apertura_at',
        'cierre_at',
        'orden',
    ];

    protected function casts(): array
    {
        return [
            'apertura_at' => 'datetime',
            'cierre_at' => 'datetime',
        ];
    }

    public function reunion(): BelongsTo
    {
        return $this->belongsTo(Reunion::class, 'reunion_id');
    }

    public function opciones(): HasMany
    {
        return $this->hasMany(Opcion::class, 'pregunta_id');
    }

    public function votos(): HasMany
    {
        return $this->hasMany(Voto::class, 'pregunta_id');
    }
}
