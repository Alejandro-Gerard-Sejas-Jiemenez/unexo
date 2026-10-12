@extends('layouts.app')
@section('titulo', $cohorte->nombre)
@section('contenido')
<div class="mb-4">
    <a href="{{ route('cohortes.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-indigo-600 transition-colors">
        <x-icono nombre="volver" class="w-4 h-4" /> Volver a cohortes
    </a>
</div>

<div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold text-slate-800 tracking-tight">{{ $cohorte->nombre }}</h1>
        <p class="text-sm text-slate-500 mt-1">{{ $cohorte->programa->nombre }} v{{ $cohorte->programa->version }}</p>
    </div>
    <div class="flex items-center gap-3">
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $cohorte->estado->value === 'en_curso' ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-800' }}">
            {{ $cohorte->estado->label() }}
        </span>
        <a class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-white text-slate-700 font-medium rounded-lg border border-slate-300 hover:bg-slate-50 shadow-sm transition-all focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 text-sm" href="{{ route('cohortes.edit', $cohorte) }}">
            <x-icono nombre="editar" class="w-4 h-4" /> Editar
        </a>
    </div>
</div>

<div class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden mb-8 p-6">
    <h3 class="text-sm font-medium text-slate-500 mb-1">Fechas</h3>
    <p class="text-base text-slate-800 font-medium">{{ $cohorte->fecha_inicio->format('d/m/Y') }} al {{ $cohorte->fecha_fin->format('d/m/Y') }}</p>
</div>

<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h2 class="text-xl font-bold text-slate-800">Grupos de la cohorte <span class="text-slate-500 text-base font-normal">({{ $cohorte->grupos->count() }})</span></h2>
    </div>
    <a class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-white text-slate-700 font-medium rounded-lg border border-slate-300 hover:bg-slate-50 shadow-sm transition-all focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 text-sm" href="{{ route('grupos.create', ['cohorte_id' => $cohorte->id]) }}">
        <x-icono nombre="mas" class="w-4 h-4" /> Nuevo grupo
    </a>
</div>

@if ($cohorte->grupos->isEmpty())
    <div class="bg-slate-50 border border-slate-200 shadow-sm rounded-xl p-8 text-center">
        <h3 class="text-lg font-medium text-slate-800 mb-2">Esta cohorte todavía no tiene grupos.</h3>
        <p class="text-slate-500">Agrega un grupo por cada módulo del programa, con su docente y fechas.</p>
    </div>
@else
    <div class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-xs uppercase tracking-wider text-slate-500 font-semibold">
                        <th class="px-6 py-4">Módulo</th>
                        <th class="px-6 py-4">Grupo</th>
                        <th class="px-6 py-4">Docente</th>
                        <th class="px-6 py-4">Fechas</th>
                        <th class="px-6 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                @foreach ($cohorte->grupos as $grupo)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-800">{{ $grupo->modulo->nombre }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">{{ $grupo->nombre }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">{{ $grupo->docente->nombre_completo }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">{{ $grupo->fecha_inicio->format('d/m/Y') }} – {{ $grupo->fecha_fin->format('d/m/Y') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium">
                            <div class="flex items-center justify-end gap-2">
                                <a class="inline-flex items-center gap-1 px-3 py-1.5 bg-white text-slate-600 hover:text-indigo-600 border border-slate-200 hover:border-indigo-200 rounded-md transition-colors text-xs font-medium shadow-sm" href="{{ route('grupos.show', $grupo) }}">
                                    <x-icono nombre="usuarios" class="w-3.5 h-3.5" /> Ver
                                </a>
                                <a class="inline-flex items-center gap-1 px-3 py-1.5 bg-white text-slate-600 hover:text-indigo-600 border border-slate-200 hover:border-indigo-200 rounded-md transition-colors text-xs font-medium shadow-sm" href="{{ route('grupos.edit', $grupo) }}">
                                    <x-icono nombre="editar" class="w-3.5 h-3.5" /> Editar
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
