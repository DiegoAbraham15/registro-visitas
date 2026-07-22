<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;

class BitacoraController extends Controller
{
    public function index()
    {
        $bitacora = Bitacora::with('usuario')->orderByDesc('created_at')->paginate(30);

        return view('bitacora.index', compact('bitacora'));
    }
}
