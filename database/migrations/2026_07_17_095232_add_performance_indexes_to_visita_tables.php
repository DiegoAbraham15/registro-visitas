<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 'visita', 'visita_familiar', 'visita_proveedor', 'visita_postulante' y
     * 'visita_torre' (tablas compartidas con la app móvil, ver
     * create_visita_tables_if_missing.php) nunca tuvieron índices más allá de
     * su llave primaria. Cada JOIN de este repo (dashboard, reportes, editar,
     * vinculación) filtra/une por estas columnas sin índice — barato ahora
     * porque las tablas son chicas, pero cada vez más lento a medida que
     * crece el histórico de visitas. Agregar un índice no cambia datos ni
     * comportamiento de ninguna query existente, solo su velocidad, así que
     * es seguro aplicarlo sobre las tablas compartidas.
     *
     * Cada índice se agrega solo si no existe ya (Schema::hasIndex(), Laravel
     * 11+), tanto para no duplicar si la BD real ya tuviera uno con otro
     * nombre como para poder correr esta migración limpio contra SQLite en
     * los tests.
     */
    public function up(): void
    {
        $agregarIndice = function (string $tabla, array $columnas) {
            if (! Schema::hasIndex($tabla, $columnas)) {
                Schema::table($tabla, function (Blueprint $table) use ($columnas) {
                    $table->index($columnas);
                });
            }
        };

        // 'visita': se filtra por edificio y por estado en casi cada consulta
        // (dashboard, reportes, ComidaController), y se ordena/filtra por
        // fecha_entrada en los reportes por periodo.
        $agregarIndice('visita', ['id_edificio']);
        $agregarIndice('visita', ['estado']);
        $agregarIndice('visita', ['fecha_entrada']);

        // Llave de join usada en absolutamente todas las consultas que cruzan
        // 'visita' con su tabla hija correspondiente.
        $agregarIndice('visita_familiar', ['id_visita']);
        $agregarIndice('visita_proveedor', ['id_visita']);
        $agregarIndice('visita_postulante', ['id_visita']);
        $agregarIndice('visita_torre', ['id_visita']);

        // ComidaController busca visitantes activos por piso+habitación en
        // cada carga del panel de vinculación y en cada guardado.
        $agregarIndice('visita_familiar', ['piso', 'habitacion']);

        // El área de cafetería filtra visitas de proveedor por área_destino.
        $agregarIndice('visita_proveedor', ['area_destino']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $quitarIndice = function (string $tabla, array $columnas) {
            if (Schema::hasIndex($tabla, $columnas)) {
                Schema::table($tabla, function (Blueprint $table) use ($columnas) {
                    $table->dropIndex($columnas);
                });
            }
        };

        $quitarIndice('visita', ['id_edificio']);
        $quitarIndice('visita', ['estado']);
        $quitarIndice('visita', ['fecha_entrada']);
        $quitarIndice('visita_familiar', ['id_visita']);
        $quitarIndice('visita_proveedor', ['id_visita']);
        $quitarIndice('visita_postulante', ['id_visita']);
        $quitarIndice('visita_torre', ['id_visita']);
        $quitarIndice('visita_familiar', ['piso', 'habitacion']);
        $quitarIndice('visita_proveedor', ['area_destino']);
    }
};
