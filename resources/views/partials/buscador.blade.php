{{-- Variables: $ruta, $busqueda, $placeholder (opcional). Conserva los filtros activos como campos ocultos. --}}
<form class="flex items-center w-full" method="GET" action="{{ $ruta }}" role="search">
    @foreach (request()->except(['q', 'page']) as $clave => $valor)
        <input type="hidden" name="{{ $clave }}" value="{{ $valor }}">
    @endforeach
    
    <label class="sr-only" for="q">Buscar</label>
    
    <div class="relative flex-grow focus-within:z-10">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <x-icono nombre="buscar" class="h-4 w-4 text-slate-400" />
        </div>
        <input type="text" id="q" name="q" value="{{ $busqueda }}" placeholder="{{ $placeholder ?? 'Buscar…' }}" autocomplete="off" 
               class="campo-control rounded-r-none pl-9 pr-3">
    </div>
    
    <button class="-ml-px relative inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-r-lg text-white bg-indigo-600 border border-transparent hover:bg-indigo-700 focus:z-10 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors" type="submit">
        Buscar
    </button>
</form>
