<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permiso separado de 'acceso_catalogos': ese es para Habitaciones y Áreas
 * (catálogos del Hospital); este es solo para /medicos (Médicos de la Torre
 * de Consultorios) — son áreas distintas del edificio y no deben compartir
 * el mismo permiso.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('acceso_medicos')->default(false)->after('acceso_catalogos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('acceso_medicos');
        });
    }
};
