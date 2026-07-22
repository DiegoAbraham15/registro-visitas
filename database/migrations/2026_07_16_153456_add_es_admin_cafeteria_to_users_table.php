<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Permiso independiente del área asignada: ve el resumen diario de
     * cafetería (conteo de platillos a preparar) aunque su área principal
     * sea otra, igual que 'acceso_vinculacion'.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('es_admin_cafeteria')->default(false)->after('acceso_vinculacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('es_admin_cafeteria');
        });
    }
};
