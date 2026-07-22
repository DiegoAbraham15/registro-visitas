<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogoAreasSeeder extends Seeder
{
    /**
     * Catálogo real de pisos y las áreas que albergan, proporcionado por el cliente.
     */
    private const PISOS = [
        ['piso' => 'Sótano', 'orden' => 0, 'areas' => [
            'ROPERIA', 'TALLER MANTENIMIENTO',
        ]],
        ['piso' => 'Planta Baja', 'orden' => 1, 'areas' => [
            'ADMINISTRACION CAFETERIA', 'ADMISIÓN', 'ALMACEN GENERAL', 'AUXILIAR DE CAFETERIA',
            'AUXILIAR DE OPERACIONES', 'BIOMEDICA', 'CAFETERÍA', 'CAJAS', 'COCINA', 'CORTA ESTANCIA',
            'DIRECCION ADMINISTRATIVA', 'EJECUTIVO DE VENTAS', 'FARMACIA', 'HEMODINAMIA', 'IMAGENOLOGIA',
            'INHALOTERAPIA', 'URGENCIAS', 'VALLET PARKING', 'VINCULACIÓN MEDICA',
        ]],
        ['piso' => 'Piso 1', 'orden' => 2, 'areas' => [
            'CAPTURA', 'CEYE', 'CUNEROS', 'HIGIENE', 'QUIROFANOS', 'UCIN', 'UTIA',
        ]],
        ['piso' => 'Piso 2', 'orden' => 3, 'areas' => [
            'ANALISTA DE FINANZAS', 'CIBERSEGURIDAD', 'CONTRALORIA', 'DIR GRAL', 'DISEÑO',
            'EPIDEMIOLOGIA', 'HOSPITALIZACION', 'NUTRICIÓN', 'SUPERVISIÓN ENFERMERIA',
        ]],
        ['piso' => 'Piso 3', 'orden' => 4, 'areas' => [
            'CONTABILIDAD', 'HOSPITALIZACION', 'INVENTARIOS', 'MANTENIMIENTO', 'RH', 'SISTEMAS',
        ]],
        ['piso' => 'Piso 4', 'orden' => 5, 'areas' => [
            'ARCHIVO CLINICO', 'BANCO SANGRE',
        ]],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::PISOS as $grupo) {
            foreach ($grupo['areas'] as $area) {
                DB::table('catalogo_areas')->updateOrInsert(
                    ['piso' => $grupo['piso'], 'nombre' => $area],
                    ['piso_orden' => $grupo['orden'], 'updated_at' => now(), 'created_at' => now()]
                );
            }
        }

        $this->rellenarPisoEnVisitasExistentes();
    }

    /**
     * Las visitas de proveedor ya registradas solo tienen 'area_destino' (texto libre).
     * Como en los datos actuales cada nombre de área corresponde a un único piso,
     * se puede inferir el piso sin ambigüedad y completar 'piso_destino' retroactivamente.
     */
    private function rellenarPisoEnVisitasExistentes(): void
    {
        $areasUnPiso = DB::table('catalogo_areas')
            ->select('nombre', DB::raw('COUNT(DISTINCT piso) as pisos_distintos'), DB::raw('MIN(piso) as piso'))
            ->groupBy('nombre')
            ->having('pisos_distintos', 1)
            ->get();

        foreach ($areasUnPiso as $area) {
            DB::table('visita_proveedor')
                ->where('area_destino', $area->nombre)
                ->whereNull('piso_destino')
                ->update(['piso_destino' => $area->piso]);
        }
    }
}
