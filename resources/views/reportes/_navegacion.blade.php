@php
    $pestanas = [
        ['Matrículas', 'reportes.matriculas', 'reportes.matriculas*'],
        ['Ocupación de grupos', 'reportes.ocupacion', 'reportes.ocupacion*'],
        ['Catálogo y oferta', 'reportes.catalogo', 'reportes.catalogo*'],
    ];
@endphp
<nav aria-label="Reportes" class="mb-6 border-b border-slate-200">
    <ul class="flex flex-wrap gap-1 -mb-px">
        @foreach ($pestanas as [$etiqueta, $ruta, $patron])
            @php $activa = request()->routeIs($patron); @endphp
            <li>
                <a href="{{ route($ruta) }}"
                    class="inline-block px-4 py-2.5 text-sm font-medium border-b-2 rounded-t-lg transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 {{ $activa ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300' }}"
                    @if ($activa) aria-current="page" @endif>{{ $etiqueta }}</a>
            </li>
        @endforeach
    </ul>
</nav>
