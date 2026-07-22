<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Notas libres de vinculación por habitación (p. ej. alergias, indicaciones
     * especiales) — tabla propia, no la ve cafetería.
     */
    public function up(): void
    {
        Schema::table('comida_visitantes', function (Blueprint $table) {
            $table->string('observaciones', 500)->nullable()->after('otro_texto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comida_visitantes', function (Blueprint $table) {
            $table->dropColumn('observaciones');
        });
    }
};
