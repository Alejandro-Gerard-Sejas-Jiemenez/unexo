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
    <form method="POST" action="{{ $programa->exists ? route('programas.update', $programa) : route('programas.store') }}" class="p-6 sm:p-8" enctype="multipart/form-data">
        @csrf
        @if ($programa->exists) @method('PUT') @endif

        <!-- Sección 1: Datos Generales y Académicos -->
        <div class="mb-8">
            <h2 class="text-lg font-semibold text-slate-800 mb-4 border-b border-slate-200 pb-2">1. Datos Generales y Académicos</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 mb-2">
                <x-campo nombre="codigo" limite="programas.codigo" etiqueta="Código" requerido :valor="$programa->codigo" />
                <x-campo nombre="version" limite="programas.version" etiqueta="Versión" requerido :valor="$programa->version" />
            </div>
            
            <x-campo nombre="nombre" limite="programas.nombre" etiqueta="Nombre" requerido :valor="$programa->nombre" />
            
            <x-campo-select nombre="certificado_otorga" etiqueta="Certificado que otorga"
                :opciones="['' => '— No definido —'] + collect(\App\Enums\Certificado::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all()"
                :valor="$programa->certificado_otorga?->value" />
        </div>

        <!-- Sección 2: Contenido y Difusión -->
        <div class="mb-8">
            <h2 class="text-lg font-semibold text-slate-800 mb-4 border-b border-slate-200 pb-2">2. Contenido y Difusión</h2>
            <x-zona-portada nombre="imagen_portada_url" :portada="$programa->imagen_portada_url ?? null" />
            <x-campo-area nombre="descripcion" limite="programas.descripcion" etiqueta="Descripción breve" :valor="$programa->descripcion" />
            <x-campo-area nombre="requisitos_ingreso" etiqueta="Conocimientos previos recomendados" :valor="$programa->requisitos_ingreso" />
        </div>

        <!-- Sección 3: Configuración Comercial y API -->
        <div class="mb-8">
            <h2 class="text-lg font-semibold text-slate-800 mb-4 border-b border-slate-200 pb-2">3. Configuración Comercial y API</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2">
                <x-campo nombre="precio" etiqueta="Precio base (Bs)"tipo="number" min="0" step="0.01" :valor="$programa->precio" />
                <x-campo-select nombre="estado_comercial" etiqueta="Estado comercial" requerido
                    :opciones="collect(\App\Enums\EstadoComercial::cases())->mapWithKeys(fn ($e) => [$e->value => $e->label()])"
                    :valor="$programa->estado_comercial?->value" />
            </div>
        </div>

        <x-form-acciones :cancelar="route('programas.index')" />
    </form>
</div>
@endsection
