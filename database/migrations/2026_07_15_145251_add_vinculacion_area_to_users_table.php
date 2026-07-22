<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega el área "vinculacion" (gestiona desayuno/cena/bebida de las visitas
     * familiares activas) al enum de área de usuarios. Ver create_users_table.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('area', ['hospital', 'consultorios', 'cafeteria', 'vinculacion'])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('area', ['hospital', 'consultorios', 'cafeteria'])->change();
        });
    }
};
