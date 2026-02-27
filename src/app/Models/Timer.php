<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Timer extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'timers';

    protected $fillable = [
        'reunion_id',
        'tipo',
        'duracion_segundos',
        'inicio_at',
        'fin_at',
        'estado',
        'interviniente_nombre',
        'interviniente_asistente_id',
    ];

    protected function casts(): array
    {
        return [
            'inicio_at' => 'datetime',
            'fin_at' => 'datetime',
        ];
    }

    public function reunion(): BelongsTo
    {
        return $this->belongsTo(Reunion::class, 'reunion_id');
    }

    public function intervinienteAsistente(): BelongsTo
    {
        return $this->belongsTo(Asistente::class, 'interviniente_asistente_id');
    }
}
