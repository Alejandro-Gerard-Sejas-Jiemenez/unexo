@props([
    'nombre',
    'etiqueta',
    'tipo' => 'text',
    'valor' => null,
    'requerido' => false,
    'ayuda' => null,
    'limite' => null, // clave de config/limites.php, ej. "usuarios.nombres": mismo valor que valida el backend
    'icono' => null,  // por defecto se deduce del tipo o del nombre del campo; false = sin ícono
])
@php
    $max = $limite ? config('limites.'.$limite) : null;
    $iconos = ['email' => 'correo', 'password' => 'candado', 'url' => 'enlace', 'date' => 'calendario', 'number' => 'hash'];
    $porNombre = ['telefono' => 'telefono', 'documento_identidad' => 'documento', 'nombres' => 'usuarios', 'apellidos' => 'usuarios'];
    $ico = $icono === false ? null : ($icono ?? ($iconos[$tipo] ?? ($porNombre[$nombre] ?? null)));
    $invalido = $errors->has($nombre);
@endphp
<div class="campo">
    <label for="{{ $nombre }}" class="campo-etiqueta">
        {{ $etiqueta }}
        @if ($requerido)<span class="campo-requerido" aria-hidden="true"></span><span class="sr-only">(obligatorio)</span>@endif
        @if ($ayuda)<span class="campo-ayuda">{{ $ayuda }}</span>@endif
    </label>
    <div class="campo-caja {{ $ico ? 'con-icono' : '' }}">
        @if ($ico)<x-icono :nombre="$ico" class="campo-icono" />@endif
        <input
            type="{{ $tipo }}"
            id="{{ $nombre }}"
            name="{{ $nombre }}"
            value="{{ $tipo === 'password' ? '' : old($nombre, $valor) }}"
            @required($requerido)
            @if ($max) {{ $tipo === 'number' ? 'max='.$max : 'maxlength='.$max }} @endif
            {{ $attributes->merge(['class' => 'campo-control']) }}
            @if ($invalido) aria-invalid="true" aria-describedby="{{ $nombre }}-error" @endif
        >
        @if ($tipo === 'password')
            <button type="button" class="campo-ojo" data-ver-clave="{{ $nombre }}" aria-label="Mostrar contraseña" aria-pressed="false">
                <x-icono nombre="ojo" class="w-[1.1rem] h-[1.1rem]" data-icono-ver />
                <x-icono nombre="ojo-tachado" class="w-[1.1rem] h-[1.1rem] hidden" data-icono-ocultar />
            </button>
            @once
                @push('scripts')
                    <script>
                        document.addEventListener('click', (e) => {
                            const b = e.target.closest('[data-ver-clave]');
                            if (!b) return;
                            const campo = document.getElementById(b.dataset.verClave);
                            const ver = campo.type === 'password';
                            campo.type = ver ? 'text' : 'password';
                            b.setAttribute('aria-pressed', ver);
                            b.setAttribute('aria-label', ver ? 'Ocultar contraseña' : 'Mostrar contraseña');
                            b.querySelector('[data-icono-ver]').classList.toggle('hidden', ver);
                            b.querySelector('[data-icono-ocultar]').classList.toggle('hidden', !ver);
                        });
                    </script>
                @endpush
            @endonce
        @endif
    </div>
    @error($nombre)
        <p class="campo-error" id="{{ $nombre }}-error"><x-icono nombre="alerta" /><span>{{ $message }}</span></p>
    @enderror
</div>
