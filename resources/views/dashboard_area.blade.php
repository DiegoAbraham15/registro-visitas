@extends('layouts.app')

@section('content')
@php
    $detalleLabel = [
        'familiar' => 'Habitación',
        'proveedor' => 'Destino',
        'postulante' => 'Puesto',
        'rep-medico' => 'Consultorio',
        'sin-datos' => 'Detalle',
    ];
    $tipoEstilo = [
        'familiar' => [
            'label' => 'Familiar',
            'bg' => 'bg-[#4978eb]/15', 'text' => 'text-[#9db6f7]',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.099 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />',
        ],
        'proveedor' => [
            'label' => 'Proveedor',
            'bg' => 'bg-[#9085e9]/15', 'text' => 'text-[#c9c3f7]',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 8.25h16.5v10.5a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5V8.25Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 8.25V6a1.5 1.5 0 0 1 1.5-1.5h4.5A1.5 1.5 0 0 1 15.75 6v2.25" /><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12.75h16.5" />',
        ],
        'postulante' => [
            'label' => 'Postulante',
            'bg' => 'bg-[#d95926]/15', 'text' => 'text-[#f4a679]',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M6 20.25h12a1.5 1.5 0 0 0 1.5-1.5V5.25a1.5 1.5 0 0 0-1.5-1.5H6a1.5 1.5 0 0 0-1.5 1.5v13.5A1.5 1.5 0 0 0 6 20.25Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M9 7.5h6M9 12.75h6M9 15.75h3.75" />',
        ],
        'rep-medico' => [
            'label' => 'Rep. Médico',
            'bg' => 'bg-[#c98500]/15', 'text' => 'text-[#e8bd6b]',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8.25v7.5m-3.75-3.75h7.5" /><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 6.75A2.25 2.25 0 0 1 6.75 4.5h10.5a2.25 2.25 0 0 1 2.25 2.25v10.5a2.25 2.25 0 0 1-2.25 2.25H6.75a2.25 2.25 0 0 1-2.25-2.25V6.75Z" />',
        ],
        'sin-datos' => [
            'label' => 'Sin datos',
            'bg' => 'bg-white/5', 'text' => 'text-slate-400',
            'icon' => null,
        ],
    ];
    $ordenPisos = array_flip(['Sótano', 'Planta Baja', 'Piso 1', 'Piso 2', 'Piso 3', 'Piso 4']);
    $pisos = $visitas->pluck('piso_general')->filter()->unique()->sortBy(fn ($p) => $ordenPisos[$p] ?? 99)->values();
@endphp

<div class="max-w-7xl mx-auto">
    <div class="flex justify-between items-center mb-6 bg-slate-800 p-4 rounded-xl text-white shadow-md">
        <div>
            <h1 class="text-xl font-bold"> Panel de Control: {{ $areaNombre }}</h1>
            <p class="text-xs text-slate-300">Conectado como: <span class="font-semibold">{{ Auth::user()->name }}</span></p>
        </div>

        <form action="/logout" method="POST">
            @csrf
            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded text-sm transition">
                 Cerrar Sesión
            </button>
        </form>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-2xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-300">
            {{ $errors->first() }}
        </div>
    @endif

    @if ($visitas->isEmpty())
        <div class="rounded-2xl border border-white/10 bg-[#081536] px-6 py-10 text-center text-slate-400">
            No hay visitas registradas para esta área asignada.
        </div>
    @else
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <div class="relative">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"
                     class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input type="text" id="buscador-nombre" placeholder="Buscar por nombre..."
                       class="w-64 rounded-xl border border-white/10 bg-white/5 py-2 pl-9 pr-4 text-sm text-white placeholder-slate-500 focus:border-[#4978eb] focus:outline-none focus:ring-2 focus:ring-[#4978eb]/20" />
            </div>

            @if ($pisos->isNotEmpty())
                <div class="flex items-center gap-3">
                    <label for="filtro-piso" class="text-xs font-semibold uppercase tracking-wide text-slate-400">Filtrar por piso</label>
                    <select id="filtro-piso"
                            class="rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm text-white focus:border-[#4978eb] focus:outline-none focus:ring-2 focus:ring-[#4978eb]/20">
                        <option value="">Todos los pisos</option>
                        @foreach ($pisos as $piso)
                            <option value="{{ $piso }}">{{ $piso }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>

        <p id="sin-resultados" class="hidden rounded-2xl border border-white/10 bg-[#081536] px-6 py-10 text-center text-slate-400">
            Ninguna visita coincide con la búsqueda.
        </p>

        <div id="grid-visitas" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($visitas as $v)
                <div class="tarjeta-visita rounded-2xl border border-white/10 bg-[#081536] p-5 flex flex-col gap-4"
                     data-piso="{{ $v->piso_general }}" data-nombre="{{ Str::lower($v->nombre_visitante) }}">
                    <div class="flex items-start gap-3">
                        @if (!empty($v->foto_persona) && !str_starts_with($v->foto_persona, 'blob:'))
                            <img class="h-12 w-12 rounded-full object-cover border-2 border-white/10 shadow-sm cursor-pointer hover:scale-110 transition shrink-0"
                                 src="{{ rtrim(config('app.mobile_uploads_url'), '/') . '/' . ltrim($v->foto_persona, '/') }}"
                                 alt="Foto de {{ $v->nombre_visitante }}"
                                 onclick="window.open(this.src, '_blank')">
                        @else
                            <div class="h-12 w-12 shrink-0 rounded-full bg-white/10 flex items-center justify-center text-white font-bold text-xs uppercase tracking-wider">
                                {{ substr($v->nombre_visitante ?? 'V', 0, 2) }}
                            </div>
                        @endif
                        <div class="min-w-0">
                            <p class="font-semibold text-white truncate">{{ $v->nombre_visitante }}</p>
                            <p class="text-xs font-mono font-bold text-[#9db6f7]">{{ $v->folio ?? 'N/A' }}</p>
                        </div>
                    </div>

                    @php $estilo = $tipoEstilo[$v->tipo_real] ?? null; @endphp
                    <div class="flex flex-wrap gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold {{ $estilo['bg'] ?? 'bg-white/5' }} {{ $estilo['text'] ?? 'text-slate-300' }}">
                            @if ($estilo['icon'] ?? null)
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-3.5 w-3.5">
                                    {!! $estilo['icon'] !!}
                                </svg>
                            @endif
                            {{ $estilo['label'] ?? ucfirst($v->tipo_real) }}
                        </span>
                        <span class="rounded-full px-3 py-1 text-xs font-bold {{ $v->estado === 'activa' ? 'bg-emerald-500/15 text-emerald-300' : 'bg-white/5 text-slate-400' }}">
                            {{ $v->estado }}
                        </span>
                    </div>

                    <div class="text-sm text-slate-300 space-y-1">
                        <p>
                            <span class="text-slate-500">{{ $detalleLabel[$v->tipo_real] ?? 'Detalle' }}:</span>
                            {{ $v->detalle ?? 'N/A' }}
                            @if ($v->piso_general)
                                <span class="text-slate-500">· {{ $v->piso_general }}</span>
                            @endif
                        </p>
                        <p>
                            <span class="text-slate-500">Entrada:</span>
                            {{ $v->fecha_entrada ? date('d/m/Y H:i', strtotime($v->fecha_entrada)) : 'N/A' }}
                        </p>
                        <p>
                            <span class="text-slate-500">Salida:</span>
                            {{ $v->fecha_salida ? date('d/m/Y H:i', strtotime($v->fecha_salida)) : ($v->estado === 'activa' ? 'En curso' : 'N/A') }}
                        </p>
                    </div>

                    <div class="flex items-center gap-2 pt-2 border-t border-white/10">
                        <a href="/visitas/{{ $v->id_visita }}/editar"
                           class="flex-1 text-center rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-xs font-semibold text-slate-200 transition hover:border-[#4978eb]/50 hover:bg-[#4978eb]/10">
                            Editar
                        </a>
                        <form action="/visitas/{{ $v->id_visita }}" method="POST" class="flex-1"
                              onsubmit="return confirm('¿Eliminar el registro de {{ $v->nombre_visitante }}? Esta acción no se puede deshacer.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full rounded-xl border border-red-500/20 bg-red-500/10 px-3 py-2 text-xs font-semibold text-red-300 transition hover:bg-red-500/20">
                                Eliminar
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        <script>
            (function () {
                const buscador = document.getElementById('buscador-nombre');
                const filtroPiso = document.getElementById('filtro-piso');
                const tarjetas = document.querySelectorAll('#grid-visitas .tarjeta-visita');
                const sinResultados = document.getElementById('sin-resultados');

                const normalizar = (texto) => texto.normalize('NFD').replace(new RegExp('[\\u0300-\\u036f]', 'g'), '');

                function aplicarFiltros() {
                    const nombre = normalizar(buscador.value.trim().toLowerCase());
                    const piso = filtroPiso ? filtroPiso.value : '';
                    let visibles = 0;

                    tarjetas.forEach(function (tarjeta) {
                        const coincideNombre = !nombre || normalizar(tarjeta.dataset.nombre || '').includes(nombre);
                        const coincidePiso = !piso || tarjeta.dataset.piso === piso;
                        const visible = coincideNombre && coincidePiso;
                        tarjeta.style.display = visible ? '' : 'none';
                        if (visible) visibles++;
                    });

                    sinResultados.classList.toggle('hidden', visibles > 0);
                }

                buscador.addEventListener('input', aplicarFiltros);
                if (filtroPiso) {
                    filtroPiso.addEventListener('change', aplicarFiltros);
                }
            })();
        </script>
    @endif
</div>
@endsection
