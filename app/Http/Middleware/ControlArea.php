<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ControlArea
{
    public function handle(Request $request, Closure $next, string $areaRequerida): Response
    {
        if (! Auth::check()) {
            return redirect('/login');
        }

        // Los administradores ven el consolidado de todas las áreas (igual que en
        // VisitaController::autorizar() y en VisitaConsultas::datosReporte()).
        if (Auth::user()->es_admin) {
            return $next($request);
        }

        // 'acceso_vinculacion' es un permiso aparte del área asignada: deja
        // entrar a /vinculacion/* aunque el área principal del usuario sea otra.
        if ($areaRequerida === 'vinculacion' && Auth::user()->acceso_vinculacion) {
            return $next($request);
        }

        if (Auth::user()->area !== $areaRequerida) {
            abort(403, 'No tienes permisos para acceder a esta área.');
        }

        return $next($request);
    }
}
