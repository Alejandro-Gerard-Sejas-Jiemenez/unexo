{{-- Variables: $ruta, $fase, $busqueda, $conteo (por fase), $total --}}
<div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white p-3 rounded-xl border border-slate-200 shadow-sm">
    <nav class="flex overflow-x-auto custom-scrollbar pb-1 lg:pb-0" aria-label="Tabs">
        <a href="{{ $ruta.($busqueda !== '' ? '?'.http_build_query(['q' => $busqueda]) : '') }}" 
           class="whitespace-nowrap py-2 px-4 text-sm font-medium rounded-lg transition-colors {{ $fase === '' ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
            Todos
            <span class="ml-1.5 py-0.5 px-2 rounded-full text-xs {{ $fase === '' ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-600' }}">{{ $total }}</span>
        </a>
        
        @foreach (['por-iniciar' => 'Por iniciar', 'en-curso' => 'En curso', 'finalizado' => 'Finalizados'] as $clave => $texto)
            <a href="{{ $ruta.'?'.http_build_query(array_filter(['fase' => $clave, 'q' => $busqueda])) }}" 
               class="whitespace-nowrap py-2 px-4 text-sm font-medium rounded-lg transition-colors {{ $fase === $clave ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
                {{ $texto }}
                <span class="ml-1.5 py-0.5 px-2 rounded-full text-xs {{ $fase === $clave ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-600' }}">{{ $conteo[$clave] }}</span>
            </a>
        @endforeach
    </nav>
    
    <form method="GET" action="{{ $ruta }}" role="search" class="relative w-full lg:w-72 shrink-0">
        @if ($fase !== '')<input type="hidden" name="fase" value="{{ $fase }}">@endif
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <label class="sr-only" for="q">Buscar</label>
            <input type="text" id="q" name="q" value="{{ $busqueda }}" placeholder="Buscar por nombre…" autocomplete="off"
                   class="campo-control pl-9 pr-20">
            <div class="absolute inset-y-0 right-0 flex items-center">
                <button type="submit" class="h-full px-3 text-xs font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 border-l border-slate-300 rounded-r-lg transition-colors focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500">
                    Buscar
                </button>
            </div>
        </div>
    </form>
</div>
