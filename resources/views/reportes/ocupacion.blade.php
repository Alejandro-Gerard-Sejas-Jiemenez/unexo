@extends('layouts.app')
@section('titulo', 'Reporte de ocupación de grupos')
@section('contenido')
@php
    // Con filtros inválidos los valores vienen tal cual de la URL: solo se reponen los que son texto.
    $valor = fn (string $campo) => is_string($filtros[$campo] ?? null) ? $filtros[$campo] : '';
    $claseCampo = 'block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500';
@endphp
<div class="mb-6">
    <h1 class="text-3xl font-bold text-slate-800 tracking-tight">Reportes</h1>
    <p class="text-sm text-slate-500 mt-1">Inscritos de cada grupo frente a su cupo mínimo.</p>
</div>

@include('reportes._navegacion')

<x-filtros :accion="route('reportes.ocupacion')" :quitar="route('reportes.ocupacion')">
    <div>
        <label for="estado" class="mb-1 block text-sm font-medium text-slate-700">Estado del grupo</label>
        <select id="estado" name="estado" class="{{ $claseCampo }}">
            <option value="">Todos</option>
            @foreach ($estados as $estado)
                <option value="{{ $estado->value }}" @selected($valor('estado') === $estado->value)>{{ $estado->label() }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="cohorte_id" class="mb-1 block text-sm font-medium text-slate-700">Cohorte</label>
        <select id="cohorte_id" name="cohorte_id" class="{{ $claseCampo }}">
            <option value="">Todas</option>
            @foreach ($cohortes as $cohorte)
                <option value="{{ $cohorte->id }}" @selected($valor('cohorte_id') === $cohorte->id)>{{ $cohorte->programa->nombre }} · {{ $cohorte->nombre }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="desde" class="mb-1 block text-sm font-medium text-slate-700">Período desde</label>
        <input id="desde" name="desde" type="date" value="{{ $valor('desde') }}" class="{{ $claseCampo }}">
    </div>
    <div>
        <label for="hasta" class="mb-1 block text-sm font-medium text-slate-700">Período hasta</label>
        <input id="hasta" name="hasta" type="date" value="{{ $valor('hasta') }}" class="{{ $claseCampo }}">
    </div>
</x-filtros>

@if ($reporte)
    <div class="mb-6">
        @include('reportes._descargas', ['rutaPdf' => 'reportes.ocupacion.pdf', 'rutaExcel' => 'reportes.ocupacion.excel', 'filtros' => $filtros])
    </div>
    @include('reportes._cuerpo', ['reporte' => $reporte])
@endif
@endsection
