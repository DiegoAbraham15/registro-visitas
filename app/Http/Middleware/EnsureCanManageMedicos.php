<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureCanManageMedicos
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect('/login');
        }

        if (! Auth::user()->es_admin && ! Auth::user()->acceso_medicos) {
            abort(403, 'No tienes permisos para administrar los médicos.');
        }

        return $next($request);
    }
}
