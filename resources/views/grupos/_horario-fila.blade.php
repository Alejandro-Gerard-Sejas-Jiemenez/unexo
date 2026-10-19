@php
    $mensajes = $mensajes ?? [];
    $errorId = "horario-{$indice}-error";
    $describe = $mensajes ? "aria-describedby={$errorId} aria-invalid=true" : '';
@endphp
<div class="horario-fila" data-horario-fila data-indice="{{ $indice }}">
    <div class="campo">
        <label for="horario-{{ $indice }}-dia" class="campo-etiqueta">Día <span class="sr-only">del horario <span data-horario-numero>{{ $numero }}</span></span></label>
        <select id="horario-{{ $indice }}-dia" name="horarios[{{ $indice }}][dia_semana]" class="campo-control" {!! $describe !!}>
            <option value="">— Elige el día —</option>
            @foreach (\App\Enums\DiaSemana::cases() as $dia)
                <option value="{{ $dia->value }}" @selected((string) ($valorDia ?? '') === (string) $dia->value)>{{ $dia->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="campo">
        <label for="horario-{{ $indice }}-inicio" class="campo-etiqueta">Hora de inicio <span class="sr-only">del horario <span data-horario-numero>{{ $numero }}</span></span></label>
        <input type="time" id="horario-{{ $indice }}-inicio" name="horarios[{{ $indice }}][hora_inicio]" value="{{ $valorInicio ?? '' }}" class="campo-control" {!! $describe !!}>
    </div>
    <div class="campo">
        <label for="horario-{{ $indice }}-fin" class="campo-etiqueta">Hora de fin <span class="sr-only">del horario <span data-horario-numero>{{ $numero }}</span></span></label>
        <input type="time" id="horario-{{ $indice }}-fin" name="horarios[{{ $indice }}][hora_fin]" value="{{ $valorFin ?? '' }}" class="campo-control" {!! $describe !!}>
    </div>
    <button type="button" data-horario-quitar class="btn-secundario">Quitar horario</button>
    @if ($mensajes)
        <div class="horario-error" id="{{ $errorId }}" role="alert">
            @foreach ($mensajes as $mensaje)
                <p>{{ $mensaje }}</p>
            @endforeach
        </div>
    @endif
</div>
