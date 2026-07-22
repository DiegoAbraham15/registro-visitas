<?php

namespace App\Http\Controllers;

use App\Support\CortesiaVigente;
use Illuminate\Support\Facades\DB;

/**
 * Resumen de cuántos platillos hay que preparar hoy, sacado de
 * 'cafeteria_cortesias' (misma tabla que llena vinculación). Es de solo
 * lectura: cafetería no edita nada desde aquí, solo ve el conteo.
 */
class CafeteriaResumenController extends Controller
{
    public function index()
    {
        $cortesias = DB::table('cafeteria_cortesias')
            ->where('activo', 1)
            ->orderBy('piso')
            ->orderBy('habitacion')
            ->get();

        // Los platillos de un día anterior sin actualizar no cuentan como
        // "para hoy" (ver CortesiaVigente): si no se excluyen, el conteo de
        // desayunos/cenas a preparar incluiría sobras de otro día.
        $cortesiasDeHoy = $cortesias->filter(fn ($c) => CortesiaVigente::esDeHoy($c));

        $contarPorPlato = fn (string $columna) => $cortesiasDeHoy->pluck($columna)->filter()->countBy()->sortDesc();

        $resumen = [
            'total_habitaciones' => $cortesias->count(),
            'sin_desayuno' => $cortesias->filter(fn ($c) => ! CortesiaVigente::esDeHoy($c) || empty($c->platillo_desayuno))->count(),
            'sin_cena' => $cortesias->filter(fn ($c) => ! CortesiaVigente::esDeHoy($c) || empty($c->platillo_cena))->count(),
            'desayuno' => $contarPorPlato('platillo_desayuno'),
            'comida' => $contarPorPlato('platillo_comida'),
            'cena' => $contarPorPlato('platillo_cena'),
            'bebida' => $contarPorPlato('bebida'),
        ];

        return view('cafeteria.resumen', compact('resumen', 'cortesias'));
    }
}
