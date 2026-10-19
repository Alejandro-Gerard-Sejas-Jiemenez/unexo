@extends('layouts.app')
@section('titulo', 'Grupo: '.$grupo->modulo->nombre)
@section('contenido')
<div class="mb-4">
    <a href="{{ route('mis-cursos') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-indigo-600 transition-colors">
        <x-icono nombre="volver" class="w-4 h-4" /> Volver a mis cursos
    </a>
</div>

<div class="mb-8">
    <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ $grupo->modulo->nombre }}</h1>
    <p class="mt-2 text-lg text-slate-500">{{ $grupo->cohorte ? $grupo->cohorte->programa->nombre.' · '.$grupo->cohorte->nombre : 'Módulo suelto' }} · {{ $grupo->nombre }}</p>
</div>

<div class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden mb-10 max-w-3xl">
    <div class="px-6 py-5 border-b border-slate-200 bg-slate-50/50">
        <h2 class="text-lg font-semibold text-slate-800">Detalles del grupo</h2>
    </div>
    <div class="p-6">
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-6">
            <div>
                <dt class="text-sm font-medium text-slate-500 mb-1">Período</dt>
                <dd class="text-base text-slate-900">{{ $grupo->periodo_texto }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-slate-500 mb-1">Docente</dt>
                <dd class="text-base text-slate-900">{{ $grupo->docente->nombre_completo }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-slate-500 mb-1">Modalidad</dt>
                <dd class="text-base text-slate-900">{{ $grupo->modalidad->label() }}@if ($grupo->aula) · {{ $grupo->aula->nombre }}@endif</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-sm font-medium text-slate-500 mb-1">Horarios</dt>
                <dd>@include('partials.horarios-grupo', ['horarios' => $grupo->horarios])</dd>
            </div>
        </dl>
    </div>
</div>
@endsection
