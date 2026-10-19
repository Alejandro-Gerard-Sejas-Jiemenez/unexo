@props(['elemento'])

@php
    $activo = request()->routeIs($elemento['patron']);
@endphp

<a href="{{ $elemento['url'] }}" title="{{ $elemento['etiqueta'] }}"
    class="sb-fila {{ $activo ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"
    @if ($activo) aria-current="page" @endif>
    <span class="sb-icono">
        <x-icono :nombre="$elemento['icono']" class="w-5 h-5 {{ $activo ? 'text-white' : 'text-slate-400' }}" />
    </span>
    <span class="sb-etiqueta">{{ $elemento['etiqueta'] }}</span>
</a>
