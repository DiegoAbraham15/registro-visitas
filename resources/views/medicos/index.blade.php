@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-400">Administración</p>
        <h1 class="text-2xl font-bold text-white">Médicos de la Torre de Consultorios</h1>
        <p class="mt-1 text-sm text-slate-400">Catálogo de médicos asignados a cada consultorio. Un mismo consultorio puede tener varios médicos.</p>
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

    <div class="grid grid-cols-1 lg:grid-cols-[1.6fr_1fr] gap-6 mb-6">
        <div class="rounded-2xl border border-white/10 bg-[#081536] p-6">
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

        <div class="rounded-2xl border border-white/10 bg-[#081536] p-6">
            <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-3">Agregar médico</h3>
            <form action="/medicos" method="POST" class="flex flex-wrap items-end gap-3">
                @csrf
                <div class="flex-1 min-w-[100px]">
                    <label class="block text-xs text-slate-400 mb-1">Consultorio</label>
                    <input type="text" name="consultorio" required placeholder="Ej. 108"
                           class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder-slate-500 focus:border-[#4978eb] focus:outline-none focus:ring-2 focus:ring-[#4978eb]/20" />
                </div>
                <div class="flex-1 min-w-[160px]">
                    <label class="block text-xs text-slate-400 mb-1">Nombre del médico</label>
                    <input type="text" name="nombre_medico" required placeholder="Ej. JUAN PEREZ GARCIA"
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
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @foreach ($medicosPorPiso as $piso => $medicos)
                <div class="rounded-2xl border border-white/10 bg-[#081536] p-6">
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-3">{{ $piso }}</h3>
                    <div class="space-y-2">
                        @foreach ($medicos as $medico)
                            <details class="group rounded-xl border border-white/10 bg-white/5 px-4 py-3">
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-3">
                                    <span class="min-w-0">
                                        <span class="rounded-full bg-[#4978eb]/15 px-2 py-0.5 text-xs font-bold text-[#9db6f7] font-mono">{{ $medico->consultorio }}</span>
                                        <span class="text-sm font-medium text-white">{{ $medico->nombre_medico }}</span>
                                    </span>
                                    <span class="flex shrink-0 items-center gap-2">
                                        <span class="text-xs font-semibold text-[#9db6f7] group-open:hidden">Editar</span>
                                        <span class="hidden text-xs font-semibold text-slate-400 group-open:inline">Cerrar</span>
                                        <form action="/medicos/{{ $medico->id }}" method="POST"
                                              onsubmit="return confirm('¿Eliminar al Dr(a). {{ $medico->nombre_medico }} del consultorio {{ $medico->consultorio }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg border border-red-500/20 bg-red-500/10 px-2 py-1 text-xs font-semibold text-red-300 transition hover:bg-red-500/20">
                                                Eliminar
                                            </button>
                                        </form>
                                    </span>
                                </summary>

                                <form action="/medicos/{{ $medico->id }}" method="POST" class="mt-3 flex flex-wrap items-end gap-3">
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
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
