export default function Paginacion({ meta, irAPagina, sustantivo = 'registros' }) {
    if (!meta || meta.last_page <= 1) return null;

    return (
        <nav aria-label="Paginación" className="mt-4 flex items-center gap-3">
            <button
                type="button"
                disabled={meta.current_page <= 1}
                onClick={() => irAPagina(meta.current_page - 1)}
                className="superficie px-3 py-1.5 text-sm disabled:opacity-50"
            >
                Anterior
            </button>
            <span className="text-sm text-[var(--color-texto-suave)]">
                Página {meta.current_page} de {meta.last_page} · {meta.total} {sustantivo}
            </span>
            <button
                type="button"
                disabled={meta.current_page >= meta.last_page}
                onClick={() => irAPagina(meta.current_page + 1)}
                className="superficie px-3 py-1.5 text-sm disabled:opacity-50"
            >
                Siguiente
            </button>
        </nav>
    );
}
