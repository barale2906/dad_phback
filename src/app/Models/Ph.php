<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ph extends Model
{
    use HasFactory;

    protected $table = 'phs';

    protected $fillable = [
        'nit',
        'nombre',
        'logo',
        'email',
        'direccion',
        'telefono',
        'estado',
        'installed_at',
    ];

    protected function casts(): array
    {
        return [
            'installed_at' => 'datetime',
        ];
    }
}
