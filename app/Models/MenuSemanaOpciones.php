<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class MenuSemanaOpciones extends Model
{
    protected $table = 'menu_semana_opciones';

    protected $fillable = [
        'desayuno_opciones',
        'cena_opciones',
    ];

    protected $casts = [
        'desayuno_opciones' => 'array',
        'cena_opciones' => 'array',
    ];

    public static function actual(): self
    {
        return static::firstOrCreate([], ['desayuno_opciones' => [], 'cena_opciones' => []]);
    }
}
