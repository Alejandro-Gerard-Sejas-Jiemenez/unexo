@extends('layouts.app')
@section('titulo', 'Calendario')
@section('contenido')
<div class="mb-8">
    <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Calendario de clases</h1>
    <p class="mt-2 text-lg text-slate-500">Fechas de inicio y fin de tus {{ auth()->user()->rol === \App\Enums\Rol::Docente ? 'grupos' : 'cursos' }}.</p>
</div>

<div class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden mb-10">
    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 bg-slate-50">
        <a href="{{ route('calendario', ['mes' => $mesAnterior]) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-slate-600 bg-white border border-slate-300 rounded-md hover:bg-slate-50 hover:text-indigo-600 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500">
            <x-icono nombre="volver" class="w-4 h-4" /> <span class="hidden sm:inline">Mes anterior</span>
        </a>
        <h2 class="text-lg font-bold text-slate-800 capitalize">{{ \Illuminate\Support\Str::ucfirst($mes->locale('es')->isoFormat('MMMM [de] YYYY')) }}</h2>
        <a href="{{ route('calendario', ['mes' => $mesSiguiente]) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-slate-600 bg-white border border-slate-300 rounded-md hover:bg-slate-50 hover:text-indigo-600 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500">
            <span class="hidden sm:inline">Mes siguiente</span> <x-icono nombre="siguiente" class="w-4 h-4" />
        </a>
    </div>

    <div class="px-6 py-3 border-b border-slate-200 flex gap-6 text-sm bg-white">
        <span class="flex items-center gap-2 text-slate-600 font-medium">
            <span class="w-3 h-3 rounded-full bg-emerald-500 border-2 border-emerald-200"></span> Inicio de clases
        </span>
        <span class="flex items-center gap-2 text-slate-600 font-medium">
            <span class="w-3 h-3 rounded-full bg-rose-500 border-2 border-rose-200"></span> Fin de clases
        </span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse min-w-[700px] table-fixed">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-xs font-bold text-slate-500 uppercase tracking-wider">
                    @foreach (['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $dia)
                        <th scope="col" class="px-2 py-3 text-center w-[14.28%] border-r border-slate-200 last:border-r-0">{{ $dia }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
                @foreach ($semanas as $semana)
                    <tr>
                        @foreach ($semana as $celda)
                            <td class="h-28 sm:h-32 align-top border-r border-slate-200 last:border-r-0 relative {{ $celda['delMes'] ? 'bg-white' : 'bg-slate-50/80 text-slate-400' }} {{ $celda['esHoy'] ? 'bg-indigo-50/30' : '' }}">
                                @if ($celda['delMes'])
                                    <div class="p-2 h-full flex flex-col">
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="inline-flex items-center justify-center w-7 h-7 text-sm font-semibold {{ $celda['esHoy'] ? 'bg-indigo-600 text-white rounded-full shadow-sm' : 'text-slate-700' }}">
                                                {{ $celda['numero'] }}
                                            </span>
                                        </div>
                                        <div class="flex-1 overflow-y-auto space-y-1.5 pr-1 custom-scrollbar">
                                            @foreach ($celda['eventos'] as $evento)
                                                <div class="text-xs px-2 py-1 rounded truncate border {{ $evento['tipo'] === 'inicio' ? 'bg-emerald-50 text-emerald-700 border-emerald-200 font-medium' : 'bg-rose-50 text-rose-700 border-rose-200 font-medium' }}" title="{{ $evento['texto'] }}">
                                                    {{ $evento['texto'] }}
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="mb-6 flex items-center gap-3">
    <h2 class="text-2xl font-bold text-slate-800">Eventos de {{ \Illuminate\Support\Str::ucfirst($mes->locale('es')->isoFormat('MMMM')) }}</h2>
    <span class="inline-flex items-center justify-center bg-slate-100 text-slate-600 text-sm font-medium px-2.5 py-0.5 rounded-full">
        {{ $agenda->count() }}
    </span>
</div>

@if ($agenda->isEmpty())
    <div class="bg-white border border-slate-200 rounded-xl p-8 text-center shadow-sm max-w-3xl">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-100 mb-3">
            <svg class="w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
        </div>
        <h3 class="text-lg font-medium text-slate-900 mb-1">No hay clases este mes</h3>
        <p class="text-slate-500">Usa "Mes anterior" o "Mes siguiente" para ver otras fechas.</p>
    </div>
@else
    <div class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden mb-10 max-w-4xl">
        <ul class="divide-y divide-slate-100">
            @foreach ($agenda as $evento)
                <li class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-6 p-4 sm:p-5 hover:bg-slate-50 transition-colors">
                    <div class="font-semibold text-slate-700 w-24 shrink-0 flex items-center gap-2">
                        <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        {{ $evento['fecha']->locale('es')->isoFormat('ddd D') }}
                    </div>
                    <div class="shrink-0">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider {{ $evento['tipo'] === 'inicio' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-rose-100 text-rose-800 border border-rose-200' }}">
                            {{ $evento['tipo'] === 'inicio' ? 'Inicio' : 'Fin' }}
                        </span>
                    </div>
                    <div class="font-medium text-slate-900 flex-1">
                        {{ $evento['texto'] }}
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
@endif
@endsection
