<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Las opciones de desayuno/cena dejaron de variar por día de la semana:
     * ahora son las mismas 4 opciones disponibles todos los días (solo la
     * comida sigue siendo fija y distinta por día, en 'menu_dias'). Se mueven
     * a una tabla propia de una sola fila ('menu_semana_opciones').
     */
    public function up(): void
    {
        Schema::create('menu_semana_opciones', function (Blueprint $table) {
            $table->id();
            $table->json('desayuno_opciones')->nullable();
            $table->json('cena_opciones')->nullable();
            $table->timestamps();
        });

        Schema::table('menu_dias', function (Blueprint $table) {
            if (Schema::hasColumn('menu_dias', 'desayuno_opciones')) {
                $table->dropColumn('desayuno_opciones');
            }
            if (Schema::hasColumn('menu_dias', 'cena_opciones')) {
                $table->dropColumn('cena_opciones');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('menu_dias', function (Blueprint $table) {
            if (! Schema::hasColumn('menu_dias', 'desayuno_opciones')) {
                $table->json('desayuno_opciones')->nullable();
            }
            if (! Schema::hasColumn('menu_dias', 'cena_opciones')) {
                $table->json('cena_opciones')->nullable();
            }
        });

        Schema::dropIfExists('menu_semana_opciones');
    }
};
