<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Convocatoria extends Model
{
    use HasFactory;

    protected $table = 'convocatorias';

    protected $fillable = [
        'reunion_id',
        'fecha_convocatoria',
        'medio',
        'contenido',
        'orden_dia_snapshot',
        'fecha_limite_legal',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_convocatoria' => 'date',
            'fecha_limite_legal' => 'date',
        ];
    }

    public function reunion(): BelongsTo
    {
        return $this->belongsTo(Reunion::class, 'reunion_id');
    }
}
