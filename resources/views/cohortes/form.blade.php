@extends('layouts.app')
@section('titulo', $cohorte->exists ? 'Editar cohorte' : 'Nueva cohorte')
@section('errores_propios', '1')
@section('contenido')
<div class="mb-4">
    <a href="{{ route('cohortes.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-indigo-600 transition-colors">
        <x-icono nombre="volver" class="w-4 h-4" /> Volver a cohortes
    </a>
</div>

<h1 class="text-3xl font-bold text-slate-800 tracking-tight mb-6">{{ $cohorte->exists ? 'Editar cohorte' : 'Nueva cohorte' }}</h1>

<div class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden max-w-3xl">
    <form method="POST" action="{{ $cohorte->exists ? route('cohortes.update', $cohorte) : route('cohortes.store') }}" class="p-6 sm:p-8">
        @csrf
        @if ($cohorte->exists) @method('PUT') @endif

        <x-campo-select nombre="programa_id" etiqueta="Programa" requerido
            :opciones="$programas->mapWithKeys(fn ($p) => [$p->id => $p->nombre.' v'.$p->version])"
            :valor="$cohorte->programa_id" />
            
        <x-campo nombre="nombre" limite="cohortes.nombre" etiqueta="Nombre de la cohorte" requerido :valor="$cohorte->nombre" placeholder="Ej. Cohorte 2026-2" />
        
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 mb-2">
            <x-campo nombre="fecha_inicio" etiqueta="Fecha de inicio" tipo="date" requerido :valor="$cohorte->fecha_inicio?->format('Y-m-d')" />
            <x-campo nombre="fecha_fin" etiqueta="Fecha de fin" tipo="date" requerido :valor="$cohorte->fecha_fin?->format('Y-m-d')" />
        </div>
        
        <div class="max-w-xs">
            <x-campo-select nombre="estado" etiqueta="Estado" requerido
                :opciones="collect(\App\Enums\EstadoPlanificacion::cases())->mapWithKeys(fn ($e) => [$e->value => $e->label()])"
                :valor="$cohorte->estado?->value" />
        </div>

        <x-form-acciones :cancelar="$cohorte->exists ? route('cohortes.show', $cohorte) : route('cohortes.index')" />
    </form>
</div>
@endsection
