@extends('layouts.app')
@section('titulo', 'Programas')
@section('contenido')
<div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <h1 class="text-3xl font-bold text-slate-800 tracking-tight">Programas</h1>
    <a class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 shadow-sm transition-all focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('programas.create') }}">
        <x-icono nombre="mas" class="w-5 h-5" /> Nuevo programa
    </a>
</div>

<div class="mb-6 flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white p-4 rounded-xl shadow-sm border border-slate-200">
    <!-- Filtros -->
    <div class="flex flex-wrap gap-2">
        @php
            $claseActivo = "px-4 py-1.5 rounded-full text-sm font-medium bg-indigo-100 text-indigo-700 ring-1 ring-inset ring-indigo-200 transition-colors";
            $claseInactivo = "px-4 py-1.5 rounded-full text-sm font-medium bg-slate-100 text-slate-600 hover:bg-slate-200 transition-colors";
        @endphp
        
        <a href="{{ route('programas.index', ['q' => $busqueda ?: null]) }}" class="{{ $estadoFiltro || $sinModulosAbiertos ? $claseInactivo : $claseActivo }}">Todos</a>
        
        @foreach (\App\Enums\EstadoComercial::cases() as $e)
            <a href="{{ route('programas.index', ['estado' => $e->value, 'q' => $busqueda ?: null]) }}" class="{{ $estadoFiltro === $e->value ? $claseActivo : $claseInactivo }}">{{ $e->label() }}</a>
        @endforeach
        
        <a href="{{ route('programas.index', ['atencion' => 'sin-modulos-abiertos', 'q' => $busqueda ?: null]) }}" class="{{ $sinModulosAbiertos ? $claseActivo : $claseInactivo }}">Abiertos sin módulos abiertos</a>
    </div>

    <!-- Buscador -->
    <div class="w-full lg:w-auto lg:min-w-[300px]">
        @include('partials.buscador', ['ruta' => route('programas.index'), 'busqueda' => $busqueda, 'placeholder' => 'Buscar por código o nombre…'])
    </div>
</div>

<div class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Código</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Nombre</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Versión</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Módulos</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Estado comercial</th>
                    <th scope="col" class="relative px-6 py-4"><span class="sr-only">Acciones</span></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-100">
            @forelse ($programas as $p)
                <tr class="hover:bg-slate-50/50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 font-medium">{{ $p->codigo }}</td>
                    <td class="px-6 py-4 text-sm font-medium text-slate-900">
                        <a href="{{ route('programas.show', $p) }}" class="text-indigo-600 hover:text-indigo-900 hover:underline">{{ $p->nombre }}</a>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $p->version }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center justify-center px-2.5 py-1 text-xs font-bold leading-none text-slate-700 bg-slate-100 rounded-full">{{ $p->modulos_count }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex px-2.5 py-1 rounded-md text-xs font-medium {{ $p->estado_comercial->value === 'abierto' ? 'bg-green-100 text-green-700 ring-1 ring-inset ring-green-600/20' : 'bg-slate-100 text-slate-700 ring-1 ring-inset ring-slate-500/20' }}">
                            {{ $p->estado_comercial->label() }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('programas.show', $p) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-600 bg-white border border-slate-300 rounded-md hover:bg-slate-50 hover:text-slate-900 transition-colors shadow-sm">
                                <x-icono nombre="modulos" class="w-4 h-4 text-slate-400" /> Módulos
                            </a>
                            <a href="{{ route('programas.edit', $p) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-600 bg-white border border-slate-300 rounded-md hover:bg-slate-50 hover:text-indigo-600 transition-colors shadow-sm">
                                <x-icono nombre="editar" class="w-4 h-4 text-slate-400" /> Editar
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-slate-900">No se encontraron programas</h3>
                        <p class="mt-1 text-sm text-slate-500">Intenta cambiar los filtros de búsqueda.</p>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
{{ $programas->links('partials.paginacion') }}
@endsection
