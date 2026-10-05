@props([
    'nombre',
    'etiqueta',
    'marcado' => false,
])
<div class="campo">
    <label class="campo-check" for="{{ $nombre }}">
        <input
            type="checkbox"
            id="{{ $nombre }}"
            name="{{ $nombre }}"
            value="1"
            @checked(old($nombre, $marcado))
            {{ $attributes }}
        >
        <span class="campo-check-texto">{{ $etiqueta }}</span>
    </label>
    @error($nombre)
        <p class="campo-error" id="{{ $nombre }}-error"><x-icono nombre="alerta" /><span>{{ $message }}</span></p>
    @enderror
</div>
