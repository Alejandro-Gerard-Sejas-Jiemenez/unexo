@props(['elemento'])

@php
    $subActivo = collect($elemento['items'])->contains(fn($it) => request()->routeIs($it[2]));
@endphp

<details class="group/sub sb-grupo" {{ $subActivo ? 'open' : '' }}>
    <summary title="{{ $elemento['etiqueta'] }}"
        class="sb-fila cursor-pointer list-none select-none transition-colors [&::-webkit-details-marker]:hidden {{ $subActivo ? 'text-indigo-700 bg-indigo-50/70 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
        <span class="sb-icono">
            <x-icono :nombre="$elemento['icono']" class="w-5 h-5 {{ $subActivo ? 'text-indigo-600' : 'text-slate-400' }}" />
        </span>
        <span class="sb-etiqueta flex items-center justify-between flex-1 pr-2">
            <span class="truncate">{{ $elemento['etiqueta'] }}</span>
            <x-icono nombre="chevron"
                class="w-4 h-4 text-slate-400 transition-transform duration-200 group-open/sub:rotate-180 shrink-0" />
        </span>
    </summary>

    <div class="sb-sub-lista">
        @foreach ($elemento['items'] as [$subEtiqueta, $subUrl, $subPatron, $subIcono])
            @php
                $itemActivo = request()->routeIs($subPatron);
            @endphp
            <a href="{{ $subUrl }}" title="{{ $subEtiqueta }}"
                class="sb-sub-fila {{ $itemActivo ? 'activo' : '' }}"
                @if ($itemActivo) aria-current="page" @endif>
                <span class="sb-sub-icono">
                    <x-icono :nombre="$subIcono" class="w-4 h-4" />
                </span>
                <span class="truncate">{{ $subEtiqueta }}</span>
            </a>
        @endforeach
    </div>
</details>
