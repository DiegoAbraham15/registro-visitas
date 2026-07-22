<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de Visitas</title>
    <style>
        @page {
            margin: 28px 32px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: "DejaVu Sans", sans-serif;
            color: #1c2534;
            font-size: 11px;
        }

        h1, h2, h3 {
            margin: 0;
            padding: 0;
        }

        .logo {
            height: 32px;
            width: 126px;
            margin-bottom: 10px;
        }

        .titulo {
            font-size: 20px;
            font-weight: bold;
            color: #1a2856;
            margin-top: 2px;
        }

        .subtitulo {
            font-size: 11px;
            color: #8f8d8a;
            margin-top: 6px;
        }

        .encabezado {
            border-bottom: 2px solid #1a2856;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }

        table.meta {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table.meta td {
            font-size: 10px;
            color: #8f8d8a;
            padding: 2px 0;
        }

        table.meta td.etiqueta {
            font-weight: bold;
            color: #1a2856;
            width: 110px;
        }

        .resumen {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px 0;
            margin-bottom: 18px;
        }

        .resumen td {
            width: 33.33%;
            border: 1px solid #d8dee8;
            border-radius: 4px;
            padding: 10px 12px;
            vertical-align: top;
        }

        .resumen .etiqueta {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #8f8d8a;
        }

        .resumen .valor {
            font-size: 17px;
            font-weight: bold;
            color: #1a2856;
            margin-top: 4px;
        }

        .resumen .detalle {
            font-size: 9px;
            color: #8f8d8a;
            margin-top: 2px;
        }

        .seccion {
            margin-bottom: 16px;
        }

        .seccion h2 {
            font-size: 13px;
            color: #1a2856;
            border-left: 3px solid #df1f2b;
            padding-left: 8px;
            margin-bottom: 8px;
        }

        table.datos {
            width: 100%;
            border-collapse: collapse;
        }

        table.datos th {
            background-color: #1a2856;
            color: #ffffff;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: left;
            padding: 6px 8px;
        }

        table.datos td {
            font-size: 10px;
            padding: 5px 8px;
            border-bottom: 1px solid #e5e9f0;
        }

        table.datos tr:nth-child(even) td {
            background-color: #f5f7fb;
        }

        .badge {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 8px;
            font-size: 9px;
            font-weight: bold;
        }

        .badge-activa {
            background-color: #d7f6e8;
            color: #0d7a4f;
        }

        .badge-finalizada {
            background-color: #eceff4;
            color: #5b6577;
        }

        .grid-2 {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px 0;
        }

        .grid-2 > tr > td {
            width: 50%;
            vertical-align: top;
        }

        .vacio {
            font-size: 10px;
            color: #8f8d8a;
            font-style: italic;
            padding: 8px 0;
        }

        .pie {
            margin-top: 18px;
            padding-top: 8px;
            border-top: 1px solid #e5e9f0;
            font-size: 9px;
            color: #8f8d8a;
            text-align: center;
        }
    </style>
</head>
<body>
@php
    $totalVisitas = $porEdificio->sum('total');
    $edificioTop = $porEdificio->sortByDesc('total')->first();
    $tipoTop = $porTipo->sortByDesc('total')->first();

    $etiquetaTipo = fn ($t) => match ($t) {
        'sin-datos' => 'Sin datos',
        'ex_empleado' => 'Ex empleado',
        default => ucfirst($t),
    };
@endphp

<div class="encabezado">
    <img src="{{ public_path('images/logo-medica-mia.png') }}" alt="Médica MIA" class="logo">
    <h1 class="titulo">Reporte de Visitas</h1>
    <p class="subtitulo">Sistema de Registro de Visitas Hospitalarias</p>

    <table class="meta">
        <tr>
            <td class="etiqueta">Periodo</td>
            <td>{{ $etiquetasPeriodo[$periodo] ?? $periodo }}</td>
        </tr>
        <tr>
            <td class="etiqueta">Generado</td>
            <td>{{ $generadoEn->format('d/m/Y H:i') }} por {{ $generadoPor }}</td>
        </tr>
    </table>
</div>

<table class="resumen">
    <tr>
        <td>
            <p class="etiqueta">Visitas totales</p>
            <p class="valor">{{ number_format($totalVisitas) }}</p>
        </td>
        <td>
            <p class="etiqueta">Tipo de visitante frecuente</p>
            <p class="valor">{{ $tipoTop ? $etiquetaTipo($tipoTop->tipo_visitante) : 'Sin datos' }}</p>
            <p class="detalle">{{ $tipoTop ? number_format($tipoTop->total) . ' visitas' : '' }}</p>
        </td>
    </tr>
</table>

<table class="grid-2">
    <tr>
        <td>
            <div class="seccion">
                <h2>Visitas por tipo de visitante</h2>
                @if ($porTipo->isEmpty())
                    <p class="vacio">Sin datos para este periodo.</p>
                @else
                    <table class="datos">
                        <thead>
                            <tr><th>Tipo</th><th>Visitas</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($porTipo as $fila)
                                <tr><td>{{ $etiquetaTipo($fila->tipo_visitante) }}</td><td>{{ number_format($fila->total) }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </td>
    </tr>
</table>

@if ($porPisoProveedor->isNotEmpty() || $porPisoFamiliar->isNotEmpty() || $porPisoTorre->isNotEmpty())
    <table class="grid-2">
        <tr>
            @if ($porPisoProveedor->isNotEmpty())
                <td>
                    <div class="seccion">
                        <h2>Proveedores por piso de destino</h2>
                        <table class="datos">
                            <thead><tr><th>Piso</th><th>Visitas</th></tr></thead>
                            <tbody>
                                @foreach ($porPisoProveedor as $fila)
                                    <tr><td>{{ $fila->piso }}</td><td>{{ number_format($fila->total) }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </td>
            @endif
            @if ($porPisoFamiliar->isNotEmpty())
                <td>
                    <div class="seccion">
                        <h2>Familiares por piso de habitación</h2>
                        <table class="datos">
                            <thead><tr><th>Piso</th><th>Visitas</th></tr></thead>
                            <tbody>
                                @foreach ($porPisoFamiliar as $fila)
                                    <tr><td>{{ $fila->piso }}</td><td>{{ number_format($fila->total) }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </td>
            @endif
        </tr>
    </table>


@endif

@if ($consultoriosMasVisitados->isNotEmpty() || $doctoresMasVisitados->isNotEmpty())
    <table class="grid-2">
        <tr>
            @if ($consultoriosMasVisitados->isNotEmpty())
                <td>
                    <div class="seccion">
                        <h2>Consultorios más visitados</h2>
                        <table class="datos">
                            <thead><tr><th>Consultorio</th><th>Visitas</th></tr></thead>
                            <tbody>
                                @foreach ($consultoriosMasVisitados as $fila)
                                    <tr><td>{{ $fila->consultorio }}</td><td>{{ number_format($fila->total) }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </td>
            @endif
            @if ($doctoresMasVisitados->isNotEmpty())
                <td>
                    <div class="seccion">
                        <h2>Médicos más visitados</h2>
                        <table class="datos">
                            <thead><tr><th>Médico</th><th>Visitas</th></tr></thead>
                            <tbody>
                                @foreach ($doctoresMasVisitados as $fila)
                                    <tr><td>{{ $fila->medico }}</td><td>{{ number_format($fila->total) }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </td>
            @endif
        </tr>
    </table>
@endif

<div class="seccion">
    <h2>Detalle de visitas ({{ number_format($detalleVisitas->count()) }} de {{ number_format($detalleVisitasTotal ?? $detalleVisitas->count()) }} registros)</h2>
    @if (($detalleVisitasTotal ?? 0) > $detalleVisitas->count())
        <p class="vacio">Se muestran las {{ number_format($detalleVisitas->count()) }} visitas más recientes. Hay {{ number_format($detalleVisitasTotal - $detalleVisitas->count()) }} adicionales en el histórico — consulta el listado completo en pantalla o acota el periodo.</p>
    @endif
    @if ($detalleVisitas->isEmpty())
        <p class="vacio">No hay visitas registradas en este periodo.</p>
    @else
        <table class="datos">
            <thead>
                <tr>
                    <th>Folio</th>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Edificio</th>
                    <th>Detalle</th>
                    <th>Piso</th>
                    <th>Entrada</th>
                    <th>Salida</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($detalleVisitas as $dv)
                    <tr>
                        <td>{{ $dv->folio ?? 'N/A' }}</td>
                        <td>{{ $dv->nombre_visitante ?? 'N/A' }}</td>
                        <td>{{ $etiquetaTipo($dv->tipo_visitante) }}</td>
                        <td>{{ $dv->edificio }}</td>
                        <td>{{ $dv->detalle ?? 'N/A' }}</td>
                        <td>{{ $dv->piso ?? '—' }}</td>
                        <td>{{ $dv->fecha_entrada ? date('d/m/Y H:i', strtotime($dv->fecha_entrada)) : 'N/A' }}</td>
                        <td>{{ $dv->fecha_salida ? date('d/m/Y H:i', strtotime($dv->fecha_salida)) : ($dv->estado === 'activa' ? 'En curso' : 'N/A') }}</td>
                        <td>
                            <span class="badge {{ $dv->estado === 'activa' ? 'badge-activa' : 'badge-finalizada' }}">
                                {{ $dv->estado }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

<div class="pie">
    Reporte generado automáticamente por el Sistema de Registro de Visitas · Hospital Médica MIA
</div>
</body>
</html>
