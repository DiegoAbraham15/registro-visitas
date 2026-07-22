<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reemplazadas por 'comida_seleccion_diaria': el diseño pasó de guardar
     * desayuno/cena/bebida por visita individual a guardarlos por habitación
     * y por día (ver esa migración). hasColumn() porque estas columnas se
     * agregaron apenas ayer y puede que un entorno aún no las tenga.
     */
    public function up(): void
    {
        Schema::table('visita_familiar', function (Blueprint $table) {
            $columnas = array_filter(
                ['desayuno', 'cena', 'bebida'],
                fn (string $columna) => Schema::hasColumn('visita_familiar', $columna)
            );

            if ($columnas) {
                $table->dropColumn($columnas);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
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
};
