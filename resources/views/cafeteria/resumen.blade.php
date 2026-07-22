@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="flex justify-between items-center mb-6 bg-slate-800 p-4 rounded-xl text-white shadow-md">
        <div>
            <h1 class="text-xl font-bold">Resumen de Cafetería</h1>
            <p class="text-xs text-slate-300">Conectado como: <span class="font-semibold">{{ Auth::user()->name }}</span></p>
        </div>
        <form action="/logout" method="POST">
            @csrf
            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded text-sm transition">
                Cerrar Sesión
            </button>
        </form>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="rounded-2xl border border-white/10 bg-[#081536] p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Habitaciones activas</p>
            <p class="text-2xl font-bold text-white mt-1">{{ $resumen['total_habitaciones'] }}</p>
        </div>
        <div class="rounded-2xl border border-white/10 bg-[#081536] p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Sin desayuno definido</p>
            <p class="text-2xl font-bold {{ $resumen['sin_desayuno'] > 0 ? 'text-amber-300' : 'text-white' }} mt-1">{{ $resumen['sin_desayuno'] }}</p>
        </div>
        <div class="rounded-2xl border border-white/10 bg-[#081536] p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Sin cena definida</p>
            <p class="text-2xl font-bold {{ $resumen['sin_cena'] > 0 ? 'text-amber-300' : 'text-white' }} mt-1">{{ $resumen['sin_cena'] }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        @foreach (['desayuno' => 'Desayuno', 'comida' => 'Comida', 'cena' => 'Cena', 'bebida' => 'Bebida'] as $clave => $titulo)
            <div class="rounded-2xl border border-white/10 bg-[#081536] p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-3">{{ $titulo }}</p>
                @if ($resumen[$clave]->isEmpty())
                    <p class="text-sm text-slate-500 italic">Sin datos</p>
                @else
                    <ul class="space-y-1.5">
                        @foreach ($resumen[$clave] as $platillo => $cantidad)
                            <li class="flex items-center justify-between text-sm text-slate-300">
                                <span class="truncate pr-2">{{ $platillo }}</span>
                                <span class="shrink-0 rounded-full bg-[#4978eb]/15 px-2 py-0.5 text-xs font-bold text-[#9db6f7]">{{ $cantidad }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endforeach
    </div>

    <div class="rounded-2xl border border-white/10 bg-[#081536] p-6 overflow-x-auto">
        <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-400 mb-4">Detalle por habitación</h2>

        @if ($cortesias->isEmpty())
            <p class="text-sm text-slate-500 italic">No hay habitaciones activas por ahora.</p>
        @else
            <table class="w-full text-sm text-left text-slate-300">
                <thead>
                    <tr class="text-xs uppercase tracking-wide text-slate-500 border-b border-white/10">
                        <th class="py-2 pr-4">Piso</th>
                        <th class="py-2 pr-4">Habitación</th>
                        <th class="py-2 pr-4">Desayuno</th>
                        <th class="py-2 pr-4">Comida</th>
                        <th class="py-2 pr-4">Cena</th>
                        <th class="py-2 pr-4">Bebida</th>
                        <th class="py-2 pr-4">Entregar a</th>
                        <th class="py-2 pr-4">Vigencia</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($cortesias as $c)
                        <tr class="border-b border-white/5">
                            <td class="py-2 pr-4">{{ $c->piso }}</td>
                            <td class="py-2 pr-4">{{ $c->habitacion }}</td>
                            <td class="py-2 pr-4">{{ $c->platillo_desayuno ?? '—' }}</td>
                            <td class="py-2 pr-4">{{ $c->platillo_comida ?? '—' }}</td>
                            <td class="py-2 pr-4">{{ $c->platillo_cena ?? '—' }}</td>
                            <td class="py-2 pr-4">{{ $c->bebida ?? '—' }}</td>
                            <td class="py-2 pr-4">{{ $c->entregar_a ?? '—' }}</td>
                            <td class="py-2 pr-4">
                                @if (\App\Support\CortesiaVigente::esDeHoy($c))
                                    <span class="rounded-full bg-emerald-500/15 px-2 py-0.5 text-xs font-semibold text-emerald-300">Hoy</span>
                                @else
                                    <span class="rounded-full bg-amber-500/15 px-2 py-0.5 text-xs font-semibold text-amber-300">Sin confirmar hoy</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
