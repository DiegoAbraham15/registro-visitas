@if ($bitacora->isEmpty())
    <p class="text-sm text-slate-500 italic p-6">Todavía no hay registros.</p>
@else
    <table class="w-full text-sm text-left text-slate-300 mt-4">
        <thead>
            <tr class="text-xs uppercase tracking-wide text-slate-500 border-b border-white/10">
                <th class="py-3 px-6">Fecha</th>
                <th class="py-3 px-6">Usuario</th>
                <th class="py-3 px-6">Acción</th>
                <th class="py-3 px-6">Detalle</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($bitacora as $registro)
                <tr class="border-b border-white/5">
                    <td class="py-3 px-6 whitespace-nowrap">{{ $registro->created_at?->format('d/m/Y H:i') }}</td>
                    <td class="py-3 px-6">{{ $registro->usuario->name ?? 'N/A' }}</td>
                    <td class="py-3 px-6">
                        <span class="rounded-full bg-white/5 px-3 py-1 text-xs font-semibold text-slate-300">{{ $registro->accion }}</span>
                    </td>
                    <td class="py-3 px-6">{{ $registro->descripcion }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="p-6 pt-4">
        {{ $bitacora->links() }}
    </div>
@endif
