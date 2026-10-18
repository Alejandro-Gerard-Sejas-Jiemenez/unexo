@extends('layouts.app')
@section('titulo', 'Catálogo Académico')
@section('contenido')
    {{-- Encabezado Principal --}}
    <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-slate-800 tracking-tight">Catálogo Académico</h1>
            <p class="text-sm text-slate-500 mt-1">Oferta abierta de programas y módulos independientes disponibles para
                inscripción y CRM.</p>
        </div>
        <div class="flex items-center gap-3">
            @if (auth()->user()->rol === \App\Enums\Rol::Administrador)
                <a href="{{ route('programas.create') }}"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 shadow-sm transition-all focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <x-icono nombre="mas" class="w-4 h-4" /> Nuevo programa
                </a>
                <a href="{{ route('matriculas.create') }}"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-300 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-50 shadow-sm transition-all">
                    <x-icono nombre="matricula" class="w-4 h-4 text-indigo-600" /> Registrar matrícula
                </a>
            @endif
        </div>
    </div>

    {{-- Barra de Herramientas: Buscador y Pestañas --}}
    <div
        class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-4 rounded-xl shadow-sm border border-slate-200">
        <div class="flex flex-wrap gap-2">
            @php
                $claseActivo = "px-4 py-1.5 rounded-full text-sm font-medium bg-indigo-100 text-indigo-700 ring-1 ring-inset ring-indigo-200 transition-colors";
                $claseInactivo = "px-4 py-1.5 rounded-full text-sm font-medium bg-slate-100 text-slate-600 hover:bg-slate-200 transition-colors";
            @endphp
            <a href="{{ route('catalogo', ['tab' => 'programas', 'q' => $busqueda ?: null]) }}"
                class="{{ $tab === 'programas' ? $claseActivo : $claseInactivo }}">
                Programas ({{ $programas->count() }})
            </a>
            <a href="{{ route('catalogo', ['tab' => 'modulos', 'q' => $busqueda ?: null]) }}"
                class="{{ $tab === 'modulos' ? $claseActivo : $claseInactivo }}">
                Módulos Libres ({{ $modulos->count() }})
            </a>
            <a href="{{ route('catalogo', ['tab' => 'todos', 'q' => $busqueda ?: null]) }}"
                class="{{ $tab === 'todos' ? $claseActivo : $claseInactivo }}">
                Ver Todos ({{ $programas->count() + $modulos->count() }})
            </a>
        </div>

        <form method="GET" action="{{ route('catalogo') }}" class="w-full sm:w-80 flex items-center gap-2">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="relative flex-1">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                    <x-icono nombre="buscar" class="w-4 h-4" />
                </span>
                <input type="text" name="q" value="{{ $busqueda }}" placeholder="Buscar en catálogo…"
                    class="w-full pl-9 pr-3 py-1.5 text-sm bg-slate-50 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all">
            </div>
            @if ($busqueda)
                <a href="{{ route('catalogo', ['tab' => $tab]) }}"
                    class="px-2.5 py-1.5 text-xs text-slate-500 hover:text-slate-800 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                    Limpiar
                </a>
            @endif
        </form>
    </div>

    {{-- SECCIÓN 1: PROGRAMAS ACADÉMICOS --}}
    @if ($tab === 'programas' || $tab === 'todos')
        <section class="mb-12">
            <div class="mb-5 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div
                        class="w-8 h-8 rounded-lg bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600">
                        <x-icono nombre="programa" class="w-4 h-4" />
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-slate-900">Programas Académicos</h2>
                        <p class="text-xs text-slate-500">Estructuras modulares con certificación y ediciones periódicas.</p>
                    </div>
                </div>
                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600">
                    {{ $programas->count() }} {{ $programas->count() === 1 ? 'programa' : 'programas' }}
                </span>
            </div>

            @if ($programas->isEmpty())
                <div class="bg-white border border-slate-200 rounded-xl p-8 text-center text-slate-500 shadow-sm">
                    No se encontraron programas académicos con los criterios seleccionados.
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($programas as $programa)
                        @php
                            $gradientes = [
                                0 => 'from-indigo-600 to-blue-700',
                                1 => 'from-slate-800 to-indigo-900',
                                2 => 'from-teal-600 to-emerald-700',
                                3 => 'from-purple-700 to-indigo-800',
                            ];
                            $gradiente = $gradientes[abs(crc32($programa->codigo)) % 4];
                            $proximaEdicion = $programa->cohortes->first();
                        @endphp

                        <div
                            class="flex flex-col bg-white border border-slate-200 rounded-xl shadow-sm hover:shadow-md transition-shadow overflow-hidden">
                            {{-- Cabecera con degradado --}}
                            <div class="bg-linear-to-r {{ $gradiente }} p-5 text-white">
                                <div class="flex items-center justify-between gap-2 mb-2">
                                    <span
                                        class="px-2 py-0.5 bg-white/20 backdrop-blur-sm rounded text-xs font-mono font-bold tracking-wider">
                                        {{ $programa->codigo }} · v{{ $programa->version }}
                                    </span>
                                    @if ($programa->nivel_academico)
                                        <span class="px-2 py-0.5 bg-black/20 rounded text-xs font-semibold">
                                            {{ $programa->nivel_academico }}
                                        </span>
                                    @endif
                                </div>
                                <h3 class="text-lg font-bold leading-snug line-clamp-2">{{ $programa->nombre }}</h3>
                                @if ($programa->precio)
                                    <div class="mt-2 text-sm font-semibold text-indigo-100">
                                        Bs. {{ number_format($programa->precio, 2) }}
                                    </div>
                                @endif
                            </div>

                            {{-- Cuerpo de la tarjeta --}}
                            <div class="p-5 flex-1 flex flex-col justify-between">
                                @if ($programa->descripcion)
                                    <p class="text-xs text-slate-600 line-clamp-3 mb-4 leading-relaxed">
                                        {{ $programa->descripcion }}
                                    </p>
                                @endif

                                {{-- Próxima edición / fecha --}}
                                @if ($proximaEdicion)
                                    <div class="mb-4 p-3 bg-indigo-50/70 border border-indigo-100 rounded-lg text-xs text-indigo-900">
                                        <div class="font-semibold flex items-center justify-between">
                                            <span>Próxima: {{ $proximaEdicion->nombre }}</span>
                                            <span class="px-1.5 py-0.5 rounded bg-white text-indigo-700 font-bold text-[10px] uppercase">
                                                {{ $proximaEdicion->estado->label() }}
                                            </span>
                                        </div>
                                        <div class="text-indigo-600 mt-1 flex items-center gap-1.5">
                                            <x-icono nombre="calendario" class="w-3.5 h-3.5" />
                                            <span>Inicia: <strong>{{ $proximaEdicion->fecha_inicio?->format('d/m/Y') }}</strong></span>
                                        </div>
                                    </div>
                                @else
                                    <div
                                        class="mb-4 p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-500 flex items-center gap-2">
                                        <x-icono nombre="info" class="w-3.5 h-3.5 text-slate-400" />
                                        <span>Sin ediciones vigentes programadas.</span>
                                    </div>
                                @endif

                                {{-- Estadísticas de módulos y duración --}}
                                <div
                                    class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500 font-medium">
                                    <span class="flex items-center gap-1.5">
                                        <x-icono nombre="modulos" class="w-3.5 h-3.5 text-slate-400" />
                                        {{ $programa->modulos->count() }} módulos
                                    </span>
                                    @if ($programa->modulos->sum('duracion_horas') > 0)
                                        <span>{{ $programa->modulos->sum('duracion_horas') }} hrs</span>
                                    @endif
                                    @if ($programa->modulos->sum('creditos') > 0)
                                        <span>{{ $programa->modulos->sum('creditos') }} créd.</span>
                                    @endif
                                </div>
                            </div>

                            {{-- Módulos Desplegables --}}
                            <details class="group bg-slate-50 border-t border-slate-200">
                                <summary
                                    class="flex items-center justify-between px-5 py-2.5 text-xs font-semibold text-indigo-600 hover:text-indigo-800 cursor-pointer list-none select-none hover:bg-slate-100/70 transition-colors">
                                    <span class="flex items-center gap-1.5">
                                        <x-icono nombre="chevron" class="w-3.5 h-3.5 transition-transform group-open:rotate-180" />
                                        Ver plan de módulos ({{ $programa->modulos->count() }})
                                    </span>
                                    <span class="text-[11px] font-normal text-slate-500">Orden secuencial</span>
                                </summary>
                                <div class="px-5 py-3 border-t border-slate-100 space-y-2">
                                    @foreach ($programa->modulos as $indice => $mod)
                                        <div class="flex items-start justify-between gap-2 text-xs">
                                            <div class="flex items-start gap-2">
                                                <span
                                                    class="w-4 h-4 rounded-full bg-slate-200 text-slate-600 text-[10px] font-bold flex items-center justify-center shrink-0 mt-0.5">
                                                    {{ $mod->pivot?->orden ?? ($indice + 1) }}
                                                </span>
                                                <span class="font-medium text-slate-800">{{ $mod->nombre }}</span>
                                            </div>
                                            <span
                                                class="text-slate-400 shrink-0">{{ $mod->duracion_horas ? $mod->duracion_horas . 'h' : '' }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </details>

                            {{-- Acciones Administrativas --}}
                            @if (auth()->user()->rol === \App\Enums\Rol::Administrador)
                                <div
                                    class="px-5 py-3 bg-white border-t border-slate-100 flex items-center justify-end gap-2 text-xs font-medium">
                                    <a href="{{ route('cohortes.create', ['programa_id' => $programa->id]) }}"
                                        class="text-indigo-600 hover:text-indigo-900 transition-colors">
                                        + Nueva edición
                                    </a>
                                    <span class="text-slate-300">·</span>
                                    <a href="{{ route('programas.show', $programa) }}"
                                        class="text-slate-600 hover:text-slate-900 transition-colors">
                                        Gestionar
                                    </a>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    @endif

    {{-- SECCIÓN 2: MÓDULOS LIBRES / CURSOS INDEPENDIENTES --}}
    @if ($tab === 'modulos' || $tab === 'todos')
        <section class="mb-12">
            <div class="mb-5 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div
                        class="w-8 h-8 rounded-lg bg-teal-50 border border-teal-100 flex items-center justify-center text-teal-600">
                        <x-icono nombre="modulos" class="w-4 h-4" />
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-slate-900">Módulos Independientes y Cursos Cortos</h2>
                        <p class="text-xs text-slate-500">Módulos que pueden cursarse de manera suelta con grupos activos.</p>
                    </div>
                </div>
                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600">
                    {{ $modulos->count() }} {{ $modulos->count() === 1 ? 'módulo' : 'módulos' }}
                </span>
            </div>

            @if ($modulos->isEmpty())
                <div class="bg-white border border-slate-200 rounded-xl p-8 text-center text-slate-500 shadow-sm">
                    No se encontraron módulos con los criterios seleccionados.
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($modulos as $modulo)
                        <div
                            class="flex flex-col bg-white border border-slate-200 rounded-xl shadow-sm hover:shadow-md transition-shadow overflow-hidden">
                            <div class="bg-slate-50 p-4 border-b border-slate-200/80">
                                <div class="flex items-center justify-between gap-2 mb-1.5">
                                    <span
                                        class="px-2 py-0.5 bg-white border border-slate-200 rounded text-xs font-mono font-bold text-slate-700">
                                        {{ $modulo->codigo }}
                                    </span>
                                    @if ($modulo->creditos)
                                        <span class="text-xs font-semibold text-slate-500">{{ $modulo->creditos }} créditos</span>
                                    @endif
                                </div>
                                <h3 class="text-base font-bold text-slate-900 leading-snug">{{ $modulo->nombre }}</h3>
                            </div>

                            <div class="p-4 flex-1 flex flex-col justify-between">
                                @if ($modulo->descripcion)
                                    <p class="text-xs text-slate-600 line-clamp-2 mb-3 leading-relaxed">{{ $modulo->descripcion }}</p>
                                @endif

                                {{-- Programas asociados --}}
                                @if ($modulo->programas->isNotEmpty())
                                    <div class="mb-3">
                                        <span
                                            class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block mb-1">Disponible
                                            en:</span>
                                        <div class="flex flex-wrap gap-1">
                                            @foreach ($modulo->programas as $p)
                                                <span class="px-2 py-0.5 bg-slate-100 text-slate-700 rounded text-[11px] font-medium">
                                                    {{ $p->nombre }} (v{{ $p->version }})
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- Grupos sueltos disponibles --}}
                                @if ($modulo->grupos->isNotEmpty())
                                    <div class="mb-3 p-2.5 bg-emerald-50 border border-emerald-100 rounded-lg text-xs text-emerald-900">
                                        <div class="font-semibold flex items-center justify-between">
                                            <span>Grupo abierto: {{ $modulo->grupos->first()->nombre }}</span>
                                            <span
                                                class="text-[10px] uppercase font-bold text-emerald-700">{{ $modulo->grupos->first()->modalidad->value }}</span>
                                        </div>
                                        <div class="text-emerald-700 mt-1">
                                            Inicia: <strong>{{ $modulo->grupos->first()->fecha_inicio?->format('d/m/Y') }}</strong>
                                        </div>
                                    </div>
                                @endif

                                {{-- Metadatos inferiores --}}
                                <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                                    <span class="flex items-center gap-1">
                                        <x-icono nombre="calendario" class="w-3.5 h-3.5 text-slate-400" />
                                        {{ $modulo->duracion_horas ? $modulo->duracion_horas . ' horas' : 'Duración flexible' }}
                                    </span>
                                    @if ($modulo->pdf_temario_url)
                                        <a href="{{ $modulo->pdf_temario_url }}" target="_blank"
                                            class="text-indigo-600 hover:underline flex items-center gap-1 font-medium">
                                            <x-icono nombre="documento" class="w-3.5 h-3.5" /> Temario PDF
                                        </a>
                                    @endif
                                </div>
                            </div>

                            @if (auth()->user()->rol === \App\Enums\Rol::Administrador)
                                <div
                                    class="px-4 py-2.5 bg-slate-50/50 border-t border-slate-100 flex items-center justify-end gap-2 text-xs font-medium">
                                    <a href="{{ route('grupos.create', ['modulo_id' => $modulo->id]) }}"
                                        class="text-indigo-600 hover:text-indigo-900">
                                        + Aperturar grupo
                                    </a>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    @endif
@endsection