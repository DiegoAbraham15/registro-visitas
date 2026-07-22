<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ConsultorioMedicoController extends Controller
{
    public function index(Request $request)
    {
        $busqueda = trim((string) $request->query('busqueda', ''));

        $medicosQuery = DB::table('consultorios_medicos')
            ->leftJoin('catalogo_consultorios', 'catalogo_consultorios.numero', '=', 'consultorios_medicos.consultorio')
            ->select(
                'consultorios_medicos.id', 'consultorios_medicos.consultorio', 'consultorios_medicos.nombre_medico',
                DB::raw("COALESCE(catalogo_consultorios.piso, 'Sin piso registrado') as piso"),
                DB::raw('COALESCE(catalogo_consultorios.piso_orden, 99) as piso_orden')
            );

        if ($busqueda !== '') {
            $medicosQuery->where(function ($query) use ($busqueda) {
                $query->where('consultorios_medicos.nombre_medico', 'like', "%{$busqueda}%")
                    ->orWhere('consultorios_medicos.consultorio', 'like', "%{$busqueda}%");
            });
        }

        $medicosPorPiso = $medicosQuery
            ->orderBy('piso_orden')
            ->orderByRaw('CAST(consultorios_medicos.consultorio AS UNSIGNED)')
            ->orderBy('consultorios_medicos.nombre_medico')
            ->get()
            ->groupBy('piso');

        return view('medicos.index', compact('medicosPorPiso', 'busqueda'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'consultorio' => ['required', 'string', 'max:20'],
            'nombre_medico' => [
                'required', 'string', 'max:150',
                Rule::unique('consultorios_medicos', 'nombre_medico')->where('consultorio', $request->input('consultorio')),
            ],
        ]);

        DB::table('consultorios_medicos')->insert([
            'consultorio' => $validated['consultorio'],
            'nombre_medico' => $validated['nombre_medico'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Cache::forget('medicos_por_consultorio');

        Bitacora::registrar('medico.crear', "Agregó al Dr(a). {$validated['nombre_medico']} en el consultorio {$validated['consultorio']}.");

        return redirect('/medicos')->with('status', 'Médico agregado correctamente.');
    }

    public function update(Request $request, int $id)
    {
        $medico = DB::table('consultorios_medicos')->where('id', $id)->first();

        if (! $medico) {
            abort(404);
        }

        $validated = $request->validate([
            'consultorio' => ['required', 'string', 'max:20'],
            'nombre_medico' => [
                'required', 'string', 'max:150',
                Rule::unique('consultorios_medicos', 'nombre_medico')->where('consultorio', $request->input('consultorio'))->ignore($id),
            ],
        ]);

        DB::table('consultorios_medicos')->where('id', $id)->update([
            'consultorio' => $validated['consultorio'],
            'nombre_medico' => $validated['nombre_medico'],
            'updated_at' => now(),
        ]);

        Cache::forget('medicos_por_consultorio');

        Bitacora::registrar('medico.actualizar', "Actualizó al Dr(a). {$medico->nombre_medico} ({$medico->consultorio}) a {$validated['nombre_medico']} ({$validated['consultorio']}).");

        return redirect('/medicos')->with('status', 'Médico actualizado correctamente.');
    }

    public function destroy(int $id)
    {
        $medico = DB::table('consultorios_medicos')->where('id', $id)->first();

        if (! $medico) {
            abort(404);
        }

        DB::table('consultorios_medicos')->where('id', $id)->delete();

        Cache::forget('medicos_por_consultorio');

        Bitacora::registrar('medico.eliminar', "Eliminó al Dr(a). {$medico->nombre_medico} del consultorio {$medico->consultorio}.");

        return redirect('/medicos')->with('status', 'Médico eliminado correctamente.');
    }
}
