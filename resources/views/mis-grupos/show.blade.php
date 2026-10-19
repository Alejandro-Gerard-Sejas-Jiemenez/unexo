@extends('layouts.app')
@section('titulo', 'Grupo: '.$grupo->modulo->nombre)
@section('contenido')
<div class="mb-4">
    <a href="{{ route('mis-grupos.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-indigo-600 transition-colors">
        <x-icono nombre="volver" class="w-4 h-4" /> Volver a mis grupos
    </a>
</div>

<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-8">
    <div>
        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ $grupo->modulo->nombre }}</h1>
        <p class="mt-2 text-lg text-slate-500">{{ $grupo->cohorte ? $grupo->cohorte->programa->nombre.' · '.$grupo->cohorte->nombre : 'Módulo suelto' }} · {{ $grupo->nombre }}</p>
    </div>
    <div>
        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ 
            $grupo->fase === 'en-curso' ? 'bg-indigo-100 text-indigo-800 border border-indigo-200' : 
            ($grupo->fase === 'finalizado' ? 'bg-slate-200 text-slate-800 border border-slate-300' : 'bg-blue-50 text-blue-700 border border-blue-200')
        }} shadow-sm">
            {{ $grupo->fase_etiqueta }}
        </span>
    </div>
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
                <dt class="text-sm font-medium text-slate-500 mb-1">Modalidad</dt>
                <dd class="text-base text-slate-900">{{ $grupo->modalidad->label() }}@if ($grupo->aula) · {{ $grupo->aula->nombre }}@endif</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-sm font-medium text-slate-500 mb-1">Horarios</dt>
                <dd>@include('partials.horarios-grupo', ['horarios' => $grupo->horarios])</dd>
            </div>
            @if ($grupo->enlace_videoconferencia)
                <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-slate-500 mb-1">Enlace de la clase</dt>
                    <dd class="text-base text-slate-900">
                        <a href="{{ $grupo->enlace_videoconferencia }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 text-indigo-600 hover:text-indigo-800 hover:underline">
                            <x-icono nombre="video" class="w-4 h-4" /> {{ $grupo->enlace_videoconferencia }}
                        </a>
                    </dd>
                </div>
            @endif
        </dl>
    </div>
</div>

<div class="mb-6 flex items-center gap-3">
    <h2 class="text-2xl font-bold text-slate-800">Estudiantes inscritos</h2>
    <span class="inline-flex items-center justify-center bg-slate-100 text-slate-600 text-sm font-medium px-2.5 py-0.5 rounded-full">
        {{ $inscripciones->count() }}
    </span>
</div>

@if ($inscripciones->isEmpty())
    <div class="bg-white border border-slate-200 rounded-xl p-12 text-center shadow-sm">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-100 mb-4">
            <svg class="w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
        </div>
        <h3 class="text-lg font-medium text-slate-900 mb-1">No hay estudiantes inscritos</h3>
        <p class="text-slate-500">Aparecerán aquí cuando la administración los inscriba.</p>
    </div>
@else
    <div class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden mb-10">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-sm font-semibold text-slate-600">
                        <th class="px-6 py-4">Estudiante</th>
                        <th class="px-6 py-4">Correo institucional</th>
                        <th class="px-6 py-4 text-right">Estado de matrícula</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-sm">
                @foreach ($inscripciones as $inscripcion)
                    @php $estudiante = $inscripcion->matricula->estudiante; @endphp
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 font-medium text-slate-900">{{ $estudiante->apellidos }}, {{ $estudiante->nombres }}</td>
                        <td class="px-6 py-4 text-slate-500">{{ $estudiante->correo_institucional ?? '—' }}</td>
                        <td class="px-6 py-4 text-right">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ 
                                $inscripcion->matricula->estado->value === 'activo' ? 'bg-emerald-100 text-emerald-800' : 
                                ($inscripcion->matricula->estado->value === 'pendiente' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800')
                            }}">
                                {{ $inscripcion->matricula->estado->label() }}
                            </span>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
