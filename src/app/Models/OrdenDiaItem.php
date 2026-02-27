<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrdenDiaItem extends Model
{
    use HasFactory;

    protected $table = 'orden_dia_items';

    protected $fillable = [
        'reunion_id',
        'titulo',
        'descripcion',
        'orden',
        'ejecutado',
    ];

    protected function casts(): array
    {
        return [
            'ejecutado' => 'boolean',
        ];
    }

    public function reunion(): BelongsTo
    {
        return $this->belongsTo(Reunion::class, 'reunion_id');
    }
}
