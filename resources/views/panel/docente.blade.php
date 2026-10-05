@extends('layouts.app')
@section('titulo', 'Panel del docente')
@section('contenido')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
    <div>
        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Hola, {{ auth()->user()->nombres }}</h1>
        <p class="mt-2 text-lg text-slate-500">Bienvenido al panel principal de docencia.</p>
    </div>
    <div>
        <a href="{{ route('mis-grupos.index') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-lg hover:bg-indigo-700 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            <x-icono nombre="usuarios" class="w-4 h-4" /> Ver mis grupos
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Main content column -->
    <div class="lg:col-span-2 space-y-8">
        <section aria-labelledby="titulo-proximos" class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-200 bg-slate-50/50">
                <h2 id="titulo-proximos" class="text-lg font-bold text-slate-800">Tus próximos grupos</h2>
            </div>
            <div class="p-0">
                @if ($grupos->isEmpty())
                    <div class="p-8 text-center">
                        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-100 mb-4">
                            <svg class="w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                        </div>
                        <h3 class="text-base font-medium text-slate-900 mb-1">No tienes grupos activos por ahora</h3>
                        <p class="text-sm text-slate-500">Cuando la administración te asigne un grupo, lo verás aquí con sus estudiantes.</p>
                    </div>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($grupos as $grupo)
                            <li class="hover:bg-slate-50 transition-colors">
                                <a href="{{ route('mis-grupos.show', $grupo) }}" class="flex items-center justify-between p-4 sm:p-6 group">
                                    <div class="flex-1 min-w-0 pr-4">
                                        <h3 class="text-base font-bold text-slate-900 group-hover:text-indigo-600 transition-colors truncate">
                                            {{ $grupo->modulo->nombre }}
                                        </h3>
                                        <div class="mt-1 flex items-center gap-3 text-sm text-slate-500">
                                            <span class="flex items-center gap-1">
                                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                                {{ $grupo->fecha_inicio->format('d/m/Y') }} al {{ $grupo->fecha_fin->format('d/m/Y') }}
                                            </span>
                                            <span class="hidden sm:inline text-slate-300">•</span>
                                            <span class="flex items-center gap-1">
                                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                                {{ $grupo->inscritos_count }} {{ $grupo->inscritos_count === 1 ? 'estudiante' : 'estudiantes' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ 
                                            $grupo->fase === 'en-curso' ? 'bg-indigo-100 text-indigo-800' : 
                                            ($grupo->fase === 'finalizado' ? 'bg-slate-100 text-slate-800' : 'bg-blue-50 text-blue-700')
                                        }} border {{
                                            $grupo->fase === 'en-curso' ? 'border-indigo-200' : 
                                            ($grupo->fase === 'finalizado' ? 'border-slate-200' : 'border-blue-200')
                                        }}">
                                            {{ $grupo->fase_etiqueta }}
                                        </span>
                                    </div>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>
    </div>

    <!-- Sidebar column -->
    <div class="lg:col-span-1 space-y-8">
        <section aria-labelledby="titulo-perfil" class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-200 bg-slate-50/50">
                <h2 id="titulo-perfil" class="text-lg font-bold text-slate-800">Mi perfil</h2>
            </div>
            <div class="p-6">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-16 h-16 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold text-2xl uppercase border-2 border-white shadow-sm">
                        {{ substr(auth()->user()->nombres, 0, 1) }}{{ substr(auth()->user()->apellidos, 0, 1) }}
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900">{{ auth()->user()->nombre_completo }}</h3>
                        <p class="text-sm text-slate-500 capitalize">{{ auth()->user()->rol->value }}</p>
                    </div>
                </div>
                
                <dl class="space-y-4">
                    <div>
                        <dt class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Correo institucional</dt>
                        <dd class="text-sm text-slate-900 font-medium bg-slate-50 p-2.5 rounded-lg border border-slate-100 break-all">
                            {{ auth()->user()->correo_institucional ?? 'Sin asignar' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Teléfono</dt>
                        <dd class="text-sm text-slate-900 font-medium bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                            {{ auth()->user()->telefono ?? '—' }}
                        </dd>
                    </div>
                </dl>
            </div>
        </section>
    </div>
</div>
@endsection
