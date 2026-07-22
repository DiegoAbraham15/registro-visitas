<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Columnas para que el usuario de "vinculación" registre si el familiar en
     * cuarto activo recibe desayuno/cena y qué bebida, sin tocar el resto del
     * esquema de 'visita_familiar' (tabla compartida con la app móvil, ver nota
     * en create_visita_tables_if_missing.php). Guardadas con hasColumn() por la
     * misma razón: esta migración también corre contra la BD real compartida.
     */
    public function up(): void
    {
        Schema::table('visita_familiar', function (Blueprint $table) {
            if (! Schema::hasColumn('visita_familiar', 'desayuno')) {
                $table->boolean('desayuno')->nullable()->after('foto_ine');
            }
            if (! Schema::hasColumn('visita_familiar', 'cena')) {
                $table->boolean('cena')->nullable()->after('desayuno');
            }
            if (! Schema::hasColumn('visita_familiar', 'bebida')) {
                $table->string('bebida', 20)->nullable()->after('cena');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visita_familiar', function (Blueprint $table) {
            $table->dropColumn(['desayuno', 'cena', 'bebida']);
        });
    }
};
