@props([
    'nombre',
    'etiqueta',
    'valor' => null,
    'requerido' => false,
    'ayuda' => null,
    'filas' => 3,
    'limite' => null, // clave de config/limites.php, ej. "programas.descripcion"
])
@php
    $max = $limite ? config('limites.'.$limite) : null;
    $invalido = $errors->has($nombre);
@endphp
<div class="campo">
    <label for="{{ $nombre }}" class="campo-etiqueta">
        {{ $etiqueta }}
        @if ($requerido)<span class="campo-requerido" aria-hidden="true"></span><span class="sr-only">(obligatorio)</span>@endif
        @if ($ayuda)<span class="campo-ayuda">{{ $ayuda }}</span>@endif
    </label>
    <textarea
        id="{{ $nombre }}"
        name="{{ $nombre }}"
        rows="{{ $filas }}"
        @required($requerido)
        @if ($max) maxlength="{{ $max }}" @endif
        {{ $attributes->merge(['class' => 'campo-control']) }}
        @if ($invalido) aria-invalid="true" aria-describedby="{{ $nombre }}-error" @endif
    >{{ old($nombre, $valor) }}</textarea>
    @if ($max)
        <p class="campo-conteo" data-contador="{{ $nombre }}" aria-hidden="true">{{ mb_strlen((string) old($nombre, $valor)) }}/{{ $max }}</p>
        @once
            @push('scripts')
                <script>
                    document.addEventListener('input', (e) => {
                        const t = e.target;
                        if (!t.matches('textarea[maxlength]')) return;
                        const c = document.querySelector('[data-contador="' + t.id + '"]');
                        if (c) c.textContent = t.value.length + '/' + t.maxLength;
                    });
                </script>
            @endpush
        @endonce
    @endif
    @error($nombre)
        <p class="campo-error" id="{{ $nombre }}-error"><x-icono nombre="alerta" /><span>{{ $message }}</span></p>
    @enderror
</div>
