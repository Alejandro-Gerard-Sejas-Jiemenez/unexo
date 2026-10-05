@extends('layouts.app')
@section('titulo', 'Panel del estudiante')
@section('contenido')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
    <div>
        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Hola, {{ auth()->user()->nombres }}</h1>
        <p class="mt-2 text-lg text-slate-500">Bienvenido a tu portal estudiantil.</p>
    </div>
    <div>
        <a href="{{ route('mis-cursos') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-lg hover:bg-indigo-700 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            <x-icono nombre="catalogo" class="w-4 h-4" /> Ver mis cursos
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Main content column -->
    <div class="lg:col-span-2 space-y-8">
        <section aria-labelledby="titulo-proximos" class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-200 bg-slate-50/50">
                <h2 id="titulo-proximos" class="text-lg font-bold text-slate-800">Tus próximos cursos</h2>
            </div>
            <div class="p-0">
                @if ($inscripciones->isEmpty())
                    <div class="p-8 text-center">
                        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-100 mb-4">
                            <svg class="w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                        </div>
                        <h3 class="text-base font-medium text-slate-900 mb-1">No tienes cursos activos por ahora</h3>
                        <p class="text-sm text-slate-500 mb-4">Cuando la administración te inscriba, los verás aquí. Mientras tanto puedes revisar la oferta disponible.</p>
                        <a href="{{ route('catalogo') }}" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition-colors">
                            Ir al catálogo
                        </a>
                    </div>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($inscripciones as $inscripcion)
                            <li class="hover:bg-slate-50 transition-colors">
                                <a href="{{ route('mis-cursos') }}" class="flex items-center justify-between p-4 sm:p-6 group">
                                    <div class="flex-1 min-w-0 pr-4">
                                        <h3 class="text-base font-bold text-slate-900 group-hover:text-indigo-600 transition-colors truncate">
                                            {{ $inscripcion->grupo->modulo->nombre }}
                                        </h3>
                                        <div class="mt-1 flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3 text-sm text-slate-500">
                                            <span class="flex items-center gap-1">
                                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                                {{ $inscripcion->grupo->fecha_inicio->format('d/m/Y') }} al {{ $inscripcion->grupo->fecha_fin->format('d/m/Y') }}
                                            </span>
                                            <span class="hidden sm:inline text-slate-300">•</span>
                                            <span class="flex items-center gap-1">
                                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                                {{ $inscripcion->grupo->docente->nombre_completo }}
                                            </span>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ 
                                            $inscripcion->grupo->fase === 'en-curso' ? 'bg-indigo-100 text-indigo-800' : 
                                            ($inscripcion->grupo->fase === 'finalizado' ? 'bg-slate-100 text-slate-800' : 'bg-blue-50 text-blue-700')
                                        }} border {{
                                            $inscripcion->grupo->fase === 'en-curso' ? 'border-indigo-200' : 
                                            ($inscripcion->grupo->fase === 'finalizado' ? 'border-slate-200' : 'border-blue-200')
                                        }}">
                                            {{ $inscripcion->grupo->fase_etiqueta }}
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
