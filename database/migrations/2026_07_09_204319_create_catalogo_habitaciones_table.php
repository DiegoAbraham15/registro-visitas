<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('catalogo_habitaciones', function (Blueprint $table) {
            $table->id();
            $table->string('piso', 30);
            $table->unsignedTinyInteger('piso_orden');
            $table->string('numero', 20);
            $table->timestamps();

            $table->unique(['piso', 'numero']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalogo_habitaciones');
    }
};
