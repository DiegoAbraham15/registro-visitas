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
        if (!Auth::check()) {
            return redirect('/login');
        }

        if (Auth::user()->area !== $areaRequerida) {
            abort(403, 'No tienes permisos para acceder a esta área.');
        }

        return $next($request);
    }
}