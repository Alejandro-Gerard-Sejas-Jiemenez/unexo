@extends('layouts.app')
@section('titulo', $grupo->exists ? 'Editar grupo' : 'Nuevo grupo')
@section('errores_propios', '1')
@section('contenido')
    <div class="mb-4">
        @if ($cohorteSeleccionada)
            <a href="{{ route('cohortes.show', $cohorteSeleccionada) }}"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-indigo-600 transition-colors">
                <x-icono nombre="volver" class="w-4 h-4" /> Volver a la edición {{ $cohorteSeleccionada->nombre }}
            </a>
        @else
            <a href="{{ route('grupos.index') }}"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-indigo-600 transition-colors">
                <x-icono nombre="volver" class="w-4 h-4" /> Volver a grupos
            </a>
        @endif
    </div>

    <h1 class="text-3xl font-bold text-slate-800 tracking-tight mb-6">{{ $grupo->exists ? 'Editar grupo' : 'Nuevo grupo' }}
    </h1>

    @if ($cohorteSeleccionada)
        <div class="mb-6 bg-indigo-50 border border-indigo-200 rounded-xl p-4 flex items-start gap-3 max-w-4xl">
            <x-icono nombre="informacion" class="w-5 h-5 text-indigo-600 shrink-0 mt-0.5" />
            <div class="text-sm">
                <p class="font-semibold text-indigo-900">Grupo vinculado a la Edición: {{ $cohorteSeleccionada->nombre }}</p>
                <p class="text-indigo-700 mt-0.5">
                    Programa: <span class="font-medium">{{ $cohorteSeleccionada->programa->nombre }}</span> |
                    Período de la edición: <span class="font-medium">{{ $cohorteSeleccionada->fecha_inicio->format('d/m/Y') }}
                        al {{ $cohorteSeleccionada->fecha_fin->format('d/m/Y') }}</span>.
                </p>
            </div>
        </div>
    @endif

    <div class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden max-w-4xl">
        <form method="POST" action="{{ $grupo->exists ? route('grupos.update', $grupo) : route('grupos.store') }}"
            class="p-6 sm:p-8">
            @csrf
            @if ($grupo->exists) @method('PUT') @endif

            @php
                $edicionActual = old('cohorte_id', $cohorteSeleccionada?->id ?? $grupo->cohorte_id);
                $esSuelto = blank($edicionActual);
            @endphp

            @php
                $modalidadActual = old('modalidad', $grupo->modalidad?->value);
                $muestraEnlace = in_array($modalidadActual, ['virtual', 'mixta'], true);
                $muestraAula = in_array($modalidadActual, ['presencial', 'mixta'], true);
            @endphp

            @if ($edicionFija)
                <input type="hidden" name="cohorte_id" value="{{ $cohorteSeleccionada->id }}">
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 mb-2">
                <x-campo-select nombre="modulo_id" :total="$modulosTotal" etiqueta="Módulo" requerido
                    :ayuda="$cohorteSeleccionada ? 'Módulos correspondientes a '.$cohorteSeleccionada->programa->nombre : null"
                    :opciones="$modulos->mapWithKeys(fn($m) => [$m->id => $m->codigo . ' · ' . $m->nombre])"
                    :valor="$grupo->modulo_id" />
                @unless ($edicionFija)
                    <x-campo-select nombre="cohorte_id" :total="$cohortesTotal" etiqueta="Edición"
                        ayuda="Elige una edición o déjalo como módulo suelto"
                        :opciones="collect(['' => 'Módulo suelto (sin edición)'])->union($cohortes->mapWithKeys(fn($c) => [$c->id => $c->programa->nombre . ' · ' . $c->nombre]))"
                        :valor="$grupo->cohorte_id" />
                @endunless
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 mb-2 mt-4 pt-4 border-t border-slate-100">
                <x-campo-select nombre="docente_id" :total="$docentesTotal" etiqueta="Docente titular" requerido
                    ayuda="Carga actual de grupos activos"
                    :opciones="$docentes->mapWithKeys(fn ($d) => [$d->id => $d->apellidos.', '.$d->nombres.' ('.($d->grupos_activos_count > 0 ? $d->grupos_activos_count.' '.\Illuminate\Support\Str::plural('grupo', $d->grupos_activos_count).' activo(s)' : 'Disponible').')'])"
                    :valor="$grupo->docente_id" />
                <x-campo-select nombre="modalidad" etiqueta="Modalidad" requerido
                    :opciones="collect(\App\Enums\Modalidad::cases())->mapWithKeys(fn ($m) => [$m->value => $m->label()])"
                    :valor="$grupo->modalidad?->value" />
            </div>

            <div data-bloque-fechas class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 mb-2" @if (! $esSuelto) style="display: none" @endif>
                <x-campo nombre="fecha_inicio" etiqueta="Fecha de inicio" tipo="date" requerido
                    :disabled="! $esSuelto" :valor="$grupo->fecha_inicio?->format('Y-m-d')" />
                <x-campo nombre="fecha_fin" etiqueta="Fecha de fin" tipo="date" requerido
                    :disabled="! $esSuelto" :valor="$grupo->fecha_fin?->format('Y-m-d')" />
            </div>

            {{-- Recursos Logísticos de Clase (CU5: Virtual vs Presencial vs Mixta) --}}
            <div id="campo-virtual" class="mt-4 pt-4 border-t border-slate-100" @unless ($muestraEnlace) style="display: none" @endunless>
                <x-campo nombre="enlace_videoconferencia" :disabled="! $muestraEnlace" limite="grupos.enlace_videoconferencia"
                    etiqueta="Enlace de videoconferencia (Google Meet / Zoom / Teams)" tipo="url" :valor="$grupo->enlace_videoconferencia"
                    placeholder="https://meet.google.com/..." ayuda="Obligatorio para clases virtuales. Visible para alumnos con matrícula pagada." />
            </div>

            <div id="campo-presencial" class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 mb-2 mt-4 pt-4 border-t border-slate-100" @unless ($muestraAula) style="display: none" @endunless>
                <x-campo-select nombre="aula_id" :disabled="! $muestraAula" :total="$aulasTotal" etiqueta="Aula física presencial"
                    ayuda="Verifica que el aforo sea suficiente para el cupo"
                    :opciones="collect(['' => '— Seleccionar aula física —'])->union($aulas->mapWithKeys(fn($a) => [$a->id => $a->nombre . ($a->capacidad ? ' (Aforo: '.$a->capacidad.' personas)' : '')]))"
                    :valor="$grupo->aula_id" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 mb-2 mt-4 pt-4 border-t border-slate-100">
                <x-campo nombre="cupo_minimo" limite="grupos.cupo_minimo" etiqueta="Cupo mínimo (Opcional)" tipo="number"
                    min="0" :valor="$grupo->cupo_minimo ?? ''"
                    ayuda="Opcional. Dejar vacío si no requiere límite mínimo (recomendado para modalidad virtual)." />
                <x-campo-select nombre="estado" etiqueta="Estado de planificación" requerido
                    :opciones="collect(\App\Enums\EstadoPlanificacion::cases())->mapWithKeys(fn ($e) => [$e->value => $e->label()])"
                    :valor="$grupo->estado?->value" />
            </div>

            @include('grupos._horarios')

            <x-form-acciones :cancelar="$cohorteSeleccionada ? route('cohortes.show', $cohorteSeleccionada) : route('grupos.index')" />
        </form>
    </div>

@endsection