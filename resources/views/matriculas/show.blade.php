@extends('layouts.app')
@section('titulo', 'Matrícula de '.$matricula->estudiante->nombre_completo)
@section('contenido')
<div class="mb-4">
    <a href="{{ route('matriculas.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-indigo-600 transition-colors">
        <x-icono nombre="volver" class="w-4 h-4" /> Volver a matrículas
    </a>
</div>

<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-8">
    <div>
        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ $matricula->estudiante->nombre_completo }}</h1>
        <p class="mt-2 text-lg text-slate-500">{{ $matricula->cohorte ? $matricula->cohorte->programa->nombre.' · '.$matricula->cohorte->nombre : 'Módulo suelto' }}</p>
    </div>
    <div>
        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ 
            $matricula->estado->value === 'activo' ? 'bg-emerald-100 text-emerald-800' : 
            ($matricula->estado->value === 'pendiente' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800')
        }} shadow-sm border {{ 
            $matricula->estado->value === 'activo' ? 'border-emerald-200' : 
            ($matricula->estado->value === 'pendiente' ? 'border-amber-200' : 'border-rose-200')
        }}">
            {{ $matricula->estado->label() }}
        </span>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-10">
    <div class="lg:col-span-2">
        <div class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden h-full">
            <div class="px-6 py-5 border-b border-slate-200 bg-slate-50/50">
                <h2 class="text-lg font-semibold text-slate-800">Detalles de la matrícula</h2>
            </div>
            <div class="p-6">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-6">
                    <div>
                        <dt class="text-sm font-medium text-slate-500 mb-1">Correo institucional</dt>
                        <dd class="text-base text-slate-900">{{ $matricula->estudiante->correo_institucional ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-slate-500 mb-1">Origen</dt>
                        <dd class="text-base text-slate-900">{{ $matricula->origen->label() }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-slate-500 mb-1">Fecha de matrícula</dt>
                        <dd class="text-base text-slate-900">{{ $matricula->fecha_matricula->format('d/m/Y') }}</dd>
                    </div>
                    @if ($matricula->fecha_retiro)
                        <div>
                            <dt class="text-sm font-medium text-slate-500 mb-1">Fecha de retiro</dt>
                            <dd class="text-base text-slate-900">{{ $matricula->fecha_retiro->format('d/m/Y') }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>
    </div>

    <div class="lg:col-span-1">
        <div class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden h-full">
            <div class="px-6 py-5 border-b border-slate-200 bg-slate-50/50">
                <h2 class="text-lg font-semibold text-slate-800">Acciones</h2>
            </div>
            <div class="p-6 space-y-3">
                @if ($matricula->estado->value === 'retirado')
                    <p class="text-sm text-slate-500">Matrícula retirada: se conserva como historial y no admite cambios.</p>
                @endif
                @if ($matricula->estado->value === 'pendiente')
                    <form method="POST" action="{{ route('matriculas.estado', $matricula) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="estado" value="activo">
                        <button type="submit" class="w-full inline-flex justify-center items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-emerald-600 border border-transparent rounded-lg hover:bg-emerald-700 transition-colors shadow-sm">
                            <x-icono nombre="usuario-activar" class="w-4 h-4" /> Aprobar pago (activar)
                        </button>
                    </form>
                @endif
                
                @if ($matricula->estado->value === 'activo')
                    <form method="POST" action="{{ route('matriculas.estado', $matricula) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="estado" value="pendiente">
                        <button type="submit" class="w-full inline-flex justify-center items-center gap-2 px-4 py-2.5 text-sm font-medium text-amber-700 bg-amber-50 border border-amber-200 rounded-lg hover:bg-amber-100 transition-colors shadow-sm">
                            Marcar pendiente
                        </button>
                    </form>
                @endif
                
                @if ($matricula->estado->value !== 'retirado')
                    <form method="POST" action="{{ route('matriculas.estado', $matricula) }}" onsubmit="return confirm('¿Retirar a {{ $matricula->estudiante->nombres }} de esta matrícula? Se conserva el historial.');">
                        @csrf @method('PATCH')
                        <input type="hidden" name="estado" value="retirado">
                        <button type="submit" class="w-full inline-flex justify-center items-center gap-2 px-4 py-2.5 text-sm font-medium text-rose-700 bg-white border border-rose-200 rounded-lg hover:bg-rose-50 transition-colors shadow-sm mt-4">
                            <x-icono nombre="usuario-desactivar" class="w-4 h-4" /> Retirar
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="mb-6"><h2 class="text-2xl font-bold text-slate-800">Comprobantes de pago</h2></div>
<div class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden mb-10">
    @forelse ($matricula->comprobantes as $comprobante)
        <div class="flex flex-wrap items-center gap-3 px-6 py-4 {{ ! $loop->last ? 'border-b border-slate-200' : '' }} text-sm">
            <a href="{{ route('comprobantes.ver', $comprobante) }}" target="_blank" rel="noopener" class="font-medium text-indigo-600 hover:underline">Ver comprobante</a>
            <span class="text-slate-500">{{ $comprobante->created_at->format('d/m/Y H:i') }} · {{ $comprobante->subidoPor->nombre_completo }}</span>
            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">{{ $comprobante->estado->label() }}</span>
            @if ($comprobante->estado === \App\Enums\EstadoComprobante::Pendiente)
                <form method="POST" action="{{ route('comprobantes.aprobar', $comprobante) }}" class="ml-auto">
                    @csrf @method('PATCH')
                    <button class="px-3 py-1.5 text-white bg-emerald-600 rounded-lg hover:bg-emerald-700">Aprobar pago</button>
                </form>
                <form method="POST" action="{{ route('comprobantes.rechazar', $comprobante) }}">
                    @csrf @method('PATCH')
                    <button class="px-3 py-1.5 text-rose-700 border border-rose-200 rounded-lg hover:bg-rose-50">Rechazar</button>
                </form>
            @endif
        </div>
    @empty
        <p class="px-6 py-8 text-center text-slate-500">El estudiante todavía no subió ningún comprobante.</p>
    @endforelse
</div>

<div class="mb-6 flex items-center gap-3">
    <h2 class="text-2xl font-bold text-slate-800">Grupos de la matrícula</h2>
    <span class="inline-flex items-center justify-center bg-slate-100 text-slate-600 text-sm font-medium px-2.5 py-0.5 rounded-full">
        {{ $matricula->inscripciones->count() }}
    </span>
</div>

@if ($matricula->inscripciones->isEmpty())
    <div class="bg-white border border-slate-200 rounded-xl p-12 text-center shadow-sm">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-100 mb-4">
            <svg class="w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
        </div>
        <h3 class="text-lg font-medium text-slate-900 mb-1">Sin grupos inscritos</h3>
        <p class="text-slate-500">Esta matrícula no tiene grupos asignados actualmente.</p>
    </div>
@else
    <div class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden mb-10">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-sm font-semibold text-slate-600">
                        <th class="px-6 py-4">Módulo</th>
                        <th class="px-6 py-4">Grupo</th>
                        <th class="px-6 py-4">Docente</th>
                        <th class="px-6 py-4">Fechas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-sm">
                @foreach ($matricula->inscripciones as $inscripcion)
                    @php $grupo = $inscripcion->grupo; @endphp
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 font-medium text-slate-900">{{ $grupo->modulo->nombre }}</td>
                        <td class="px-6 py-4 text-slate-700">{{ $grupo->nombre }}</td>
                        <td class="px-6 py-4 text-slate-500">{{ $grupo->docente->nombre_completo }}</td>
                        <td class="px-6 py-4 text-slate-500">{{ $grupo->fecha_inicio->format('d/m/Y') }} – {{ $grupo->fecha_fin->format('d/m/Y') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
