@extends('layouts.app')
@section('titulo', $programa->nombre)
@section('contenido')
<div class="mb-4 flex flex-col md:flex-row md:items-start md:justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold text-slate-800 tracking-tight flex items-center gap-3">
            {{ $programa->nombre }} 
            <span class="text-sm font-semibold bg-slate-100 text-slate-500 px-2.5 py-0.5 rounded-full ring-1 ring-inset ring-slate-200">v{{ $programa->version }}</span>
        </h1>
        <p class="mt-2 text-slate-500 font-medium flex items-center gap-2">
            <span class="text-indigo-600 font-bold">{{ $programa->codigo }}</span>
            @if ($programa->descripcion) 
                <span class="text-slate-300">&bull;</span> 
                <span>{{ $programa->descripcion }}</span>
            @endif
        </p>
    </div>
    
    <div class="flex flex-wrap items-center gap-3">
        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold {{ $programa->estado_comercial->value === 'abierto' ? 'bg-green-100 text-green-700 ring-1 ring-inset ring-green-600/20' : 'bg-slate-100 text-slate-700 ring-1 ring-inset ring-slate-500/20' }}">
            {{ $programa->estado_comercial->label() }}
        </span>
        <a class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 hover:text-indigo-600 transition-colors shadow-sm" href="{{ route('programas.edit', $programa) }}">
            <x-icono nombre="editar" class="w-4 h-4 text-slate-400" /> Editar
        </a>
        <a class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors shadow-sm" href="{{ route('programas.index') }}">
            <x-icono nombre="volver" class="w-4 h-4 text-slate-400" /> Volver
        </a>
    </div>
</div>

<div class="mt-10 mb-4 flex items-center justify-between">
    <h2 class="text-xl font-bold text-slate-800">Módulos del programa (en orden)</h2>
</div>

<div class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden mb-10">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider w-16">#</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Código</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Módulo</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Horas</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Estado</th>
                    <th scope="col" class="px-6 py-4 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-100">
            @forelse ($programa->modulos as $modulo)
                <tr class="hover:bg-slate-50/50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-400">{{ $modulo->pivot->orden }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-600">{{ $modulo->codigo }}</td>
                    <td class="px-6 py-4 text-sm font-medium text-slate-900">{{ $modulo->nombre }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $modulo->duracion_horas ?? '—' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex px-2.5 py-1 rounded-md text-xs font-medium {{ $modulo->estado_comercial->value === 'abierto' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-700' }}">
                            {{ $modulo->estado_comercial->label() }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right">
                        <div class="flex items-center justify-end gap-1.5">
                            @unless ($loop->first)
                            <form method="POST" action="{{ route('programas.modulos.mover', [$programa, $modulo]) }}" class="inline">
                                @csrf @method('PATCH')
                                <input type="hidden" name="direccion" value="subir">
                                <button type="submit" title="Subir" class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-md transition-colors">
                                    <x-icono nombre="subir" class="w-4 h-4" />
                                </button>
                            </form>
                            @endunless
                            
                            @unless ($loop->last)
                            <form method="POST" action="{{ route('programas.modulos.mover', [$programa, $modulo]) }}" class="inline">
                                @csrf @method('PATCH')
                                <input type="hidden" name="direccion" value="bajar">
                                <button type="submit" title="Bajar" class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-md transition-colors">
                                    <x-icono nombre="bajar" class="w-4 h-4" />
                                </button>
                            </form>
                            @endunless
                            
                            <form method="POST" action="{{ route('programas.modulos.quitar', [$programa, $modulo]) }}" class="inline ml-1">
                                @csrf @method('DELETE')
                                <button type="submit" title="Quitar" class="p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-md transition-colors">
                                    <x-icono nombre="cerrar" class="w-4 h-4" />
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-sm text-slate-500 bg-slate-50/50">
                        Este programa todavía no tiene módulos.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<h2 class="text-lg font-bold text-slate-800 mb-3">Agregar un módulo</h2>
<div class="bg-slate-50 border border-slate-200 p-5 rounded-xl">
    @if ($disponibles->isEmpty())
        <div class="flex items-center gap-2 text-sm text-slate-600">
            <svg class="w-5 h-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            <span>No quedan módulos por agregar.</span>
            <a href="{{ route('modulos.create') }}" class="text-indigo-600 hover:text-indigo-800 font-medium hover:underline">Crear un módulo nuevo</a>
        </div>
    @else
        <form method="POST" action="{{ route('programas.modulos.agregar', $programa) }}" class="flex flex-col sm:flex-row gap-3">
            @csrf
            <div class="flex-grow max-w-lg campo-caja">
                <select name="modulo_id" aria-label="Módulo a agregar" class="campo-control">
                    @foreach ($disponibles as $m)
                        <option value="{{ $m->id }}">{{ $m->codigo }} &bull; {{ $m->nombre }}</option>
                    @endforeach
                </select>
                <x-icono nombre="chevron" class="campo-flecha" />
            </div>
            <button type="submit" class="btn btn-primario">
                <x-icono nombre="mas" class="w-4 h-4" /> Agregar al final
            </button>
        </form>
        @if ($disponiblesTotal > $disponibles->count())
            <p class="mt-2 text-xs text-amber-600">Se muestran los primeros {{ $disponibles->count() }} de {{ $disponiblesTotal }} módulos disponibles.</p>
        @endif
    @endif
</div>
@endsection
