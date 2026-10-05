{{-- Paginación NEXO. Uso: {{ $items->links('partials.paginacion') }} (recibe $paginator y $elements). --}}
@if ($paginator->total() > 0)
    <div class="mt-4 flex flex-col sm:flex-row items-center justify-between gap-3 text-sm text-slate-500">
        <p>
            Mostrando
            <span class="font-semibold text-slate-700">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</span>
            de <span class="font-semibold text-slate-700">{{ $paginator->total() }}</span>
        </p>

        @if ($paginator->hasPages())
            <nav class="flex items-center gap-1" role="navigation" aria-label="Paginación">
                @if ($paginator->onFirstPage())
                    <span class="pag-item pag-off" aria-disabled="true">Anterior</span>
                @else
                    <a class="pag-item" href="{{ $paginator->previousPageUrl() }}" rel="prev">Anterior</a>
                @endif

                @foreach ($elements as $elemento)
                    @if (is_string($elemento))
                        <span class="px-1 text-slate-400" aria-hidden="true">…</span>
                    @else
                        @foreach ($elemento as $pagina => $url)
                            @if ($pagina === $paginator->currentPage())
                                <span class="pag-item pag-actual" aria-current="page">{{ $pagina }}</span>
                            @else
                                <a class="pag-item hidden sm:inline-flex" href="{{ $url }}" aria-label="Ir a la página {{ $pagina }}">{{ $pagina }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <a class="pag-item" href="{{ $paginator->nextPageUrl() }}" rel="next">Siguiente</a>
                @else
                    <span class="pag-item pag-off" aria-disabled="true">Siguiente</span>
                @endif
            </nav>
        @endif
    </div>
@endif
