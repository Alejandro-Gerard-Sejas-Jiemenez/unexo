@extends('layouts.app')
@section('titulo', 'Matrículas')
@section('contenido')
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-slate-800 tracking-tight">Matrículas</h1>
            <p class="text-sm text-slate-500 mt-1">Inscripciones de estudiantes a ediciones o módulos sueltos.</p>
        </div>
        <a class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 shadow-sm transition-all focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            href="{{ route('matriculas.create') }}">
            <x-icono nombre="mas" class="w-5 h-5" /> Nueva matrícula
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

            <a href="{{ route('matriculas.index', ['q' => $busqueda ?: null]) }}"
                class="{{ $estadoFiltro ? $claseInactivo : $claseActivo }}">Todas</a>

            @foreach (\App\Enums\EstadoMatricula::cases() as $e)
                <a href="{{ route('matriculas.index', ['estado' => $e->value, 'q' => $busqueda ?: null]) }}"
                    class="{{ $estadoFiltro === $e->value ? $claseActivo : $claseInactivo }}">{{ $e->label() }}</a>
            @endforeach
        </div>

        <!-- Buscador -->
        <div class="w-full lg:w-auto lg:min-w-75">
            @include('partials.buscador', ['ruta' => route('matriculas.index'), 'busqueda' => $busqueda, 'placeholder' => 'Buscar por estudiante…'])
        </div>
    </div>

    @if ($matriculas->isEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-slate-900">No hay matrículas con ese filtro</h3>
            <p class="mt-1 text-sm text-slate-500">Registra una matrícula para inscribir a un estudiante.</p>
        </div>
    @else
        <div class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                Estudiante</th>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                Inscripción</th>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Grupos
                            </th>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Fecha
                            </th>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Estado
                            </th>
                            <th scope="col" class="relative px-6 py-4"><span class="sr-only">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-100">
                        @foreach ($matriculas as $matricula)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-800 flex items-center gap-2">
                                    <span
                                        class="w-7 h-7 rounded-full bg-slate-200 text-xs font-bold text-slate-600 flex items-center justify-center uppercase">{{ mb_substr($matricula->estudiante->nombres, 0, 1) . mb_substr($matricula->estudiante->apellidos, 0, 1) }}</span>
                                    {{ $matricula->estudiante->apellidos }}, {{ $matricula->estudiante->nombres }}
                                </td>
                                <td class="px-6 py-4 text-sm font-medium text-slate-600">
                                    {{ $matricula->cohorte ? $matricula->cohorte->programa->nombre . ' · ' . $matricula->cohorte->nombre : 'Módulo suelto' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center justify-center px-2.5 py-1 text-xs font-bold leading-none text-slate-700 bg-slate-100 rounded-full">{{ $matricula->inscripciones_count }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                    {{ $matricula->fecha_matricula->format('d/m/Y') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex px-2.5 py-1 rounded-md text-xs font-medium {{ $matricula->estado->value === 'activa' ? 'bg-green-100 text-green-700 ring-1 ring-inset ring-green-600/20' : ($matricula->estado->value === 'congelada' ? 'bg-amber-100 text-amber-700 ring-1 ring-inset ring-amber-600/20' : 'bg-slate-100 text-slate-700 ring-1 ring-inset ring-slate-500/20') }}">
                                        {{ $matricula->estado->label() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="{{ route('matriculas.show', $matricula) }}"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-600 bg-white border border-slate-300 rounded-md hover:bg-slate-50 hover:text-indigo-600 transition-colors shadow-sm">
                                        <x-icono nombre="usuarios" class="w-4 h-4 text-slate-400" /> Ver
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        {{ $matriculas->links('partials.paginacion') }}
    @endif
@endsection