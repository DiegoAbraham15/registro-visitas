<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menú semanal fijo (7 filas, una por día): lo edita la persona de
     * vinculación, normalmente cada domingo para la semana entrante. 'comida'
     * es un solo texto (la comida del día es fija, sin opciones a elegir);
     * desayuno/cena sí tienen varias opciones, guardadas como JSON.
     */
    public function up(): void
    {
        Schema::create('menu_dias', function (Blueprint $table) {
            $table->id();
            $table->string('dia', 20)->unique();
            $table->string('comida')->nullable();
            $table->json('desayuno_opciones')->nullable();
            $table->json('cena_opciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_dias');
    }
};
