<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuDia extends Model
{

    public const DIAS = ['domingo', 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado'];

    protected $fillable = [
        'dia',
        'comida',
    ];
}
