<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Permiso independiente del área asignada y de 'es_admin': deja administrar
     * los catálogos de Habitaciones, Áreas (/catalogos) y Médicos (/medicos)
     * sin tener que volver a alguien administrador completo (que además vería
     * Usuarios y Bitácora). Mismo patrón que 'acceso_vinculacion'.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('acceso_catalogos')->default(false)->after('es_admin_cafeteria');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('acceso_catalogos');
        });
    }
};
