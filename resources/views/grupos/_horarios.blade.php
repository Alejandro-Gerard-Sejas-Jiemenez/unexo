@php
    $filas = old('horarios');
    if ($filas === null) {
        $filas = $grupo->horarios
            ->map(fn ($h) => [
                'dia_semana' => $h->dia_semana->value,
                'hora_inicio' => substr($h->hora_inicio, 0, 5),
                'hora_fin' => substr($h->hora_fin, 0, 5),
            ])->all();
        if (! $grupo->exists && $filas === []) {
            $filas = [['dia_semana' => '', 'hora_inicio' => '', 'hora_fin' => '']];
        }
    }
    $filas = is_array($filas) ? $filas : [];
    $siguienteIndice = $filas === [] ? 0 : max(array_map('intval', array_keys($filas))) + 1;
    $max = config('limites.grupos.horarios_max');
@endphp
<fieldset class="mt-4 pt-4 border-t border-slate-100">
    <legend class="campo-etiqueta">Horarios <span class="campo-ayuda">Los horarios son informativos y se interpretan en hora de Bolivia. Se piden para grupos programados o en curso.</span></legend>

    @error('horarios')
        <p class="campo-error" id="horarios-error"><x-icono nombre="alerta" /><span>{{ $message }}</span></p>
    @enderror

    <div data-horarios data-max="{{ $max }}" data-siguiente-indice="{{ $siguienteIndice }}">
        <div data-horarios-lista>
            @foreach ($filas as $indice => $fila)
                @include('grupos._horario-fila', [
                    'indice' => $indice,
                    'numero' => $loop->iteration,
                    'valorDia' => is_array($fila) ? ($fila['dia_semana'] ?? '') : '',
                    'valorInicio' => is_array($fila) ? ($fila['hora_inicio'] ?? '') : '',
                    'valorFin' => is_array($fila) ? ($fila['hora_fin'] ?? '') : '',
                    'mensajes' => collect($errors->getBag('default')->get("horarios.$indice.*"))->flatten()->unique()->all(),
                ])
            @endforeach
        </div>
        <div class="horario-acciones">
            <button type="button" data-horario-agregar class="btn-secundario horario-agregar" @disabled(count($filas) >= $max)>Agregar horario</button>
            <p class="horario-anuncio" data-horarios-anuncio role="status" aria-live="polite"></p>
        </div>
    </div>

    <template id="plantilla-horario">
        @include('grupos._horario-fila', ['indice' => '__INDICE__', 'numero' => '__NUMERO__'])
    </template>
</fieldset>
