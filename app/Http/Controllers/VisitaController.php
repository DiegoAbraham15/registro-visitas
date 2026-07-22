<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Support\VisitaConsultas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class VisitaController extends Controller
{
    /**
     * Columna del detalle propio de cada tabla hija, según el tipo de visitante.
     * No incluye Torre de Consultorios (id_edificio=2): esa siempre usa 'consultorio'
     * sin importar el tipo_acceso (ver campoDetallePara()), porque 'tipo_acceso' puede
     * tomar el mismo valor ('proveedor') que ya usa esta tabla para Hospital y ambos
     * casos necesitan una columna de detalle distinta.
     */
    private const CAMPO_DETALLE = [
        'familiar' => 'habitacion',
        'proveedor' => 'area_destino',
        'postulante' => 'puesto',
        'ex_empleado' => 'motivo',
    ];

    private const CAMPO_DETALLE_TORRE = 'consultorio';

    /**
     * Tipos de visitante cuyo detalle se captura con un <select> en cascada Piso → Valor,
     * validado contra un catálogo. 'postulante' queda fuera: su 'puesto' es texto libre.
     * 'campo_piso' es la columna real de la tabla hija (para guardar); 'piso_alias' es el
     * nombre con el que se selecciona esa misma columna en buscarVisita() (para leerla sin
     * chocar con la columna 'piso' de otras tablas hijas en el mismo SELECT).
     * Torre de Consultorios va aparte en CASCADA_TORRE, por la misma razón que CAMPO_DETALLE.
     */
    private const CASCADA_POR_TIPO = [
        'proveedor' => ['catalogo' => 'catalogo_areas', 'columna' => 'nombre', 'campo_piso' => 'piso_destino', 'piso_alias' => 'piso_destino'],
        'familiar' => ['catalogo' => 'catalogo_habitaciones', 'columna' => 'numero', 'campo_piso' => 'piso', 'piso_alias' => 'piso_familiar'],
    ];

    private const CASCADA_TORRE = ['catalogo' => 'catalogo_consultorios', 'columna' => 'numero', 'campo_piso' => 'piso', 'piso_alias' => 'piso_torre'];

    /**
     * La tabla hija de una visita se decide por edificio primero: Torre de Consultorios
     * (id_edificio=2) siempre vive en visita_torre sin importar qué diga tipo_real, ya
     * que 'tipo_real' ahí viene de 'tipo_acceso' y puede coincidir con un tipo de Hospital
     * (p. ej. 'proveedor') que en realidad vive en otra tabla.
     */
    private function tablaHija(int $idEdificio, string $tipo): string
    {
        if ($idEdificio === 2) {
            return 'visita_torre';
        }

        return match ($tipo) {
            'familiar' => 'visita_familiar',
            'proveedor' => 'visita_proveedor',
            'postulante' => 'visita_postulante',
            'ex_empleado' => 'ex_empleados',
            default => abort(422, 'Esta visita no tiene datos en ninguna tabla; no se puede editar.'),
        };
    }

    private function cascadaPara($visita): ?array
    {
        if ((int) $visita->id_edificio === 2) {
            return self::CASCADA_TORRE;
        }

        return self::CASCADA_POR_TIPO[$visita->tipo_real] ?? null;
    }

    private function campoDetallePara($visita): ?string
    {
        if ((int) $visita->id_edificio === 2) {
            return self::CAMPO_DETALLE_TORRE;
        }

        return self::CAMPO_DETALLE[$visita->tipo_real] ?? null;
    }

    /**
     * El campo 'visita.tipo_visitante' no es confiable: el sistema real lo deja en un
     * valor genérico ("visitante") y el tipo real solo se puede saber por el edificio
     * y por cuál tabla hija tiene realmente los datos de esa visita. Para id_edificio=2
     * (Torre de Consultorios) el tipo real es 'visita_torre.tipo_acceso' ('visitante',
     * 'paciente', 'proveedor', ...) — es la única tabla hija de ese edificio.
     */
    private const SQL_TIPO_REAL = "
        CASE
            WHEN visita.id_edificio = 2 THEN COALESCE(visita_torre.tipo_acceso, 'sin-datos')
            WHEN visita_postulante.puesto IS NOT NULL THEN 'postulante'
            WHEN visita_proveedor.area_destino IS NOT NULL THEN 'proveedor'
            WHEN visita_familiar.habitacion IS NOT NULL THEN 'familiar'
            WHEN ex_empleados.nombre IS NOT NULL THEN 'ex_empleado'
            ELSE 'sin-datos'
        END
    ";

    private function buscarVisita(int $id)
    {
        try {
            $visita = VisitaConsultas::unirTablasHija(DB::table('visita'))
                ->select(
                    'visita.id_visita', 'visita.fecha_entrada', 'visita.fecha_salida', 'visita.estado', 'visita.id_edificio',
                    DB::raw(self::SQL_TIPO_REAL.' AS tipo_real'),
                    DB::raw('CASE WHEN visita.id_edificio = 2 THEN visita_torre.nombre ELSE COALESCE(visita_familiar.nombre, visita_proveedor.nombre, visita_postulante.nombre, ex_empleados.nombre) END AS nombre_visitante'),
                    DB::raw('CASE WHEN visita.id_edificio = 2 THEN visita_torre.folio ELSE COALESCE(visita_familiar.folio, visita_proveedor.folio, visita_postulante.folio, ex_empleados.folio) END AS folio'),
                    DB::raw('CASE WHEN visita.id_edificio = 2 THEN visita_torre.foto_persona ELSE COALESCE(visita_familiar.foto_persona, visita_proveedor.foto_persona, visita_postulante.foto_persona, ex_empleados.foto_persona) END AS foto_persona'),
                    DB::raw('CASE WHEN visita.id_edificio = 2 THEN visita_torre.consultorio ELSE COALESCE(visita_postulante.puesto, visita_proveedor.area_destino, visita_familiar.habitacion, ex_empleados.motivo) END AS detalle'),
                    'visita_proveedor.area_destino',
                    'visita_familiar.piso as piso_familiar', 'visita_proveedor.piso_destino', 'visita_torre.piso as piso_torre',
                    'visita_torre.nombre_medico as medico'
                )
                ->where('visita.id_visita', $id)
                ->first();
        } catch (\Throwable $e) {
            Log::error('Fallo al consultar una visita por id', [
                'id_visita' => $id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        if (! $visita) {
            abort(404);
        }

        return $visita;
    }

    private function autorizar($visita): void
    {
        $usuario = Auth::user();

        if ($usuario->es_admin) {
            return;
        }

        $permitido = match ($usuario->area) {
            'hospital' => (int) $visita->id_edificio === 1,
            'consultorios' => (int) $visita->id_edificio === 2,
            'cafeteria' => $visita->tipo_real === 'proveedor' && $visita->area_destino === 'CAFETERÍA',
            default => false,
        };

        if (! $permitido) {
            abort(403, 'No tienes permisos para modificar este registro.');
        }
    }

    private function urlDashboard(): string
    {
        return '/'.Auth::user()->area.'/dashboard';
    }

    /**
     * Catálogo genérico piso → valores (áreas u habitaciones) para el <select> en cascada.
     * Se preserva el orden de piso_orden porque los arrays asociativos de PHP mantienen
     * el orden de inserción. Estos catálogos casi no cambian (alguien los edita
     * directo en la BD, no hay pantalla para ello en esta app) y se consultan
     * en cada carga de /visitas/{id}/editar, así que vale la pena cachearlos
     * unos minutos en vez de volver a pegarle a la BD en cada visita editada.
     */
    private function catalogoPorPiso(string $tabla, string $columna): array
    {
        return Cache::remember("catalogo_por_piso:{$tabla}:{$columna}", 600, function () use ($tabla, $columna) {
            $porPiso = [];

            DB::table($tabla)
                ->orderBy('piso_orden')
                ->orderBy($columna)
                ->get(['piso', $columna])
                ->each(function ($fila) use (&$porPiso, $columna) {
                    $porPiso[$fila->piso][] = $fila->$columna;
                });

            return $porPiso;
        });
    }

    /**
     * Consultorio → nombres de médico, para el tercer <select> en cascada
     * (Piso → Consultorio → Médico) que solo aplica a Torre de Consultorios.
     * Mismo patrón de caché que catalogoPorPiso(): esta tabla la administra
     * un admin desde /medicos, no cambia a cada rato.
     */
    private function medicosPorConsultorio(): array
    {
        return Cache::remember('medicos_por_consultorio', 600, function () {
            $porConsultorio = [];

            DB::table('consultorios_medicos')
                ->orderBy('consultorio')
                ->orderBy('nombre_medico')
                ->get(['consultorio', 'nombre_medico'])
                ->each(function ($fila) use (&$porConsultorio) {
                    $porConsultorio[$fila->consultorio][] = $fila->nombre_medico;
                });

            return $porConsultorio;
        });
    }

    public function edit(int $id)
    {
        $visita = $this->buscarVisita($id);
        $this->autorizar($visita);

        $config = $this->cascadaPara($visita);
        $opcionesPorPiso = $config ? $this->catalogoPorPiso($config['catalogo'], $config['columna']) : [];
        $pisoActual = $config ? $visita->{$config['piso_alias']} : null;
        $medicosPorConsultorio = (int) $visita->id_edificio === 2 ? $this->medicosPorConsultorio() : [];

        return view('visitas.editar', compact('visita', 'opcionesPorPiso', 'pisoActual', 'medicosPorConsultorio'));
    }

    public function update(Request $request, int $id)
    {
        $visita = $this->buscarVisita($id);
        $this->autorizar($visita);

        $config = $this->cascadaPara($visita);
        $campoDetalle = $this->campoDetallePara($visita);

        $reglas = [
            'nombre_visitante' => ['required', 'string', 'max:150'],
            'detalle' => ['nullable', 'string', 'max:100'],
            'estado' => ['required', 'in:activa,finalizada'],
        ];

        if ($config) {
            $reglas['piso'] = ['required', 'string', 'max:30'];
            $reglas['detalle'] = [
                'required',
                'string',
                'max:100',
                Rule::exists($config['catalogo'], $config['columna'])->where('piso', $request->input('piso')),
            ];
        }

        // El médico es específico de Torre de Consultorios y es opcional: el staff no
        // siempre lo sabe al editar. Un <select> vacío llega como "" (no null), así que
        // se normaliza antes de validar para que 'nullable' sí libre el exists() de abajo.
        if ((int) $visita->id_edificio === 2) {
            $request->merge(['medico' => $request->input('medico') ?: null]);
            $reglas['medico'] = [
                'nullable',
                'string',
                'max:150',
                Rule::exists('consultorios_medicos', 'nombre_medico')->where('consultorio', $request->input('detalle')),
            ];
        }

        $validated = $request->validate($reglas);

        $tabla = $this->tablaHija((int) $visita->id_edificio, $visita->tipo_real);

        if ($config) {
            $datosHija = [
                'nombre' => $validated['nombre_visitante'],
                $config['campo_piso'] => $validated['piso'],
                $campoDetalle => $validated['detalle'],
            ];

            if ((int) $visita->id_edificio === 2) {
                $datosHija['nombre_medico'] = $validated['medico'] ?? null;
            }
        } else {
            $datosHija = [
                'nombre' => $validated['nombre_visitante'],
                $campoDetalle => $validated['detalle'],
            ];
        }

        DB::table($tabla)->where('id_visita', $id)->update($datosHija);

        $datosVisita = ['estado' => $validated['estado']];

        if ($validated['estado'] === 'finalizada' && $visita->estado !== 'finalizada') {
            // Se acaba de marcar la salida: registra el momento exacto.
            $datosVisita['fecha_salida'] = now();
            if ($tabla === 'visita_proveedor') {
                DB::table('visita_proveedor')->where('id_visita', $id)->update(['hora_salida' => now()->format('H:i:s')]);
            } elseif ($tabla === 'ex_empleados') {
                DB::table('ex_empleados')->where('id_visita', $id)->update(['fecha_salida' => now()]);
            }
        } elseif ($validated['estado'] === 'activa' && $visita->estado === 'finalizada') {
            // Se reabrió la visita: ya no tiene una salida registrada.
            $datosVisita['fecha_salida'] = null;
            if ($tabla === 'visita_proveedor') {
                DB::table('visita_proveedor')->where('id_visita', $id)->update(['hora_salida' => null]);
            } elseif ($tabla === 'ex_empleados') {
                DB::table('ex_empleados')->where('id_visita', $id)->update(['fecha_salida' => null]);
            }
        }

        DB::table('visita')->where('id_visita', $id)->update($datosVisita);

        Bitacora::registrar('visita.actualizar', "Actualizó la visita de {$validated['nombre_visitante']} (folio {$visita->folio}), estado: {$validated['estado']}.");

        return redirect($this->urlDashboard())->with('status', 'Registro actualizado correctamente.');
    }

    public function destroy(int $id)
    {
        $visita = $this->buscarVisita($id);
        $this->autorizar($visita);

        // Un registro 'sin-datos' no tiene tabla hija que borrar (es un huérfano de origen);
        // aun así se debe poder limpiar la fila de 'visita'. Torre de Consultorios siempre
        // tiene tabla hija (visita_torre) aunque 'tipo_acceso' venga nulo y tipo_real
        // termine en 'sin-datos' — por eso ese caso se checa aparte con id_edificio.
        $tieneTablaHija = (int) $visita->id_edificio === 2 || $visita->tipo_real !== 'sin-datos';
        $tabla = $tieneTablaHija ? $this->tablaHija((int) $visita->id_edificio, $visita->tipo_real) : null;

        // Log de archivo del borrado: estos datos los genera la app móvil (fuente de
        // verdad), este sistema no tiene soft-deletes ni "deshacer". Se loguea ANTES
        // de borrar, para que quede constancia de la intención aunque el borrado
        // mismo falle después.
        Log::info('Eliminando registro de visita', [
            'id_visita' => $id,
            'tipo_real' => $visita->tipo_real,
            'nombre_visitante' => $visita->nombre_visitante,
            'folio' => $visita->folio,
            'eliminado_por' => Auth::user()->id,
            'eliminado_por_nombre' => Auth::user()->name,
        ]);

        DB::transaction(function () use ($tabla, $id) {
            if ($tabla) {
                DB::table($tabla)->where('id_visita', $id)->delete();
            }
            DB::table('visita')->where('id_visita', $id)->delete();
        });

        // La bitácora (a diferencia del log de archivo de arriba) es un registro
        // visible en la app que un admin puede leer como "esto sí pasó": se escribe
        // DESPUÉS de que la transacción de borrado terminó sin errores, para no
        // dejar una entrada de "Eliminó..." si el borrado en realidad falló.
        Bitacora::registrar('visita.eliminar', "Eliminó la visita de {$visita->nombre_visitante} (folio {$visita->folio}, tipo {$visita->tipo_real}).");

        return redirect($this->urlDashboard())->with('status', 'Registro eliminado correctamente.');
    }
}
