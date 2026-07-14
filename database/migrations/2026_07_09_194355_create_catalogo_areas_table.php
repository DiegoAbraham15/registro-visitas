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
        Schema::create('catalogo_areas', function (Blueprint $table) {
            $table->id();
            $table->string('piso', 30);
            $table->unsignedTinyInteger('piso_orden');
            $table->string('nombre', 100);
            $table->timestamps();

            $table->unique(['piso', 'nombre']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalogo_areas');
    }
};
