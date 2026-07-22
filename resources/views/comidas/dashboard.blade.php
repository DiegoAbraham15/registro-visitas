@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="flex justify-between items-center mb-6 bg-slate-800 p-4 rounded-xl text-white shadow-md">
        <div>
            <h1 class="text-xl font-bold">Panel de Control: Vinculación</h1>
            <p class="text-xs text-slate-300">Conectado como: <span class="font-semibold">{{ Auth::user()->name }}</span></p>
        </div>

        <div class="flex items-center gap-2">
            <a href="/vinculacion/menus"
               class="rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:bg-white/10">
                Editar menú semanal
            </a>
            <form action="/logout" method="POST">
                @csrf
                <button type="submit" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded text-sm transition">
                    Cerrar Sesión
                </button>
            </form>
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

    <div class="mb-6 rounded-2xl border border-white/10 bg-[#081536] px-6 py-4">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Menú de hoy · {{ ucfirst($comidaHoy->dia) }}</p>
        <p class="text-sm text-slate-200 mt-1">Comida: {{ $comidaHoy->comida ?? 'Sin definir' }}</p>
    </div>

    @if ($habitacionesSinMenu > 0)
        <div class="mb-6 rounded-2xl border border-amber-500/30 bg-amber-500/10 px-6 py-4 text-sm text-amber-300">
            {{ $habitacionesSinMenu }} {{ $habitacionesSinMenu === 1 ? 'habitación no tiene' : 'habitaciones no tienen' }} desayuno o cena definidos todavía hoy.
        </div>
    @endif

    <div class="mb-6 rounded-2xl border border-white/10 bg-[#081536] p-6">
        <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-400 mb-4">Agregar habitación</h2>
        <p class="text-xs text-slate-500 mb-4">Úsalo cuando una habitación todavía no tenga paciente ni visita registrados en el sistema. Solo se listan las habitaciones que aún no aparecen abajo.</p>

        @if ($habitacionesPorPiso->isEmpty())
            <p class="text-sm text-slate-500 italic">No hay habitaciones del catálogo sin registrar por ahora.</p>
        @else
            <form action="/vinculacion/habitaciones" method="POST" class="flex flex-wrap items-end gap-3">
                @csrf

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">Piso</label>
                    <select id="agregar-piso-select" required
                            class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white focus:border-[#4978eb] focus:outline-none focus:ring-2 focus:ring-[#4978eb]/20">
                        <option value="" disabled selected>Selecciona un piso</option>
                        @foreach ($habitacionesPorPiso as $piso => $opciones)
                            <option value="{{ $piso }}">{{ $piso }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">Habitación</label>
                    <select id="agregar-habitacion-select" name="catalogo_habitacion_id" required disabled
                            class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white focus:border-[#4978eb] focus:outline-none focus:ring-2 focus:ring-[#4978eb]/20">
                        <option value="" disabled selected>Selecciona primero un piso</option>
                    </select>
                </div>

                <button type="submit" class="rounded-xl bg-[#4978eb] px-4 py-3 text-sm font-semibold text-white shadow-md shadow-[#4978eb]/30 transition hover:bg-[#3a63d1]">
                    Agregar
                </button>
            </form>

            <script>
                (function () {
                    const HABITACIONES_POR_PISO = {!! json_encode($habitacionesPorPiso) !!};
                    const pisoSelect = document.getElementById('agregar-piso-select');
                    const habitacionSelect = document.getElementById('agregar-habitacion-select');

                    pisoSelect.addEventListener('change', function () {
                        const opciones = HABITACIONES_POR_PISO[this.value] || [];
                        habitacionSelect.disabled = opciones.length === 0;
                        habitacionSelect.innerHTML = '<option value="" disabled selected>Selecciona una habitación</option>' +
                            opciones.map(o => `<option value="${o.id}">Habitación ${o.numero}</option>`).join('');
                    });
                })();
            </script>
        @endif
    </div>

    @if ($habitaciones->isEmpty())
        <div class="rounded-2xl border border-white/10 bg-[#081536] px-6 py-10 text-center text-slate-400">
            No hay habitaciones con pacientes activos por ahora. Usa "Agregar habitación" si necesitas registrar una de todos modos.
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($habitaciones as $h)
                @php $formId = 'form-habitacion-'.\Illuminate\Support\Str::slug($h->piso.'-'.$h->habitacion); @endphp
                <div class="rounded-2xl border border-white/10 bg-[#081536] p-5 flex flex-col gap-4">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="font-semibold text-white truncate">{{ $h->piso }}</p>
                            <p class="text-xs text-slate-400 truncate">Habitación {{ $h->habitacion }}</p>
                            <p class="text-xs text-slate-500 truncate mt-1">
                                Paciente: {{ $h->pacientes->isNotEmpty() ? $h->pacientes->pluck('nombre')->join(', ') : 'Sin registrar' }}
                            </p>
                            @if ($h->cortesia && ! \App\Support\CortesiaVigente::esDeHoy($h->cortesia))
                                <p class="text-xs font-semibold text-amber-300 mt-1">⚠ Menú sin confirmar hoy</p>
                            @endif
                        </div>

                        @if ($h->pacientes->isEmpty() && $h->visitantes->isEmpty())
                            <form action="/vinculacion/habitaciones/comida" method="POST"
                                  onsubmit="return confirm('¿Quitar esta habitación del panel?');">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="piso" value="{{ $h->piso }}" />
                                <input type="hidden" name="habitacion" value="{{ $h->habitacion }}" />
                                <button type="submit"
                                        class="shrink-0 rounded-lg border border-red-500/20 bg-red-500/10 px-2 py-1 text-xs font-semibold text-red-300 transition hover:bg-red-500/20">
                                    Quitar
                                </button>
                            </form>
                        @endif
                    </div>

                    <div class="space-y-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Visitante que recibe la comida</p>
                        @if ($h->visitantes->isEmpty())
                            <p class="text-sm text-slate-500 italic">Sin visitantes activos registrados</p>
                        @else
                            @foreach ($h->visitantes as $visitante)
                                <label class="flex items-center gap-2 text-sm text-slate-300">
                                    <input type="radio"
                                           form="{{ $formId }}"
                                           name="recipiente"
                                           value="{{ $visitante->id_visita }}"
                                           {{ $h->seleccion && in_array($visitante->id_visita, $h->seleccion->visitantes_seleccionados ?? []) ? 'checked' : '' }}
                                           class="h-4 w-4 border-white/20 bg-white/5 text-[#4978eb] focus:ring-[#4978eb]/40" />
                                    {{ $visitante->nombre }}
                                </label>
                            @endforeach
                        @endif

                        <label class="flex items-center gap-2 text-sm text-slate-300">
                            <input type="radio"
                                   form="{{ $formId }}"
                                   name="recipiente"
                                   value="otro"
                                   {{ ($h->seleccion->otro_texto ?? null) ? 'checked' : '' }}
                                   class="h-4 w-4 border-white/20 bg-white/5 text-[#4978eb] focus:ring-[#4978eb]/40" />
                            <input type="text"
                                   form="{{ $formId }}"
                                   name="otro"
                                   value="{{ $h->seleccion->otro_texto ?? '' }}"
                                   maxlength="150"
                                   placeholder="Otro (nombre)"
                                   class="flex-1 rounded-lg border border-white/10 bg-white/5 px-2 py-1 text-sm text-white placeholder-slate-500 focus:border-[#4978eb] focus:outline-none focus:ring-1 focus:ring-[#4978eb]/20" />
                        </label>
                    </div>

                    <form id="{{ $formId }}" action="/vinculacion/habitaciones/comida" method="POST" class="space-y-3 pt-2 border-t border-white/10">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="piso" value="{{ $h->piso }}" />
                        <input type="hidden" name="habitacion" value="{{ $h->habitacion }}" />

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1">Observaciones</label>
                            <textarea name="observaciones" rows="2" maxlength="500"
                                      class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder-slate-500 focus:border-[#4978eb] focus:outline-none focus:ring-2 focus:ring-[#4978eb]/20">{{ $h->seleccion->observaciones ?? '' }}</textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1">Desayuno</label>
                            <select name="desayuno" {{ empty($opcionesSemana->desayuno_opciones) ? 'disabled' : '' }}
                                    class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white focus:border-[#4978eb] focus:outline-none focus:ring-2 focus:ring-[#4978eb]/20">
                                @if (empty($opcionesSemana->desayuno_opciones))
                                    <option value="">Sin opciones definidas</option>
                                @else
                                    <option value="">Sin elegir</option>
                                    @foreach ($opcionesSemana->desayuno_opciones as $opcion)
                                        <option value="{{ $opcion }}" {{ ($h->cortesia->platillo_desayuno ?? null) === $opcion ? 'selected' : '' }}>{{ $opcion }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1">Cena</label>
                            <select name="cena" {{ empty($opcionesSemana->cena_opciones) ? 'disabled' : '' }}
                                    class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white focus:border-[#4978eb] focus:outline-none focus:ring-2 focus:ring-[#4978eb]/20">
                                @if (empty($opcionesSemana->cena_opciones))
                                    <option value="">Sin opciones definidas</option>
                                @else
                                    <option value="">Sin elegir</option>
                                    @foreach ($opcionesSemana->cena_opciones as $opcion)
                                        <option value="{{ $opcion }}" {{ ($h->cortesia->platillo_cena ?? null) === $opcion ? 'selected' : '' }}>{{ $opcion }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1">Bebida</label>
                            <select name="bebida"
                                    class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white focus:border-[#4978eb] focus:outline-none focus:ring-2 focus:ring-[#4978eb]/20">
                                <option value="">Sin elegir</option>
                                @foreach ($opcionesBebida as $opcion)
                                    <option value="{{ $opcion }}" {{ ($h->cortesia->bebida ?? null) === $opcion ? 'selected' : '' }}>{{ $opcion }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="w-full rounded-xl bg-[#4978eb] px-3 py-2 text-sm font-semibold text-white shadow-md shadow-[#4978eb]/30 transition hover:bg-[#3a63d1]">
                            Guardar
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
