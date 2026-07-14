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
    $valorActual = old('detalle', $visita->detalle);
    $pisoActual = old('piso', $pisoActual);
@endphp

<div class="max-w-xl mx-auto">
    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-400">Registro de visitas</p>
        <h1 class="text-2xl font-bold text-white">Editar visita</h1>
    </div>

    @if ($errors->any())
        <div class="mb-6 rounded-2xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-300">
            <ul class="list-disc pl-4 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-2xl border border-white/10 bg-[#081536] p-6">
        <div class="mb-5 flex flex-wrap gap-2">
            <span class="rounded-full bg-white/5 px-3 py-1 text-xs font-semibold capitalize text-slate-300">{{ $visita->tipo_real }}</span>
            <span class="rounded-full bg-white/5 px-3 py-1 text-xs font-mono font-bold text-[#9db6f7]">{{ $visita->folio ?? 'N/A' }}</span>
            <span class="rounded-full bg-white/5 px-3 py-1 text-xs text-slate-400">
                Entrada: {{ $visita->fecha_entrada ? date('d/m/Y H:i', strtotime($visita->fecha_entrada)) : 'N/A' }}
            </span>
        </div>

        <form action="/visitas/{{ $visita->id_visita }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">Nombre</label>
                <input type="text" name="nombre_visitante" value="{{ old('nombre_visitante', $visita->nombre_visitante) }}" required maxlength="150"
                       class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder-slate-500 focus:border-[#4978eb] focus:outline-none focus:ring-2 focus:ring-[#4978eb]/20" />
            </div>

            @if (!empty($opcionesPorPiso))
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">Piso</label>
                    <select id="piso-select" name="piso" required
                            class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white focus:border-[#4978eb] focus:outline-none focus:ring-2 focus:ring-[#4978eb]/20">
                        <option value="" disabled {{ $pisoActual ? '' : 'selected' }}>Selecciona un piso</option>
                        @foreach ($opcionesPorPiso as $piso => $valores)
                            <option value="{{ $piso }}" {{ $pisoActual === $piso ? 'selected' : '' }}>{{ $piso }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">
                        {{ $detalleLabel[$visita->tipo_real] ?? 'Detalle' }}
                    </label>
                    <select id="detalle-select" name="detalle" required
                            class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white focus:border-[#4978eb] focus:outline-none focus:ring-2 focus:ring-[#4978eb]/20">
                        <option value="" disabled {{ $valorActual ? '' : 'selected' }}>Selecciona primero un piso</option>
                        @if ($pisoActual && isset($opcionesPorPiso[$pisoActual]))
                            @foreach ($opcionesPorPiso[$pisoActual] as $valor)
                                <option value="{{ $valor }}" {{ $valorActual === $valor ? 'selected' : '' }}>{{ $valor }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <script>
                    (function () {
                        const OPCIONES_POR_PISO = {!! json_encode($opcionesPorPiso) !!};
                        const pisoSelect = document.getElementById('piso-select');
                        const detalleSelect = document.getElementById('detalle-select');

                        pisoSelect.addEventListener('change', function () {
                            const valores = OPCIONES_POR_PISO[this.value] || [];
                            detalleSelect.innerHTML = '<option value="" disabled selected>Selecciona una opción</option>' +
                                valores.map(v => `<option value="${v}">${v}</option>`).join('');
                        });
                    })();
                </script>
            @else
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">
                        {{ $detalleLabel[$visita->tipo_real] ?? 'Detalle' }}
                    </label>
                    <input type="text" name="detalle" value="{{ $valorActual }}" maxlength="100"
                           class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder-slate-500 focus:border-[#4978eb] focus:outline-none focus:ring-2 focus:ring-[#4978eb]/20" />
                </div>
            @endif

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">Estado</label>
                <select name="estado" required
                        class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white focus:border-[#4978eb] focus:outline-none focus:ring-2 focus:ring-[#4978eb]/20">
                    <option value="activa" {{ old('estado', $visita->estado) === 'activa' ? 'selected' : '' }}>Activa</option>
                    <option value="finalizada" {{ old('estado', $visita->estado) === 'finalizada' ? 'selected' : '' }}>Finalizada</option>
                </select>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <a href="/dashboard_area" class="flex-1 text-center rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm font-semibold text-slate-300 transition hover:bg-white/10">
                    Cancelar
                </a>
                <button type="submit" class="flex-1 rounded-xl bg-[#4978eb] px-4 py-3 text-sm font-semibold text-white shadow-md shadow-[#4978eb]/30 transition hover:bg-[#3a63d1]">
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
