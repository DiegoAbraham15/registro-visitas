<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CatalogoController extends Controller
{
    public function index()
    {
        $habitacionesPorPiso = DB::table('catalogo_habitaciones')
            ->orderBy('piso_orden')
            ->orderBy('numero')
            ->get()
            ->groupBy('piso');

        $areasPorPiso = DB::table('catalogo_areas')
            ->orderBy('piso_orden')
            ->orderBy('nombre')
            ->get()
            ->groupBy('piso');

        return view('catalogos.index', compact('habitacionesPorPiso', 'areasPorPiso'));
    }

    /**
     * El piso_orden no se le pide al administrador: si el piso ya existe en el
     * catálogo se reutiliza su orden (para no desincronizar el orden de las
     * demás filas de ese mismo piso); si es un piso nuevo, se le asigna el
     * siguiente orden disponible.
     */
    private function pisoOrden(string $tabla, string $piso): int
    {
        $existente = DB::table($tabla)->where('piso', $piso)->value('piso_orden');

        if ($existente !== null) {
            return (int) $existente;
        }

        return (int) DB::table($tabla)->max('piso_orden') + 1;
    }

    /**
     * Los dropdowns en cascada de visitas/editar y del panel de Vinculación
     * cachean estos catálogos por varios minutos (ver VisitaController y
     * ComidaController); si no se invalida aquí, una edición hecha desde esta
     * pantalla tardaría hasta 10 minutos en reflejarse ahí.
     */
    private function olvidarCacheHabitaciones(): void
    {
        Cache::forget('catalogo_por_piso:catalogo_habitaciones:numero');
        Cache::forget('catalogo_habitaciones_por_piso_orden');
    }

    private function olvidarCacheAreas(): void
    {
        Cache::forget('catalogo_por_piso:catalogo_areas:nombre');
    }

    public function storeHabitacion(Request $request)
    {
        $validated = $request->validate([
            'piso' => ['required', 'string', 'max:30'],
            'numero' => [
                'required', 'string', 'max:20',
                Rule::unique('catalogo_habitaciones', 'numero')->where('piso', $request->input('piso')),
            ],
        ]);

        DB::table('catalogo_habitaciones')->insert([
            'piso' => $validated['piso'],
            'piso_orden' => $this->pisoOrden('catalogo_habitaciones', $validated['piso']),
            'numero' => $validated['numero'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->olvidarCacheHabitaciones();

        Bitacora::registrar('catalogo.crear_habitacion', "Agregó la habitación {$validated['numero']} en {$validated['piso']}.");

        return redirect('/catalogos')->with('status', 'Habitación agregada correctamente.');
    }

    public function updateHabitacion(Request $request, int $id)
    {
        $habitacion = DB::table('catalogo_habitaciones')->where('id', $id)->first();

        if (! $habitacion) {
            abort(404);
        }

        $validated = $request->validate([
            'piso' => ['required', 'string', 'max:30'],
            'numero' => [
                'required', 'string', 'max:20',
                Rule::unique('catalogo_habitaciones', 'numero')->where('piso', $request->input('piso'))->ignore($id),
            ],
        ]);

        DB::table('catalogo_habitaciones')->where('id', $id)->update([
            'piso' => $validated['piso'],
            'piso_orden' => $this->pisoOrden('catalogo_habitaciones', $validated['piso']),
            'numero' => $validated['numero'],
            'updated_at' => now(),
        ]);

        $this->olvidarCacheHabitaciones();

        Bitacora::registrar('catalogo.actualizar_habitacion', "Actualizó la habitación {$habitacion->numero} ({$habitacion->piso}) a {$validated['numero']} ({$validated['piso']}).");

        return redirect('/catalogos')->with('status', 'Habitación actualizada correctamente.');
    }

    public function destroyHabitacion(int $id)
    {
        $habitacion = DB::table('catalogo_habitaciones')->where('id', $id)->first();

        if (! $habitacion) {
            abort(404);
        }

        DB::table('catalogo_habitaciones')->where('id', $id)->delete();

        $this->olvidarCacheHabitaciones();

        Bitacora::registrar('catalogo.eliminar_habitacion', "Eliminó la habitación {$habitacion->numero} ({$habitacion->piso}) del catálogo.");

        return redirect('/catalogos')->with('status', 'Habitación eliminada correctamente.');
    }

    public function storeArea(Request $request)
    {
        $validated = $request->validate([
            'piso' => ['required', 'string', 'max:30'],
            'nombre' => [
                'required', 'string', 'max:100',
                Rule::unique('catalogo_areas', 'nombre')->where('piso', $request->input('piso')),
            ],
        ]);

        DB::table('catalogo_areas')->insert([
            'piso' => $validated['piso'],
            'piso_orden' => $this->pisoOrden('catalogo_areas', $validated['piso']),
            'nombre' => $validated['nombre'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->olvidarCacheAreas();

        Bitacora::registrar('catalogo.crear_area', "Agregó el área {$validated['nombre']} en {$validated['piso']}.");

        return redirect('/catalogos')->with('status', 'Área agregada correctamente.');
    }

    public function updateArea(Request $request, int $id)
    {
        $area = DB::table('catalogo_areas')->where('id', $id)->first();

        if (! $area) {
            abort(404);
        }

        $validated = $request->validate([
            'piso' => ['required', 'string', 'max:30'],
            'nombre' => [
                'required', 'string', 'max:100',
                Rule::unique('catalogo_areas', 'nombre')->where('piso', $request->input('piso'))->ignore($id),
            ],
        ]);

        DB::table('catalogo_areas')->where('id', $id)->update([
            'piso' => $validated['piso'],
            'piso_orden' => $this->pisoOrden('catalogo_areas', $validated['piso']),
            'nombre' => $validated['nombre'],
            'updated_at' => now(),
        ]);

        $this->olvidarCacheAreas();

        Bitacora::registrar('catalogo.actualizar_area', "Actualizó el área {$area->nombre} ({$area->piso}) a {$validated['nombre']} ({$validated['piso']}).");

        return redirect('/catalogos')->with('status', 'Área actualizada correctamente.');
    }

    public function destroyArea(int $id)
    {
        $area = DB::table('catalogo_areas')->where('id', $id)->first();

        if (! $area) {
            abort(404);
        }

        DB::table('catalogo_areas')->where('id', $id)->delete();

        $this->olvidarCacheAreas();

        Bitacora::registrar('catalogo.eliminar_area', "Eliminó el área {$area->nombre} ({$area->piso}) del catálogo.");

        return redirect('/catalogos')->with('status', 'Área eliminada correctamente.');
    }
}
