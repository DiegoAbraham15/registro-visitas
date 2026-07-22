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
        Schema::create('consultorios_medicos', function (Blueprint $table) {
            $table->id();
            $table->string('consultorio', 20);
            $table->string('nombre_medico', 150);
            $table->timestamps();

            $table->unique(['consultorio', 'nombre_medico']);
            $table->index('consultorio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultorios_medicos');
    }
};
