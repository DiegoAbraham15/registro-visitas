<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class ComidaVisitantes extends Model
{
    protected $table = 'comida_visitantes';

    protected $fillable = [
        'piso',
        'habitacion',
        'visitantes_seleccionados',
        'otro_texto',
        'observaciones',
    ];

    protected $casts = [
        'visitantes_seleccionados' => 'array',
    ];
}
