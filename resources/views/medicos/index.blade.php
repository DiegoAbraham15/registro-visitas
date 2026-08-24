@extends('layouts.app')

@section('content')
@php
    $totalMedicos = $medicosPorPiso->collapse()->count();
    $totalConsultorios = $medicosPorPiso->collapse()->pluck('consultorio')->unique()->count();
    $iconoEditar = '<path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" />';
    $iconoEliminar = '<path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />';
@endphp

<div class="max-w-5xl mx-auto">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-400">Administración</p>
            <h1 class="text-2xl font-bold text-white">Médicos de la Torre de Consultorios</h1>
            <p class="mt-1 text-sm text-slate-400">Un mismo consultorio puede tener varios médicos; se listan agrupados debajo de su número.</p>
        </div>
        <div class="flex gap-2">
            <span class="rounded-full border border-white/10 bg-[#081536] px-4 py-1.5 text-xs text-slate-300">
                <strong class="text-white">{{ $totalMedicos }}</strong> médicos
            </span>
            <span class="rounded-full border border-white/10 bg-[#081536] px-4 py-1.5 text-xs text-slate-300">
                <strong class="text-white">{{ $totalConsultorios }}</strong> consultorios
            </span>
        </div>
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

    <div class="grid grid-cols-1 lg:grid-cols-[1.6fr_1fr] gap-4 mb-6">
        <div class="rounded-2xl border border-white/10 bg-[#081536] p-5">
            <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-3">Buscar</h3>
            <form action="/medicos" method="GET" class="flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs text-slate-400 mb-1">Nombre del médico o número de consultorio</label>
                    <input type="text" name="busqueda" value="{{ $busqueda }}" placeholder="Ej. GARCIA o 108"
                           class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder-slate-500 focus:border-[#4978eb] focus:outline-none focus:ring-2 focus:ring-[#4978eb]/20" />
                </div>
                <button type="submit" class="rounded-xl bg-[#4978eb] px-4 py-2 text-sm font-semibold text-white shadow-md shadow-[#4978eb]/30 transition hover:bg-[#3a63d1]">
                    Buscar
                </button>
                @if ($busqueda !== '')
                    <a href="/medicos" class="rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-semibold text-slate-300 transition hover:bg-white/10">
                        Limpiar
                    </a>
                @endif
            </form>
        </div>

        <div class="rounded-2xl border border-white/10 bg-[#081536] p-5">
            <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-3">Agregar médico</h3>
            <form action="/medicos" method="POST" class="flex flex-wrap items-end gap-3">
                @csrf
                <div class="flex-1 min-w-[90px]">
                    <label class="block text-xs text-slate-400 mb-1">Consultorio</label>
                    <input type="text" name="consultorio" required placeholder="108"
                           class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder-slate-500 focus:border-[#4978eb] focus:outline-none focus:ring-2 focus:ring-[#4978eb]/20" />
                </div>
                <div class="flex-1 min-w-[160px]">
                    <label class="block text-xs text-slate-400 mb-1">Nombre del médico</label>
                    <input type="text" name="nombre_medico" required placeholder="JUAN PEREZ GARCIA"
                           class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder-slate-500 focus:border-[#4978eb] focus:outline-none focus:ring-2 focus:ring-[#4978eb]/20" />
                </div>
                <button type="submit" class="rounded-xl bg-[#4978eb] px-4 py-2 text-sm font-semibold text-white shadow-md shadow-[#4978eb]/30 transition hover:bg-[#3a63d1]">
                    Agregar
                </button>
            </form>
        </div>
    </div>

    @if ($medicosPorPiso->isEmpty())
        <p class="text-sm text-slate-500 italic">
            @if ($busqueda !== '')
                Ningún médico coincide con "{{ $busqueda }}".
            @else
                Todavía no hay médicos en el catálogo.
            @endif
        </p>
    @else
        @if ($medicosPorPiso->count() > 1)
            <div class="flex flex-wrap gap-2 mb-6">
                @foreach ($medicosPorPiso as $piso => $medicos)
                    <a href="#piso-{{ Str::slug($piso) }}"
                       class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-semibold text-slate-300 transition hover:border-[#4978eb]/50 hover:bg-[#4978eb]/10 hover:text-white">
                        {{ $piso }} <span class="text-slate-500">· {{ $medicos->count() }}</span>
                    </a>
                @endforeach
            </div>
        @endif

        <div class="space-y-6">
            @foreach ($medicosPorPiso as $piso => $medicos)
                <section id="piso-{{ Str::slug($piso) }}" class="scroll-mt-4">
                    <div class="flex items-baseline justify-between mb-2 px-1">
                        <h2 class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">{{ $piso }}</h2>
                        <p class="text-xs text-slate-500">{{ $medicos->pluck('consultorio')->unique()->count() }} consultorios · {{ $medicos->count() }} médicos</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
                        @foreach ($medicos->groupBy('consultorio') as $consultorio => $medicosDelConsultorio)
                            <details class="consultorio-card group rounded-2xl border border-white/10 bg-[#081536] overflow-hidden transition hover:border-[#4978eb]/40">
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3.5">
                                    <span class="flex items-center gap-3 min-w-0">
                                        <span class="inline-flex h-9 shrink-0 items-center justify-center rounded-xl bg-[#4978eb]/15 px-3 font-mono text-sm font-bold text-[#9db6f7]">
                                            {{ $consultorio }}
                                        </span>
                                        <span class="text-xs text-slate-400 truncate">
                                            {{ $medicosDelConsultorio->count() }} {{ Str::plural('médico', $medicosDelConsultorio->count()) }}
                                        </span>
                                    </span>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                         class="chevron h-4 w-4 shrink-0 text-slate-500 transition-transform duration-200">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                    </svg>
                                </summary>

                                <div class="border-t border-white/10 divide-y divide-white/5">
                                    @foreach ($medicosDelConsultorio as $medico)
                                        <details class="group/doctor">
                                            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-2.5 transition hover:bg-white/5">
                                                <span class="text-sm text-white">{{ $medico->nombre_medico }}</span>
                                                <span class="flex shrink-0 items-center gap-1">
                                                    <span class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-semibold text-[#9db6f7] group-open/doctor:bg-[#4978eb]/15">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-3.5 w-3.5">
                                                            {!! $iconoEditar !!}
                                                        </svg>
                                                        <span class="group-open/doctor:hidden">Editar</span>
                                                        <span class="hidden group-open/doctor:inline">Cerrar</span>
                                                    </span>
                                                    <form action="/medicos/{{ $medico->id }}" method="POST"
                                                          onsubmit="return confirm('¿Eliminar al Dr(a). {{ $medico->nombre_medico }} del consultorio {{ $medico->consultorio }}?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                                class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-semibold text-red-300 transition hover:bg-red-500/15">
                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-3.5 w-3.5">
                                                                {!! $iconoEliminar !!}
                                                            </svg>
                                                            Eliminar
                                                        </button>
                                                    </form>
                                                </span>
                                            </summary>

                                            <form action="/medicos/{{ $medico->id }}" method="POST" class="mx-3 mb-3 flex flex-wrap items-end gap-3 rounded-lg bg-white/5 px-3 py-3">
                                                @csrf
                                                @method('PUT')
                                                <div class="w-24">
                                                    <label class="block text-xs text-slate-400 mb-1">Consultorio</label>
                                                    <input type="text" name="consultorio" value="{{ $medico->consultorio }}" required
                                                           class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white focus:border-[#4978eb] focus:outline-none focus:ring-2 focus:ring-[#4978eb]/20" />
                                                </div>
                                                <div class="flex-1 min-w-[160px]">
                                                    <label class="block text-xs text-slate-400 mb-1">Nombre del médico</label>
                                                    <input type="text" name="nombre_medico" value="{{ $medico->nombre_medico }}" required
                                                           class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white focus:border-[#4978eb] focus:outline-none focus:ring-2 focus:ring-[#4978eb]/20" />
                                                </div>
                                                <button type="submit" class="rounded-xl bg-[#4978eb] px-4 py-2 text-sm font-semibold text-white shadow-md shadow-[#4978eb]/30 transition hover:bg-[#3a63d1]">
                                                    Guardar
                                                </button>
                                            </form>
                                        </details>
                                    @endforeach
                                </div>
                            </details>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    @endif
</div>

<style>
    details.consultorio-card[open] > summary .chevron {
        transform: rotate(180deg);
    }
</style>
@endsection
