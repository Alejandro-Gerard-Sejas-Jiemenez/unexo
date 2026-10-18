@extends('layouts.app')
@section('titulo', $cohorte->nombre . ' · Planificación')
@section('contenido')
    <div class="mb-4">
        <a href="{{ route('cohortes.index') }}"
            class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-indigo-600 transition-colors">
            <x-icono nombre="volver" class="w-4 h-4" /> Volver a ediciones
        </a>
    </div>

    {{-- Mensajes de feedback --}}
    @if (session('ok'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800 flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                    clip-rule="evenodd" />
            </svg>
            <span class="text-sm font-medium">{{ session('ok') }}</span>
        </div>
    @endif
    @if (session('info'))
        <div class="mb-6 rounded-xl border border-blue-200 bg-blue-50 p-4 text-blue-800 flex items-center gap-3">
            <x-icono nombre="alerta" class="w-5 h-5 text-blue-600 shrink-0" />
            <span class="text-sm font-medium">{{ session('info') }}</span>
        </div>
    @endif

    {{-- Cabecera Principal --}}
    <div class="mb-8 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 flex-wrap">
                <h1 class="text-3xl font-bold text-slate-800 tracking-tight">{{ $cohorte->nombre }}</h1>
                @php
                    $colorEstado = match ($cohorte->estado->value) {
                        'programado' => 'bg-amber-100 text-amber-700 ring-amber-600/20',
                        'en_curso' => 'bg-emerald-100 text-emerald-700 ring-emerald-600/20',
                        'finalizado' => 'bg-blue-100 text-blue-700 ring-blue-600/20',
                        default => 'bg-slate-100 text-slate-700 ring-slate-500/20'
                    };
                @endphp
                <span
                    class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold ring-1 ring-inset {{ $colorEstado }}">
                    {{ $cohorte->estado->label() }}
                </span>
            </div>
            <p class="text-sm text-slate-500 mt-1">
                Programa: <span class="font-semibold text-slate-700">{{ $cohorte->programa->nombre }}</span>
                (v{{ $cohorte->programa->version }}) · Código: <code
                    class="font-mono text-xs text-indigo-700 bg-indigo-50 px-1.5 py-0.5 rounded">{{ $cohorte->programa->codigo }}</code>
            </p>
        </div>

        {{-- Acciones y Cambio Rápido de Estado --}}
        <div class="flex items-center gap-3 flex-wrap">
            <form method="POST" action="{{ route('cohortes.estado', $cohorte) }}" class="inline-flex items-center gap-2">
                @csrf
                @method('PATCH')
                <label for="cambio_estado" class="text-xs text-slate-500 font-medium">Estado:</label>
                <select name="estado" id="cambio_estado" onchange="this.form.submit()"
                    class="text-xs font-semibold rounded-lg border-slate-300 py-1.5 pl-3 pr-8 focus:ring-indigo-500 focus:border-indigo-500 bg-white shadow-sm">
                    @foreach (\App\Enums\EstadoPlanificacion::cases() as $est)
                        <option value="{{ $est->value }}" @selected($cohorte->estado === $est)>{{ $est->label() }}</option>
                    @endforeach
                </select>
            </form>

            <a class="inline-flex items-center justify-center gap-2 px-3.5 py-2 bg-white text-slate-700 font-medium rounded-lg border border-slate-300 hover:bg-slate-50 shadow-sm transition-all focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 text-xs"
                href="{{ route('cohortes.edit', $cohorte) }}">
                <x-icono nombre="editar" class="w-3.5 h-3.5" /> Editar edición
            </a>
        </div>
    </div>

    {{-- Tarjetas Resumen de Planificación --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
            <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Fechas Globales</div>
            <div class="text-base font-bold text-slate-800">
                {{ $cohorte->fecha_inicio->format('d/m/Y') }} – {{ $cohorte->fecha_fin->format('d/m/Y') }}
            </div>
            <div class="text-xs text-slate-500 mt-1">
                {{ $cohorte->fecha_inicio->diffInWeeks($cohorte->fecha_fin) }} semanas de duración
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
            <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Módulos del Programa</div>
            <div class="text-2xl font-bold text-indigo-600">
                {{ $cohorte->grupos->pluck('modulo_id')->unique()->count() }} / {{ $modulosPrograma->count() }}
            </div>
            <div class="text-xs text-slate-500 mt-1">
                @if ($modulosPendientes->isEmpty())
                    <span class="text-emerald-600 font-medium">✓ Planificación completa</span>
                @else
                    <span class="text-amber-600 font-medium">Faltan {{ $modulosPendientes->count() }} por aperturar</span>
                @endif
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
            <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Grupos Aperturados</div>
            <div class="text-2xl font-bold text-slate-800">
                {{ $cohorte->grupos->count() }}
            </div>
            <div class="text-xs text-slate-500 mt-1">
                Grupos operativos activos
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
            <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Alumnos Matriculados</div>
            <div class="text-2xl font-bold text-emerald-600">
                {{ $totalMatriculados }}
            </div>
            <div class="text-xs text-slate-500 mt-1">
                Estudiantes en la edición
            </div>
        </div>
    </div>

    {{-- Banner de Apertura Asistida de Grupos (Si faltan módulos por aperturar) --}}
    @if ($modulosPendientes->isNotEmpty())
        <div class="mb-8 rounded-xl border border-indigo-200 bg-linear-to-r from-indigo-50 to-slate-50 p-6 shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div class="max-w-2xl">
                    <div class="flex items-center gap-2 text-indigo-900 font-bold text-base mb-1">
                        <x-icono nombre="modulos" class="w-5 h-5 text-indigo-600" />
                        <span>Apertura Asistida de Grupos Operativos</span>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-600">
                        Esta edición tiene <span class="font-bold text-indigo-700">{{ $modulosPendientes->count() }} módulo(s)
                            pendiente(s)</span> de apertura. El generador creará automáticamente los grupos en secuencia
                        cronológica, distribuyendo los tramos de fechas sugeridos para cada módulo.
                    </p>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        @foreach ($modulosPendientes as $pend)
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-mono bg-white text-indigo-800 border border-indigo-200 shadow-2xs">
                                {{ $pend->codigo }}: {{ \Illuminate\Support\Str::limit($pend->nombre, 24) }}
                            </span>
                        @endforeach
                    </div>
                </div>

                <form method="POST" action="{{ route('cohortes.aperturar-grupos', $cohorte) }}"
                    class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 shrink-0">
                    @csrf
                    <div>
                        <label for="docente_id" class="sr-only">Docente titular inicial</label>
                        <select name="docente_id" id="docente_id"
                            class="text-xs rounded-lg border-slate-300 py-2 pl-3 pr-8 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                            <option value="">— Docente inicial (automático) —</option>
                            @foreach ($docentes as $doc)
                                <option value="{{ $doc->id }}">
                                    {{ $doc->apellidos }}, {{ $doc->nombres }}
                                    ({{ $doc->grupos_activos_count > 0 ? $doc->grupos_activos_count . ' ' . \Illuminate\Support\Str::plural('grupo', $doc->grupos_activos_count) : 'Disponible' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="modalidad_apertura" class="sr-only">Modalidad inicial</label>
                        <select name="modalidad" id="modalidad_apertura"
                            class="text-xs rounded-lg border-slate-300 py-2 pl-3 pr-8 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                            @foreach (\App\Enums\Modalidad::cases() as $mod)
                                <option value="{{ $mod->value }}">{{ $mod->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit"
                        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 shadow-sm transition-all focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 text-xs">
                        <x-icono nombre="mas" class="w-4 h-4" /> Aperturar {{ $modulosPendientes->count() }} Grupos
                    </button>
                </form>
            </div>
        </div>
    @endif

    {{-- Sección de Grupos Aperturados --}}
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Grupos Operativos</h2>
            <p class="text-xs text-slate-500 mt-0.5">Control de fechas secuenciales, estudiantes inscritos y estado de cada grupo.</p>
        </div>
        <a class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-white text-slate-700 font-medium rounded-lg border border-slate-300 hover:bg-slate-50 shadow-sm transition-all focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 text-xs"
            href="{{ route('grupos.create', ['cohorte_id' => $cohorte->id]) }}">
            <x-icono nombre="mas" class="w-4 h-4" /> Añadir grupo paralelo (ej. Grupo B)
        </a>
    </div>

    @if ($cohorte->grupos->isEmpty())
        <div class="bg-slate-50 border border-slate-200 shadow-sm rounded-xl p-10 text-center">
            <svg class="mx-auto h-12 w-12 text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
            <h3 class="text-base font-bold text-slate-800 mb-1">Esta edición no tiene grupos aperturados</h3>
            <p class="text-sm text-slate-500 max-w-md mx-auto mb-4">Utiliza la apertura asistida superior para generar
                automáticamente los grupos de los módulos en orden secuencial.</p>
        </div>
    @else
        <div class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden mb-8">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr
                            class="bg-slate-50 border-b border-slate-200 text-xs uppercase tracking-wider text-slate-500 font-semibold">
                            <th class="px-6 py-4">Módulo</th>
                            <th class="px-6 py-4">Grupo</th>
                            <th class="px-6 py-4">Tramo de Fechas</th>
                            <th class="px-6 py-4">Fase / Estado</th>
                            <th class="px-6 py-4">Inscritos</th>
                            <th class="px-6 py-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach ($cohorte->grupos as $grupo)
                            @php
                                $quorum = $grupo->quorum;
                            @endphp
                            <tr class="hover:bg-slate-50/75 transition-colors">
                                {{-- Módulo --}}
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-900 text-sm">{{ $grupo->modulo->nombre }}</div>
                                    <div class="text-xs font-mono text-slate-400 mt-0.5">{{ $grupo->modulo->codigo }} ·
                                        {{ $grupo->modulo->duracion_horas ?? 15 }}h</div>
                                </td>

                                {{-- Nombre Grupo y Modalidad --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-semibold text-slate-800 text-sm">{{ $grupo->nombre }}</span>
                                    <div class="text-xs text-slate-500 mt-0.5 flex items-center gap-1">
                                        <span class="capitalize">{{ $grupo->modalidad->label() }}</span>
                                        @if ($grupo->docente)
                                            · <span
                                                class="text-slate-600 truncate max-w-30">{{ $grupo->docente->nombre_completo }}</span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Fechas --}}
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-600">
                                    <div class="font-medium text-slate-800">{{ $grupo->fecha_inicio->format('d/m/Y') }} –
                                        {{ $grupo->fecha_fin->format('d/m/Y') }}</div>
                                    <div class="text-slate-400 mt-0.5">{{ $grupo->fecha_inicio->diffInDays($grupo->fecha_fin) + 1 }}
                                        días</div>
                                </td>

                                {{-- Fase / Estado --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $badgeFase = match ($grupo->fase) {
                                             'en-curso' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                             'finalizado' => 'bg-slate-100 text-slate-600 border-slate-200',
                                             default => 'bg-amber-50 text-amber-700 border-amber-200'
                                         };
                                    @endphp
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium border {{ $badgeFase }}">
                                        {{ $grupo->fase_etiqueta }}
                                    </span>
                                </td>

                                {{-- Inscritos --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-slate-800">
                                            {{ $quorum['inscritos'] }} {{ $quorum['inscritos'] === 1 ? 'inscrito' : 'inscritos' }}
                                        </span>
                                        @if ($quorum['tiene_limite'])
                                            <span class="text-[11px] font-medium text-slate-500">
                                                (mín. {{ $quorum['minimo'] }})
                                            </span>
                                            @if ($quorum['alcanzado'])
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                                    ✓ Cubierto
                                                </span>
                                            @endif
                                        @endif
                                    </div>
                                </td>

                                {{-- Acciones --}}
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium">
                                    <div class="flex items-center justify-end gap-2">
                                        <a class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-white text-slate-600 hover:text-indigo-600 border border-slate-200 hover:border-indigo-200 rounded-md transition-colors text-xs font-medium shadow-2xs"
                                            href="{{ route('grupos.show', $grupo) }}" title="Ver detalle de alumnos y logística">
                                            <x-icono nombre="usuarios" class="w-3.5 h-3.5" /> Ver
                                        </a>
                                        <a class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-white text-slate-600 hover:text-indigo-600 border border-slate-200 hover:border-indigo-200 rounded-md transition-colors text-xs font-medium shadow-2xs"
                                            href="{{ route('grupos.edit', $grupo) }}" title="Editar fechas o cupo">
                                            <x-icono nombre="editar" class="w-3.5 h-3.5" /> Editar
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection