@use('App\Support\Reporte')
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $reporte->titulo }}</title>
    {{-- CSS en línea: el PDF no usa Vite, Tailwind, imágenes ni recursos remotos. --}}
    <style>
        @page { margin: 18mm 14mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #1e293b; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        h2 { font-size: 12px; margin: 16px 0 6px; }
        .generado { color: #64748b; margin: 0 0 12px; }
        .aviso { border: 1px solid #fcd34d; background: #fffbeb; color: #92400e; padding: 8px; margin: 0 0 12px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        th, td { border: 1px solid #cbd5e1; padding: 4px 6px; text-align: left; vertical-align: top; }
        th { background: #f1f5f9; font-weight: bold; }
        td.numero, th.numero { text-align: right; }
        table.datos th { width: 35%; }
    </style>
</head>
<body>
    <h1>{{ $reporte->titulo }}</h1>
    <p class="generado">Generado el {{ $reporte->fechaGeneracion() }}</p>

    <h2>Filtros aplicados</h2>
    <table class="datos">
        <tbody>
            @foreach ($reporte->filtros as $filtro)
                <tr><th scope="row">{{ $filtro['etiqueta'] }}</th><td>{{ $filtro['valor'] }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <h2>Totales</h2>
    <table class="datos">
        <tbody>
            @foreach ($reporte->totales as $total)
                <tr><th scope="row">{{ $total['etiqueta'] }}</th><td>{{ $total['valor'] }}</td></tr>
            @endforeach
        </tbody>
    </table>

    @if ($reporte->sinDatos)
        <p class="aviso">{{ Reporte::AVISO_SIN_DATOS }}</p>
    @endif

    @foreach ($reporte->secciones as $seccion)
        <h2>{{ $seccion['titulo'] }}</h2>
        <table>
            <thead>
                <tr>
                    @foreach ($seccion['columnas'] as $columna)
                        <th scope="col" class="{{ in_array($columna['tipo'], ['entero', 'porcentaje']) ? 'numero' : '' }}">{{ $columna['titulo'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($seccion['filas'] as $fila)
                    <tr>
                        @foreach ($seccion['columnas'] as $i => $columna)
                            <td class="{{ in_array($columna['tipo'], ['entero', 'porcentaje']) ? 'numero' : '' }}">{{ Reporte::formatear($fila[$i], $columna['tipo']) }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($seccion['columnas']) }}">{{ $seccion['vacio'] ?? Reporte::AVISO_SIN_DATOS }}</td></tr>
                @endforelse
            </tbody>
        </table>
    @endforeach
</body>
</html>
