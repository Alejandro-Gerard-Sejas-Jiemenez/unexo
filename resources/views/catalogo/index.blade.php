@extends('layouts.app')
@section('titulo', 'Catálogo')
@section('contenido')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
    <div>
        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Catálogo académico</h1>
        <p class="mt-2 text-lg text-slate-500">Esta es la oferta abierta de {{ config('app.name') }}. La inscripción la realiza la administración.</p>
    </div>
    @if (auth()->user()->rol === \App\Enums\Rol::Administrador)
        <div>
            <a href="{{ route('programas.create') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-lg hover:bg-indigo-700 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <x-icono nombre="mas" class="w-4 h-4" /> Nuevo programa
            </a>
        </div>
    @endif
</div>

<section aria-labelledby="titulo-programas" class="mb-12">
    <div class="mb-6 flex items-center gap-3">
        <h2 id="titulo-programas" class="text-2xl font-bold text-slate-800">Programas</h2>
        <span class="inline-flex items-center justify-center bg-slate-100 text-slate-600 text-sm font-medium px-2.5 py-0.5 rounded-full">
            {{ $programas->count() }}
        </span>
    </div>

    @forelse ($programas as $programa)
        @if ($loop->first)<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">@endif
        
        @php
            $colores = [
                0 => 'from-blue-500 to-indigo-600',
                1 => 'from-emerald-500 to-teal-600',
                2 => 'from-violet-500 to-purple-600',
                3 => 'from-rose-500 to-pink-600',
            ];
            $colorFondo = $colores[abs(crc32($programa->codigo)) % 4];
        @endphp

        <article class="flex flex-col bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden hover:shadow-md transition-shadow">
            <div class="bg-gradient-to-r {{ $colorFondo }} p-6 text-white">
                <span class="inline-block px-2.5 py-1 bg-white/20 backdrop-blur-sm rounded-md text-xs font-bold tracking-wider mb-3">
                    {{ $programa->codigo }} · v{{ $programa->version }}
                </span>
                <h3 class="text-xl font-bold leading-tight">{{ $programa->nombre }}</h3>
            </div>
            
            <div class="p-6 flex-1 flex flex-col">
                @if ($programa->descripcion)
                    <p class="text-slate-600 mb-4 line-clamp-3">{{ $programa->descripcion }}</p>
                @endif
                
                <div class="mt-auto pt-4 flex items-center gap-4 text-sm text-slate-600 border-t border-slate-100">
                    <span class="flex items-center gap-1.5 font-medium">
                        <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                        {{ $programa->modulos->count() }} {{ $programa->modulos->count() === 1 ? 'módulo' : 'módulos' }}
                    </span>
                    
                    @if ($programa->modulos->sum('duracion_horas') > 0)
                        <span class="flex items-center gap-1.5 font-medium">
                            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            {{ $programa->modulos->sum('duracion_horas') }} horas
                        </span>
                    @endif
                </div>
            </div>
            
            <details class="group bg-slate-50 border-t border-slate-200">
                <summary class="flex items-center gap-2 px-6 py-3 text-sm font-medium text-indigo-600 cursor-pointer hover:bg-slate-100 transition-colors list-none select-none">
                    <svg class="w-4 h-4 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    Ver módulos
                </summary>
                <div class="px-6 pb-4 pt-1">
                    <ol class="space-y-3 relative border-l border-slate-200 ml-2.5">
                        @foreach ($programa->modulos as $modulo)
                            <li class="pl-5 relative">
                                <span class="absolute -left-1.5 top-1.5 w-3 h-3 rounded-full bg-slate-200 border-2 border-white"></span>
                                <div class="text-sm font-medium text-slate-900">{{ $modulo->nombre }}</div>
                                
                                <div class="flex flex-col gap-0.5 mt-0.5">
                                    @if ($modulo->duracion_horas)
                                        <span class="text-xs text-slate-500">{{ $modulo->duracion_horas }} horas</span>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </details>
        </article>
        
        @if ($loop->last)</div>@endif
    @empty
        <div class="bg-white border border-slate-200 rounded-xl p-12 text-center shadow-sm max-w-3xl">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-100 mb-4">
                <svg class="w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
            </div>
            <h3 class="text-lg font-medium text-slate-900 mb-1">Todavía no hay programas abiertos</h3>
            <p class="text-slate-500">Cuando se abra uno, aparecerá aquí con sus módulos.</p>
        </div>
    @endforelse
</section>

<section aria-labelledby="titulo-sueltos">
    <div class="mb-6 flex items-center gap-3">
        <h2 id="titulo-sueltos" class="text-2xl font-bold text-slate-800">Todos los módulos</h2>
        <span class="inline-flex items-center justify-center bg-slate-100 text-slate-600 text-sm font-medium px-2.5 py-0.5 rounded-full">
            {{ $modulos->count() }}
        </span>
    </div>

    @forelse ($modulos as $modulo)
        @if ($loop->first)<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">@endif
        
        <article class="flex flex-col bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden hover:shadow-md transition-shadow">
            <div class="bg-slate-100 p-5 border-b border-slate-200">
                <span class="inline-block px-2 py-0.5 bg-white rounded text-xs font-bold text-slate-600 tracking-wider mb-2 border border-slate-200">
                    {{ $modulo->codigo }}
                </span>
                <h3 class="text-lg font-bold text-slate-900 leading-tight">{{ $modulo->nombre }}</h3>
            </div>
            <div class="p-5 flex-1 flex flex-col justify-between">
                
                @if ($modulo->duracion_horas)
                    <div class="mt-auto flex items-center gap-1.5 text-sm font-medium text-slate-600">
                        <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        {{ $modulo->duracion_horas }} horas
                    </div>
                @endif
            </div>
        </article>
        
        @if ($loop->last)</div>@endif
    @empty
        <div class="bg-white border border-slate-200 rounded-xl p-8 shadow-sm max-w-3xl flex items-center gap-4">
            <div class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-slate-100">
                <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
            </div>
            <p class="text-slate-600">No hay módulos sueltos abiertos por ahora.</p>
        </div>
    @endforelse
</section>
@endsection
