<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El desayuno/comida/cena de cada habitación ahora se guarda en
     * 'cafeteria_cortesias' (tabla real, ya usada por la app móvil, única por
     * piso+habitacion, sin columna de fecha). 'comida_seleccion_diaria' queda
     * obsoleta: se reemplaza por 'comida_visitantes', que solo guarda lo que
     * cafeteria_cortesias no tiene (qué visitantes están marcados, el texto
     * libre "otro" y la bebida) — igual de "vigente" que cortesias, sin fecha.
     */
    public function up(): void
    {
        Schema::dropIfExists('comida_seleccion_diaria');

        Schema::create('comida_visitantes', function (Blueprint $table) {
            $table->id();
            $table->string('piso', 50);
            $table->string('habitacion', 20);
            $table->json('visitantes_seleccionados')->nullable();
            $table->string('otro_texto')->nullable();
            $table->string('bebida_elegida', 20)->nullable();
            $table->timestamps();

            $table->unique(['piso', 'habitacion']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comida_visitantes');

        Schema::create('comida_seleccion_diaria', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('piso', 30);
            $table->string('habitacion', 50);
            $table->json('visitantes_seleccionados')->nullable();
            $table->string('otro_texto')->nullable();
            $table->string('desayuno_elegido')->nullable();
            $table->string('cena_elegido')->nullable();
            $table->string('bebida_elegida', 20)->nullable();
            $table->timestamps();

            $table->unique(['fecha', 'piso', 'habitacion']);
        });
    }
};
