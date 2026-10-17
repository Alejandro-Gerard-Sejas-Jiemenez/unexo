@extends('layouts.app')
@section('titulo', 'Grupos')
@section('contenido')
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-slate-800 tracking-tight">Grupos</h1>
            <p class="text-sm text-slate-500 mt-1">Dictados de un módulo, dentro de una cohorte o como módulo suelto.</p>
        </div>
        <div class="flex items-center gap-3">
            <a class="inline-flex items-center justify-center px-5 py-2.5 bg-white border border-slate-300 text-slate-700 font-medium rounded-lg hover:bg-slate-50 shadow-sm transition-colors"
                href="{{ route('aulas.index') }}">
                Aulas
            </a>
            <a class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 shadow-sm transition-all focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                href="{{ route('grupos.create') }}">
                <x-icono nombre="mas" class="w-5 h-5" /> Nuevo grupo
            </a>
        </div>
    </div>

    <div
        class="mb-6 flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white p-4 rounded-xl shadow-sm border border-slate-200">
        <!-- Filtros -->
        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            <!-- Tipo de grupo -->
            <div class="inline-flex items-center p-1 bg-slate-100 rounded-lg text-xs">
                @php
                    $tabActivo = "px-3 py-1.5 rounded-md font-semibold bg-white text-indigo-700 shadow-sm transition-all";
                    $tabInactivo = "px-3 py-1.5 rounded-md font-medium text-slate-600 hover:text-slate-900 transition-all";
                @endphp
                <a href="{{ route('grupos.index', ['tipo' => null, 'estado' => $estadoFiltro ?: null, 'q' => $busqueda ?: null]) }}"
                    class="{{ empty($tipoFiltro) ? $tabActivo : $tabInactivo }}">Todos</a>
                <a href="{{ route('grupos.index', ['tipo' => 'cohorte', 'estado' => $estadoFiltro ?: null, 'q' => $busqueda ?: null]) }}"
                    class="{{ $tipoFiltro === 'cohorte' ? $tabActivo : $tabInactivo }}">De Cohorte</a>
                <a href="{{ route('grupos.index', ['tipo' => 'suelto', 'estado' => $estadoFiltro ?: null, 'q' => $busqueda ?: null]) }}"
                    class="{{ $tipoFiltro === 'suelto' ? $tabActivo : $tabInactivo }}">Módulos Sueltos</a>
            </div>

            <div class="h-4 w-px bg-slate-200 hidden sm:block"></div>

            <!-- Estados -->
            <div class="flex flex-wrap gap-1.5">
                @php
                    $claseActivo = "px-3 py-1 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700 ring-1 ring-inset ring-indigo-200 transition-colors";
                    $claseInactivo = "px-3 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-600 hover:bg-slate-200 transition-colors";
                @endphp

                <a href="{{ route('grupos.index', ['tipo' => $tipoFiltro ?: null, 'q' => $busqueda ?: null]) }}"
                    class="{{ $estadoFiltro ? $claseInactivo : $claseActivo }}">Todos estados</a>

                @foreach (\App\Enums\EstadoPlanificacion::cases() as $e)
                    <a href="{{ route('grupos.index', ['tipo' => $tipoFiltro ?: null, 'estado' => $e->value, 'q' => $busqueda ?: null]) }}"
                        class="{{ $estadoFiltro === $e->value ? $claseActivo : $claseInactivo }}">{{ $e->label() }}</a>
                @endforeach
            </div>
        </div>

        <!-- Buscador -->
        <div class="w-full lg:w-auto lg:min-w-70">
            @include('partials.buscador', ['ruta' => route('grupos.index'), 'busqueda' => $busqueda, 'placeholder' => 'Buscar por grupo o módulo…'])
        </div>
    </div>

    @if ($grupos->isEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-slate-900">No hay grupos con ese filtro</h3>
            <p class="mt-1 text-sm text-slate-500">Crea un grupo para dictar un módulo con su docente y fechas.</p>
        </div>
    @else
        <div class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Módulo
                            </th>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Grupo
                            </th>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                Cohorte / Programa</th>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                Docente</th>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Inscritos</th>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Estado
                            </th>
                            <th scope="col" class="relative px-6 py-4"><span class="sr-only">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-100">
                        @foreach ($grupos as $grupo)
                            @php
                                $quorum = $grupo->quorum;
                            @endphp
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-4 text-sm font-medium text-slate-800">
                                    <div>{{ $grupo->modulo->nombre }}</div>
                                    <div class="text-xs text-slate-400 font-mono">{{ $grupo->modulo->codigo }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm font-bold text-indigo-600">{{ $grupo->nombre }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">
                                    @if ($grupo->cohorte)
                                        <a href="{{ route('cohortes.show', $grupo->cohorte) }}"
                                            class="font-medium text-indigo-600 hover:text-indigo-800 hover:underline">
                                            {{ $grupo->cohorte->nombre }}
                                        </a>
                                        <div class="text-xs text-slate-400 truncate max-w-55">{{ $grupo->cohorte->programa->nombre }}
                                        </div>
                                    @else
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-600">
                                            Módulo suelto
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700 flex items-center gap-2">
                                    <span
                                        class="w-6 h-6 rounded-full bg-slate-200 text-xs font-bold text-slate-600 flex items-center justify-center uppercase">{{ mb_substr($grupo->docente->nombres, 0, 1) . mb_substr($grupo->docente->apellidos, 0, 1) }}</span>
                                    {{ $grupo->docente->nombre_completo }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                            {{ $quorum['inscritos'] }} {{ $quorum['inscritos'] === 1 ? 'estudiante' : 'estudiantes' }}
                                        </span>
                                        @if ($quorum['tiene_limite'])
                                            <span class="text-xs {{ $quorum['alcanzado'] ? 'text-emerald-600 font-medium' : 'text-slate-500' }}">
                                                (mín. {{ $quorum['minimo'] }})
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex px-2.5 py-1 rounded-md text-xs font-medium {{ $grupo->estado->value === 'planificado' ? 'bg-amber-100 text-amber-700 ring-1 ring-inset ring-amber-600/20' : ($grupo->estado->value === 'ejecucion' ? 'bg-blue-100 text-blue-700 ring-1 ring-inset ring-blue-600/20' : 'bg-slate-100 text-slate-700 ring-1 ring-inset ring-slate-500/20') }}">
                                        {{ $grupo->estado->label() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('grupos.show', $grupo) }}"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-600 bg-white border border-slate-300 rounded-md hover:bg-slate-50 hover:text-slate-900 transition-colors shadow-sm">
                                            <x-icono nombre="usuarios" class="w-4 h-4 text-slate-400" /> Ver
                                        </a>
                                        <a href="{{ route('grupos.edit', $grupo) }}"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-600 bg-white border border-slate-300 rounded-md hover:bg-slate-50 hover:text-indigo-600 transition-colors shadow-sm">
                                            <x-icono nombre="editar" class="w-4 h-4 text-slate-400" /> Editar
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        {{ $grupos->links('partials.paginacion') }}
    @endif
@endsection