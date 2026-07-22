<?php

namespace App\Http\Controllers;

use App\Models\ComidaVisitantes;
use App\Models\MenuDia;
use App\Models\MenuSemanaOpciones;
use App\Support\CortesiaVigente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ComidaController extends Controller
{
    /**
     * El usuario de "vinculación" elige entre estas opciones para la bebida;
     * a diferencia de desayuno/cena, la bebida no forma parte del menú
     * semanal editable (ver decisión con el cliente), ni existe una columna
     * para ella en 'cafeteria_cortesias'.
     */
    public const OPCIONES_BEBIDA = ['Agua', 'Café', 'Jugo', 'Refresco', 'Ninguna'];

    /**
     * Mismo orden usado en el resto de la app (VisitaConsultas, dashboard_area)
     * para listar pisos en un orden lógico en vez de alfabético.
     */
    private const ORDEN_PISOS = ['Sótano', 'Planta Baja', 'Piso 1', 'Piso 2', 'Piso 3', 'Piso 4'];

    private function menuDeHoy(): MenuDia
    {
        return MenuDia::firstOrCreate(['dia' => MenuDia::DIAS[now()->dayOfWeek]]);
    }

    /**
     * Crea o reactiva la fila de 'cafeteria_cortesias' (tabla real de la app
     * móvil) para una habitación, sin pisar created_at ni el resto de los
     * campos si ya existía (updateOrInsert no sirve aquí: su conteo de filas
     * afectadas no distingue "ya existía sin cambios" de "no existía", y
     * insertaría un duplicado contra el unique(piso, habitacion)).
     *
     * Si se reactiva (store()) una cortesía que quedó de un día anterior y
     * esta llamada no trae platillos nuevos, se limpian los platillos/bebida
     * viejos: son de otro ocupante/día y dejarlos tal cual haría que
     * CortesiaVigente los cuente como "ya decididos hoy" sin serlo.
     */
    private function marcarCortesiaActiva(string $piso, string $habitacion, array $extra = []): void
    {
        $query = DB::table('cafeteria_cortesias')->where('piso', $piso)->where('habitacion', $habitacion);
        $existente = $query->first();

        $datos = array_merge([
            'activo' => 1,
            'id_usuario_carga' => Auth::id(),
            'updated_at' => now(),
        ], $extra);

        if ($existente && empty($extra) && ! CortesiaVigente::esDeHoy($existente)) {
            $datos = array_merge($datos, [
                'platillo_desayuno' => null,
                'platillo_cena' => null,
                'bebida' => null,
                'entregar_a' => null,
            ]);
        }

        if ($existente) {
            $query->update($datos);
        } else {
            DB::table('cafeteria_cortesias')->insert(array_merge($datos, [
                'piso' => $piso,
                'habitacion' => $habitacion,
                'created_at' => now(),
            ]));
        }
    }

    /**
     * "Habitación activa" (aparece en el panel) es cualquiera con: un
     * paciente activo en cafeteria_pacientes, una cortesía activa (agregada
     * a mano), o una visita familiar activa — así no depende de que
     * cafeteria_pacientes esté al día para que la habitación aparezca en
     * cuanto se registra una visita nueva. La comida es para las visitas del
     * paciente, no para el paciente mismo: el paciente se muestra solo como
     * referencia, y son los visitantes (visita_familiar) los que llevan
     * checkbox.
     */
    public function index()
    {
        $comidaHoy = $this->menuDeHoy();
        $opcionesSemana = MenuSemanaOpciones::actual();

        $pacientesActivos = DB::table('cafeteria_pacientes')
            ->where('activo', 1)
            ->get()
            ->groupBy(fn ($p) => $p->piso.'|'.$p->habitacion);

        $cortesiasActivas = DB::table('cafeteria_cortesias')
            ->where('activo', 1)
            ->get()
            ->keyBy(fn ($c) => $c->piso.'|'.$c->habitacion);

        $visitantesActivos = DB::table('visita')
            ->join('visita_familiar', 'visita.id_visita', '=', 'visita_familiar.id_visita')
            ->where('visita.estado', 'activa')
            ->select('visita_familiar.piso', 'visita_familiar.habitacion', 'visita.id_visita', 'visita_familiar.nombre')
            ->get()
            ->groupBy(fn ($v) => $v->piso.'|'.$v->habitacion);

        $seleccionesVisitantes = ComidaVisitantes::all()->keyBy(fn ($s) => $s->piso.'|'.$s->habitacion);

        $ordenPisos = array_flip(self::ORDEN_PISOS);

        // El panel muestra una habitación si tiene un paciente activo, una
        // cortesía activa (incluye las agregadas a mano), o una visita
        // familiar activa.
        $llavesEnPanel = $pacientesActivos->keys()
            ->merge($cortesiasActivas->keys())
            ->merge($visitantesActivos->keys())
            ->unique();

        $habitaciones = $llavesEnPanel
            ->map(function ($llave) use ($pacientesActivos, $visitantesActivos, $cortesiasActivas, $seleccionesVisitantes) {
                [$piso, $habitacion] = explode('|', $llave, 2);

                return (object) [
                    'piso' => $piso,
                    'habitacion' => $habitacion,
                    'pacientes' => $pacientesActivos->get($llave, collect()),
                    'visitantes' => $visitantesActivos->get($llave, collect()),
                    'cortesia' => $cortesiasActivas->get($llave),
                    'seleccion' => $seleccionesVisitantes->get($llave),
                ];
            })
            ->sortBy(fn ($h) => [$ordenPisos[$h->piso] ?? 99, $h->habitacion])
            ->values();

        // Aviso para no depender de que alguien se acuerde de llenar cada
        // habitación: cuenta las que todavía no tienen desayuno o cena definidos
        // hoy (o cuyo desayuno/cena guardado es de un día anterior sin actualizar).
        $habitacionesSinMenu = $habitaciones
            ->filter(fn ($h) => ! CortesiaVigente::tieneMenuDeHoy($h->cortesia))
            ->count();

        // Solo se ofrecen para "agregar" las habitaciones del catálogo que todavía
        // no aparecen arriba. Se agrupan por piso para el <select> en cascada
        // Piso → Habitación (mismo patrón que visitas/editar.blade.php). El
        // catálogo en sí casi no cambia, así que se cachea; el filtrado contra
        // $llavesEnPanel (ocupación de hoy) se recalcula siempre en fresco.
        // Se cachea como array de arrays (ni Collection ni stdClass): guardar
        // cualquier objeto serializado deja la caché expuesta a "incomplete
        // object" si una escritura del archivo de caché queda a medias (proceso
        // matado a mitad de escritura, antivirus, etc.) — un array no tiene
        // clase que "cargar" al deserializar, así que no puede quedar incompleto.
        $catalogoHabitaciones = collect(Cache::remember('catalogo_habitaciones_por_piso_orden', 600, fn () => DB::table('catalogo_habitaciones')
            ->orderBy('piso_orden')
            ->orderBy('numero')
            ->get()
            ->map(fn ($cat) => (array) $cat)
            ->all()));

        $habitacionesPorPiso = $catalogoHabitaciones
            ->reject(fn ($cat) => $llavesEnPanel->contains($cat['piso'].'|'.$cat['numero']))
            ->groupBy('piso')
            ->map(fn ($grupo) => $grupo->map(fn ($cat) => ['id' => $cat['id'], 'numero' => $cat['numero']])->values());

        $opcionesBebida = self::OPCIONES_BEBIDA;

        return view('comidas.dashboard', compact('habitaciones', 'comidaHoy', 'opcionesSemana', 'opcionesBebida', 'habitacionesPorPiso', 'habitacionesSinMenu'));
    }

    /**
     * Agrega al panel una habitación del catálogo que todavía no tenga
     * cortesía activa ni paciente activo (p. ej. un paciente que aún no está
     * registrado en cafeteria_pacientes). Solo activa la cortesía; no toca
     * los platillos si la fila ya existía.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'catalogo_habitacion_id' => ['required', 'integer', 'exists:catalogo_habitaciones,id'],
        ]);

        $habitacion = DB::table('catalogo_habitaciones')->find($validated['catalogo_habitacion_id']);

        $this->marcarCortesiaActiva($habitacion->piso, $habitacion->numero);

        return redirect('/vinculacion/dashboard')->with('status', 'Habitación agregada al panel de hoy.');
    }

    public function update(Request $request)
    {
        $habitacion = $request->validate([
            'piso' => ['required', 'string', 'max:50'],
            'habitacion' => ['required', 'string', 'max:20'],
        ]);

        $comidaHoy = $this->menuDeHoy();
        $opcionesSemana = MenuSemanaOpciones::actual();

        $idsVisitantesValidos = DB::table('visita')
            ->join('visita_familiar', 'visita.id_visita', '=', 'visita_familiar.id_visita')
            ->where('visita.estado', 'activa')
            ->where('visita_familiar.piso', $habitacion['piso'])
            ->where('visita_familiar.habitacion', $habitacion['habitacion'])
            ->pluck('visita.id_visita')
            ->all();

        // Solo se puede elegir a un familiar a la vez (o "otro"): un único
        // campo de radio en vez de checkboxes independientes, con las mismas
        // opciones válidas ya calculadas arriba, más el valor especial "otro".
        $validated = $request->validate([
            'recipiente' => ['nullable', 'string', Rule::in(array_merge(array_map('strval', $idsVisitantesValidos), ['otro']))],
            'otro' => ['nullable', 'string', 'max:150'],
            'observaciones' => ['nullable', 'string', 'max:500'],
            'desayuno' => ['nullable', 'string', Rule::in($opcionesSemana->desayuno_opciones ?? [])],
            'cena' => ['nullable', 'string', Rule::in($opcionesSemana->cena_opciones ?? [])],
            'bebida' => ['nullable', 'string', Rule::in(self::OPCIONES_BEBIDA)],
        ]);

        $recipiente = $validated['recipiente'] ?? null;
        $idVisitanteElegido = ($recipiente && $recipiente !== 'otro') ? (int) $recipiente : null;
        $otro = ($recipiente === 'otro') ? ($validated['otro'] ?? null) : null;

        // Resumen legible de a quién se le entrega, para que cafetería (app móvil)
        // sepa a quién llevarle la comida sin tener que cruzar ids de visita.
        $nombreVisitante = $idVisitanteElegido
            ? DB::table('visita_familiar')->where('id_visita', $idVisitanteElegido)->value('nombre')
            : null;
        $entregarA = collect([$nombreVisitante, $otro])->filter()->implode(', ') ?: null;

        // El platillo del día, la bebida y a quién entregarle viven en la tabla
        // real de la app móvil, para que cafetería los pueda ver.
        $this->marcarCortesiaActiva($habitacion['piso'], $habitacion['habitacion'], [
            'platillo_desayuno' => $validated['desayuno'] ?? null,
            'platillo_comida' => $comidaHoy->comida,
            'platillo_cena' => $validated['cena'] ?? null,
            'bebida' => $validated['bebida'] ?? null,
            'entregar_a' => $entregarA,
        ]);

        // Qué visitante quedó elegido, "otro" y las observaciones no tienen
        // columna en cafeteria_cortesias: se guardan aparte para poder repintar
        // el formulario tal cual se dejó. 'visitantes_seleccionados' sigue
        // siendo un arreglo por compatibilidad, pero ahora con 0 o 1 elemento.
        ComidaVisitantes::updateOrCreate(
            ['piso' => $habitacion['piso'], 'habitacion' => $habitacion['habitacion']],
            [
                'visitantes_seleccionados' => $idVisitanteElegido ? [$idVisitanteElegido] : [],
                'otro_texto' => $otro,
                'observaciones' => $validated['observaciones'] ?? null,
            ]
        );

        return redirect('/vinculacion/dashboard')->with('status', 'Comida actualizada correctamente.');
    }

    /**
     * Quita una habitación del panel desactivando su cortesía
     * (cafeteria_cortesias.activo = 0), lo mismo que ve cafetería. No se
     * puede quitar si todavía tiene un paciente activo real en
     * cafeteria_pacientes o una visita familiar activa: esa habitación
     * sigue necesitando comida, sin importar lo que diga su cortesía.
     */
    public function destroy(Request $request)
    {
        $habitacion = $request->validate([
            'piso' => ['required', 'string', 'max:50'],
            'habitacion' => ['required', 'string', 'max:20'],
        ]);

        $tienePacienteActivo = DB::table('cafeteria_pacientes')
            ->where('piso', $habitacion['piso'])
            ->where('habitacion', $habitacion['habitacion'])
            ->where('activo', 1)
            ->exists();

        $tieneVisitaActiva = DB::table('visita')
            ->join('visita_familiar', 'visita.id_visita', '=', 'visita_familiar.id_visita')
            ->where('visita.estado', 'activa')
            ->where('visita_familiar.piso', $habitacion['piso'])
            ->where('visita_familiar.habitacion', $habitacion['habitacion'])
            ->exists();

        if ($tienePacienteActivo || $tieneVisitaActiva) {
            return back()->withErrors(['error' => 'Esta habitación tiene un paciente o una visita activa; no se puede quitar del panel.']);
        }

        DB::table('cafeteria_cortesias')
            ->where('piso', $habitacion['piso'])
            ->where('habitacion', $habitacion['habitacion'])
            ->update(['activo' => 0, 'updated_at' => now()]);

        return redirect('/vinculacion/dashboard')->with('status', 'Habitación quitada del panel.');
    }
}
