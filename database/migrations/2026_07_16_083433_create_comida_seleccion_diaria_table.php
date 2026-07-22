<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La selección de comida vive por habitación y por día (no por visita
     * individual): una habitación puede tener 0, 1 o varios familiares activos,
     * y el desayuno/cena/bebida se elige una sola vez para toda la habitación.
     * 'piso'+'habitacion' identifican la habitación (mismo par que
     * catalogo_habitaciones.piso/numero); no se usa una FK porque
     * catalogo_habitaciones es apenas un catálogo de valores válidos, no la
     * llave real de la habitación en las tablas de visita.
     */
    public function up(): void
    {
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comida_seleccion_diaria');
    }
};
