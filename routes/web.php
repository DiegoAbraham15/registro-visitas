<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

// 1. Redirección de la raíz al login de forma directa por URL
Route::get('/', function () {
    if (Auth::check()) {
        return redirect('/' . Auth::user()->area . '/dashboard');
    }

    return redirect('/login');
});

// 2. Formulario de Login
Route::get('/login', function () {
    // Si ya hay sesión activa, no debe poder "regresar" al login y quedarse ahí:
    // se manda directo a su dashboard en vez de mostrar el formulario de nuevo.
    if (Auth::check()) {
        return redirect('/' . Auth::user()->area . '/dashboard');
    }

    return view('auth.login');
})->name('login');

// 3. Procesar el formulario de Login (Redirección fija a su segmento correspondiente)
Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    if (Auth::attempt($credentials)) {
        $request->session()->regenerate();
        // Esto mandará a /hospital/dashboard, /consultorios/dashboard o /cafeteria/dashboard
        return redirect()->intended(Auth::user()->area . '/dashboard');
    }

    return back()->withErrors(['email' => 'Credenciales incorrectas.']);
});

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/login');
});

// --- FUNCIÓN HELPER PARA OBTENER LAS VISITAS SEGÚN FILTROS ---
// 'visita.tipo_visitante' no es confiable (el sistema real solo guarda "visitante" ahí);
// el tipo real se deduce por edificio y por cuál tabla hija tiene los datos de esa visita.
// Todo lo que es id_edificio=2 se trata como 'rep-medico' SIN mirar las demás tablas, para
// blindarnos de filas huérfanas/duplicadas que puedan quedar por error en otra tabla hija.
function obtenerVisitasPorArea($tipoFiltro, $valorFiltro) {
    $query = DB::table('visita')
        ->leftJoin('visita_familiar', 'visita.id_visita', '=', 'visita_familiar.id_visita')
        ->leftJoin('visita_proveedor', 'visita.id_visita', '=', 'visita_proveedor.id_visita')
        ->leftJoin('visita_postulante', 'visita.id_visita', '=', 'visita_postulante.id_visita')
        ->leftJoin('visita_torre', 'visita.id_visita', '=', 'visita_torre.id_visita')
        ->select(
            'visita.id_visita', 'visita.fecha_entrada', 'visita.fecha_salida', 'visita.estado', 'visita.id_edificio',
            DB::raw("
                CASE
                    WHEN visita.id_edificio = 2 THEN 'rep-medico'
                    WHEN visita_postulante.puesto IS NOT NULL THEN 'postulante'
                    WHEN visita_proveedor.area_destino IS NOT NULL THEN 'proveedor'
                    WHEN visita_familiar.habitacion IS NOT NULL THEN 'familiar'
                    ELSE 'sin-datos'
                END AS tipo_real
            "),
            DB::raw("CASE WHEN visita.id_edificio = 2 THEN visita_torre.nombre ELSE COALESCE(visita_familiar.nombre, visita_proveedor.nombre, visita_postulante.nombre) END AS nombre_visitante"),
            DB::raw("CASE WHEN visita.id_edificio = 2 THEN visita_torre.folio ELSE COALESCE(visita_familiar.folio, visita_proveedor.folio, visita_postulante.folio) END AS folio"),
            DB::raw("CASE WHEN visita.id_edificio = 2 THEN visita_torre.foto_persona ELSE COALESCE(visita_familiar.foto_persona, visita_proveedor.foto_persona, visita_postulante.foto_persona) END AS foto_persona"),
            DB::raw("CASE WHEN visita.id_edificio = 2 THEN visita_torre.piso ELSE COALESCE(visita_proveedor.piso_destino, visita_familiar.piso) END AS piso_general"),
            DB::raw("CASE WHEN visita.id_edificio = 2 THEN visita_torre.consultorio ELSE COALESCE(visita_postulante.puesto, visita_proveedor.area_destino, visita_familiar.habitacion) END AS detalle"),
            'visita_proveedor.area_destino'
        );

    if ($tipoFiltro === 'edificio') {
        $query->where('visita.id_edificio', $valorFiltro);
    } elseif ($tipoFiltro === 'cafeteria') {
        $query->where('visita_proveedor.area_destino', '=', 'CAFETERÍA');
    }

    return $query->orderBy('visita.fecha_entrada', 'desc')->get();
}

// --- RUTAS PROTEGIDAS POR MIDDLEWARE ---
Route::middleware(['auth'])->group(function () {

    // 1. HOSPITAL (id_edificio = 1)
    Route::middleware(['area:hospital'])->get('/hospital/dashboard', function () {
        $visitas = obtenerVisitasPorArea('edificio', 1);
        $areaNombre = "Hospital Central";
        return view('dashboard_area', compact('visitas', 'areaNombre'));
    });

    // 2. TORRE DE CONSULTORIOS (id_edificio = 2)
    Route::middleware(['area:consultorios'])->get('/consultorios/dashboard', function () {
        $visitas = obtenerVisitasPorArea('edificio', 2);
        $areaNombre = "Torre de Consultorios";
        return view('dashboard_area', compact('visitas', 'areaNombre'));
    });

    // 3. CAFETERÍA (area_destino = 'CAFETERÍA')
    Route::middleware(['area:cafeteria'])->get('/cafeteria/dashboard', function () {
        $visitas = obtenerVisitasPorArea('cafeteria', null);
        $areaNombre = "Zona de Cafetería";
        return view('dashboard_area', compact('visitas', 'areaNombre'));
    });

    // Redirección de auxilio por si entran a /dashboard o /dashboard_area a secas
    Route::get('/dashboard', function () {
        return redirect('/' . Auth::user()->area . '/dashboard');
    });
    Route::get('/dashboard_area', function () {
        return redirect('/' . Auth::user()->area . '/dashboard');
    });

    // Edición y eliminación de registros de visita (la autorización por área se valida dentro del controlador)
    Route::get('/visitas/{id}/editar', [App\Http\Controllers\VisitaController::class, 'edit']);
    Route::put('/visitas/{id}', [App\Http\Controllers\VisitaController::class, 'update']);
    Route::delete('/visitas/{id}', [App\Http\Controllers\VisitaController::class, 'destroy']);
});

// --- GESTIÓN DE USUARIOS (solo administradores) ---
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/usuarios', [App\Http\Controllers\UserController::class, 'index']);
    Route::post('/usuarios', [App\Http\Controllers\UserController::class, 'store']);
    Route::get('/usuarios/{id}/editar', [App\Http\Controllers\UserController::class, 'edit']);
    Route::put('/usuarios/{id}', [App\Http\Controllers\UserController::class, 'update']);
    Route::delete('/usuarios/{id}', [App\Http\Controllers\UserController::class, 'destroy']);
});

// --- REPORTES GRÁFICOS ---
Route::middleware(['auth'])->get('/reportes-graficos', function (Request $request) {
    $usuario = Auth::user();

    if (!$usuario->acceso_reportes) {
        abort(403, 'No tienes acceso a los reportes.');
    }

    // Periodo del reporte: día, semana, mes o todo el historial.
    $periodo = in_array($request->query('periodo'), ['dia', 'semana', 'mes', 'todo'], true)
        ? $request->query('periodo')
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
        }
    };

    $porEdificioQuery = DB::table('visita')
        ->leftJoin('visita_proveedor', 'visita.id_visita', '=', 'visita_proveedor.id_visita')
        ->select(
            DB::raw("CASE WHEN visita.id_edificio = 1 THEN 'Hospital' ELSE 'Torre de Consultorios' END as edificio"),
            DB::raw("COUNT(DISTINCT visita.id_visita) as total")
        )
        ->groupBy('visita.id_edificio');
    $filtrarPorArea($porEdificioQuery);
    $filtrarPeriodo($porEdificioQuery);
    $porEdificio = $porEdificioQuery->get();

    // 'visita.tipo_visitante' no es confiable (ver nota en obtenerVisitasPorArea);
    // el tipo real se deduce por edificio y por cuál tabla hija tiene los datos.
    // El servidor real corre con sql_mode=only_full_group_by, así que hay que agrupar
    // por la expresión CASE completa (no por su alias) para que MySQL la acepte.
    $expresionTipoReal = "
        CASE
            WHEN visita.id_edificio = 2 THEN 'rep-medico'
            WHEN visita_postulante.puesto IS NOT NULL THEN 'postulante'
            WHEN visita_proveedor.area_destino IS NOT NULL THEN 'proveedor'
            WHEN visita_familiar.habitacion IS NOT NULL THEN 'familiar'
            ELSE 'sin-datos'
        END
    ";

    $porTipoQuery = DB::table('visita')
        ->leftJoin('visita_proveedor', 'visita.id_visita', '=', 'visita_proveedor.id_visita')
        ->leftJoin('visita_postulante', 'visita.id_visita', '=', 'visita_postulante.id_visita')
        ->leftJoin('visita_familiar', 'visita.id_visita', '=', 'visita_familiar.id_visita')
        ->select(
            DB::raw($expresionTipoReal . ' AS tipo_visitante'),
            DB::raw('COUNT(DISTINCT visita.id_visita) as total')
        )
        ->groupByRaw($expresionTipoReal);
    $filtrarPorArea($porTipoQuery);
    $filtrarPeriodo($porTipoQuery);
    $porTipo = $porTipoQuery->get();

    $ordenPisos = array_flip(['Sótano', 'Planta Baja', 'Piso 1', 'Piso 2', 'Piso 3', 'Piso 4']);
    $ordenarPorPiso = fn ($coleccion) => $coleccion->sortBy(fn ($fila) => $ordenPisos[$fila->piso] ?? 99)->values();

    $porPisoProveedorQuery = DB::table('visita')
        ->join('visita_proveedor', 'visita.id_visita', '=', 'visita_proveedor.id_visita')
        ->whereNotNull('visita_proveedor.piso_destino')
        ->select('visita_proveedor.piso_destino as piso', DB::raw('COUNT(DISTINCT visita.id_visita) as total'))
        ->groupBy('visita_proveedor.piso_destino');
    $filtrarPorArea($porPisoProveedorQuery);
    $filtrarPeriodo($porPisoProveedorQuery);
    $porPisoProveedor = $ordenarPorPiso($porPisoProveedorQuery->get());

    $porPisoFamiliarQuery = DB::table('visita')
        ->join('visita_familiar', 'visita.id_visita', '=', 'visita_familiar.id_visita')
        ->whereNotNull('visita_familiar.piso')
        ->select('visita_familiar.piso as piso', DB::raw('COUNT(DISTINCT visita.id_visita) as total'))
        ->groupBy('visita_familiar.piso');
    $filtrarPorArea($porPisoFamiliarQuery, false);
    $filtrarPeriodo($porPisoFamiliarQuery);
    $porPisoFamiliar = $ordenarPorPiso($porPisoFamiliarQuery->get());

    $porPisoTorreQuery = DB::table('visita')
        ->join('visita_torre', 'visita.id_visita', '=', 'visita_torre.id_visita')
        ->whereNotNull('visita_torre.piso')
        ->select('visita_torre.piso as piso', DB::raw('COUNT(DISTINCT visita.id_visita) as total'))
        ->groupBy('visita_torre.piso');
    $filtrarPorArea($porPisoTorreQuery, false);
    $filtrarPeriodo($porPisoTorreQuery);
    $porPisoTorre = $ordenarPorPiso($porPisoTorreQuery->get());

    return view('reportes.graficos', compact('porEdificio', 'porTipo', 'porPisoProveedor', 'porPisoFamiliar', 'porPisoTorre', 'periodo'));
});