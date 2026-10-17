{{-- Dibuja un App\Support\Reporte: la misma estructura que usan el PDF y el Excel. --}}
@use('App\Support\Reporte')
<p class="text-sm text-slate-500 mb-4">Generado el {{ $reporte->fechaGeneracion() }}</p>

@if ($reporte->sinDatos)
    <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm font-medium text-amber-800" role="status">
        {{ Reporte::AVISO_SIN_DATOS }}
    </div>
@endif

{{-- Cada total es una tarjeta con su nombre y solo el número (RF-30). --}}
<dl class="mb-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    @foreach ($reporte->totales as $total)
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-5 py-4 flex items-center justify-between gap-4">
            <dt class="text-sm font-medium text-slate-600">{{ $total['etiqueta'] }}</dt>
            <dd class="text-xl font-semibold text-slate-800 tabular-nums" data-total="{{ $total['etiqueta'] }}">{{ $total['valor'] }}</dd>
        </div>
    @endforeach
</dl>

@foreach ($reporte->secciones as $seccion)
    <x-tabla :titulo="$seccion['titulo']" :columnas="$seccion['columnas']" :filas="$seccion['filas']" :vacio="$seccion['vacio'] ?? null" />
@endforeach
