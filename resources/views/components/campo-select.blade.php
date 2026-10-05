@props([
    'nombre',
    'etiqueta',
    'opciones' => [],
    'valor' => null,
    'requerido' => false,
    'ayuda' => null,
    'total' => null, // total real de opciones en la base; si es mayor a las cargadas, se avisa del recorte
])
@php
    $actual = old($nombre, $valor);
    $mostradas = collect($opciones)->keys()->filter(fn ($k) => (string) $k !== '')->count();
    $invalido = $errors->has($nombre);
@endphp
<div class="campo">
    <label for="{{ $nombre }}" class="campo-etiqueta">
        {{ $etiqueta }}
        @if ($requerido)<span class="campo-requerido" aria-hidden="true"></span><span class="sr-only">(obligatorio)</span>@endif
        @if ($ayuda)<span class="campo-ayuda">{{ $ayuda }}</span>@endif
    </label>
    <div class="campo-caja">
        <select
            id="{{ $nombre }}"
            name="{{ $nombre }}"
            @required($requerido)
            {{ $attributes->merge(['class' => 'campo-control']) }}
            @if ($invalido) aria-invalid="true" aria-describedby="{{ $nombre }}-error" @endif
        >
            @foreach ($opciones as $clave => $texto)
                <option value="{{ $clave }}" @selected((string) $actual === (string) $clave)>{{ $texto }}</option>
            @endforeach
        </select>
        <x-icono nombre="chevron" class="campo-flecha" />
    </div>
    @if ($total && $total > $mostradas)
        <p class="campo-nota">Se muestran las primeras {{ $mostradas }} de {{ $total }} opciones.</p>
    @endif
    @error($nombre)
        <p class="campo-error" id="{{ $nombre }}-error"><x-icono nombre="alerta" /><span>{{ $message }}</span></p>
    @enderror
</div>
