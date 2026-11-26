import { useId, useState } from 'react';

export default function SerieTemporal({ titulo, descripcion, datos = [] }) {
    const id = useId();
    const [activo, setActivo] = useState(null);

    const ANCHO = 640;
    const ALTO = 180;
    const MARGEN = { arriba: 14, derecha: 12, abajo: 24, izquierda: 36 };

    if (datos.length === 0) {
        return (
            <section className="superficie p-5">
                <h3 className="text-sm font-semibold">{titulo}</h3>
                <p className="mt-4 text-sm text-(--color-texto-suave)">
                    Todavía no hay accesos registrados en el período.
                </p>
            </section>
        );
    }

    const tope = Math.max(1, ...datos.map((d) => d.valor));
    const anchoUtil = ANCHO - MARGEN.izquierda - MARGEN.derecha;
    const altoUtil = ALTO - MARGEN.arriba - MARGEN.abajo;
    const paso = datos.length > 1 ? anchoUtil / (datos.length - 1) : 0;

    const x = (i) => (datos.length === 1 ? MARGEN.izquierda + anchoUtil / 2 : MARGEN.izquierda + i * paso);
    const y = (v) => MARGEN.arriba + altoUtil - (v / tope) * altoUtil;

    const linea = datos.map((d, i) => `${i === 0 ? 'M' : 'L'}${x(i)},${y(d.valor)}`).join(' ');
    const area =
        datos.length === 1
            ? `M${x(0) - 2},${MARGEN.arriba + altoUtil} L${x(0)},${y(datos[0].valor)} L${x(0) + 2},${MARGEN.arriba + altoUtil} Z`
            : `${linea} L${x(datos.length - 1)},${MARGEN.arriba + altoUtil} L${x(0)},${MARGEN.arriba + altoUtil} Z`;

    const porcentajeTooltip =
        activo !== null ? Math.min(88, Math.max(12, (x(activo) / ANCHO) * 100)) : 50;

    return (
        <section className="superficie p-5">
            <h3 className="text-sm font-semibold">{titulo}</h3>
            {descripcion && (
                <p className="mt-0.5 text-xs text-(--color-texto-suave)">{descripcion}</p>
            )}

            <div className="relative mt-3">
                <svg
                    viewBox={`0 0 ${ANCHO} ${ALTO}`}
                    className="w-full select-none"
                    role="img"
                    aria-label={`${titulo}. Máximo ${tope} en el período.`}
                    onMouseLeave={() => setActivo(null)}
                >
                    <defs>
                        <linearGradient id={`g-${id}`} x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stopColor="var(--color-acento)" stopOpacity="0.28" />
                            <stop offset="100%" stopColor="var(--color-acento)" stopOpacity="0.02" />
                        </linearGradient>
                    </defs>

                    {[0, 0.5, 1].map((f) => (
                        <g key={f}>
                            <line
                                x1={MARGEN.izquierda}
                                x2={ANCHO - MARGEN.derecha}
                                y1={y(tope * f)}
                                y2={y(tope * f)}
                                stroke="var(--color-borde)"
                                strokeWidth="1"
                            />
                            <text
                                x={MARGEN.izquierda - 6}
                                y={y(tope * f) + 4}
                                textAnchor="end"
                                fontSize="10"
                                fill="var(--color-texto-suave)"
                            >
                                {Math.round(tope * f)}
                            </text>
                        </g>
                    ))}

                    <path d={area} fill={`url(#g-${id})`} />
                    <path
                        d={linea}
                        fill="none"
                        stroke="var(--color-acento)"
                        strokeWidth="2"
                        strokeLinejoin="round"
                        strokeLinecap="round"
                    />

                    {activo !== null && datos[activo] && (
                        <>
                            <line
                                x1={x(activo)}
                                x2={x(activo)}
                                y1={MARGEN.arriba}
                                y2={MARGEN.arriba + altoUtil}
                                stroke="var(--color-texto-suave)"
                                strokeWidth="1"
                                strokeDasharray="3 3"
                            />
                            <circle
                                cx={x(activo)}
                                cy={y(datos[activo].valor)}
                                r="5"
                                fill="var(--color-acento)"
                                stroke="var(--color-superficie)"
                                strokeWidth="2"
                            />
                        </>
                    )}

                    {datos.map((d, i) => {
                        const anchoZona = datos.length === 1 ? anchoUtil : Math.max(paso, 12);
                        return (
                            <rect
                                key={d.etiqueta}
                                x={x(i) - anchoZona / 2}
                                y={MARGEN.arriba}
                                width={anchoZona}
                                height={altoUtil}
                                fill="transparent"
                                tabIndex={0}
                                aria-label={`${d.etiqueta}: ${d.valor}`}
                                onMouseEnter={() => setActivo(i)}
                                onClick={() => setActivo((prev) => (prev === i ? null : i))}
                                onFocus={() => setActivo(i)}
                                onBlur={() => setActivo(null)}
                                onKeyDown={(e) => {
                                    if (e.key === 'ArrowRight') {
                                        e.preventDefault();
                                        setActivo(Math.min(datos.length - 1, i + 1));
                                    } else if (e.key === 'ArrowLeft') {
                                        e.preventDefault();
                                        setActivo(Math.max(0, i - 1));
                                    } else if (e.key === 'Escape') {
                                        setActivo(null);
                                    }
                                }}
                            >
                                <title>{`${d.etiqueta}: ${d.valor}`}</title>
                            </rect>
                        );
                    })}
                </svg>

                {activo !== null && datos[activo] && (
                    <p
                        className="superficie pointer-events-none absolute top-0 px-2.5 py-1 text-xs shadow-sm"
                        style={{ left: `${porcentajeTooltip}%`, transform: 'translateX(-50%)' }}
                    >
                        <b>{datos[activo].valor}</b> el {datos[activo].etiqueta}
                    </p>
                )}
            </div>
        </section>
    );
}
