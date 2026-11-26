import Icono from './Icono';

export default function Paginacion({ meta, irAPagina, sustantivo = 'registros' }) {
    if (!meta || meta.last_page <= 1) return null;

    const actual = meta.current_page;
    const ultima = meta.last_page;
    const desde = meta.from ?? (actual - 1) * meta.per_page + 1;
    const hasta = meta.to ?? Math.min(actual * meta.per_page, meta.total);

    const paginas = [];
    for (let i = 1; i <= ultima; i++) {
        if (i === 1 || i === ultima || Math.abs(i - actual) <= 1) {
            paginas.push(i);
        } else if (paginas[paginas.length - 1] !== '…') {
            paginas.push('…');
        }
    }

    return (
        <nav
            aria-label="Paginación"
            className="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm"
        >
            <p className="text-[var(--color-texto-suave)]">
                Mostrando <b className="text-[var(--color-texto)]">{desde}–{hasta}</b> de{' '}
                <b className="text-[var(--color-texto)]">{meta.total}</b> {sustantivo}
            </p>

            <div className="flex flex-wrap items-center gap-1.5">
                <button
                    type="button"
                    disabled={actual <= 1}
                    onClick={() => irAPagina(actual - 1)}
                    className="btn btn-secundario !min-h-9 !px-2.5 text-xs"
                >
                    <Icono nombre="volver" className="h-3.5 w-3.5" />
                    Anterior
                </button>

                {paginas.map((p, idx) =>
                    p === '…' ? (
                        <span key={`el-${idx}`} className="px-1.5 text-[var(--color-texto-suave)]">
                            …
                        </span>
                    ) : (
                        <button
                            key={p}
                            type="button"
                            aria-current={p === actual ? 'page' : undefined}
                            onClick={() => irAPagina(p)}
                            className={
                                'btn !min-h-9 !px-3 text-xs ' +
                                (p === actual ? 'btn-primario' : 'btn-secundario')
                            }
                        >
                            {p}
                        </button>
                    ),
                )}

                <button
                    type="button"
                    disabled={actual >= ultima}
                    onClick={() => irAPagina(actual + 1)}
                    className="btn btn-secundario !min-h-9 !px-2.5 text-xs"
                >
                    Siguiente
                    <Icono nombre="siguiente" className="h-3.5 w-3.5" />
                </button>
            </div>
        </nav>
    );
}
