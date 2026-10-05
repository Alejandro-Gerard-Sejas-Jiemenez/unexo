@extends('layouts.app')
@section('titulo', $programa->exists ? 'Editar programa' : 'Nuevo programa')
@section('errores_propios', '1')
@section('contenido')
<div class="mb-4">
    <a href="{{ route('programas.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-indigo-600 transition-colors">
        <x-icono nombre="volver" class="w-4 h-4" /> Volver a programas
    </a>
</div>

<h1 class="text-3xl font-bold text-slate-800 tracking-tight mb-6">{{ $programa->exists ? 'Editar programa' : 'Nuevo programa' }}</h1>

<div class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden max-w-3xl">
    <form method="POST" action="{{ $programa->exists ? route('programas.update', $programa) : route('programas.store') }}" class="p-6 sm:p-8">
        @csrf
        @if ($programa->exists) @method('PUT') @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 mb-2">
            <x-campo nombre="codigo" limite="programas.codigo" etiqueta="Código" requerido :valor="$programa->codigo" />
            <x-campo nombre="version" limite="programas.version" etiqueta="Versión" requerido :valor="$programa->version" />
        </div>
        
        <x-campo nombre="nombre" limite="programas.nombre" etiqueta="Nombre" requerido :valor="$programa->nombre" />
        <x-campo-area nombre="descripcion" limite="programas.descripcion" etiqueta="Descripción" :valor="$programa->descripcion" />
        
        <div class="max-w-xs">
            <x-campo-select nombre="estado_comercial" etiqueta="Estado comercial" requerido
                :opciones="collect(\App\Enums\EstadoComercial::cases())->mapWithKeys(fn ($e) => [$e->value => $e->label()])"
                :valor="$programa->estado_comercial?->value" />
        </div>

        <x-form-acciones :cancelar="route('programas.index')" />
    </form>
</div>
@endsection
