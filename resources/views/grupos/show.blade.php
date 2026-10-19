@extends('layouts.app')
@section('titulo', 'Grupo: '.$grupo->modulo->nombre)
@section('contenido')
<div class="mb-4">
    <a href="{{ route('grupos.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-indigo-600 transition-colors">
        <x-icono nombre="volver" class="w-4 h-4" /> Volver a grupos
    </a>
</div>

<div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold text-slate-800 tracking-tight">{{ $grupo->modulo->nombre }}</h1>
        <p class="text-sm text-slate-500 mt-1">{{ $grupo->cohorte ? $grupo->cohorte->programa->nombre.' · '.$grupo->cohorte->nombre : 'Módulo suelto' }} · {{ $grupo->nombre }}</p>
    </div>
    <div class="flex items-center gap-3">
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $grupo->estado->value === 'programado' ? 'bg-blue-100 text-blue-800' : ($grupo->estado->value === 'en_curso' ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-800') }}">
            {{ $grupo->estado->label() }}
        </span>
        <a class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-white text-slate-700 font-medium rounded-lg border border-slate-300 hover:bg-slate-50 shadow-sm transition-all focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 text-sm" href="{{ route('grupos.edit', $grupo) }}">
            <x-icono nombre="editar" class="w-4 h-4" /> Editar
        </a>
    </div>
</div>

<div class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden mb-8 p-6">
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
        <div>
            <h3 class="text-sm font-medium text-slate-500 mb-1">Docente</h3>
            <p class="text-base text-slate-800 font-medium">{{ $grupo->docente->nombre_completo }}</p>
        </div>
        <div>
            <h3 class="text-sm font-medium text-slate-500 mb-1">Período</h3>
            <p class="text-base text-slate-800 font-medium">{{ $grupo->periodo_texto }}</p>
        </div>
        <div>
            <h3 class="text-sm font-medium text-slate-500 mb-1">Modalidad</h3>
            <p class="text-base text-slate-800 font-medium">{{ $grupo->modalidad->label() }}@if ($grupo->aula) <span class="text-slate-400 font-normal">·</span> {{ $grupo->aula->nombre }}@endif</p>
        </div>
        @if ($grupo->enlace_videoconferencia)
        <div class="sm:col-span-2 md:col-span-3">
            <h3 class="text-sm font-medium text-slate-500 mb-1">Enlace</h3>
            <p class="text-base font-medium">
                <a href="{{ $grupo->enlace_videoconferencia }}" target="_blank" rel="noopener" class="text-indigo-600 hover:underline break-all">{{ $grupo->enlace_videoconferencia }}</a>
            </p>
        </div>
        @endif
        @if ($grupo->cupo_minimo)
        <div>
            <h3 class="text-sm font-medium text-slate-500 mb-1">Cupo mínimo</h3>
            <p class="text-base text-slate-800 font-medium">{{ $grupo->cupo_minimo }}</p>
        </div>
        @endif
    </div>
</div>

<div class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden mb-8 p-6">
    <h2 class="text-xl font-bold text-slate-800 mb-3">Horarios</h2>
    @include('partials.horarios-grupo', ['horarios' => $grupo->horarios])
</div>

<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h2 class="text-xl font-bold text-slate-800">Estudiantes inscritos <span class="text-slate-500 text-base font-normal">({{ $grupo->inscripciones->count() }})</span></h2>
    </div>
</div>

@if ($grupo->inscripciones->isEmpty())
    <div class="bg-slate-50 border border-slate-200 shadow-sm rounded-xl p-8 text-center">
        <h3 class="text-lg font-medium text-slate-800 mb-2">Todavía no hay estudiantes inscritos.</h3>
        <p class="text-slate-500">Inscribe estudiantes desde <a href="{{ route('matriculas.create') }}" class="text-indigo-600 hover:underline">Matrículas</a>.</p>
    </div>
@else
    <div class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-xs uppercase tracking-wider text-slate-500 font-semibold">
                        <th class="px-6 py-4">Estudiante</th>
                        <th class="px-6 py-4">Correo institucional</th>
                        <th class="px-6 py-4">Matrícula</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                @foreach ($grupo->inscripciones as $inscripcion)
                    @php $estudiante = $inscripcion->matricula->estudiante; @endphp
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-800">{{ $estudiante->apellidos }}, {{ $estudiante->nombres }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">{{ $estudiante->correo_institucional ?? '—' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $inscripcion->matricula->estado->value === 'activa' ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-800' }}">
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
