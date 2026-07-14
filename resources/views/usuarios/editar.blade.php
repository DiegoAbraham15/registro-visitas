@extends('layouts.app')

@section('content')
@php
    $areaLabels = [
        'hospital' => 'Hospital',
        'consultorios' => 'Torre de Consultorios',
        'cafeteria' => 'Cafetería',
    ];
@endphp

<div class="max-w-xl mx-auto">
    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-400">Administración</p>
        <h1 class="text-2xl font-bold text-white">Editar usuario</h1>
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
        <form action="/usuarios/{{ $usuario->id }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">Nombre</label>
                <input type="text" name="name" value="{{ old('name', $usuario->name) }}" required
                       class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder-slate-500 focus:border-[#4978eb] focus:outline-none focus:ring-2 focus:ring-[#4978eb]/20" />
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">Correo electrónico</label>
                <input type="email" name="email" value="{{ old('email', $usuario->email) }}" required
                       class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder-slate-500 focus:border-[#4978eb] focus:outline-none focus:ring-2 focus:ring-[#4978eb]/20" />
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">Nueva contraseña</label>
                <input type="password" name="password" minlength="8" placeholder="Dejar en blanco para no cambiarla"
                       class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder-slate-500 focus:border-[#4978eb] focus:outline-none focus:ring-2 focus:ring-[#4978eb]/20" />
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">Área asignada</label>
                <select name="area" required
                        class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white focus:border-[#4978eb] focus:outline-none focus:ring-2 focus:ring-[#4978eb]/20">
                    @foreach ($areaLabels as $value => $label)
                        <option value="{{ $value }}" {{ old('area', $usuario->area) === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="space-y-3 pt-2">
                <label class="flex items-center gap-3 text-sm text-slate-300">
                    <input type="checkbox" name="es_admin" value="1" {{ old('es_admin', $usuario->es_admin) ? 'checked' : '' }}
                           class="h-4 w-4 rounded border-white/20 bg-white/5 text-[#4978eb] focus:ring-[#4978eb]/40" />
                    Permitir administrar usuarios
                </label>
                <label class="flex items-center gap-3 text-sm text-slate-300">
                    <input type="checkbox" name="acceso_reportes" value="1" {{ old('acceso_reportes', $usuario->acceso_reportes) ? 'checked' : '' }}
                           class="h-4 w-4 rounded border-white/20 bg-white/5 text-[#4978eb] focus:ring-[#4978eb]/40" />
                    Permitir acceso a Reportes
                </label>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <a href="/usuarios" class="flex-1 text-center rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm font-semibold text-slate-300 transition hover:bg-white/10">
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
