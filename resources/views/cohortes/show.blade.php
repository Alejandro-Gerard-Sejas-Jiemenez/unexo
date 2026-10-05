@extends('layouts.app')
@section('titulo', $cohorte->nombre)
@section('contenido')
<p style="margin:0 0 .75rem"><a class="enlace-icono" href="{{ route('cohortes.index') }}"><x-icono nombre="volver" /> Volver a cohortes</a></p>
<div class="encabezado">
    <div>
        <h1>{{ $cohorte->nombre }}</h1>
        <p class="suave" style="margin:0">{{ $cohorte->programa->nombre }} v{{ $cohorte->programa->version }}</p>
    </div>
    <div class="acciones">
        <span class="etiqueta {{ $cohorte->estado->value }}">{{ $cohorte->estado->label() }}</span>
        <a class="btn sec" href="{{ route('cohortes.edit', $cohorte) }}"><x-icono nombre="editar" /> Editar</a>
    </div>
</div>

<div class="tarjeta">
    <dl class="datos">
        <dt>Fechas</dt><dd>{{ $cohorte->fecha_inicio->format('d/m/Y') }} al {{ $cohorte->fecha_fin->format('d/m/Y') }}</dd>
    </dl>
</div>

<div class="encabezado">
    <h2 style="margin:0">Grupos de la cohorte <span class="suave">({{ $cohorte->grupos->count() }})</span></h2>
    <a class="btn sec" href="{{ route('grupos.create', ['cohorte_id' => $cohorte->id]) }}"><x-icono nombre="mas" /> Nuevo grupo</a>
</div>
@if ($cohorte->grupos->isEmpty())
    <div class="tarjeta">
        <p style="margin:0 0 .25rem"><strong>Esta cohorte todavía no tiene grupos.</strong></p>
        <p class="suave" style="margin:0">Agrega un grupo por cada módulo del programa, con su docente y fechas.</p>
    </div>
@else
    <div class="tabla-scroll">
        <table class="tabla-adaptable">
            <thead><tr><th>Módulo</th><th>Grupo</th><th>Docente</th><th>Fechas</th><th></th></tr></thead>
            <tbody>
            @foreach ($cohorte->grupos as $grupo)
                <tr>
                    <td data-label="Módulo">{{ $grupo->modulo->nombre }}</td>
                    <td data-label="Grupo">{{ $grupo->nombre }}</td>
                    <td data-label="Docente">{{ $grupo->docente->nombre_completo }}</td>
                    <td data-label="Fechas">{{ $grupo->fecha_inicio->format('d/m/Y') }} – {{ $grupo->fecha_fin->format('d/m/Y') }}</td>
                    <td class="acciones">
                        <a class="btn sec mini" href="{{ route('grupos.show', $grupo) }}"><x-icono nombre="usuarios" /> Ver</a>
                        <a class="btn sec mini" href="{{ route('grupos.edit', $grupo) }}"><x-icono nombre="editar" /> Editar</a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection
