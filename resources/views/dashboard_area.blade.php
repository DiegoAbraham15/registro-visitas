@extends('layouts.app')

@section('content')
@php
    // Torre de Consultorios (id_edificio=2) no tiene entrada aquí: su "detalle" siempre
    // es el consultorio, sin importar el tipo_acceso — se resuelve por id_edificio en
    // vez de por tipo_real más abajo, en la tarjeta y en el modal "Ver más".
    $detalleLabel = [
        'familiar' => 'Habitación',
        'proveedor' => 'Destino',
        'postulante' => 'Puesto',
        'ex_empleado' => 'Motivo',
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
        'visitante' => [
            'label' => 'Visitante',
            'bg' => 'bg-[#199e70]/15', 'text' => 'text-[#6fd8ac]',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />',
        ],
        'paciente' => [
            'label' => 'Paciente',
            'bg' => 'bg-[#e66767]/15', 'text' => 'text-[#f4a3a3]',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z" />',
        ],
        'ex_empleado' => [
            'label' => 'Ex empleado',
            'bg' => 'bg-amber-500/15', 'text' => 'text-amber-300',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /><path stroke-linecap="round" stroke-linejoin="round" d="m3 3 18 18" />',
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

    <div class="mb-4 flex items-center gap-3">
        <a href="{{ request()->fullUrlWithQuery(['estado' => 'activas']) }}"
           class="rounded-full px-4 py-1.5 text-xs font-semibold transition
                  {{ $soloActivas ? 'bg-[#4978eb] text-white shadow-md shadow-[#4978eb]/30' : 'bg-white/5 text-slate-300 hover:bg-white/10' }}">
            Activas
        </a>
        <a href="{{ request()->fullUrlWithQuery(['estado' => 'todas']) }}"
           class="rounded-full px-4 py-1.5 text-xs font-semibold transition
                  {{ ! $soloActivas ? 'bg-[#4978eb] text-white shadow-md shadow-[#4978eb]/30' : 'bg-white/5 text-slate-300 hover:bg-white/10' }}">
            Todas
        </a>
        
    </div>

    <div id="panel-visitas">
    @if ($visitas->isEmpty())
        <div class="rounded-2xl border border-white/10 bg-[#081536] px-6 py-10 text-center text-slate-400">
            {{ $soloActivas ? 'No hay visitas activas para esta área asignada.' : 'No hay visitas registradas para esta área asignada.' }}
        </div>
    @else
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <div class="relative">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"
                     class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input type="text" id="buscador-nombre" placeholder="Buscar por nombre o folio..."
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
                @php
                    $camposExtra = match (true) {
                        (int) $v->id_edificio === 2 => [
                            ['Tipo de acceso', $v->tipo_acceso ? ucfirst($v->tipo_acceso) : null],
                            ['Médico', $v->medico],
                        ],
                        $v->tipo_real === 'familiar' => [
                            ['Paciente', $v->nombre_paciente],
                            ['Parentesco', $v->parentesco],
                        ],
                        $v->tipo_real === 'proveedor' => [
                            ['Empresa representada', $v->empresa_representada],
                            ['Motivo de visita', $v->motivo_visita],
                            ['Fecha', $v->fecha_proveedor ? date('d/m/Y', strtotime($v->fecha_proveedor)) : null],
                            ['Hora de entrada', $v->hora_entrada],
                            ['Hora de salida', $v->hora_salida],
                        ],
                        $v->tipo_real === 'postulante' => [
                            ['Área destino', $v->area_destino_postulante],
                            ['Responsable de RH', $v->responsable_rh],
                            ['Tipo de cita', $v->tipo_cita],
                            ['CV entregado', is_null($v->cv_entregado) ? null : ($v->cv_entregado ? 'Sí' : 'No')],
                        ],
                        default => [],
                    };
                @endphp
                <div class="tarjeta-visita rounded-2xl border border-white/10 bg-[#081536] p-5 flex flex-col gap-4"
                     data-piso="{{ $v->piso_general }}" data-nombre="{{ Str::lower($v->nombre_visitante) }}"
                     data-folio="{{ Str::lower($v->folio ?? '') }}">
                    <div class="flex items-start gap-3">
                        @if (!empty($v->foto_persona) && !str_starts_with($v->foto_persona, 'blob:'))
                            <img class="h-12 w-12 rounded-full object-cover border-2 border-white/10 shadow-sm cursor-pointer hover:scale-110 transition shrink-0"
                                 src="{{ rtrim(config('app.mobile_uploads_url'), '/') . '/' . ltrim($v->foto_persona, '/') }}"
                                 alt="Foto de {{ $v->nombre_visitante }}"
                                 loading="lazy"
                                 onclick="window.open(this.src, '_blank')"
                                 onerror="this.hidden = true; this.nextElementSibling.hidden = false;">
                            <div class="h-12 w-12 shrink-0 rounded-full bg-white/10 flex items-center justify-center text-white font-bold text-xs uppercase tracking-wider" hidden>
                                {{ substr($v->nombre_visitante ?? 'V', 0, 2) }}
                            </div>
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
                            <span class="text-slate-500">{{ (int) $v->id_edificio === 2 ? 'Consultorio' : ($detalleLabel[$v->tipo_real] ?? 'Detalle') }}:</span>
                            {{ $v->detalle ?? 'N/A' }}
                            @if ($v->piso_general)
                                <span class="text-slate-500">· {{ $v->piso_general }}</span>
                            @endif
                        </p>
                        @if ((int) $v->id_edificio === 2 && $v->medico)
                            <p>
                                <span class="text-slate-500">Médico:</span>
                                {{ $v->medico }}
                            </p>
                        @endif
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
                        <button type="button" data-id="{{ $v->id_visita }}"
                                class="boton-ver-mas flex-1 text-center rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-xs font-semibold text-slate-200 transition hover:border-[#4978eb]/50 hover:bg-[#4978eb]/10">
                            Ver más
                        </button>
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

                <template id="detalle-visita-{{ $v->id_visita }}">
                    <div class="flex items-start gap-3">
                        @if (!empty($v->foto_persona) && !str_starts_with($v->foto_persona, 'blob:'))
                            <img class="h-16 w-16 rounded-full object-cover border-2 border-white/10 shadow-sm cursor-pointer hover:scale-110 transition shrink-0"
                                 src="{{ rtrim(config('app.mobile_uploads_url'), '/') . '/' . ltrim($v->foto_persona, '/') }}"
                                 alt="Foto de {{ $v->nombre_visitante }}"
                                 onclick="window.open(this.src, '_blank')"
                                 onerror="this.hidden = true; this.nextElementSibling.hidden = false;">
                            <div class="h-16 w-16 shrink-0 rounded-full bg-white/10 flex items-center justify-center text-white font-bold text-sm uppercase tracking-wider" hidden>
                                {{ substr($v->nombre_visitante ?? 'V', 0, 2) }}
                            </div>
                        @else
                            <div class="h-16 w-16 shrink-0 rounded-full bg-white/10 flex items-center justify-center text-white font-bold text-sm uppercase tracking-wider">
                                {{ substr($v->nombre_visitante ?? 'V', 0, 2) }}
                            </div>
                        @endif
                        <div class="min-w-0">
                            <p class="text-lg font-semibold text-white truncate">{{ $v->nombre_visitante }}</p>
                            <p class="text-sm font-mono font-bold text-[#9db6f7]">{{ $v->folio ?? 'N/A' }}</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2 mt-4">
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

                    <dl class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                        <div>
                            <dt class="text-slate-500">{{ (int) $v->id_edificio === 2 ? 'Consultorio' : ($detalleLabel[$v->tipo_real] ?? 'Detalle') }}</dt>
                            <dd class="text-slate-200">{{ $v->detalle ?? 'N/A' }}</dd>
                        </div>
                        @if ($v->piso_general)
                            <div>
                                <dt class="text-slate-500">Piso</dt>
                                <dd class="text-slate-200">{{ $v->piso_general }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-slate-500">Entrada</dt>
                            <dd class="text-slate-200">{{ $v->fecha_entrada ? date('d/m/Y H:i', strtotime($v->fecha_entrada)) : 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Salida</dt>
                            <dd class="text-slate-200">{{ $v->fecha_salida ? date('d/m/Y H:i', strtotime($v->fecha_salida)) : ($v->estado === 'activa' ? 'En curso' : 'N/A') }}</dd>
                        </div>
                        @foreach ($camposExtra as [$etiqueta, $valor])
                            @continue(empty($valor))
                            <div>
                                <dt class="text-slate-500">{{ $etiqueta }}</dt>
                                <dd class="text-slate-200">{{ $valor }}</dd>
                            </div>
                        @endforeach
                    </dl>

                    @if (!empty($v->foto_ine) && !str_starts_with($v->foto_ine, 'blob:'))
                        <div class="mt-4">
                            <p class="text-xs text-slate-500 mb-2">Identificación (INE)</p>
                            <img class="h-28 rounded-lg object-cover border border-white/10 cursor-pointer hover:opacity-80 transition"
                                 src="{{ rtrim(config('app.mobile_uploads_url'), '/') . '/' . ltrim($v->foto_ine, '/') }}"
                                 alt="INE de {{ $v->nombre_visitante }}"
                                 onclick="window.open(this.src, '_blank')"
                                 onerror="this.replaceWith(Object.assign(document.createElement('p'), { className: 'text-xs text-slate-500 italic', textContent: 'Identificación no disponible en este momento.' }));">
                        </div>
                    @endif
                </template>
            @endforeach
        </div>

        <div id="modal-overlay" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
            <div class="relative w-full max-w-md rounded-2xl border border-white/10 bg-[#081536] p-6 shadow-xl max-h-[90vh] overflow-y-auto">
                <button type="button" id="modal-cerrar"
                        class="absolute top-4 right-4 text-slate-400 hover:text-white transition">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
                <div id="modal-contenido"></div>
            </div>
        </div>
    @endif
    </div>

    <script>
        // El contenido de #panel-visitas se reemplaza por completo en cada
        // refresco automático, así que la lógica de filtros/modal vive aquí
        // (fuera del panel) como funciones que se vuelven a ejecutar después
        // de cada actualización, en vez de listeners atados una sola vez.
        (function () {
            const panel = document.getElementById('panel-visitas');
            const normalizar = (texto) => texto.normalize('NFD').replace(new RegExp('[\\u0300-\\u036f]', 'g'), '');

            function initFiltros() {
                const buscador = document.getElementById('buscador-nombre');
                const sinResultados = document.getElementById('sin-resultados');
                if (!buscador || !sinResultados) return;

                const filtroPiso = document.getElementById('filtro-piso');
                const tarjetas = document.querySelectorAll('#grid-visitas .tarjeta-visita');

                function aplicarFiltros() {
                    const busqueda = normalizar(buscador.value.trim().toLowerCase());
                    const piso = filtroPiso ? filtroPiso.value : '';
                    let visibles = 0;

                    tarjetas.forEach(function (tarjeta) {
                        const coincideNombre = normalizar(tarjeta.dataset.nombre || '').includes(busqueda);
                        const coincideFolio = normalizar(tarjeta.dataset.folio || '').includes(busqueda);
                        const coincideBusqueda = !busqueda || coincideNombre || coincideFolio;
                        const coincidePiso = !piso || tarjeta.dataset.piso === piso;
                        const visible = coincideBusqueda && coincidePiso;
                        tarjeta.style.display = visible ? '' : 'none';
                        if (visible) visibles++;
                    });

                    sinResultados.classList.toggle('hidden', visibles > 0);
                }

                buscador.addEventListener('input', aplicarFiltros);
                if (filtroPiso) {
                    filtroPiso.addEventListener('change', aplicarFiltros);
                }
                aplicarFiltros();
            }

            function initModal() {
                const overlay = document.getElementById('modal-overlay');
                const contenido = document.getElementById('modal-contenido');
                if (!overlay || !contenido) return;

                function abrirModal(id) {
                    const plantilla = document.getElementById('detalle-visita-' + id);
                    if (!plantilla) return;
                    contenido.replaceChildren(plantilla.content.cloneNode(true));
                    overlay.classList.remove('hidden');
                }

                function cerrarModal() {
                    overlay.classList.add('hidden');
                    contenido.replaceChildren();
                }

                document.querySelectorAll('.boton-ver-mas').forEach(function (boton) {
                    boton.addEventListener('click', function () {
                        abrirModal(boton.dataset.id);
                    });
                });

                document.getElementById('modal-cerrar').addEventListener('click', cerrarModal);
                overlay.addEventListener('click', function (evento) {
                    if (evento.target === overlay) cerrarModal();
                });
                document.addEventListener('keydown', function (evento) {
                    if (evento.key === 'Escape') cerrarModal();
                });
            }

            function modalAbierto() {
                const overlay = document.getElementById('modal-overlay');
                return !!overlay && !overlay.classList.contains('hidden');
            }

            initFiltros();
            initModal();

            const INTERVALO_MS = 10000;
            let refrescando = false;

            async function refrescar() {
                if (refrescando || document.hidden || modalAbierto()) return;

                const buscador = document.getElementById('buscador-nombre');
                if (buscador && document.activeElement === buscador) return;

                refrescando = true;
                try {
                    const respuesta = await fetch(window.location.href, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        cache: 'no-store',
                    });
                    if (!respuesta.ok) return;

                    const html = await respuesta.text();
                    const panelNuevo = new DOMParser().parseFromString(html, 'text/html').getElementById('panel-visitas');
                    if (!panelNuevo) {
                        // Probablemente la sesión expiró y el servidor devolvió el login:
                        // se navega de verdad para que el usuario vea la pantalla correcta.
                        window.location.reload();
                        return;
                    }

                    const valorBusqueda = buscador ? buscador.value : '';
                    const filtroPisoActual = document.getElementById('filtro-piso');
                    const valorPiso = filtroPisoActual ? filtroPisoActual.value : '';

                    panel.innerHTML = panelNuevo.innerHTML;

                    const nuevoBuscador = document.getElementById('buscador-nombre');
                    if (nuevoBuscador) nuevoBuscador.value = valorBusqueda;
                    const nuevoFiltroPiso = document.getElementById('filtro-piso');
                    if (nuevoFiltroPiso && valorPiso) nuevoFiltroPiso.value = valorPiso;

                    initFiltros();
                    initModal();
                } catch (error) {
                    // Falla de red silenciosa: se reintenta en el próximo ciclo.
                } finally {
                    refrescando = false;
                }
            }

            setInterval(refrescar, INTERVALO_MS);
        })();
    </script>
</div>
@endsection
