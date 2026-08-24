@extends('layouts.app')

@section('content')
@php
    $totalHabitaciones = $habitacionesPorPiso->collapse()->count();
    $totalAreas = $areasPorPiso->collapse()->count();
    $iconoEditar = '<path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" />';
    $iconoEliminar = '<path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />';
@endphp

<div class="max-w-6xl mx-auto">
    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-400">Administración</p>
        <h1 class="text-2xl font-bold text-white">Habitaciones y Áreas</h1>
        <p class="mt-1 text-sm text-slate-400">Catálogo usado en los formularios de registro y edición de visitas (habitaciones para familiares, áreas para proveedores).</p>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-2xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-300">
            <ul class="list-disc pl-4 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <datalist id="pisos-habitaciones">
        @foreach ($habitacionesPorPiso->keys() as $piso)
            <option value="{{ $piso }}"></option>
        @endforeach
    </datalist>

    <datalist id="pisos-areas">
        @foreach ($areasPorPiso->keys() as $piso)
            <option value="{{ $piso }}"></option>
        @endforeach
    </datalist>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">

        {{-- HABITACIONES --}}
        <div>
            <div class="flex items-baseline justify-between mb-4">
                <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-400">Habitaciones</h2>
                <span class="text-xs text-slate-500">{{ $totalHabitaciones }} en {{ $habitacionesPorPiso->count() }} pisos</span>
            </div>

            <div class="rounded-2xl border border-white/10 bg-[#081536] p-5 mb-4">
                <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-3">Agregar habitación</h3>
                <form action="/catalogos/habitaciones" method="POST" class="flex flex-wrap items-end gap-3">
                    @csrf
                    <div class="flex-1 min-w-[140px]">
                        <label class="block text-xs text-slate-400 mb-1">Piso</label>
                        <input type="text" name="piso" list="pisos-habitaciones" required placeholder="Ej. Piso 2"
                               class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder-slate-500 focus:border-[#4978eb] focus:outline-none focus:ring-2 focus:ring-[#4978eb]/20" />
                    </div>
                    <div class="flex-1 min-w-[140px]">
                        <label class="block text-xs text-slate-400 mb-1">Número / nombre</label>
                        <input type="text" name="numero" required placeholder="Ej. 224 o UTIMA 4"
                               class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder-slate-500 focus:border-[#4978eb] focus:outline-none focus:ring-2 focus:ring-[#4978eb]/20" />
                    </div>
                    <button type="submit" class="rounded-xl bg-[#4978eb] px-4 py-2 text-sm font-semibold text-white shadow-md shadow-[#4978eb]/30 transition hover:bg-[#3a63d1]">
                        Agregar
                    </button>
                </form>
            </div>

            @if ($habitacionesPorPiso->count() > 1)
                <div class="flex flex-wrap gap-2 mb-4">
                    @foreach ($habitacionesPorPiso as $piso => $habitaciones)
                        <a href="#hab-{{ Str::slug($piso) }}"
                           class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-semibold text-slate-300 transition hover:border-[#4978eb]/50 hover:bg-[#4978eb]/10 hover:text-white">
                            {{ $piso }} <span class="text-slate-500">· {{ $habitaciones->count() }}</span>
                        </a>
                    @endforeach
                </div>
            @endif

            <div class="space-y-4">
                @forelse ($habitacionesPorPiso as $piso => $habitaciones)
                    <section id="hab-{{ Str::slug($piso) }}" class="scroll-mt-4">
                        <p class="mb-2 px-1 text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">{{ $piso }}</p>
                        <div class="rounded-2xl border border-white/10 bg-[#081536] divide-y divide-white/5 overflow-hidden">
                            @foreach ($habitaciones as $habitacion)
                                <details class="group">
                                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-5 py-3 transition hover:bg-white/5">
                                        <span class="text-sm font-medium text-white">{{ $habitacion->numero }}</span>
                                        <span class="flex shrink-0 items-center gap-1">
                                            <span class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-semibold text-[#9db6f7] group-open:bg-[#4978eb]/15">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-3.5 w-3.5">
                                                    {!! $iconoEditar !!}
                                                </svg>
                                                <span class="group-open:hidden">Editar</span>
                                                <span class="hidden group-open:inline">Cerrar</span>
                                            </span>
                                            <form action="/catalogos/habitaciones/{{ $habitacion->id }}" method="POST"
                                                  onsubmit="return confirm('¿Eliminar la habitación {{ $habitacion->numero }} ({{ $piso }})?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-semibold text-red-300 transition hover:bg-red-500/15">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-3.5 w-3.5">
                                                        {!! $iconoEliminar !!}
                                                    </svg>
                                                    Eliminar
                                                </button>
                                            </form>
                                        </span>
                                    </summary>

                                    <form action="/catalogos/habitaciones/{{ $habitacion->id }}" method="POST" class="mx-5 mb-3 flex flex-wrap items-end gap-3 rounded-lg bg-white/5 px-3 py-3">
                                        @csrf
                                        @method('PUT')
                                        <div class="flex-1 min-w-[120px]">
                                            <label class="block text-xs text-slate-400 mb-1">Piso</label>
                                            <input type="text" name="piso" value="{{ $habitacion->piso }}" list="pisos-habitaciones" required
                                                   class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white focus:border-[#4978eb] focus:outline-none focus:ring-2 focus:ring-[#4978eb]/20" />
                                        </div>
                                        <div class="flex-1 min-w-[120px]">
                                            <label class="block text-xs text-slate-400 mb-1">Número / nombre</label>
                                            <input type="text" name="numero" value="{{ $habitacion->numero }}" required
                                                   class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white focus:border-[#4978eb] focus:outline-none focus:ring-2 focus:ring-[#4978eb]/20" />
                                        </div>
                                        <button type="submit" class="rounded-xl bg-[#4978eb] px-4 py-2 text-sm font-semibold text-white shadow-md shadow-[#4978eb]/30 transition hover:bg-[#3a63d1]">
                                            Guardar
                                        </button>
                                    </form>
                                </details>
                            @endforeach
                        </div>
                    </section>
                @empty
                    <p class="text-sm text-slate-500 italic">Todavía no hay habitaciones en el catálogo.</p>
                @endforelse
            </div>
        </div>

        {{-- ÁREAS --}}
        <div>
            <div class="flex items-baseline justify-between mb-4">
                <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-400">Áreas</h2>
                <span class="text-xs text-slate-500">{{ $totalAreas }} en {{ $areasPorPiso->count() }} pisos</span>
            </div>

            <div class="rounded-2xl border border-white/10 bg-[#081536] p-5 mb-4">
                <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-3">Agregar área</h3>
                <form action="/catalogos/areas" method="POST" class="flex flex-wrap items-end gap-3">
                    @csrf
                    <div class="flex-1 min-w-[140px]">
                        <label class="block text-xs text-slate-400 mb-1">Piso</label>
                        <input type="text" name="piso" list="pisos-areas" required placeholder="Ej. Piso 1"
                               class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder-slate-500 focus:border-[#4978eb] focus:outline-none focus:ring-2 focus:ring-[#4978eb]/20" />
                    </div>
                    <div class="flex-1 min-w-[140px]">
                        <label class="block text-xs text-slate-400 mb-1">Nombre</label>
                        <input type="text" name="nombre" required placeholder="Ej. LABORATORIO"
                               class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder-slate-500 focus:border-[#4978eb] focus:outline-none focus:ring-2 focus:ring-[#4978eb]/20" />
                    </div>
                    <button type="submit" class="rounded-xl bg-[#4978eb] px-4 py-2 text-sm font-semibold text-white shadow-md shadow-[#4978eb]/30 transition hover:bg-[#3a63d1]">
                        Agregar
                    </button>
                </form>
            </div>

            @if ($areasPorPiso->count() > 1)
                <div class="flex flex-wrap gap-2 mb-4">
                    @foreach ($areasPorPiso as $piso => $areas)
                        <a href="#area-{{ Str::slug($piso) }}"
                           class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-semibold text-slate-300 transition hover:border-[#4978eb]/50 hover:bg-[#4978eb]/10 hover:text-white">
                            {{ $piso }} <span class="text-slate-500">· {{ $areas->count() }}</span>
                        </a>
                    @endforeach
                </div>
            @endif

            <div class="space-y-4">
                @forelse ($areasPorPiso as $piso => $areas)
                    <section id="area-{{ Str::slug($piso) }}" class="scroll-mt-4">
                        <p class="mb-2 px-1 text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">{{ $piso }}</p>
                        <div class="rounded-2xl border border-white/10 bg-[#081536] divide-y divide-white/5 overflow-hidden">
                            @foreach ($areas as $area)
                                <details class="group">
                                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-5 py-3 transition hover:bg-white/5">
                                        <span class="text-sm font-medium text-white">{{ $area->nombre }}</span>
                                        <span class="flex shrink-0 items-center gap-1">
                                            <span class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-semibold text-[#9db6f7] group-open:bg-[#4978eb]/15">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-3.5 w-3.5">
                                                    {!! $iconoEditar !!}
                                                </svg>
                                                <span class="group-open:hidden">Editar</span>
                                                <span class="hidden group-open:inline">Cerrar</span>
                                            </span>
                                            <form action="/catalogos/areas/{{ $area->id }}" method="POST"
                                                  onsubmit="return confirm('¿Eliminar el área {{ $area->nombre }} ({{ $piso }})?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-semibold text-red-300 transition hover:bg-red-500/15">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-3.5 w-3.5">
                                                        {!! $iconoEliminar !!}
                                                    </svg>
                                                    Eliminar
                                                </button>
                                            </form>
                                        </span>
                                    </summary>

                                    <form action="/catalogos/areas/{{ $area->id }}" method="POST" class="mx-5 mb-3 flex flex-wrap items-end gap-3 rounded-lg bg-white/5 px-3 py-3">
                                        @csrf
                                        @method('PUT')
                                        <div class="flex-1 min-w-[120px]">
                                            <label class="block text-xs text-slate-400 mb-1">Piso</label>
                                            <input type="text" name="piso" value="{{ $area->piso }}" list="pisos-areas" required
                                                   class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white focus:border-[#4978eb] focus:outline-none focus:ring-2 focus:ring-[#4978eb]/20" />
                                        </div>
                                        <div class="flex-1 min-w-[120px]">
                                            <label class="block text-xs text-slate-400 mb-1">Nombre</label>
                                            <input type="text" name="nombre" value="{{ $area->nombre }}" required
                                                   class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white focus:border-[#4978eb] focus:outline-none focus:ring-2 focus:ring-[#4978eb]/20" />
                                        </div>
                                        <button type="submit" class="rounded-xl bg-[#4978eb] px-4 py-2 text-sm font-semibold text-white shadow-md shadow-[#4978eb]/30 transition hover:bg-[#3a63d1]">
                                            Guardar
                                        </button>
                                    </form>
                                </details>
                            @endforeach
                        </div>
                    </section>
                @empty
                    <p class="text-sm text-slate-500 italic">Todavía no hay áreas en el catálogo.</p>
                @endforelse
            </div>
        </div>

    </div>
</div>
@endsection
