@extends('layouts.app')
@section('titulo', 'Mis cursos')
@section('contenido')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
    <div>
        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Mis cursos</h1>
        <p class="mt-2 text-lg text-slate-500">Los módulos y programas en los que estás inscrito.</p>
    </div>
    <div>
        <a href="{{ route('catalogo') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500">
            <x-icono nombre="catalogo" class="w-4 h-4 text-slate-400" /> Ver catálogo
        </a>
    </div>
</div>

@foreach ($pendientes as $pendiente)
    @php $ultimo = $pendiente->comprobantes->first(); @endphp
    <section class="mb-6 p-5 bg-amber-50 border border-amber-200 rounded-xl">
        <h2 class="font-semibold text-amber-900">Pago pendiente: {{ $pendiente->cohorte ? $pendiente->cohorte->programa->nombre : $pendiente->inscripciones->first()?->grupo->modulo->nombre }}</h2>
        @if ($ultimo)
            <p class="mt-1 text-sm text-amber-800">Último comprobante: {{ $ultimo->estado->label() }} ({{ $ultimo->created_at->format('d/m/Y') }}).</p>
        @endif
        @if (! $ultimo || $ultimo->estado !== \App\Enums\EstadoComprobante::Pendiente)
            <form method="POST" action="{{ route('comprobantes.store', $pendiente) }}" enctype="multipart/form-data" class="mt-3 flex flex-col sm:flex-row gap-3 sm:items-center">
                @csrf
                <input type="file" name="archivo" accept=".jpg,.jpeg,.png,.pdf" required class="text-sm">
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Subir comprobante</button>
            </form>
        @endif
    </section>
@endforeach

@if ($hayInscripciones)
    <div class="mb-8">
        @include('partials.filtro-clases', ['ruta' => route('mis-cursos'), 'fase' => $fase, 'busqueda' => $busqueda, 'conteo' => $conteo, 'total' => $total])
    </div>
@endif

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 mb-12">
    @forelse ($inscripciones as $inscripcion)
        @php
            $grupo = $inscripcion->grupo;
            $matricula = $inscripcion->matricula;
            $activa = $matricula->estado === \App\Enums\EstadoMatricula::Activo;
            $enCurso = in_array($grupo->fase, ['por-iniciar', 'en-curso'], true);
            
            $colores = [
                0 => 'from-blue-500 to-indigo-600',
                1 => 'from-emerald-500 to-teal-600',
                2 => 'from-violet-500 to-purple-600',
                3 => 'from-rose-500 to-pink-600',
            ];
            $colorFondo = $colores[abs(crc32($grupo->modulo->codigo)) % 4];
            
            $esRetirado = $matricula->estado->value === 'retirado';
        @endphp
        
        <article class="flex flex-col bg-white border {{ $esRetirado ? 'border-rose-200' : 'border-slate-200' }} shadow-sm rounded-xl overflow-hidden hover:shadow-md transition-shadow relative {{ $esRetirado ? 'opacity-75 grayscale-[0.5]' : '' }}">
            <div class="bg-gradient-to-r {{ $colorFondo }} p-5 text-white">
                <span class="inline-block px-2.5 py-1 bg-white/20 backdrop-blur-sm rounded-md text-xs font-bold tracking-wider mb-3">
                    {{ $grupo->cohorte ? $grupo->cohorte->programa->nombre.' · '.$grupo->cohorte->nombre : 'Módulo suelto' }}
                </span>
                <h3 class="text-xl font-bold leading-tight">{{ $grupo->modulo->nombre }}</h3>
            </div>
            
            <div class="p-5 flex-1 flex flex-col">
                <dl class="grid grid-cols-1 gap-y-3 text-sm mb-5">
                    <div>
                        <dt class="font-medium text-slate-500">Docente</dt>
                        <dd class="text-slate-900 mt-0.5">{{ $grupo->docente->nombre_completo }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-slate-500">Período</dt>
                        <dd class="text-slate-900 mt-0.5">{{ $grupo->periodo_texto }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-slate-500">Modalidad</dt>
                        <dd class="text-slate-900 mt-0.5">
                            {{ $grupo->modalidad->label() }}@if ($grupo->aula) · {{ $grupo->aula->nombre }}@endif
                        </dd>
                    </div>
                </dl>
                
                <div class="mt-auto pt-4 border-t border-slate-100 space-y-3">
                    @if (! $esRetirado)
                        <a href="{{ route('mis-cursos.show', $grupo) }}" class="w-full inline-flex justify-center items-center px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500">
                            Ver detalle<span class="sr-only"> de {{ $grupo->modulo->nombre }}</span>
                        </a>
                    @endif
                    @if ($activa && $enCurso && $grupo->enlace_videoconferencia)
                        <a href="{{ $grupo->enlace_videoconferencia }}" target="_blank" rel="noopener" class="w-full inline-flex justify-center items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-lg hover:bg-indigo-700 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <x-icono nombre="video" class="w-4 h-4" /> Entrar a la clase
                        </a>
                    @elseif ($matricula->estado === \App\Enums\EstadoMatricula::Pendiente)
                        <div class="p-3 bg-amber-50 rounded-lg border border-amber-100 text-sm text-amber-800 flex gap-2">
                            <svg class="w-5 h-5 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                            <p>El enlace de la clase se habilitará cuando se confirme tu pago.</p>
                        </div>
                    @endif
                </div>
            </div>
            
            <div class="bg-slate-50 p-4 border-t border-slate-200 flex flex-wrap gap-2 items-center justify-between">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ 
                    $matricula->estado->value === 'activo' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 
                    ($matricula->estado->value === 'pendiente' ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-rose-100 text-rose-800 border border-rose-200')
                }}">
                    {{ $matricula->estado->label() }}
                </span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ 
                    $grupo->fase === 'en-curso' ? 'bg-indigo-100 text-indigo-800 border border-indigo-200' : 
                    ($grupo->fase === 'finalizado' ? 'bg-slate-200 text-slate-800 border border-slate-300' : 'bg-blue-50 text-blue-700 border border-blue-200')
                }}">
                    {{ $grupo->fase_etiqueta }}
                </span>
            </div>
        </article>
    @empty
        <div class="col-span-1 md:col-span-2 xl:col-span-3">
            <div class="bg-white border border-slate-200 rounded-xl p-12 text-center shadow-sm max-w-3xl mx-auto">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-100 mb-4">
                    <svg class="w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                </div>
                
                @if ($hayInscripciones)
                    <h3 class="text-lg font-medium text-slate-900 mb-1">No hay cursos con ese filtro</h3>
                    <p class="text-slate-500">Prueba con otra búsqueda o muestra todos tus cursos.</p>
                @else
                    <h3 class="text-lg font-medium text-slate-900 mb-1">Todavía no estás inscrito en ningún curso</h3>
                    <p class="text-slate-500 mb-6">La administración de {{ config('app.name') }} te inscribirá y aquí verás tus módulos.</p>
                    <a href="{{ route('catalogo') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-indigo-600 bg-indigo-50 border border-indigo-100 rounded-lg hover:bg-indigo-100 transition-colors">
                        Revisar el catálogo
                    </a>
                @endif
            </div>
        </div>
    @endforelse
</div>
@endsection
