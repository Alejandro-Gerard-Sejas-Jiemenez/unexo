@extends('layouts.app')
@section('titulo', 'Grupo: '.$grupo->modulo->nombre)
@section('contenido')
<p style="margin:0 0 .75rem"><a class="enlace-icono" href="{{ route('grupos.index') }}"><x-icono nombre="volver" /> Volver a grupos</a></p>
<div class="encabezado">
    <div>
        <h1>{{ $grupo->modulo->nombre }}</h1>
        <p class="suave" style="margin:0">{{ $grupo->cohorte ? $grupo->cohorte->programa->nombre.' · '.$grupo->cohorte->nombre : 'Módulo suelto' }} · {{ $grupo->nombre }}</p>
    </div>
    <div class="acciones">
        <span class="etiqueta {{ $grupo->estado->value }}">{{ $grupo->estado->label() }}</span>
        <a class="btn sec" href="{{ route('grupos.edit', $grupo) }}"><x-icono nombre="editar" /> Editar</a>
    </div>
</div>

<div class="tarjeta">
    <dl class="datos">
        <dt>Docente</dt><dd>{{ $grupo->docente->nombre_completo }}</dd>
        <dt>Fechas</dt><dd>{{ $grupo->fecha_inicio->format('d/m/Y') }} al {{ $grupo->fecha_fin->format('d/m/Y') }}</dd>
        <dt>Modalidad</dt><dd>{{ $grupo->modalidad->label() }}@if ($grupo->aula) · {{ $grupo->aula->nombre }}@endif</dd>
        @if ($grupo->enlace_videoconferencia)
            <dt>Enlace</dt><dd><a href="{{ $grupo->enlace_videoconferencia }}" target="_blank" rel="noopener">{{ $grupo->enlace_videoconferencia }}</a></dd>
        @endif
        @if ($grupo->cupo_minimo)
            <dt>Cupo mínimo</dt><dd class="cifra-tabular">{{ $grupo->cupo_minimo }}</dd>
        @endif
    </dl>
</div>

<h2>Estudiantes inscritos <span class="suave">({{ $grupo->inscripciones->count() }})</span></h2>
@if ($grupo->inscripciones->isEmpty())
    <div class="tarjeta">
        <p style="margin:0 0 .25rem"><strong>Todavía no hay estudiantes inscritos.</strong></p>
        <p class="suave" style="margin:0">Inscribe estudiantes desde <a href="{{ route('matriculas.create') }}">Matrículas</a>.</p>
    </div>
@else
    <div class="tabla-scroll">
        <table class="tabla-adaptable">
            <thead><tr><th>Estudiante</th><th>Correo institucional</th><th>Matrícula</th></tr></thead>
            <tbody>
            @foreach ($grupo->inscripciones as $inscripcion)
                @php $estudiante = $inscripcion->matricula->estudiante; @endphp
                <tr>
                    <td data-label="Estudiante">{{ $estudiante->apellidos }}, {{ $estudiante->nombres }}</td>
                    <td data-label="Correo institucional">{{ $estudiante->correo_institucional ?? '—' }}</td>
                    <td data-label="Matrícula"><span class="etiqueta {{ $inscripcion->matricula->estado->value }}">{{ $inscripcion->matricula->estado->label() }}</span></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection
