{{-- Tarjeta de filtros de un reporte: los campos van en el slot; trae los botones «Aplicar filtros» y «Quitar filtros». --}}
@props(['accion', 'quitar'])
<form method="GET" action="{{ $accion }}" class="mb-6 bg-white p-4 rounded-xl shadow-sm border border-slate-200">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{ $slot }}
    </div>
    <div class="mt-4 flex flex-wrap items-center gap-3">
        <button type="submit" class="inline-flex items-center gap-2 px-5 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 shadow-sm transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-indigo-500">Aplicar filtros</button>
        <a href="{{ $quitar }}" class="text-sm font-medium text-slate-600 hover:text-indigo-600">Quitar filtros</a>
    </div>
</form>
