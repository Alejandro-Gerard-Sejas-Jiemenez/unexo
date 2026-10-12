@extends('layouts.app')
@section('titulo', $modulo->exists ? 'Editar módulo' : 'Nuevo módulo')
@section('errores_propios', '1')
@section('contenido')
<div class="mb-4">
    <a href="{{ route('modulos.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-indigo-600 transition-colors">
        <x-icono nombre="volver" class="w-4 h-4" /> Volver a módulos
    </a>
</div>

<h1 class="text-3xl font-bold text-slate-800 tracking-tight mb-6">{{ $modulo->exists ? 'Editar módulo' : 'Nuevo módulo' }}</h1>

<div class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden max-w-3xl">
    <form method="POST" action="{{ $modulo->exists ? route('modulos.update', $modulo) : route('modulos.store') }}" class="p-6 sm:p-8">
        @csrf
        @if ($modulo->exists) @method('PUT') @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 mb-2">
            <x-campo nombre="codigo" limite="modulos.codigo" etiqueta="Código" requerido :valor="$modulo->codigo" />
            <x-campo nombre="duracion_horas" limite="modulos.duracion_horas" etiqueta="Duración (horas)" tipo="number" min="1" :valor="$modulo->duracion_horas" />
        </div>
        
        <x-campo nombre="nombre" limite="modulos.nombre" etiqueta="Nombre" requerido :valor="$modulo->nombre" />
        
        <x-campo-area nombre="descripcion" etiqueta="Descripción" :valor="$modulo->descripcion" />
        
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 mb-2 mt-4 pt-4 border-t border-slate-100">
            <x-campo nombre="creditos" etiqueta="Créditos Académicos" tipo="number" min="1" :valor="$modulo->creditos" />
            <x-campo nombre="tipo_evaluacion" etiqueta="Tipo de Evaluación" :valor="$modulo->tipo_evaluacion" />
            <x-campo nombre="pdf_temario_url" etiqueta="URL del PDF del Temario" tipo="url" :valor="$modulo->pdf_temario_url" />
            
            <x-campo-select nombre="estado_comercial" etiqueta="Estado comercial" requerido
                :opciones="collect(\App\Enums\EstadoComercial::cases())->mapWithKeys(fn ($e) => [$e->value => $e->label()])"
                :valor="$modulo->estado_comercial?->value" />
        </div>

        <x-form-acciones :cancelar="route('modulos.index')" />
    </form>
</div>
@endsection
