{{-- Botones de descarga con los filtros vigentes. Requiere $rutaPdf, $rutaExcel y $filtros. --}}
<div class="flex flex-wrap items-center gap-2">
    <a href="{{ route($rutaPdf, $filtros) }}"
        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg shadow-sm hover:bg-slate-50 hover:text-indigo-600 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
        <x-icono nombre="documento" class="w-4 h-4 text-slate-400" /> Descargar PDF
    </a>
    <a href="{{ route($rutaExcel, $filtros) }}"
        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg shadow-sm hover:bg-slate-50 hover:text-indigo-600 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
        <x-icono nombre="guardar" class="w-4 h-4 text-slate-400" /> Descargar Excel
    </a>
</div>
