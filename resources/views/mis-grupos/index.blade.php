@extends('layouts.app')
@section('titulo', 'Mis grupos')
@section('contenido')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
    <div>
        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Mis grupos</h1>
        <p class="mt-2 text-lg text-slate-500">Los grupos que tienes asignados y los estudiantes inscritos en cada uno.</p>
    </div>
    <div>
        <a href="{{ route('catalogo') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500">
            <x-icono nombre="catalogo" class="w-4 h-4 text-slate-400" /> Ver catálogo
        </a>
    </div>
</div>

@if ($hayGrupos)
    <div class="mb-8">
        @include('partials.filtro-clases', ['ruta' => route('mis-grupos.index'), 'fase' => $fase, 'busqueda' => $busqueda, 'conteo' => $conteo, 'total' => $total])
    </div>
@endif

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 mb-12">
    @forelse ($grupos as $grupo)
        @php
            $colores = [
                0 => 'from-blue-500 to-indigo-600',
                1 => 'from-emerald-500 to-teal-600',
                2 => 'from-violet-500 to-purple-600',
                3 => 'from-rose-500 to-pink-600',
            ];
            $colorFondo = $colores[abs(crc32($grupo->modulo->codigo)) % 4];
        @endphp
        
        <article class="flex flex-col bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden hover:shadow-md transition-shadow">
            <div class="bg-gradient-to-r {{ $colorFondo }} p-5 text-white">
                <span class="inline-block px-2.5 py-1 bg-white/20 backdrop-blur-sm rounded-md text-xs font-bold tracking-wider mb-3">
                    {{ $grupo->cohorte ? $grupo->cohorte->programa->nombre.' · '.$grupo->cohorte->nombre : 'Módulo suelto' }}
                </span>
                <h3 class="text-xl font-bold leading-tight">{{ $grupo->modulo->nombre }}</h3>
            </div>
            
            <div class="p-5 flex-1 flex flex-col">
                <dl class="grid grid-cols-1 gap-y-3 text-sm mb-5">
                    <div>
                        <dt class="font-medium text-slate-500">Grupo</dt>
                        <dd class="text-slate-900 mt-0.5 font-medium">{{ $grupo->nombre }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-slate-500">Fechas</dt>
                        <dd class="text-slate-900 mt-0.5">{{ $grupo->fecha_inicio->format('d/m/Y') }} al {{ $grupo->fecha_fin->format('d/m/Y') }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-slate-500">Modalidad</dt>
                        <dd class="text-slate-900 mt-0.5">
                            {{ $grupo->modalidad->label() }}@if ($grupo->aula) · {{ $grupo->aula->nombre }}@endif
                        </dd>
                    </div>
                    <div>
                        <dt class="font-medium text-slate-500">Estudiantes</dt>
                        <dd class="mt-0.5">
                            <span class="inline-flex items-center justify-center bg-slate-100 text-slate-700 text-xs font-bold px-2 py-0.5 rounded-full border border-slate-200">
                                {{ $grupo->inscritos_count }} inscritos
                            </span>
                        </dd>
                    </div>
                </dl>
            </div>
            
            <div class="bg-slate-50 p-4 border-t border-slate-200 flex flex-wrap gap-3 items-center justify-between">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ 
                    $grupo->fase === 'en-curso' ? 'bg-indigo-100 text-indigo-800 border border-indigo-200' : 
                    ($grupo->fase === 'finalizado' ? 'bg-slate-200 text-slate-800 border border-slate-300' : 'bg-blue-50 text-blue-700 border border-blue-200')
                }}">
                    {{ $grupo->fase_etiqueta }}
                </span>
                
                <a href="{{ route('mis-grupos.show', $grupo) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-indigo-600 bg-white border border-indigo-200 rounded-lg hover:bg-indigo-50 hover:border-indigo-300 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <x-icono nombre="usuarios" class="w-3.5 h-3.5" /> Ver estudiantes
                </a>
            </div>
        </article>
    @empty
        <div class="col-span-1 md:col-span-2 xl:col-span-3">
            <div class="bg-white border border-slate-200 rounded-xl p-12 text-center shadow-sm max-w-3xl mx-auto">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-100 mb-4">
                    <svg class="w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                </div>
                
                @if ($hayGrupos)
                    <h3 class="text-lg font-medium text-slate-900 mb-1">No hay grupos con ese filtro</h3>
                    <p class="text-slate-500">Prueba con otra búsqueda o muestra todos tus grupos.</p>
                @else
                    <h3 class="text-lg font-medium text-slate-900 mb-1">Todavía no tienes grupos asignados</h3>
                    <p class="text-slate-500">Cuando la administración te asigne un grupo, aparecerá aquí con sus estudiantes.</p>
                @endif
            </div>
        </div>
    @endforelse
</div>
@endsection
