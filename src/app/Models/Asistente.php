<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asistente extends Model
{
    protected $table = 'asistentes';

    protected $fillable = [
        'reunion_id',
        'telefono',
        'codigo_barras',
    ];

    public function reunion(): BelongsTo
    {
        return $this->belongsTo(Reunion::class, 'reunion_id');
    }

    public function inmuebles(): BelongsToMany
    {
        return $this->belongsToMany(Inmueble::class, 'asistente_inmueble', 'asistente_id', 'inmueble_id')
            ->withPivot(['coeficiente', 'poder_url'])
            ->withTimestamps();
    }

    /**
     * Votos registrados por este asistente.
     * Se mantiene para trazabilidad en reportes y actas:
     * permite saber qué asistente votó por qué inmuebles en esta reunión.
     */
    public function votos(): HasMany
    {
        return $this->hasMany(Voto::class, 'asistente_id');
    }
}
