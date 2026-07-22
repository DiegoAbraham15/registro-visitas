<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;


class Bitacora extends Model
{
    public $timestamps = false;

    protected $table = 'bitacora';

    protected $fillable = [
        'usuario_id',
        'accion',
        'descripcion',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }


    public static function registrar(string $accion, ?string $descripcion = null): self
    {
        return static::create([
            'usuario_id' => Auth::id(),
            'accion' => $accion,
            'descripcion' => $descripcion,
            'created_at' => now(),
        ]);
    }
}
