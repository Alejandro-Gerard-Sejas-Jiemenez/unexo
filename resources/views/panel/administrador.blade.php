@extends('layouts.app')
@section('titulo', 'Inicio')
@section('contenido')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
    <div>
        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Hola, {{ auth()->user()->nombres }}</h1>
        <p class="mt-2 text-lg text-slate-500">Resumen del catálogo académico de {{ config('app.name') }}.</p>
    </div>
    <div>
        <a href="{{ route('programas.create') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-lg hover:bg-indigo-700 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            <x-icono nombre="mas" class="w-4 h-4" /> Nuevo programa
        </a>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
    <!-- KPI 1 -->
    <a href="{{ route('catalogo') }}" class="block bg-white rounded-xl border border-slate-200 shadow-sm p-6 hover:shadow-md hover:border-indigo-300 transition-all group">
        <div class="flex items-center justify-between mb-4">
            <span class="text-sm font-semibold text-slate-500 uppercase tracking-wider">Visible para CRM</span>
            <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
            </div>
        </div>
        <div class="mb-1">
            <span class="text-3xl font-bold text-slate-900 group-hover:text-indigo-600 transition-colors">{{ $oferta['programas'] }}</span>
        </div>
        <p class="text-sm text-slate-500">{{ $oferta['programas'] === 1 ? 'programa abierto' : 'programas abiertos' }} · {{ $oferta['modulos'] }} {{ $oferta['modulos'] === 1 ? 'módulo' : 'módulos' }}</p>
    </a>

    <!-- KPI 2 -->
    <a href="{{ route('programas.index') }}" class="block bg-white rounded-xl border border-slate-200 shadow-sm p-6 hover:shadow-md hover:border-indigo-300 transition-all group">
        <div class="flex items-center justify-between mb-4">
            <span class="text-sm font-semibold text-slate-500 uppercase tracking-wider">Programas</span>
            <div class="p-2 bg-slate-50 rounded-lg text-slate-600 group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
            </div>
        </div>
        <div class="mb-1">
            <span class="text-3xl font-bold text-slate-900 group-hover:text-indigo-600 transition-colors">{{ $totales['programas'] }}</span>
        </div>
        <p class="text-sm text-slate-500">en el catálogo</p>
    </a>

    <!-- KPI 3 -->
    <a href="{{ route('modulos.index') }}" class="block bg-white rounded-xl border border-slate-200 shadow-sm p-6 hover:shadow-md hover:border-indigo-300 transition-all group">
        <div class="flex items-center justify-between mb-4">
            <span class="text-sm font-semibold text-slate-500 uppercase tracking-wider">Módulos</span>
            <div class="p-2 bg-slate-50 rounded-lg text-slate-600 group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
            </div>
        </div>
        <div class="mb-1">
            <span class="text-3xl font-bold text-slate-900 group-hover:text-indigo-600 transition-colors">{{ $totales['modulos'] }}</span>
        </div>
        <p class="text-sm text-slate-500">en el catálogo</p>
    </a>

    <!-- KPI 4 -->
    <a href="{{ route('usuarios.index') }}" class="block bg-white rounded-xl border border-slate-200 shadow-sm p-6 hover:shadow-md hover:border-indigo-300 transition-all group">
        <div class="flex items-center justify-between mb-4">
            <span class="text-sm font-semibold text-slate-500 uppercase tracking-wider">Usuarios</span>
            <div class="p-2 bg-slate-50 rounded-lg text-slate-600 group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
            </div>
        </div>
        <div class="mb-1">
            <span class="text-3xl font-bold text-slate-900 group-hover:text-indigo-600 transition-colors">{{ $totales['usuarios'] }}</span>
        </div>
        <p class="text-sm text-slate-500">{{ $totales['docentes'] }} docentes · {{ $totales['estudiantes'] }} estudiantes</p>
    </a>
</div>

<section aria-labelledby="titulo-atencion" class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden">
    <div class="px-6 py-5 border-b border-slate-200 bg-slate-50/50">
        <h2 id="titulo-atencion" class="text-lg font-bold text-slate-800">Requiere atención</h2>
    </div>
    <div class="p-6">
        @if (count($avisos) > 0)
            <ul class="space-y-4">
                @foreach ($avisos as $aviso)
                    <li>
                        <a href="{{ $aviso['ruta'] }}" class="flex items-center justify-between p-4 rounded-xl border border-amber-200 bg-amber-50 hover:bg-amber-100 transition-colors group">
                            <div class="flex items-center gap-4">
                                <span class="flex items-center justify-center w-10 h-10 rounded-full bg-amber-200 text-amber-800 font-bold text-lg">
                                    {{ $aviso['cantidad'] }}
                                </span>
                                <span class="font-medium text-amber-900">
                                    {{ $aviso['cantidad'] === 1 ? $aviso['singular'] : $aviso['plural'] }}
                                </span>
                            </div>
                            <span class="flex items-center gap-1 text-sm font-semibold text-amber-700 group-hover:text-amber-900 transition-colors">
                                Revisar <x-icono nombre="siguiente" class="w-4 h-4" />
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @else
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-6 flex items-center gap-4">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-emerald-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" /></svg>
                </div>
                <div>
                    <h3 class="text-base font-medium text-emerald-800">Todo al día</h3>
                    <p class="mt-1 text-sm text-emerald-700">El catálogo está al día: no hay nada pendiente de revisar.</p>
                </div>
            </div>
        @endif
    </div>
</section>
@endsection
