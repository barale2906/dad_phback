<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Opcion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'opciones';

    protected $fillable = [
        'pregunta_id',
        'texto',
        'orden',
    ];

    public function pregunta(): BelongsTo
    {
        return $this->belongsTo(Pregunta::class, 'pregunta_id');
    }

    public function votos(): HasMany
    {
        return $this->hasMany(Voto::class, 'opcion_id');
    }
}
