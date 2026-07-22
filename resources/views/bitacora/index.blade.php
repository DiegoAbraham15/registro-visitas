@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-400">Administración</p>
        <h1 class="text-2xl font-bold text-white">Bitácora</h1>
    </div>

    <div class="rounded-2xl border border-white/10 bg-[#081536] overflow-x-auto">
        <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-400 p-6 pb-0">Bitácora de actividad</h2>

        @include('bitacora._tabla', ['bitacora' => $bitacora])
    </div>
</div>
@endsection
