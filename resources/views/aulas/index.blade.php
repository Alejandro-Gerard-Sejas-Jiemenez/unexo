@extends('layouts.app')
@section('titulo', 'Aulas')
@section('errores_propios', '1')
@section('contenido')
<div class="mb-8">
    <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Aulas</h1>
    <p class="mt-2 text-lg text-slate-500 max-w-3xl">Espacios físicos que se asignan a los grupos presenciales.</p>
</div>

<div class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden mb-10 max-w-3xl">
    <div class="px-6 py-5 border-b border-slate-200 bg-slate-50/50">
        <h2 class="text-lg font-semibold text-slate-800">Registrar un aula</h2>
    </div>
    <form method="POST" action="{{ route('aulas.store') }}" class="p-6">
        @csrf
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 mb-2">
            <x-campo nombre="nombre" limite="aulas.nombre" etiqueta="Nombre" requerido placeholder="Ej. Aula 101" />
            <x-campo nombre="capacidad" limite="aulas.capacidad" etiqueta="Capacidad" tipo="number" min="1" ayuda="Opcional" />
        </div>
        <div class="mt-4 pt-4 border-t border-slate-100 flex justify-end">
            <button type="submit" class="btn btn-primario">
                <x-icono nombre="mas" class="w-4 h-4" /> Registrar aula
            </button>
        </div>
    </form>
</div>

@if ($aulas->isEmpty())
    <div class="bg-white border border-slate-200 rounded-xl p-12 text-center shadow-sm">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-100 mb-4">
            <svg class="w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
        </div>
        <h3 class="text-lg font-medium text-slate-900 mb-1">No hay aulas registradas</h3>
        <p class="text-slate-500">Comienza registrando la primera aula usando el formulario de arriba.</p>
    </div>
@else
    <div class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-sm font-semibold text-slate-600">
                        <th class="px-6 py-4">Nombre</th>
                        <th class="px-6 py-4">Capacidad</th>
                        <th class="px-6 py-4">Grupos</th>
                        <th class="px-6 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-sm">
                @foreach ($aulas as $aula)
                    <tr class="hover:bg-slate-50 transition-colors group">
                        <td class="px-6 py-4 font-medium text-slate-900">{{ $aula->nombre }}</td>
                        <td class="px-6 py-4 text-slate-500">{{ $aula->capacidad ?? '—' }}</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-800">
                                {{ $aula->grupos_count }} grupos
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('aulas.edit', $aula) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 hover:text-indigo-600 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500">
                                <x-icono nombre="editar" class="w-3.5 h-3.5" /> Editar
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    {{ $aulas->links('partials.paginacion') }}
@endif
@endsection
