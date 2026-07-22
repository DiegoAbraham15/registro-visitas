<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Las tablas de visitas (visita, visita_familiar, visita_proveedor,
 * visita_postulante, visita_torre, ex_empleados) y catalogo_consultorios ya
 * existen en la base de datos compartida con la app móvil — nunca las creó
 * una migración de este repo, así que un `php artisan migrate` fresco (p.
 * ej. la BD de pruebas en SQLite) no las tenía, y la suite de tests tronaba
 * con "no such table: visita_familiar" en cuanto la migración de 'piso'
 * intentaba alterarla.
 *
 * Cada CREATE va envuelto en `hasTable()` para que esta migración sea segura
 * de correr también contra la BD real: ahí no hace nada (las tablas ya
 * existen), solo queda registrada.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('visita')) {
            Schema::create('visita', function (Blueprint $table) {
                $table->increments('id_visita');
                $table->integer('id_edificio');
                $table->string('tipo_visitante', 50)->nullable();
                $table->dateTime('fecha_entrada')->nullable();
                $table->string('estado', 20)->nullable();
                $table->dateTime('fecha_salida')->nullable();
            });
        }

        if (! Schema::hasTable('visita_familiar')) {
            Schema::create('visita_familiar', function (Blueprint $table) {
                $table->integer('id_visita');
                $table->string('nombre', 150)->nullable();
                $table->string('parentesco', 100)->nullable();
                $table->string('habitacion', 50)->nullable();
                // 'piso' se agrega en la migración add_piso_to_visita_familiar_table.
                $table->string('nombre_paciente', 150)->nullable();
                $table->string('folio', 20)->nullable();
                $table->string('foto_persona')->nullable();
                $table->string('foto_ine')->nullable();
            });
        }

        if (! Schema::hasTable('visita_proveedor')) {
            Schema::create('visita_proveedor', function (Blueprint $table) {
                $table->integer('id_visita');
                $table->string('empresa_representada', 150)->nullable();
                $table->string('nombre', 150)->nullable();
                $table->string('piso_destino', 100)->nullable();
                $table->string('area_destino', 100)->nullable();
                $table->time('hora_entrada')->nullable();
                $table->time('hora_salida')->nullable();
                $table->string('estado', 20)->nullable();
                $table->date('fecha')->nullable();
                $table->string('folio', 20)->nullable();
                $table->string('foto_persona')->nullable();
                $table->string('foto_ine')->nullable();
                $table->string('motivo_visita')->nullable();
            });
        }

        if (! Schema::hasTable('visita_postulante')) {
            Schema::create('visita_postulante', function (Blueprint $table) {
                $table->integer('id_visita');
                $table->string('nombre', 150)->nullable();
                $table->string('puesto', 100)->nullable();
                $table->string('area_destino', 100)->nullable();
                $table->string('responsable_rh', 150)->nullable();
                $table->string('tipo_cita', 100)->nullable();
                $table->boolean('cv_entregado')->nullable();
                $table->string('foto_persona')->nullable();
                $table->string('foto_ine')->nullable();
                $table->string('folio', 20)->nullable();
            });
        }

        if (! Schema::hasTable('visita_torre')) {
            Schema::create('visita_torre', function (Blueprint $table) {
                $table->integer('id_visita');
                $table->string('tipo_acceso', 50)->nullable();
                $table->string('piso', 30)->nullable();
                $table->string('consultorio', 20)->nullable();
                // Ya existía en la BD real sin que ninguna migración de este repo la
                // hubiera creado (ni la app móvil ni esta app la llenaban); se usa aquí
                // para capturar, desde /visitas/{id}/editar, con qué médico específico
                // va cada visitante de Torre (ver ConsultorioMedicoController y /medicos).
                $table->string('nombre_medico', 150)->nullable();
                $table->string('nombre', 150)->nullable();
                $table->string('foto_persona')->nullable();
                $table->string('folio', 20)->nullable();
            });
        }

        if (! Schema::hasTable('ex_empleados')) {
            Schema::create('ex_empleados', function (Blueprint $table) {
                $table->id();
                $table->integer('id_visita')->nullable()->unique();
                $table->string('folio', 25)->unique();
                $table->string('nombre', 255);
                $table->string('motivo', 100)->default('Finiquito');
                $table->string('tipo_visita', 50)->default('Ex Empleado');
                $table->string('foto_persona');
                $table->string('foto_ine')->nullable();
                $table->timestamp('fecha_entrada')->useCurrent();
                $table->timestamp('fecha_salida')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('catalogo_consultorios')) {
            Schema::create('catalogo_consultorios', function (Blueprint $table) {
                $table->id();
                $table->string('piso', 30);
                $table->unsignedTinyInteger('piso_orden');
                $table->string('numero', 20);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // No se borran: en la BD real estas tablas no son responsabilidad de
        // este repo. Solo aplicaría a una BD de pruebas creada por esta misma
        // migración, y ahí RefreshDatabase ya descarta la BD completa.
    }
};
