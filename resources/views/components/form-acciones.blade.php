@props([
    'cancelar',               // URL a la que vuelve el botón Cancelar
    'etiqueta' => 'Guardar',  // texto del botón principal
])
<div class="form-acciones">
    <a href="{{ $cancelar }}" class="btn btn-secundario">
        <x-icono nombre="cerrar" class="w-4 h-4 text-slate-400" /> Cancelar
    </a>
    <button type="submit" class="btn btn-primario">
        <x-icono nombre="guardar" class="w-4 h-4" /> {{ $etiqueta }}
    </button>
</div>
