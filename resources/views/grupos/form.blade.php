@extends('layouts.app')
@section('titulo', $grupo->exists ? 'Editar grupo' : 'Nuevo grupo')
@section('errores_propios', '1')
@section('contenido')
<div class="mb-4">
    <a href="{{ route('grupos.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-indigo-600 transition-colors">
        <x-icono nombre="volver" class="w-4 h-4" /> Volver a grupos
    </a>
</div>

<h1 class="text-3xl font-bold text-slate-800 tracking-tight mb-6">{{ $grupo->exists ? 'Editar grupo' : 'Nuevo grupo' }}</h1>

<div class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden max-w-4xl">
    <form method="POST" action="{{ $grupo->exists ? route('grupos.update', $grupo) : route('grupos.store') }}" class="p-6 sm:p-8">
        @csrf
        @if ($grupo->exists) @method('PUT') @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 mb-2">
            <x-campo-select nombre="modulo_id" :total="$modulosTotal" etiqueta="Módulo" requerido
                :opciones="$modulos->mapWithKeys(fn ($m) => [$m->id => $m->codigo.' · '.$m->nombre])"
                :valor="$grupo->modulo_id" />
            <x-campo nombre="nombre" limite="grupos.nombre" etiqueta="Nombre del grupo" requerido :valor="$grupo->nombre" placeholder="Ej. Grupo A" />
        </div>

        <x-campo-select nombre="cohorte_id" :total="$cohortesTotal" etiqueta="Cohorte"
            ayuda="Déjalo en módulo suelto si no pertenece a una cohorte"
            :opciones="collect(['' => '— Módulo suelto (sin cohorte) —'])->union($cohortes->mapWithKeys(fn ($c) => [$c->id => $c->programa->nombre.' · '.$c->nombre]))"
            :valor="$grupo->cohorte_id" />

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 mb-2 mt-4 pt-4 border-t border-slate-100">
            <x-campo-select nombre="docente_id" :total="$docentesTotal" etiqueta="Docente" requerido
                :opciones="$docentes->mapWithKeys(fn ($d) => [$d->id => $d->apellidos.', '.$d->nombres])"
                :valor="$grupo->docente_id" />
            <x-campo-select nombre="modalidad" etiqueta="Modalidad" requerido
                :opciones="collect(\App\Enums\Modalidad::cases())->mapWithKeys(fn ($m) => [$m->value => $m->label()])"
                :valor="$grupo->modalidad?->value" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 mb-2">
            <x-campo nombre="fecha_inicio" etiqueta="Fecha de inicio" tipo="date" requerido :valor="$grupo->fecha_inicio?->format('Y-m-d')" />
            <x-campo nombre="fecha_fin" etiqueta="Fecha de fin" tipo="date" requerido :valor="$grupo->fecha_fin?->format('Y-m-d')" />
        </div>

        <x-campo nombre="enlace_videoconferencia" limite="grupos.enlace_videoconferencia" etiqueta="Enlace de videoconferencia" tipo="url" :valor="$grupo->enlace_videoconferencia" placeholder="https://meet…" ayuda="Para grupos virtuales" />

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 mb-2 mt-4 pt-4 border-t border-slate-100">
            <x-campo-select nombre="aula_id" :total="$aulasTotal" etiqueta="Aula"
                ayuda="Para grupos presenciales"
                :opciones="collect(['' => '— Sin aula —'])->union($aulas->mapWithKeys(fn ($a) => [$a->id => $a->nombre]))"
                :valor="$grupo->aula_id" />
            <x-campo nombre="cupo_minimo" limite="grupos.cupo_minimo" etiqueta="Cupo mínimo" tipo="number" min="1" :valor="$grupo->cupo_minimo" ayuda="Opcional" />
        </div>

        <div class="max-w-xs mt-4 pt-4 border-t border-slate-100">
            <x-campo-select nombre="estado" etiqueta="Estado" requerido
                :opciones="collect(\App\Enums\EstadoPlanificacion::cases())->mapWithKeys(fn ($e) => [$e->value => $e->label()])"
                :valor="$grupo->estado?->value" />
        </div>

        <x-form-acciones :cancelar="route('grupos.index')" />
    </form>
</div>
@endsection
