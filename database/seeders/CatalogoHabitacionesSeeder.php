<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogoHabitacionesSeeder extends Seeder
{
    /**
     * Catálogo real de habitaciones por piso, confirmado con el cliente.
     * Piso 2: 201-223 (no existe la 213) + 3 habitaciones UTIMA.
     * Piso 3: 301-310 + 4 habitaciones UTIMA.
     */
    private function habitacionesPiso2(): array
    {
        $normales = array_values(array_diff(range(201, 223), [213]));
        $numeros = array_map(fn ($n) => (string) $n, $normales);

        return array_merge($numeros, ['UTIMA 1', 'UTIMA 2', 'UTIMA 3']);
    }

    private function habitacionesPiso3(): array
    {
        $normales = array_map(fn ($n) => (string) $n, range(301, 310));

        return array_merge($normales, ['UTIMA 1', 'UTIMA 2', 'UTIMA 3', 'UTIMA 4']);
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pisos = [
            ['piso' => 'Piso 2', 'orden' => 3, 'habitaciones' => $this->habitacionesPiso2()],
            ['piso' => 'Piso 3', 'orden' => 4, 'habitaciones' => $this->habitacionesPiso3()],
        ];

        foreach ($pisos as $grupo) {
            foreach ($grupo['habitaciones'] as $numero) {
                DB::table('catalogo_habitaciones')->updateOrInsert(
                    ['piso' => $grupo['piso'], 'numero' => $numero],
                    ['piso_orden' => $grupo['orden'], 'updated_at' => now(), 'created_at' => now()]
                );
            }
        }

        $this->rellenarPisoEnVisitasExistentes();
    }

    /**
     * Las visitas de familiares ya registradas solo tienen 'habitacion' (texto libre).
     * Cuando el número coincide con un único piso del catálogo, se completa 'piso'
     * retroactivamente; los valores ambiguos o inválidos (datos de prueba) se dejan igual.
     */
    private function rellenarPisoEnVisitasExistentes(): void
    {
        $habitacionesUnPiso = DB::table('catalogo_habitaciones')
            ->select('numero', DB::raw('COUNT(DISTINCT piso) as pisos_distintos'), DB::raw('MIN(piso) as piso'))
            ->groupBy('numero')
            ->having('pisos_distintos', 1)
            ->get();

        foreach ($habitacionesUnPiso as $habitacion) {
            DB::table('visita_familiar')
                ->where('habitacion', $habitacion->numero)
                ->whereNull('piso')
                ->update(['piso' => $habitacion->piso]);
        }
    }
}
