@extends('layouts.app')

@section('content')
@php
    // Torre de Consultorios (id_edificio=2) no tiene entrada aquí: su "detalle" siempre
    // es el consultorio, sin importar el tipo_acceso (ver App\Http\Controllers\VisitaController).
    $detalleLabel = [
        'familiar' => 'Habitación',
        'proveedor' => 'Destino',
        'postulante' => 'Puesto',
        'ex_empleado' => 'Motivo',
        'sin-datos' => 'Detalle',
    ];
    $etiquetaDetalle = (int) $visita->id_edificio === 2 ? 'Consultorio' : ($detalleLabel[$visita->tipo_real] ?? 'Detalle');
    $valorActual = old('detalle', $visita->detalle);
    $pisoActual = old('piso', $pisoActual);
    $medicoActual = old('medico', $visita->medico);
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
                        {{ $etiquetaDetalle }}
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
                        const medicoSelect = document.getElementById('medico-select');

                        pisoSelect.addEventListener('change', function () {
                            const valores = OPCIONES_POR_PISO[this.value] || [];
                            detalleSelect.innerHTML = '<option value="" disabled selected>Selecciona una opción</option>' +
                                valores.map(v => `<option value="${v}">${v}</option>`).join('');
                            if (medicoSelect) {
                                medicoSelect.innerHTML = '<option value="">Sin especificar</option>';
                            }
                        });
                    })();
                </script>
            @else
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">
                        {{ $etiquetaDetalle }}
                    </label>
                    <input type="text" name="detalle" value="{{ $valorActual }}" maxlength="100"
                           class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder-slate-500 focus:border-[#4978eb] focus:outline-none focus:ring-2 focus:ring-[#4978eb]/20" />
                </div>
            @endif

            {{-- Independiente de si el consultorio se captura con <select> o texto libre
                 arriba: siempre debe poder registrarse el médico de una visita de Torre. --}}
            @if ((int) $visita->id_edificio === 2)
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">
                        Médico
                    </label>
                    <select id="medico-select" name="medico"
                            class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white focus:border-[#4978eb] focus:outline-none focus:ring-2 focus:ring-[#4978eb]/20">
                        <option value="">Sin especificar</option>
                        @if ($valorActual && isset($medicosPorConsultorio[$valorActual]))
                            @foreach ($medicosPorConsultorio[$valorActual] as $medico)
                                <option value="{{ $medico }}" {{ $medicoActual === $medico ? 'selected' : '' }}>{{ $medico }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <script>
                    (function () {
                        const MEDICOS_POR_CONSULTORIO = {!! json_encode($medicosPorConsultorio) !!};
                        const detalleSelect = document.getElementById('detalle-select');
                        const medicoSelect = document.getElementById('medico-select');

                        // Sin <select id="detalle-select"> (consultorio como texto libre, ver
                        // rama @else de arriba) no hay de dónde escuchar el cambio: el médico
                        // se deja tal cual venga precargado, sin recalcular sus opciones.
                        if (detalleSelect && medicoSelect) {
                            detalleSelect.addEventListener('change', function () {
                                const medicos = MEDICOS_POR_CONSULTORIO[this.value] || [];
                                medicoSelect.innerHTML = '<option value="">Sin especificar</option>' +
                                    medicos.map(m => `<option value="${m}">${m}</option>`).join('');
                            });
                        }
                    })();
                </script>
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
