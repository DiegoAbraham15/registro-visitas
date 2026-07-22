@extends('layouts.app')

@section('content')
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
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-400">Habitaciones</h2>
            </div>

            <div class="rounded-2xl border border-white/10 bg-[#081536] p-6 mb-4">
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

            @forelse ($habitacionesPorPiso as $piso => $habitaciones)
                <div class="rounded-2xl border border-white/10 bg-[#081536] p-6 mb-4">
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-3">{{ $piso }}</h3>
                    <div class="space-y-2">
                        @foreach ($habitaciones as $habitacion)
                            <details class="group rounded-xl border border-white/10 bg-white/5 px-4 py-3">
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-3">
                                    <span class="text-sm font-medium text-white">{{ $habitacion->numero }}</span>
                                    <span class="flex items-center gap-2">
                                        <span class="text-xs font-semibold text-[#9db6f7] group-open:hidden">Editar</span>
                                        <span class="hidden text-xs font-semibold text-slate-400 group-open:inline">Cerrar</span>
                                        <form action="/catalogos/habitaciones/{{ $habitacion->id }}" method="POST"
                                              onsubmit="return confirm('¿Eliminar la habitación {{ $habitacion->numero }} ({{ $piso }})?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg border border-red-500/20 bg-red-500/10 px-2 py-1 text-xs font-semibold text-red-300 transition hover:bg-red-500/20">
                                                Eliminar
                                            </button>
                                        </form>
                                    </span>
                                </summary>

                                <form action="/catalogos/habitaciones/{{ $habitacion->id }}" method="POST" class="mt-3 flex flex-wrap items-end gap-3">
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
                </div>
            @empty
                <p class="text-sm text-slate-500 italic">Todavía no hay habitaciones en el catálogo.</p>
            @endforelse
        </div>

        {{-- ÁREAS --}}
        <div>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-400">Áreas</h2>
            </div>

            <div class="rounded-2xl border border-white/10 bg-[#081536] p-6 mb-4">
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

            @forelse ($areasPorPiso as $piso => $areas)
                <div class="rounded-2xl border border-white/10 bg-[#081536] p-6 mb-4">
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-3">{{ $piso }}</h3>
                    <div class="space-y-2">
                        @foreach ($areas as $area)
                            <details class="group rounded-xl border border-white/10 bg-white/5 px-4 py-3">
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-3">
                                    <span class="text-sm font-medium text-white">{{ $area->nombre }}</span>
                                    <span class="flex items-center gap-2">
                                        <span class="text-xs font-semibold text-[#9db6f7] group-open:hidden">Editar</span>
                                        <span class="hidden text-xs font-semibold text-slate-400 group-open:inline">Cerrar</span>
                                        <form action="/catalogos/areas/{{ $area->id }}" method="POST"
                                              onsubmit="return confirm('¿Eliminar el área {{ $area->nombre }} ({{ $piso }})?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg border border-red-500/20 bg-red-500/10 px-2 py-1 text-xs font-semibold text-red-300 transition hover:bg-red-500/20">
                                                Eliminar
                                            </button>
                                        </form>
                                    </span>
                                </summary>

                                <form action="/catalogos/areas/{{ $area->id }}" method="POST" class="mt-3 flex flex-wrap items-end gap-3">
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
                </div>
            @empty
                <p class="text-sm text-slate-500 italic">Todavía no hay áreas en el catálogo.</p>
            @endforelse
        </div>

    </div>
</div>
@endsection
