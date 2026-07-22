@extends('layouts.app')

@section('content')
@php
    $areaLabels = [
        'hospital' => 'Hospital',
        'consultorios' => 'Torre de Consultorios',
        'cafeteria' => 'Cafetería',
        'vinculacion' => 'Vinculación',
    ];
@endphp

<div class="max-w-6xl mx-auto">
    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-400">Administración</p>
        <h1 class="text-2xl font-bold text-white">Usuarios</h1>
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

    <div class="grid grid-cols-1 lg:grid-cols-[1.4fr_1fr] gap-6">

        <div>
            <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-400 mb-4">Cuentas existentes</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach ($usuarios as $usuario)
                    <div class="rounded-2xl border border-white/10 bg-[#081536] p-5 flex flex-col gap-4">
                        <div class="flex items-start gap-3">
                            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#4978eb]/20 text-sm font-bold uppercase text-[#9db6f7]">
                                {{ Str::substr($usuario->name, 0, 1) }}
                            </span>
                            <div class="min-w-0">
                                <p class="font-semibold text-white truncate">{{ $usuario->name }}</p>
                                <p class="text-xs text-slate-400 truncate">{{ $usuario->email }}</p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <span class="rounded-full bg-white/5 px-3 py-1 text-xs font-semibold text-slate-300">
                                {{ $areaLabels[$usuario->area] ?? $usuario->area }}
                            </span>
                            @if ($usuario->es_admin)
                                <span class="rounded-full bg-[#4978eb]/15 px-3 py-1 text-xs font-semibold text-[#9db6f7]">Admin</span>
                            @endif
                            @if ($usuario->acceso_reportes)
                                <span class="rounded-full bg-emerald-500/15 px-3 py-1 text-xs font-semibold text-emerald-300">Reportes</span>
                            @endif
                            @if ($usuario->acceso_vinculacion)
                                <span class="rounded-full bg-amber-500/15 px-3 py-1 text-xs font-semibold text-amber-300">Vinculación</span>
                            @endif
                            @if ($usuario->es_admin_cafeteria)
                                <span class="rounded-full bg-orange-500/15 px-3 py-1 text-xs font-semibold text-orange-300">Resumen Cafetería</span>
                            @endif
                        </div>

                        <div class="flex items-center gap-2 pt-2 border-t border-white/10">
                            <a href="/usuarios/{{ $usuario->id }}/editar"
                               class="flex-1 text-center rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-xs font-semibold text-slate-200 transition hover:border-[#4978eb]/50 hover:bg-[#4978eb]/10">
                                Editar
                            </a>
                            @if ($usuario->id !== auth()->id())
                                <form action="/usuarios/{{ $usuario->id }}" method="POST" class="flex-1"
                                      onsubmit="return confirm('¿Eliminar a {{ $usuario->name }}? Esta acción no se puede deshacer.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="w-full rounded-xl border border-red-500/20 bg-red-500/10 px-3 py-2 text-xs font-semibold text-red-300 transition hover:bg-red-500/20">
                                        Eliminar
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="rounded-2xl border border-white/10 bg-[#081536] p-6">
            <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-400 mb-4">Crear nuevo usuario</h2>

            <form action="/usuarios" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">Nombre</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder-slate-500 focus:border-[#4978eb] focus:outline-none focus:ring-2 focus:ring-[#4978eb]/20" />
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">Correo electrónico</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder-slate-500 focus:border-[#4978eb] focus:outline-none focus:ring-2 focus:ring-[#4978eb]/20" />
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">Contraseña</label>
                    <input type="password" name="password" required minlength="8"
                           class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder-slate-500 focus:border-[#4978eb] focus:outline-none focus:ring-2 focus:ring-[#4978eb]/20" />
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">Área asignada</label>
                    <select name="area" required
                            class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white focus:border-[#4978eb] focus:outline-none focus:ring-2 focus:ring-[#4978eb]/20">
                        <option value="" disabled selected>Selecciona un área</option>
                        @foreach ($areaLabels as $value => $label)
                            <option value="{{ $value }}" {{ old('area') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-3 pt-2">
                    <label class="flex items-center gap-3 text-sm text-slate-300">
                        <input type="checkbox" name="es_admin" value="1" class="h-4 w-4 rounded border-white/20 bg-white/5 text-[#4978eb] focus:ring-[#4978eb]/40" />
                        Permitir administrar usuarios
                    </label>
                    <label class="flex items-center gap-3 text-sm text-slate-300">
                        <input type="checkbox" name="acceso_reportes" value="1" checked class="h-4 w-4 rounded border-white/20 bg-white/5 text-[#4978eb] focus:ring-[#4978eb]/40" />
                        Permitir acceso a reportes
                    </label>
                    <label class="flex items-center gap-3 text-sm text-slate-300">
                        <input type="checkbox" name="acceso_vinculacion" value="1" class="h-4 w-4 rounded border-white/20 bg-white/5 text-[#4978eb] focus:ring-[#4978eb]/40" />
                        Permitir acceso a cortesias
                    </label>
                    <label class="flex items-center gap-3 text-sm text-slate-300">
                        <input type="checkbox" name="es_admin_cafeteria" value="1" class="h-4 w-4 rounded border-white/20 bg-white/5 text-[#4978eb] focus:ring-[#4978eb]/40" />
                        Permitir ver el resumen de Cafetería (solo lectura)
                    </label>
                </div>

                <button type="submit" class="w-full rounded-xl bg-[#4978eb] px-4 py-3 text-sm font-semibold text-white shadow-md shadow-[#4978eb]/30 transition hover:bg-[#3a63d1]">
                    Crear usuario
                </button>
            </form>
        </div>

    </div>

    <div class="mt-6 rounded-2xl border border-white/10 bg-[#081536] overflow-x-auto">
        <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-400 p-6 pb-0">Bitácora de actividad</h2>

        @include('bitacora._tabla', ['bitacora' => $bitacora])
    </div>
</div>
@endsection
