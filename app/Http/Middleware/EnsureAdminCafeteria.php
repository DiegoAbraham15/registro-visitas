<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminCafeteria
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect('/login');
        }

        if (! Auth::user()->es_admin && ! Auth::user()->es_admin_cafeteria) {
            abort(403, 'No tienes permisos de administrador de cafetería.');
        }

        return $next($request);
    }
}
