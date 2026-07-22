<?php

use App\Http\Controllers\BitacoraController;
use App\Http\Controllers\CafeteriaResumenController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\ComidaController;
use App\Http\Controllers\ConsultorioMedicoController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VisitaController;
use App\Support\VisitaConsultas;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// 1. Redirección de la raíz al login de forma directa por URL
Route::get('/', function () {
    if (Auth::check()) {
        return redirect('/'.Auth::user()->area.'/dashboard');
    }

    return redirect('/login');
})->name('home');

// 2. Formulario de Login
Route::get('/login', function () {
    // Si ya hay sesión activa, no debe poder "regresar" al login y quedarse ahí:
    // se manda directo a su dashboard en vez de mostrar el formulario de nuevo.
    if (Auth::check()) {
        return redirect('/'.Auth::user()->area.'/dashboard');
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
        return redirect()->intended(Auth::user()->area.'/dashboard');
    }

    return back()->withErrors(['email' => 'Credenciales incorrectas.']);
})->middleware('throttle:login');

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/login');
})->name('logout');

// --- RUTAS PROTEGIDAS POR MIDDLEWARE ---
Route::middleware(['auth'])->group(function () {

    // 1. HOSPITAL (id_edificio = 1)
    Route::middleware(['area:hospital'])->get('/hospital/dashboard', function (Request $request) {
        $soloActivas = $request->query('estado') !== 'todas';
        $visitas = VisitaConsultas::porArea('edificio', 1, $soloActivas);
        $areaNombre = 'Hospital Central';

        return view('dashboard_area', compact('visitas', 'areaNombre', 'soloActivas'));
    });

    // 2. TORRE DE CONSULTORIOS (id_edificio = 2)
    Route::middleware(['area:consultorios'])->get('/consultorios/dashboard', function (Request $request) {
        $soloActivas = $request->query('estado') !== 'todas';
        $visitas = VisitaConsultas::porArea('edificio', 2, $soloActivas);
        $areaNombre = 'Torre de Consultorios';

        return view('dashboard_area', compact('visitas', 'areaNombre', 'soloActivas'));
    });

    // 3. CAFETERÍA (area_destino = 'CAFETERÍA')
    Route::middleware(['area:cafeteria'])->get('/cafeteria/dashboard', function (Request $request) {
        $soloActivas = $request->query('estado') !== 'todas';
        $visitas = VisitaConsultas::porArea('cafeteria', null, $soloActivas);
        $areaNombre = 'Zona de Cafetería';

        return view('dashboard_area', compact('visitas', 'areaNombre', 'soloActivas'));
    });

    // 4. VINCULACIÓN (elige desayuno/cena/bebida por habitación activa, y edita el menú semanal)
    Route::middleware(['area:vinculacion'])->group(function () {
        Route::get('/vinculacion/dashboard', [ComidaController::class, 'index']);
        Route::post('/vinculacion/habitaciones', [ComidaController::class, 'store']);
        Route::put('/vinculacion/habitaciones/comida', [ComidaController::class, 'update']);
        Route::delete('/vinculacion/habitaciones/comida', [ComidaController::class, 'destroy']);
        Route::get('/vinculacion/menus', [MenuController::class, 'edit']);
        Route::put('/vinculacion/menus', [MenuController::class, 'update']);
    });

    // Redirección de auxilio por si entran a /dashboard o /dashboard_area a secas
    Route::get('/dashboard', function () {
        return redirect('/'.Auth::user()->area.'/dashboard');
    })->name('dashboard');
    Route::get('/dashboard_area', function () {
        return redirect('/'.Auth::user()->area.'/dashboard');
    });

    // Edición y eliminación de registros de visita (la autorización por área se valida dentro del controlador)
    Route::get('/visitas/{id}/editar', [VisitaController::class, 'edit']);
    Route::put('/visitas/{id}', [VisitaController::class, 'update']);
    Route::delete('/visitas/{id}', [VisitaController::class, 'destroy']);
});

// --- GESTIÓN DE USUARIOS (solo administradores) ---
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/bitacora', [BitacoraController::class, 'index']);
    Route::get('/usuarios', [UserController::class, 'index']);
    Route::post('/usuarios', [UserController::class, 'store']);
    Route::get('/usuarios/{id}/editar', [UserController::class, 'edit']);
    Route::put('/usuarios/{id}', [UserController::class, 'update']);
    Route::delete('/usuarios/{id}', [UserController::class, 'destroy']);

    Route::get('/catalogos', [CatalogoController::class, 'index']);
    Route::post('/catalogos/habitaciones', [CatalogoController::class, 'storeHabitacion']);
    Route::put('/catalogos/habitaciones/{id}', [CatalogoController::class, 'updateHabitacion']);
    Route::delete('/catalogos/habitaciones/{id}', [CatalogoController::class, 'destroyHabitacion']);
    Route::post('/catalogos/areas', [CatalogoController::class, 'storeArea']);
    Route::put('/catalogos/areas/{id}', [CatalogoController::class, 'updateArea']);
    Route::delete('/catalogos/areas/{id}', [CatalogoController::class, 'destroyArea']);

    Route::get('/medicos', [ConsultorioMedicoController::class, 'index']);
    Route::post('/medicos', [ConsultorioMedicoController::class, 'store']);
    Route::put('/medicos/{id}', [ConsultorioMedicoController::class, 'update']);
    Route::delete('/medicos/{id}', [ConsultorioMedicoController::class, 'destroy']);
});

// --- RESUMEN DE CAFETERÍA (administradores generales o de cafetería) ---
Route::middleware(['auth', 'admin.cafeteria'])->get('/cafeteria/resumen', [CafeteriaResumenController::class, 'index']);

// --- REPORTES GRÁFICOS ---
Route::middleware(['auth'])->get('/reportes-graficos', function (Request $request) {
    $usuario = Auth::user();

    if (! $usuario->acceso_reportes) {
        abort(403, 'No tienes acceso a los reportes.');
    }

    $datos = VisitaConsultas::datosReporte($usuario, $request->query('periodo'));

    return view('reportes.graficos', $datos);
});

// --- REPORTE EN PDF (mismos datos y filtros que la vista de reportes gráficos) ---
Route::middleware(['auth'])->get('/reportes-graficos/pdf', function (Request $request) {
    $usuario = Auth::user();

    if (! $usuario->acceso_reportes) {
        abort(403, 'No tienes acceso a los reportes.');
    }

    $datos = VisitaConsultas::datosReporte($usuario, $request->query('periodo'));
    $datos['generadoPor'] = $usuario->name;
    $datos['generadoEn'] = now();

    $limiteDetallePdf = 300;
    $datos['detalleVisitasTotal'] = $datos['detalleVisitas']->count();
    $datos['detalleVisitas'] = $datos['detalleVisitas']->take($limiteDetallePdf);


    ini_set('memory_limit', '512M');

    $nombreArchivo = 'reporte-visitas-'.$datos['periodo'].'-'.now()->format('Y-m-d').'.pdf';

    return Pdf::loadView('reportes.pdf', $datos)
        ->setPaper('letter')
        ->download($nombreArchivo);
});

Route::middleware(['auth'])->get('/reportes-graficos/csv', function (Request $request) {
    $usuario = Auth::user();

    if (! $usuario->acceso_reportes) {
        abort(403, 'No tienes acceso a los reportes.');
    }

    $datos = VisitaConsultas::datosReporte($usuario, $request->query('periodo'));
    $nombreArchivo = 'reporte-visitas-'.$datos['periodo'].'-'.now()->format('Y-m-d').'.csv';

    $etiquetaTipo = fn ($t) => match ($t) {
        'sin-datos' => 'Sin datos',
        'ex_empleado' => 'Ex empleado',
        default => ucfirst($t),
    };

    $csvSeguro = function ($valor) {
        if (! is_string($valor) || $valor === '') {
            return $valor;
        }

        return preg_match('/^[=+\-@]/', $valor) ? "'".$valor : $valor;
    };

    $csvTexto = fn (string $valor) => "'".$valor;

    return response()->streamDownload(function () use ($datos, $etiquetaTipo, $csvSeguro, $csvTexto) {
        $salida = fopen('php://output', 'w');
        // BOM UTF-8 para que Excel abra los acentos correctamente.
        fwrite($salida, "\xEF\xBB\xBF");
        fputcsv($salida, ['Folio', 'Nombre', 'Tipo', 'Edificio', 'Detalle', 'Piso', 'Entrada', 'Salida', 'Estado']);

        foreach ($datos['detalleVisitas'] as $visita) {
            fputcsv($salida, [
                $csvSeguro($visita->folio ?? 'N/A'),
                $csvSeguro($visita->nombre_visitante ?? 'N/A'),
                $etiquetaTipo($visita->tipo_visitante),
                $visita->edificio,
                $csvSeguro($visita->detalle ?? 'N/A'),
                $visita->piso ?? '—',
                $visita->fecha_entrada ? $csvTexto(date('d/m/Y H:i', strtotime($visita->fecha_entrada))) : 'N/A',
                $visita->fecha_salida ? $csvTexto(date('d/m/Y H:i', strtotime($visita->fecha_salida))) : ($visita->estado === 'activa' ? 'En curso' : 'N/A'),
                $visita->estado,
            ]);
        }

        fclose($salida);
    }, $nombreArchivo, ['Content-Type' => 'text/csv']);
});
