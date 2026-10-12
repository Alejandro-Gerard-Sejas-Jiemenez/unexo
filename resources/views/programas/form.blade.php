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
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 mb-2">
                <x-campo nombre="nivel_academico" etiqueta="Nivel Académico" :valor="$programa->nivel_academico" />
                <x-campo nombre="certificado_otorga" etiqueta="Certificado que otorga" :valor="$programa->certificado_otorga" />
            </div>
        </div>

        <!-- Sección 2: Contenido y Difusión -->
        <div class="mb-8">
            <h2 class="text-lg font-semibold text-slate-800 mb-4 border-b border-slate-200 pb-2">2. Contenido y Difusión</h2>
            <div class="mb-4">
                <x-campo nombre="imagen_portada_url" etiqueta="Imagen de Portada" tipo="file" />
                @if ($programa->imagen_portada_url)
                    <div class="mt-2 text-sm text-slate-500">
                        Imagen actual: <a href="{{ asset($programa->imagen_portada_url) }}" target="_blank" class="text-indigo-600 hover:underline">Ver imagen</a>
                    </div>
                @endif
            </div>
            <x-campo-area nombre="descripcion" limite="programas.descripcion" etiqueta="Descripción breve" :valor="$programa->descripcion" />
            <x-campo-area nombre="perfil_egreso" etiqueta="Perfil de Egreso (Propuesta de valor)" :valor="$programa->perfil_egreso" />
            <x-campo-area nombre="requisitos_ingreso" etiqueta="Conocimientos previos recomendados" :valor="$programa->requisitos_ingreso" />
        </div>

        <!-- Sección 3: Configuración Comercial y API -->
        <div class="mb-8">
            <h2 class="text-lg font-semibold text-slate-800 mb-4 border-b border-slate-200 pb-2">3. Configuración Comercial y API</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2">
                <x-campo nombre="precio" etiqueta="Precio Base" tipo="number" min="0" step="0.01" :valor="$programa->precio" />
                <x-campo-select nombre="estado_comercial" etiqueta="Estado comercial" requerido
                    :opciones="collect(\App\Enums\EstadoComercial::cases())->mapWithKeys(fn ($e) => [$e->value => $e->label()])"
                    :valor="$programa->estado_comercial?->value" />
            </div>
        </div>

        <x-form-acciones :cancelar="route('programas.index')" />
    </form>
</div>
@endsection
