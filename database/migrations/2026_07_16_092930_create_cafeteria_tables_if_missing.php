<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 'cafeteria_pacientes' (roster de pacientes activos por habitación) y
 * 'cafeteria_cortesias' (platillo elegido por habitación) ya existen en la
 * base de datos compartida con la app móvil — igual que las tablas de
 * visita (ver create_visita_tables_if_missing.php), nunca las creó una
 * migración de este repo, así que la BD de pruebas en SQLite no las tenía.
 *
 * Cada CREATE va envuelto en hasTable() para que esta migración sea segura
 * de correr también contra la BD real: ahí no hace nada (las tablas ya
 * existen), solo queda registrada.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cafeteria_pacientes')) {
            Schema::create('cafeteria_pacientes', function (Blueprint $table) {
                $table->id();
                $table->string('piso', 50);
                $table->string('habitacion', 20);
                $table->string('nombre', 150);
                $table->boolean('activo')->default(true);
                $table->date('fecha_ingreso')->nullable();
                $table->date('fecha_alta')->nullable();
                $table->foreignId('id_usuario_registro')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('cafeteria_cortesias')) {
            Schema::create('cafeteria_cortesias', function (Blueprint $table) {
                $table->id();
                $table->string('piso', 50);
                $table->string('habitacion', 20);
                $table->boolean('activo')->default(true);
                $table->string('platillo_desayuno', 150)->nullable();
                $table->string('platillo_comida', 150)->nullable();
                $table->string('platillo_cena', 150)->nullable();
                $table->foreignId('id_usuario_carga')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['piso', 'habitacion']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No se borran: en la BD real estas tablas no son responsabilidad de
        // este repo. Solo aplicaría a una BD de pruebas creada por esta misma
        // migración, y ahí RefreshDatabase ya descarta la BD completa.
    }
};
