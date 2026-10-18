@extends('layouts.app')
@section('titulo', 'Ediciones')
@section('contenido')
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-slate-800 tracking-tight">Ediciones</h1>
            <p class="text-sm text-slate-500 mt-1">Aperturas de un programa con fecha de inicio y fin (gestiones académicas / semestres).</p>
        </div>
        <a class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 shadow-sm transition-all focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            href="{{ route('cohortes.create') }}">
            <x-icono nombre="mas" class="w-5 h-5" /> Nueva edición
        </a>
    </div>

    <div
        class="mb-6 flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white p-4 rounded-xl shadow-sm border border-slate-200">
        <!-- Filtros -->
        <div class="flex flex-wrap gap-2">
            @php
                $claseActivo = "px-4 py-1.5 rounded-full text-sm font-medium bg-indigo-100 text-indigo-700 ring-1 ring-inset ring-indigo-200 transition-colors";
                $claseInactivo = "px-4 py-1.5 rounded-full text-sm font-medium bg-slate-100 text-slate-600 hover:bg-slate-200 transition-colors";
            @endphp

            <a href="{{ route('cohortes.index', ['q' => $busqueda ?: null]) }}"
                class="{{ $estadoFiltro ? $claseInactivo : $claseActivo }}">Todas</a>

            @foreach (\App\Enums\EstadoPlanificacion::cases() as $e)
                <a href="{{ route('cohortes.index', ['estado' => $e->value, 'q' => $busqueda ?: null]) }}"
                    class="{{ $estadoFiltro === $e->value ? $claseActivo : $claseInactivo }}">{{ $e->label() }}</a>
            @endforeach
        </div>

        <!-- Buscador -->
        <div class="w-full lg:w-auto lg:min-w-75">
            @include('partials.buscador', ['ruta' => route('cohortes.index'), 'busqueda' => $busqueda, 'placeholder' => 'Buscar por edición o programa…'])
        </div>
    </div>

    @if ($cohortes->isEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-slate-900">No hay ediciones con ese filtro</h3>
            <p class="mt-1 text-sm text-slate-500">Crea una edición para abrir un programa a inscripción.</p>
        </div>
    @else
        <div class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                Programa</th>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                Edición</th>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Inicio
                            </th>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Fin
                            </th>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Grupos
                            </th>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                Alumnos</th>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Estado
                            </th>
                            <th scope="col"
                                class="relative px-6 py-4 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-100">
                        @foreach ($cohortes as $cohorte)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-4 text-sm text-slate-600 font-medium">
                                    <div>{{ $cohorte->programa->nombre }}</div>
                                    <div class="text-xs text-slate-400 font-mono">v{{ $cohorte->programa->version }} ·
                                        {{ $cohorte->programa->codigo }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm font-bold text-slate-900">
                                    <a href="{{ route('cohortes.show', $cohorte) }}"
                                        class="text-indigo-600 hover:text-indigo-900 hover:underline">{{ $cohorte->nombre }}</a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                    {{ $cohorte->fecha_inicio->format('d/m/Y') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                    {{ $cohorte->fecha_fin->format('d/m/Y') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $cohorte->grupos_count > 0 ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-slate-100 text-slate-500' }}">
                                        {{ $cohorte->grupos_count }} grupos
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $cohorte->matriculas_count > 0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500' }}">
                                        {{ $cohorte->matriculas_count }} inscritos
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $badgeColor = match ($cohorte->estado->value) {
                                            'programado' => 'bg-amber-100 text-amber-700 ring-amber-600/20',
                                            'en_curso' => 'bg-emerald-100 text-emerald-700 ring-emerald-600/20',
                                            'finalizado' => 'bg-blue-100 text-blue-700 ring-blue-600/20',
                                            default => 'bg-slate-100 text-slate-700 ring-slate-500/20'
                                        };
                                    @endphp
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold ring-1 ring-inset {{ $badgeColor }}">
                                        {{ $cohorte->estado->label() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('cohortes.show', $cohorte) }}"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 bg-white border border-slate-300 rounded-md hover:bg-slate-50 hover:text-indigo-600 transition-colors shadow-2xs">
                                            <x-icono nombre="modulos" class="w-3.5 h-3.5 text-indigo-500" /> Planificar
                                        </a>
                                        <a href="{{ route('cohortes.edit', $cohorte) }}"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-600 bg-white border border-slate-300 rounded-md hover:bg-slate-50 hover:text-indigo-600 transition-colors shadow-2xs">
                                            <x-icono nombre="editar" class="w-3.5 h-3.5 text-slate-400" /> Editar
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        {{ $cohortes->links('partials.paginacion') }}
    @endif
@endsection