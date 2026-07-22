<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Permiso independiente del área asignada: permite ver /vinculacion/*
     * aunque el área principal del usuario sea otra (hospital, consultorios,
     * cafetería), igual que 'acceso_reportes' no depende del área.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('acceso_vinculacion')->default(false)->after('acceso_reportes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('acceso_vinculacion');
        });
    }
};
