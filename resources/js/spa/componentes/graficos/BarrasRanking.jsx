/**
 * Barras horizontales rankeadas, un solo tono.
 *
 * Es la forma segura para comparar magnitudes: todas las barras arrancan de la
 * misma línea base, así que se comparan con más exactitud que los segmentos de
 * una torta, y al ser una sola serie no hace falta leyenda ni paleta categórica.
 *
 * Con `meta` por fila se convierte en meter list: cada barra contra su propio
 * límite (por ejemplo, inscritos frente al cupo mínimo del grupo).
 */
export default function BarrasRanking({ titulo, descripcion, datos, meta: usaMeta = false }) {
    const filas = [...datos].sort((a, b) => b.valor - a.valor);
    const tope = Math.max(1, ...filas.map((f) => Math.max(f.valor, usaMeta ? (f.meta ?? 0) : 0)));

    return (
        <section className="superficie p-4" aria-labelledby={`t-${titulo}`}>
            <h3 id={`t-${titulo}`} className="text-sm font-semibold">
                {titulo}
            </h3>
            {descripcion && (
                <p className="mt-0.5 text-xs text-[var(--color-texto-suave)]">{descripcion}</p>
            )}

            {filas.length === 0 ? (
                <p className="mt-4 text-sm text-[var(--color-texto-suave)]">
                    Todavía no hay datos para mostrar.
                </p>
            ) : (
                <ul className="mt-4 list-none space-y-3 p-0">
                    {filas.map((fila) => {
                        const ancho = Math.max(2, (fila.valor / tope) * 100);
                        const alcanzo = usaMeta && fila.meta != null && fila.valor >= fila.meta;

                        return (
                            <li key={fila.etiqueta}>
                                <div className="mb-1 flex items-baseline justify-between gap-3">
                                    <span className="truncate text-sm" title={fila.etiqueta}>
                                        {fila.etiqueta}
                                    </span>
                                    <span className="shrink-0 text-sm tabular-nums">
                                        {fila.valor.toLocaleString('es-BO')}
                                        {usaMeta && fila.meta != null && (
                                            <span className="text-[var(--color-texto-suave)]">
                                                {' '}
                                                / {fila.meta} {alcanzo ? '· alcanzado' : '· falta'}
                                            </span>
                                        )}
                                    </span>
                                </div>

                                {/* La pista es el mismo tono al 12 %: sin hue nuevo. */}
                                <div
                                    className="relative h-2.5 w-full overflow-hidden rounded-full"
                                    style={{ background: 'color-mix(in oklab, var(--color-acento) 12%, transparent)' }}
                                    role="img"
                                    aria-label={
                                        usaMeta && fila.meta != null
                                            ? `${fila.etiqueta}: ${fila.valor} de ${fila.meta}`
                                            : `${fila.etiqueta}: ${fila.valor}`
                                    }
                                >
                                    <div
                                        className="h-full rounded-full"
                                        style={{
                                            width: `${ancho}%`,
                                            background: 'var(--color-acento)',
                                            opacity: usaMeta && !alcanzo ? 0.55 : 1,
                                        }}
                                    />
                                    {usaMeta && fila.meta != null && (
                                        // Marca del límite: una línea fina, no otro color de dato.
                                        <span
                                            aria-hidden="true"
                                            className="absolute top-0 h-full w-0.5"
                                            style={{
                                                left: `${Math.min(100, (fila.meta / tope) * 100)}%`,
                                                background: 'var(--color-texto)',
                                                opacity: 0.45,
                                            }}
                                        />
                                    )}
                                </div>
                            </li>
                        );
                    })}
                </ul>
            )}
        </section>
    );
}
