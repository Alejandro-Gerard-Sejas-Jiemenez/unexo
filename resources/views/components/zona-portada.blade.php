@props([
    'nombre' => 'imagen_portada_url',
    'portada' => null, // ruta pública de la portada actual (ej. "/storage/programas/x.jpg") o null
])
@php
    $maxKb = config('limites.programas.portada_kb');
    $mensajeError = $errors->first($nombre);
    $urlActual = $portada ? asset($portada) : null;
@endphp
<div class="campo">
    <span class="campo-etiqueta" id="{{ $nombre }}-etiqueta">Imagen de portada</span>
    <div
        class="portada-zona"
        role="group"
        aria-labelledby="{{ $nombre }}-etiqueta"
        data-portada
        data-portada-max-kb="{{ $maxKb }}"
        @if ($urlActual) data-portada-actual="{{ $urlActual }}" @endif
    >
        <img
            class="portada-vista"
            alt="Vista previa de la portada"
            data-portada-vista
            @if ($urlActual) src="{{ $urlActual }}" @else hidden @endif
        >
        @if ($urlActual)
            <p class="portada-actual">Imagen actual</p>
        @endif
        <p class="portada-ayuda" id="{{ $nombre }}-ayuda">Arrastra una imagen aquí o elige un archivo. JPG, PNG o WebP, hasta 2 MB.</p>
        <label for="{{ $nombre }}" class="sr-only">Elegir imagen de portada</label>
        <input
            type="file"
            class="sr-only"
            id="{{ $nombre }}"
            name="{{ $nombre }}"
            accept="image/jpeg,image/png,image/webp"
            tabindex="-1"
            aria-describedby="{{ $nombre }}-ayuda {{ $nombre }}-estado"
            @if ($mensajeError) aria-invalid="true" @endif
        >
        <button type="button" class="btn btn-secundario portada-boton" data-portada-elegir aria-describedby="{{ $nombre }}-ayuda {{ $nombre }}-estado">Elegir imagen</button>
        <div class="portada-estado" id="{{ $nombre }}-estado" role="status" aria-live="polite" data-portada-estado>{{ $mensajeError }}</div>
    </div>
</div>
