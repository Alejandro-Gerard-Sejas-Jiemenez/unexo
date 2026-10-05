@extends('layouts.app')
@section('titulo', 'Editar aula')
@section('errores_propios', '1')
@section('contenido')
<div class="mb-4">
    <a href="{{ route('aulas.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-indigo-600 transition-colors">
        <x-icono nombre="volver" class="w-4 h-4" /> Volver a aulas
    </a>
</div>

<h1 class="text-3xl font-bold text-slate-800 tracking-tight mb-6">Editar aula</h1>

<div class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden max-w-3xl">
    <form method="POST" action="{{ route('aulas.update', $aula) }}" class="p-6 sm:p-8">
        @csrf @method('PUT')
        
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 mb-2">
            <x-campo nombre="nombre" limite="aulas.nombre" etiqueta="Nombre" requerido :valor="$aula->nombre" />
            <x-campo nombre="capacidad" limite="aulas.capacidad" etiqueta="Capacidad" tipo="number" min="1" :valor="$aula->capacidad" ayuda="Opcional" />
        </div>
        
        <x-form-acciones :cancelar="route('aulas.index')" />
    </form>
</div>
@endsection
