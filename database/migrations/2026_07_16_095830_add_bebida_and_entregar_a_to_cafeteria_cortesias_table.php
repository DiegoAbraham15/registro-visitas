<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Para que cafetería (app móvil) pueda ver la bebida y a quién se le
     * entrega la comida, esa información tiene que vivir en una tabla que la
     * app móvil ya consulte. Se agregan aquí en vez de dejarlas solo en
     * 'comida_visitantes' (que la app móvil no conoce). hasColumn() porque
     * 'cafeteria_cortesias' es una tabla real compartida con esa app.
     *
     * Nota: agregar estas columnas no hace que aparezcan solas en la app
     * móvil — eso requiere que alguien actualice también su código para
     * leerlas y mostrarlas.
     */
    public function up(): void
    {
        Schema::table('cafeteria_cortesias', function (Blueprint $table) {
            if (! Schema::hasColumn('cafeteria_cortesias', 'bebida')) {
                $table->string('bebida', 20)->nullable()->after('platillo_cena');
            }
            if (! Schema::hasColumn('cafeteria_cortesias', 'entregar_a')) {
                $table->string('entregar_a', 255)->nullable()->after('bebida');
            }
        });

        // La bebida quedaba en 'comida_visitantes'; se migra lo ya guardado
        // ahí antes de quitarle esa columna (ver abajo), para no perderlo.
        if (Schema::hasTable('comida_visitantes') && Schema::hasColumn('comida_visitantes', 'bebida_elegida')) {
            DB::table('comida_visitantes')->whereNotNull('bebida_elegida')->get()->each(function ($fila) {
                DB::table('cafeteria_cortesias')
                    ->where('piso', $fila->piso)
                    ->where('habitacion', $fila->habitacion)
                    ->update(['bebida' => $fila->bebida_elegida]);
            });

            Schema::table('comida_visitantes', function (Blueprint $table) {
                $table->dropColumn('bebida_elegida');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('comida_visitantes') && ! Schema::hasColumn('comida_visitantes', 'bebida_elegida')) {
            Schema::table('comida_visitantes', function (Blueprint $table) {
                $table->string('bebida_elegida', 20)->nullable();
            });
        }

        Schema::table('cafeteria_cortesias', function (Blueprint $table) {
            if (Schema::hasColumn('cafeteria_cortesias', 'entregar_a')) {
                $table->dropColumn('entregar_a');
            }
            if (Schema::hasColumn('cafeteria_cortesias', 'bebida')) {
                $table->dropColumn('bebida');
            }
        });
    }
};
