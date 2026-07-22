@extends('layouts.app')

@section('content')
@php
    $totalVisitas = $porEdificio->sum('total');
    $edificioTop = $porEdificio->sortByDesc('total')->first();
    $tipoTop = $porTipo->sortByDesc('total')->first();

    $periodos = $etiquetasPeriodo;

    // Torre de Consultorios ya no fuerza 'rep-medico': el tipo real ahí es
    // 'tipo_acceso' ('visitante', 'paciente', 'proveedor', ...), así que basta con
    // capitalizar cualquier valor salvo 'sin-datos'.
    $etiquetaTipo = fn ($t) => match ($t) {
        'sin-datos' => 'Sin datos',
        'ex_empleado' => 'Ex empleado',
        default => ucfirst($t),
    };
@endphp

<div class="max-w-6xl mx-auto">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-400">Reportes · {{ $areaNombre }}</p>
            <h1 class="text-2xl font-bold text-white">Reportes Estadísticos Gráficos</h1>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @foreach ($periodos as $valor => $etiqueta)
                <a href="{{ request()->fullUrlWithQuery(['periodo' => $valor]) }}"
                   class="rounded-full px-4 py-1.5 text-xs font-semibold transition
                          {{ $periodo === $valor ? 'bg-[#4978eb] text-white shadow-md shadow-[#4978eb]/30' : 'bg-white/5 text-slate-300 hover:bg-white/10' }}">
                    {{ $etiqueta }}
                </a>
            @endforeach

            <a href="/reportes-graficos/pdf?periodo={{ $periodo }}"
               class="ml-1 inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/5 px-4 py-1.5 text-xs font-semibold text-slate-200 transition hover:border-[#4978eb]/50 hover:bg-[#4978eb]/10">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-3.5 w-3.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" />
                </svg>
                Descargar PDF
            </a>

            <a href="/reportes-graficos/csv?periodo={{ $periodo }}"
               class="inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/5 px-4 py-1.5 text-xs font-semibold text-slate-200 transition hover:border-[#4978eb]/50 hover:bg-[#4978eb]/10">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-3.5 w-3.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" />
                </svg>
                Exportar CSV
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 mb-6 sm:grid-cols-3">
        <div class="rounded-2xl border border-white/10 bg-[#081536] p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Visitas totales</p>
            <p class="mt-2 text-3xl font-bold text-white">{{ number_format($totalVisitas) }}</p>
        </div>
        <div class="rounded-2xl border border-white/10 bg-[#081536] p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Edificio con más afluencia</p>
            <p class="mt-2 text-xl font-bold text-white">{{ $edificioTop->edificio ?? 'Sin datos' }}</p>
            <p class="text-sm text-slate-400">{{ $edificioTop ? number_format($edificioTop->total) . ' visitas' : '' }}</p>
        </div>
        <div class="rounded-2xl border border-white/10 bg-[#081536] p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Tipo de visitante frecuente</p>
            <p class="mt-2 text-xl font-bold text-white">{{ $tipoTop ? $etiquetaTipo($tipoTop->tipo_visitante) : 'Sin datos' }}</p>
            <p class="text-sm text-slate-400">{{ $tipoTop ? number_format($tipoTop->total) . ' visitas' : '' }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <div class="rounded-2xl border border-white/10 bg-[#081536] p-6">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400 mb-1">Visitas por edificio</p>
            <h2 class="text-lg font-semibold text-white mb-4">Visitas Totales</h2>
            <div class="relative h-72">
                <canvas id="graficoEdificios"></canvas>
            </div>
        </div>

        <div class="rounded-2xl border border-white/10 bg-[#081536] p-6">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400 mb-1">Composición</p>
            <h2 class="text-lg font-semibold text-white mb-4">Distribución por Tipo de Visitante</h2>
            <div class="relative h-72">
                <canvas id="graficoTipos"></canvas>
            </div>
        </div>

    </div>

    @if ($porPisoProveedor->isNotEmpty() || $porPisoFamiliar->isNotEmpty() || $porPisoTorre->isNotEmpty())
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
            @if ($porPisoProveedor->isNotEmpty())
                <div class="rounded-2xl border border-white/10 bg-[#081536] p-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400 mb-1">Proveedores</p>
                    <h2 class="text-lg font-semibold text-white mb-4">Visitas por Piso de Destino</h2>
                    <div class="relative h-72">
                        <canvas id="graficoPisosProveedor"></canvas>
                    </div>
                </div>
            @endif

            @if ($porPisoFamiliar->isNotEmpty())
                <div class="rounded-2xl border border-white/10 bg-[#081536] p-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400 mb-1">Familiares</p>
                    <h2 class="text-lg font-semibold text-white mb-4">Visitas por Piso de Habitación</h2>
                    <div class="relative h-72">
                        <canvas id="graficoPisosFamiliar"></canvas>
                    </div>
                </div>
            @endif

            @if ($porPisoTorre->isNotEmpty())
                <div class="rounded-2xl border border-white/10 bg-[#081536] p-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400 mb-1">Representantes médicos</p>
                    <h2 class="text-lg font-semibold text-white mb-4">Visitas por Piso de Consultorio</h2>
                    <div class="relative h-72">
                        <canvas id="graficoPisosTorre"></canvas>
                    </div>
                </div>
            @endif
        </div>
    @endif

    @if ($consultoriosMasVisitados->isNotEmpty() || $doctoresMasVisitados->isNotEmpty())
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
            @if ($consultoriosMasVisitados->isNotEmpty())
                <div class="rounded-2xl border border-white/10 bg-[#081536] p-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400 mb-1">Torre de Consultorios</p>
                    <h2 class="text-lg font-semibold text-white mb-4">Consultorios Más Visitados</h2>
                    <div class="relative h-72">
                        <canvas id="graficoConsultoriosTop"></canvas>
                    </div>
                </div>
            @endif

            @if ($doctoresMasVisitados->isNotEmpty())
                <div class="rounded-2xl border border-white/10 bg-[#081536] p-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400 mb-1">Torre de Consultorios</p>
                    <h2 class="text-lg font-semibold text-white mb-4">Médicos Más Visitados</h2>
                    <div class="relative h-72">
                        <canvas id="graficoDoctoresTop"></canvas>
                    </div>
                </div>
            @endif
        </div>
    @endif

    <div class="rounded-2xl border border-white/10 bg-[#081536] p-6 mt-6">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400 mb-1">Listado</p>
                <h2 class="text-lg font-semibold text-white">Detalle de visitas ({{ $periodos[$periodo] ?? $periodo }})</h2>
            </div>
            <div class="relative">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"
                     class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input type="text" id="buscador-detalle" placeholder="Buscar por nombre o folio..."
                       class="w-64 rounded-xl border border-white/10 bg-white/5 py-2 pl-9 pr-4 text-sm text-white placeholder-slate-500 focus:border-[#4978eb] focus:outline-none focus:ring-2 focus:ring-[#4978eb]/20" />
            </div>
        </div>

        @if ($detalleVisitas->isEmpty())
            <p class="rounded-xl border border-white/10 bg-white/5 px-4 py-8 text-center text-sm text-slate-400">
                No hay visitas registradas en este periodo.
            </p>
        @else
            <div class="max-h-[28rem] overflow-y-auto rounded-xl border border-white/10">
                <table class="w-full text-left text-sm">
                    <thead class="sticky top-0 bg-[#0b1b40] text-xs font-semibold uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="px-4 py-3">Folio</th>
                            <th class="px-4 py-3">Nombre</th>
                            <th class="px-4 py-3">Tipo</th>
                            <th class="px-4 py-3">Edificio</th>
                            <th class="px-4 py-3">Detalle</th>
                            <th class="px-4 py-3">Piso</th>
                            <th class="px-4 py-3">Entrada</th>
                            <th class="px-4 py-3">Salida</th>
                            <th class="px-4 py-3">Estado</th>
                        </tr>
                    </thead>
                    <tbody id="cuerpo-detalle" class="divide-y divide-white/5">
                        @foreach ($detalleVisitas as $dv)
                            <tr class="fila-detalle text-slate-300"
                                data-busqueda="{{ Str::lower($dv->nombre_visitante.' '.$dv->folio) }}">
                                <td class="px-4 py-2.5 font-mono text-xs font-bold text-[#9db6f7]">{{ $dv->folio ?? 'N/A' }}</td>
                                <td class="px-4 py-2.5 text-white">{{ $dv->nombre_visitante ?? 'N/A' }}</td>
                                <td class="px-4 py-2.5">{{ $etiquetaTipo($dv->tipo_visitante) }}</td>
                                <td class="px-4 py-2.5">{{ $dv->edificio }}</td>
                                <td class="px-4 py-2.5">{{ $dv->detalle ?? 'N/A' }}</td>
                                <td class="px-4 py-2.5">{{ $dv->piso ?? '—' }}</td>
                                <td class="px-4 py-2.5 whitespace-nowrap">{{ $dv->fecha_entrada ? date('d/m/Y H:i', strtotime($dv->fecha_entrada)) : 'N/A' }}</td>
                                <td class="px-4 py-2.5 whitespace-nowrap">{{ $dv->fecha_salida ? date('d/m/Y H:i', strtotime($dv->fecha_salida)) : ($dv->estado === 'activa' ? 'En curso' : 'N/A') }}</td>
                                <td class="px-4 py-2.5">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $dv->estado === 'activa' ? 'bg-emerald-500/15 text-emerald-300' : 'bg-white/5 text-slate-400' }}">
                                        {{ $dv->estado }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="mt-3 text-xs text-slate-500">{{ number_format($detalleVisitas->count()) }} registros en total.</p>
        @endif
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    const PALETTE = ['#4978eb', '#199e70', '#c98500', '#9085e9', '#e66767', '#d95926'];
    const INK_SECONDARY = '#c3c2b7';
    const INK_MUTED = '#8ba0c9';
    const GRIDLINE = 'rgba(255, 255, 255, 0.06)';

    Chart.defaults.font.family = "system-ui, -apple-system, 'Segoe UI', sans-serif";
    Chart.defaults.color = INK_SECONDARY;

    const tooltipStyle = {
        backgroundColor: '#0b1b40',
        titleColor: '#ffffff',
        bodyColor: INK_SECONDARY,
        borderColor: 'rgba(255, 255, 255, 0.1)',
        borderWidth: 1,
        padding: 10,
        cornerRadius: 8,
        displayColors: true,
        usePointStyle: true,
    };

    const edificios = {!! json_encode($porEdificio->pluck('edificio')) !!};
    const totalesEdificios = {!! json_encode($porEdificio->pluck('total')) !!};

    const valueOnBarTip = {
        id: 'valueOnBarTip',
        afterDatasetsDraw(chart) {
            const { ctx } = chart;
            chart.getDatasetMeta(0).data.forEach((bar, i) => {
                const value = chart.data.datasets[0].data[i];
                ctx.save();
                ctx.fillStyle = '#ffffff';
                ctx.font = '600 12px system-ui, -apple-system, sans-serif';
                ctx.textAlign = 'center';
                ctx.fillText(value, bar.x, bar.y - 8);
                ctx.restore();
            });
        }
    };

    new Chart(document.getElementById('graficoEdificios'), {
        type: 'bar',
        data: {
            labels: edificios,
            datasets: [{
                label: 'Cantidad de Visitas',
                data: totalesEdificios,
                backgroundColor: PALETTE.slice(0, edificios.length),
                borderRadius: { topLeft: 4, topRight: 4, bottomLeft: 0, bottomRight: 0 },
                borderSkipped: 'bottom',
                maxBarThickness: 40,
            }]
        },
        plugins: [valueOnBarTip],
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: { padding: { top: 16 } },
            plugins: {
                legend: { display: false },
                tooltip: tooltipStyle,
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: INK_MUTED },
                    border: { color: 'rgba(255,255,255,0.1)' },
                },
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1, color: INK_MUTED, precision: 0 },
                    grid: { color: GRIDLINE },
                    border: { display: false },
                }
            }
        }
    });

    const tipos = {!! json_encode($porTipo->pluck('tipo_visitante')->map($etiquetaTipo)) !!};
    const totalesTipos = {!! json_encode($porTipo->pluck('total')) !!};
    const totalTipos = totalesTipos.reduce((a, b) => a + b, 0);

    // Total al centro de la dona (cifra héroe del conjunto)
    const centerTotal = {
        id: 'centerTotal',
        afterDraw(chart) {
            const { ctx, chartArea: { top, bottom, left, right } } = chart;
            const x = (left + right) / 2;
            const y = (top + bottom) / 2;
            ctx.save();
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillStyle = '#ffffff';
            ctx.font = '700 24px system-ui, -apple-system, sans-serif';
            ctx.fillText(totalTipos, x, y - 8);
            ctx.fillStyle = INK_MUTED;
            ctx.font = '600 11px system-ui, -apple-system, sans-serif';
            ctx.fillText('TOTAL', x, y + 14);
            ctx.restore();
        }
    };

    new Chart(document.getElementById('graficoTipos'), {
        type: 'doughnut',
        data: {
            labels: tipos,
            datasets: [{
                data: totalesTipos,
                backgroundColor: PALETTE.slice(0, tipos.length),
                borderColor: '#081536',
                borderWidth: 3,
                hoverOffset: 6,
            }]
        },
        plugins: [centerTotal],
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '68%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: INK_SECONDARY,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        padding: 16,
                        boxWidth: 8,
                    }
                },
                tooltip: tooltipStyle,
            }
        }
    });

    // Barra horizontal reutilizable para los desgloses "visitas por piso"
    function graficoPorPiso(canvasId, labels, data, seriesLabel) {
        new Chart(document.getElementById(canvasId), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: seriesLabel,
                    data: data,
                    backgroundColor: PALETTE.slice(0, labels.length),
                    borderRadius: { topLeft: 0, topRight: 4, bottomLeft: 0, bottomRight: 4 },
                    borderSkipped: 'left',
                    maxBarThickness: 28,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: tooltipStyle,
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, color: INK_MUTED, precision: 0 },
                        grid: { color: GRIDLINE },
                        border: { display: false },
                    },
                    y: {
                        grid: { display: false },
                        ticks: { color: INK_MUTED },
                        border: { color: 'rgba(255,255,255,0.1)' },
                    }
                }
            }
        });
    }

    @if ($porPisoProveedor->isNotEmpty())
        graficoPorPiso(
            'graficoPisosProveedor',
            {!! json_encode($porPisoProveedor->pluck('piso')) !!},
            {!! json_encode($porPisoProveedor->pluck('total')) !!},
            'Visitas de proveedores'
        );
    @endif

    @if ($porPisoFamiliar->isNotEmpty())
        graficoPorPiso(
            'graficoPisosFamiliar',
            {!! json_encode($porPisoFamiliar->pluck('piso')) !!},
            {!! json_encode($porPisoFamiliar->pluck('total')) !!},
            'Visitas de familiares'
        );
    @endif

    @if ($porPisoTorre->isNotEmpty())
        graficoPorPiso(
            'graficoPisosTorre',
            {!! json_encode($porPisoTorre->pluck('piso')) !!},
            {!! json_encode($porPisoTorre->pluck('total')) !!},
            'Representantes médicos'
        );
    @endif

    @if ($consultoriosMasVisitados->isNotEmpty())
        graficoPorPiso(
            'graficoConsultoriosTop',
            {!! json_encode($consultoriosMasVisitados->pluck('consultorio')) !!},
            {!! json_encode($consultoriosMasVisitados->pluck('total')) !!},
            'Visitas'
        );
    @endif

    @if ($doctoresMasVisitados->isNotEmpty())
        graficoPorPiso(
            'graficoDoctoresTop',
            {!! json_encode($doctoresMasVisitados->pluck('medico')) !!},
            {!! json_encode($doctoresMasVisitados->pluck('total')) !!},
            'Visitas'
        );
    @endif

    (function () {
        const buscador = document.getElementById('buscador-detalle');
        if (!buscador) return;

        const filas = document.querySelectorAll('#cuerpo-detalle .fila-detalle');
        const normalizar = (texto) => texto.normalize('NFD').replace(new RegExp('[\\u0300-\\u036f]', 'g'), '');

        buscador.addEventListener('input', function () {
            const termino = normalizar(buscador.value.trim().toLowerCase());
            filas.forEach(function (fila) {
                const coincide = !termino || normalizar(fila.dataset.busqueda || '').includes(termino);
                fila.style.display = coincide ? '' : 'none';
            });
        });
    })();
</script>
@endsection
