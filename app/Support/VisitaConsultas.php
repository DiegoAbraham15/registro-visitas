<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Consultas de visitas usadas por el dashboard y por los reportes.
 *
 * Antes vivían como funciones PHP top-level en routes/web.php, lo cual hacía
 * que la suite de tests tronara con "Cannot redeclare function" (Laravel
 * vuelve a incluir routes/web.php en cada test, y PHP no permite redeclarar
 * funciones globales). Al vivir en una clase con autoload PSR-4, el archivo
 * se vuelve a cargar sin problema.
 */
class VisitaConsultas
{
    /**
     * 'visita.tipo_visitante' no es confiable (el sistema real solo guarda "visitante" ahí);
     * el tipo real se deduce por edificio y por cuál tabla hija tiene los datos de esa visita.
     * Para id_edificio=2 (Torre de Consultorios) el tipo real es 'visita_torre.tipo_acceso'
     * ('visitante', 'paciente', 'proveedor', ...) — es la única tabla hija de ese edificio,
     * así que no hay riesgo de filas huérfanas/duplicadas de otras tablas que confundir.
     */
    /**
     * $soloActivas=true (default) acota el dashboard a las visitas en curso:
     * sin esto, la consulta traía TODO el histórico del área en cada carga
     * (sin límite ni paginación), cada vez más pesada según crece la tabla.
     * No se filtra por fecha_entrada porque una visita activa puede llevar
     * mucho tiempo (p. ej. una estancia larga) y seguiría siendo relevante
     * hoy aunque haya entrado hace semanas; filtrar por estado en cambio no
     * esconde a nadie que siga en curso.
     */
    public static function porArea($tipoFiltro, $valorFiltro, $soloActivas = true)
    {
        try {
            return self::consultarPorArea($tipoFiltro, $valorFiltro, $soloActivas);
        } catch (\Throwable $e) {
            Log::error('Fallo al consultar visitas por área', [
                'tipoFiltro' => $tipoFiltro,
                'valorFiltro' => $valorFiltro,
                'soloActivas' => $soloActivas,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Une 'visita' con sus 5 tablas hija, sin arrastrar la fila de otro edificio.
     * 'id_visita' en las tablas hija es un entero libre (no autoincremental, sin
     * FK ni unique) que llena la app móvil; se ha visto en la BD real que puede
     * repetirse entre tablas por un registro huérfano o una carrera al guardar
     * (p. ej. una visita_familiar con id_visita=1 mientras la visita real #1 es
     * de Torre de Consultorios). Sin este candado en el JOIN, esa fila huérfana
     * se colaba como si fuera el detalle de la visita del otro edificio: por
     * eso el piso/detalle no se lee con COALESCE a secas en ningún lado, sino
     * siempre detrás de un CASE que prioriza id_edificio — pero encadenarlo acá
     * evita que la fila huérfana aparezca del todo (p. ej. en los desgloses por
     * piso de los reportes, que si no quedaban con un "Familiares" fantasma
     * dentro del reporte de Torre).
     */
    public static function unirTablasHija($query)
    {
        return $query
            ->leftJoin('visita_familiar', function ($join) {
                $join->on('visita.id_visita', '=', 'visita_familiar.id_visita')->where('visita.id_edificio', 1);
            })
            ->leftJoin('visita_proveedor', function ($join) {
                $join->on('visita.id_visita', '=', 'visita_proveedor.id_visita')->where('visita.id_edificio', 1);
            })
            ->leftJoin('visita_postulante', function ($join) {
                $join->on('visita.id_visita', '=', 'visita_postulante.id_visita')->where('visita.id_edificio', 1);
            })
            ->leftJoin('visita_torre', function ($join) {
                $join->on('visita.id_visita', '=', 'visita_torre.id_visita')->where('visita.id_edificio', 2);
            })
            ->leftJoin('ex_empleados', function ($join) {
                $join->on('visita.id_visita', '=', 'ex_empleados.id_visita')->where('visita.id_edificio', 1);
            });
    }

    private static function consultarPorArea($tipoFiltro, $valorFiltro, $soloActivas)
    {
        $query = self::unirTablasHija(DB::table('visita'))
            ->select(
                'visita.id_visita', 'visita.fecha_entrada', 'visita.fecha_salida', 'visita.estado', 'visita.id_edificio',
                DB::raw("
                    CASE
                        WHEN visita.id_edificio = 2 THEN COALESCE(visita_torre.tipo_acceso, 'sin-datos')
                        WHEN visita_postulante.puesto IS NOT NULL THEN 'postulante'
                        WHEN visita_proveedor.area_destino IS NOT NULL THEN 'proveedor'
                        WHEN visita_familiar.habitacion IS NOT NULL THEN 'familiar'
                        WHEN ex_empleados.nombre IS NOT NULL THEN 'ex_empleado'
                        ELSE 'sin-datos'
                    END AS tipo_real
                "),
                DB::raw('CASE WHEN visita.id_edificio = 2 THEN visita_torre.nombre ELSE COALESCE(visita_familiar.nombre, visita_proveedor.nombre, visita_postulante.nombre, ex_empleados.nombre) END AS nombre_visitante'),
                DB::raw('CASE WHEN visita.id_edificio = 2 THEN visita_torre.folio ELSE COALESCE(visita_familiar.folio, visita_proveedor.folio, visita_postulante.folio, ex_empleados.folio) END AS folio'),
                DB::raw('CASE WHEN visita.id_edificio = 2 THEN visita_torre.foto_persona ELSE COALESCE(visita_familiar.foto_persona, visita_proveedor.foto_persona, visita_postulante.foto_persona, ex_empleados.foto_persona) END AS foto_persona'),
                DB::raw('COALESCE(visita_familiar.foto_ine, visita_proveedor.foto_ine, visita_postulante.foto_ine, ex_empleados.foto_ine) AS foto_ine'),
                DB::raw('CASE WHEN visita.id_edificio = 2 THEN visita_torre.piso ELSE COALESCE(visita_proveedor.piso_destino, visita_familiar.piso) END AS piso_general'),
                DB::raw('CASE WHEN visita.id_edificio = 2 THEN visita_torre.consultorio ELSE COALESCE(visita_postulante.puesto, visita_proveedor.area_destino, visita_familiar.habitacion, ex_empleados.motivo) END AS detalle'),
                'visita_proveedor.area_destino',
                // Campos propios de cada tipo, capturados por la app móvil, que no caben en la
                // tarjeta resumida y se muestran en el detalle ("Ver más").
                'visita_familiar.parentesco',
                'visita_familiar.nombre_paciente',
                'visita_proveedor.empresa_representada',
                'visita_proveedor.motivo_visita',
                'visita_proveedor.hora_entrada',
                'visita_proveedor.hora_salida',
                'visita_proveedor.fecha as fecha_proveedor',
                DB::raw('visita_postulante.area_destino AS area_destino_postulante'),
                'visita_postulante.responsable_rh',
                'visita_postulante.tipo_cita',
                'visita_postulante.cv_entregado',
                'visita_torre.tipo_acceso',
                'visita_torre.nombre_medico as medico',
                'ex_empleados.motivo as motivo_ex_empleado'
            );

        if ($tipoFiltro === 'edificio') {
            $query->where('visita.id_edificio', $valorFiltro);
        } elseif ($tipoFiltro === 'cafeteria') {
            $query->where('visita_proveedor.area_destino', '=', 'CAFETERÍA');
        }

        if ($soloActivas) {
            $query->where('visita.estado', 'activa');
        }

        return $query->orderBy('visita.fecha_entrada', 'desc')->get();
    }

    /**
     * Arma los datos del reporte (usados tanto por la vista en pantalla como por el PDF).
     * 'visita.tipo_visitante' no es confiable (ver nota en porArea()); el tipo real
     * se deduce por edificio y por cuál tabla hija tiene los datos de esa visita.
     */
    public static function datosReporte($usuario, $periodoSolicitado)
    {
        try {
            return self::consultarDatosReporte($usuario, $periodoSolicitado);
        } catch (\Throwable $e) {
            Log::error('Fallo al armar los datos del reporte de visitas', [
                'usuario_id' => $usuario->id ?? null,
                'periodoSolicitado' => $periodoSolicitado,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private static function consultarDatosReporte($usuario, $periodoSolicitado)
    {
        // Periodo del reporte: día, semana, mes o todo el historial.
        $periodo = in_array($periodoSolicitado, ['dia', 'semana', 'mes', 'todo'], true)
            ? $periodoSolicitado
            : 'mes';

        $rangoFechas = match ($periodo) {
            'dia' => [now()->startOfDay(), now()->endOfDay()],
            'semana' => [now()->startOfWeek(), now()->endOfWeek()],
            'mes' => [now()->startOfMonth(), now()->endOfMonth()],
            default => null, // 'todo' = sin filtro de fecha
        };

        $filtrarPeriodo = function ($query) use ($rangoFechas) {
            if ($rangoFechas) {
                $query->whereBetween('visita.fecha_entrada', $rangoFechas);
            }
        };

        // Los administradores ven el consolidado de todas las áreas;
        // el resto solo ve las visitas de su propia área asignada.
        // $conJoinProveedor debe ser false cuando la consulta no une 'visita_proveedor'
        // (p. ej. el desglose de familiares), para no referenciar una tabla ausente.
        $filtrarPorArea = function ($query, $conJoinProveedor = true) use ($usuario) {
            if ($usuario->es_admin) {
                return;
            }

            if ($usuario->area === 'hospital') {
                $query->where('visita.id_edificio', 1);
            } elseif ($usuario->area === 'consultorios') {
                $query->where('visita.id_edificio', 2);
            } elseif ($usuario->area === 'cafeteria') {
                // El personal de cafetería solo gestiona visitas de proveedor; nunca ve familiares.
                $conJoinProveedor
                    ? $query->where('visita_proveedor.area_destino', '=', 'CAFETERÍA')
                    : $query->whereRaw('1 = 0');
            } else {
                // Áreas sin visitas propias en este reporte (p. ej. 'vinculacion', que solo
                // gestiona comida) no deben caer en "sin filtro" = ver el consolidado completo.
                $query->whereRaw('1 = 0');
            }
        };

        $porEdificioQuery = DB::table('visita')
            ->leftJoin('visita_proveedor', function ($join) {
                $join->on('visita.id_visita', '=', 'visita_proveedor.id_visita')->where('visita.id_edificio', 1);
            })
            ->select(
                DB::raw("CASE WHEN visita.id_edificio = 1 THEN 'Hospital' ELSE 'Torre de Consultorios' END as edificio"),
                DB::raw('COUNT(DISTINCT visita.id_visita) as total')
            )
            ->groupBy('visita.id_edificio');
        $filtrarPorArea($porEdificioQuery);
        $filtrarPeriodo($porEdificioQuery);
        $porEdificio = $porEdificioQuery->get();

        // 'visita.tipo_visitante' no es confiable (ver nota en porArea());
        // el tipo real se deduce por edificio y por cuál tabla hija tiene los datos.
        // El servidor real corre con sql_mode=only_full_group_by, así que hay que agrupar
        // por la expresión CASE completa (no por su alias) para que MySQL la acepte.
        $expresionTipoReal = "
            CASE
                WHEN visita.id_edificio = 2 THEN COALESCE(visita_torre.tipo_acceso, 'sin-datos')
                WHEN visita_postulante.puesto IS NOT NULL THEN 'postulante'
                WHEN visita_proveedor.area_destino IS NOT NULL THEN 'proveedor'
                WHEN visita_familiar.habitacion IS NOT NULL THEN 'familiar'
                WHEN ex_empleados.nombre IS NOT NULL THEN 'ex_empleado'
                ELSE 'sin-datos'
            END
        ";

        $porTipoQuery = self::unirTablasHija(DB::table('visita'))
            ->select(
                DB::raw($expresionTipoReal.' AS tipo_visitante'),
                DB::raw('COUNT(DISTINCT visita.id_visita) as total')
            )
            ->groupByRaw($expresionTipoReal);
        $filtrarPorArea($porTipoQuery);
        $filtrarPeriodo($porTipoQuery);
        $porTipo = $porTipoQuery->get();

        $ordenPisos = array_flip(['Sótano', 'Planta Baja', 'Piso 1', 'Piso 2', 'Piso 3', 'Piso 4']);
        $ordenarPorPiso = fn ($coleccion) => $coleccion->sortBy(fn ($fila) => $ordenPisos[$fila->piso] ?? 99)->values();

        // 'where id_edificio' además del join: visita_proveedor/familiar/torre no tienen
        // FK ni unique en 'id_visita' (lo llena la app móvil) — se ha visto en la BD real
        // una fila huérfana de un edificio compartiendo id_visita con una visita real de
        // otro edificio. Sin este candado, ese fantasma se colaba en el desglose por piso
        // del edificio equivocado (p. ej. un piso de "Familiares" fantasma en Torre).
        $porPisoProveedorQuery = DB::table('visita')
            ->join('visita_proveedor', 'visita.id_visita', '=', 'visita_proveedor.id_visita')
            ->where('visita.id_edificio', 1)
            ->whereNotNull('visita_proveedor.piso_destino')
            ->select('visita_proveedor.piso_destino as piso', DB::raw('COUNT(DISTINCT visita.id_visita) as total'))
            ->groupBy('visita_proveedor.piso_destino');
        $filtrarPorArea($porPisoProveedorQuery);
        $filtrarPeriodo($porPisoProveedorQuery);
        $porPisoProveedor = $ordenarPorPiso($porPisoProveedorQuery->get());

        $porPisoFamiliarQuery = DB::table('visita')
            ->join('visita_familiar', 'visita.id_visita', '=', 'visita_familiar.id_visita')
            ->where('visita.id_edificio', 1)
            ->whereNotNull('visita_familiar.piso')
            ->select('visita_familiar.piso as piso', DB::raw('COUNT(DISTINCT visita.id_visita) as total'))
            ->groupBy('visita_familiar.piso');
        $filtrarPorArea($porPisoFamiliarQuery, false);
        $filtrarPeriodo($porPisoFamiliarQuery);
        $porPisoFamiliar = $ordenarPorPiso($porPisoFamiliarQuery->get());

        $porPisoTorreQuery = DB::table('visita')
            ->join('visita_torre', 'visita.id_visita', '=', 'visita_torre.id_visita')
            ->where('visita.id_edificio', 2)
            ->whereNotNull('visita_torre.piso')
            ->select('visita_torre.piso as piso', DB::raw('COUNT(DISTINCT visita.id_visita) as total'))
            ->groupBy('visita_torre.piso');
        $filtrarPorArea($porPisoTorreQuery, false);
        $filtrarPeriodo($porPisoTorreQuery);
        $porPisoTorre = $ordenarPorPiso($porPisoTorreQuery->get());

        // Top 10 de consultorios y médicos con más visitas de Torre de Consultorios.
        // Un mismo consultorio puede compartirse entre varios médicos (ver /medicos),
        // pero 'medico' aquí es el que el staff capturó para esa visita puntual al
        // editarla (VisitaController::update()), no todo el catálogo del consultorio —
        // por eso el conteo de médicos es exacto y no cuenta una visita para médicos
        // que comparten consultorio pero no fueron el que la atendió.
        $consultoriosMasVisitadosQuery = DB::table('visita')
            ->join('visita_torre', 'visita.id_visita', '=', 'visita_torre.id_visita')
            ->where('visita.id_edificio', 2)
            ->whereNotNull('visita_torre.consultorio')
            ->select('visita_torre.consultorio', DB::raw('COUNT(DISTINCT visita.id_visita) as total'))
            ->groupBy('visita_torre.consultorio')
            ->orderByDesc('total')
            ->limit(10);
        $filtrarPorArea($consultoriosMasVisitadosQuery, false);
        $filtrarPeriodo($consultoriosMasVisitadosQuery);
        $consultoriosMasVisitados = $consultoriosMasVisitadosQuery->get();

        $doctoresMasVisitadosQuery = DB::table('visita')
            ->join('visita_torre', 'visita.id_visita', '=', 'visita_torre.id_visita')
            ->where('visita.id_edificio', 2)
            ->whereNotNull('visita_torre.nombre_medico')
            ->select('visita_torre.nombre_medico as medico', DB::raw('COUNT(DISTINCT visita.id_visita) as total'))
            ->groupBy('visita_torre.nombre_medico')
            ->orderByDesc('total')
            ->limit(10);
        $filtrarPorArea($doctoresMasVisitadosQuery, false);
        $filtrarPeriodo($doctoresMasVisitadosQuery);
        $doctoresMasVisitados = $doctoresMasVisitadosQuery->get();

        // Listado detallado de cada visita del periodo (no solo los agregados de arriba),
        // para la tabla de detalle en pantalla y para el reporte en PDF.
        $detalleVisitasQuery = self::unirTablasHija(DB::table('visita'))
            ->select(
                'visita.id_visita', 'visita.fecha_entrada', 'visita.fecha_salida', 'visita.estado',
                DB::raw("CASE WHEN visita.id_edificio = 1 THEN 'Hospital' ELSE 'Torre de Consultorios' END AS edificio"),
                DB::raw($expresionTipoReal.' AS tipo_visitante'),
                DB::raw('CASE WHEN visita.id_edificio = 2 THEN visita_torre.nombre ELSE COALESCE(visita_familiar.nombre, visita_proveedor.nombre, visita_postulante.nombre, ex_empleados.nombre) END AS nombre_visitante'),
                DB::raw('CASE WHEN visita.id_edificio = 2 THEN visita_torre.folio ELSE COALESCE(visita_familiar.folio, visita_proveedor.folio, visita_postulante.folio, ex_empleados.folio) END AS folio'),
                DB::raw('CASE WHEN visita.id_edificio = 2 THEN visita_torre.consultorio ELSE COALESCE(visita_postulante.puesto, visita_proveedor.area_destino, visita_familiar.habitacion, ex_empleados.motivo) END AS detalle'),
                DB::raw('CASE WHEN visita.id_edificio = 2 THEN visita_torre.piso ELSE COALESCE(visita_proveedor.piso_destino, visita_familiar.piso) END AS piso'),
                DB::raw('CASE WHEN visita.id_edificio = 2 THEN visita_torre.foto_persona ELSE COALESCE(visita_familiar.foto_persona, visita_proveedor.foto_persona, visita_postulante.foto_persona, ex_empleados.foto_persona) END AS foto_persona'),
                'visita_torre.nombre_medico as medico'
            );
        $filtrarPorArea($detalleVisitasQuery);
        $filtrarPeriodo($detalleVisitasQuery);
        $detalleVisitas = $detalleVisitasQuery->orderBy('visita.fecha_entrada', 'desc')->get();

        $etiquetasPeriodo = [
            'dia' => 'Hoy',
            'semana' => 'Esta semana',
            'mes' => 'Este mes',
            'todo' => 'Todo el historial',
        ];

        $areaNombre = $usuario->es_admin ? 'Todas las áreas' : match ($usuario->area) {
            'hospital' => 'Hospital Central',
            'consultorios' => 'Torre de Consultorios',
            'cafeteria' => 'Zona de Cafetería',
            default => $usuario->area,
        };

        return compact(
            'periodo', 'etiquetasPeriodo', 'areaNombre',
            'porEdificio', 'porTipo', 'porPisoProveedor', 'porPisoFamiliar', 'porPisoTorre',
            'consultoriosMasVisitados', 'doctoresMasVisitados',
            'detalleVisitas'
        );
    }
}
