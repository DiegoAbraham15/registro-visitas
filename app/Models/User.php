<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'area',
        'es_admin',
        'acceso_reportes',
        'acceso_vinculacion',
        'es_admin_cafeteria',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'es_admin' => 'boolean',
        'acceso_reportes' => 'boolean',
        'acceso_vinculacion' => 'boolean',
        'es_admin_cafeteria' => 'boolean',
    ];
}
