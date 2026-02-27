<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reunion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'reuniones';

    protected $fillable = [
        'tipo',
        'fecha',
        'hora',
        'modalidad',
        'ente',
        'estado',
        'inicio_at',
        'cierre_at',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'inicio_at' => 'datetime',
            'cierre_at' => 'datetime',
        ];
    }

    public function ordenDiaItems(): HasMany
    {
        return $this->hasMany(OrdenDiaItem::class, 'reunion_id');
    }

    public function convocatoria(): HasOne
    {
        return $this->hasOne(Convocatoria::class, 'reunion_id');
    }

    public function preguntas(): HasMany
    {
        return $this->hasMany(Pregunta::class, 'reunion_id');
    }

    public function timers(): HasMany
    {
        return $this->hasMany(Timer::class, 'reunion_id');
    }

    public function zonasComunes(): BelongsToMany
    {
        return $this->belongsToMany(ZonaComun::class, 'reunion_zona_comun', 'reunion_id', 'zona_comun_id')
            ->withTimestamps();
    }
}
