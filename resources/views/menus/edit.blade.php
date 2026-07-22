@extends('layouts.app')

@section('content')
@php
    $etiquetasDias = [
        'domingo' => 'Domingo',
        'lunes' => 'Lunes',
        'martes' => 'Martes',
        'miercoles' => 'Miércoles',
        'jueves' => 'Jueves',
        'viernes' => 'Viernes',
        'sabado' => 'Sábado',
    ];
@endphp

<div class="max-w-5xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-400">Vinculación</p>
            <h1 class="text-2xl font-bold text-white">Menú semanal</h1>
        </div>
        <a href="/vinculacion/dashboard"
           class="rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:bg-white/10">
            Volver al panel
        </a>
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

    <form action="/vinculacion/menus" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="rounded-2xl border border-white/10 bg-[#081536] p-6">
            <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-400 mb-1">Opciones de la semana</h2>
            <p class="text-xs text-slate-500 mb-4">Estas mismas opciones de desayuno y cena están disponibles todos los días de la semana.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">Opciones de desayuno</label>
                    <div id="desayuno-opciones-lista" class="space-y-2">
                        @foreach ((old('desayuno_opciones', $opciones->desayuno_opciones) ?: ['']) as $opcion)
                            <div class="flex items-center gap-2">
                                <input type="text" name="desayuno_opciones[]" value="{{ $opcion }}" maxlength="150"
                                       class="flex-1 rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm text-white placeholder-slate-500 focus:border-[#4978eb] focus:outline-none focus:ring-2 focus:ring-[#4978eb]/20" />
                                <button type="button" class="quitar-opcion shrink-0 rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-slate-400 transition hover:border-red-500/30 hover:text-red-300">&times;</button>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" data-lista="desayuno-opciones-lista" data-name="desayuno_opciones[]"
                            class="agregar-opcion mt-2 text-xs font-semibold text-[#9db6f7] transition hover:text-white">
                        + Agregar opción
                    </button>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">Opciones de cena</label>
                    <div id="cena-opciones-lista" class="space-y-2">
                        @foreach ((old('cena_opciones', $opciones->cena_opciones) ?: ['']) as $opcion)
                            <div class="flex items-center gap-2">
                                <input type="text" name="cena_opciones[]" value="{{ $opcion }}" maxlength="150"
                                       class="flex-1 rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm text-white placeholder-slate-500 focus:border-[#4978eb] focus:outline-none focus:ring-2 focus:ring-[#4978eb]/20" />
                                <button type="button" class="quitar-opcion shrink-0 rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-slate-400 transition hover:border-red-500/30 hover:text-red-300">&times;</button>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" data-lista="cena-opciones-lista" data-name="cena_opciones[]"
                            class="agregar-opcion mt-2 text-xs font-semibold text-[#9db6f7] transition hover:text-white">
                        + Agregar opción
                    </button>
                </div>
            </div>
        </div>

        <script>
            (function () {
                function crearFila(nombre) {
                    const fila = document.createElement('div');
                    fila.className = 'flex items-center gap-2';
                    fila.innerHTML = '<input type="text" name="' + nombre + '" maxlength="150" ' +
                        'class="flex-1 rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm text-white placeholder-slate-500 focus:border-[#4978eb] focus:outline-none focus:ring-2 focus:ring-[#4978eb]/20" />' +
                        '<button type="button" class="quitar-opcion shrink-0 rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-slate-400 transition hover:border-red-500/30 hover:text-red-300">&times;</button>';
                    return fila;
                }

                document.querySelectorAll('.agregar-opcion').forEach(function (boton) {
                    boton.addEventListener('click', function () {
                        document.getElementById(boton.dataset.lista).appendChild(crearFila(boton.dataset.name));
                    });
                });

                document.addEventListener('click', function (evento) {
                    if (evento.target.classList.contains('quitar-opcion')) {
                        evento.target.closest('div').remove();
                    }
                });
            })();
        </script>

        <div class="space-y-4">
            <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-400">Comida de cada día (fija)</h2>

            @foreach ($dias as $menuDia)
                <div class="rounded-2xl border border-white/10 bg-[#081536] p-6 flex flex-wrap items-center gap-4">
                    <label class="w-28 shrink-0 text-sm font-semibold text-white">{{ $etiquetasDias[$menuDia->dia] ?? $menuDia->dia }}</label>
                    <input type="text" name="dias[{{ $menuDia->dia }}][comida]"
                           value="{{ old('dias.'.$menuDia->dia.'.comida', $menuDia->comida) }}"
                           placeholder="Comida de este día"
                           class="flex-1 min-w-[200px] rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder-slate-500 focus:border-[#4978eb] focus:outline-none focus:ring-2 focus:ring-[#4978eb]/20" />
                </div>
            @endforeach
        </div>

        <button type="submit" class="w-full rounded-xl bg-[#4978eb] px-4 py-3 text-sm font-semibold text-white shadow-md shadow-[#4978eb]/30 transition hover:bg-[#3a63d1]">
            Guardar menú semanal
        </button>
    </form>
</div>
@endsection
