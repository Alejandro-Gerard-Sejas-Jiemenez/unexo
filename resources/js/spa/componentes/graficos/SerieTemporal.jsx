import { useId, useState } from 'react';

/**
 * Tendencia en el tiempo: una sola serie, así que área + línea en un tono y sin
 * leyenda (el título nombra la serie). Lleva capa de hover con guía y globo,
 * que es lo que se espera de un gráfico en pantalla.
 */
export default function SerieTemporal({ titulo, descripcion, datos }) {
    const id = useId();
    const [activo, setActivo] = useState(null);

    const ANCHO = 640;
    const ALTO = 180;
    const MARGEN = { arriba: 12, derecha: 8, abajo: 24, izquierda: 36 };

    if (datos.length === 0) {
        return (
            <section className="superficie p-4">
                <h3 className="text-sm font-semibold">{titulo}</h3>
                <p className="mt-4 text-sm text-[var(--color-texto-suave)]">
                    Todavía no hay accesos registrados en el periodo.
                </p>
            </section>
        );
    }

    const tope = Math.max(1, ...datos.map((d) => d.valor));
    const anchoUtil = ANCHO - MARGEN.izquierda - MARGEN.derecha;
    const altoUtil = ALTO - MARGEN.arriba - MARGEN.abajo;
    const paso = datos.length > 1 ? anchoUtil / (datos.length - 1) : 0;

    const x = (i) => MARGEN.izquierda + i * paso;
    const y = (v) => MARGEN.arriba + altoUtil - (v / tope) * altoUtil;

    const linea = datos.map((d, i) => `${i === 0 ? 'M' : 'L'}${x(i)},${y(d.valor)}`).join(' ');
    const area = `${linea} L${x(datos.length - 1)},${MARGEN.arriba + altoUtil} L${x(0)},${MARGEN.arriba + altoUtil} Z`;

    return (
        <section className="superficie p-4">
            <h3 className="text-sm font-semibold">{titulo}</h3>
            {descripcion && (
                <p className="mt-0.5 text-xs text-[var(--color-texto-suave)]">{descripcion}</p>
            )}

            <div className="relative mt-3">
                <svg
                    viewBox={`0 0 ${ANCHO} ${ALTO}`}
                    className="w-full"
                    role="img"
                    aria-label={`${titulo}. Máximo ${tope} en el periodo.`}
                    onMouseLeave={() => setActivo(null)}
                >
                    <defs>
                        <linearGradient id={`g-${id}`} x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stopColor="var(--color-acento)" stopOpacity="0.28" />
                            <stop offset="100%" stopColor="var(--color-acento)" stopOpacity="0.02" />
                        </linearGradient>
                    </defs>

                    {/* Rejilla recesiva: guía la lectura sin competir con el dato. */}
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

                    {activo !== null && (
                        <>
                            <line
                                x1={x(activo)}
                                x2={x(activo)}
                                y1={MARGEN.arriba}
                                y2={MARGEN.arriba + altoUtil}
                                stroke="var(--color-texto-suave)"
                                strokeWidth="1"
                            />
                            {/* Anillo de superficie para que el punto se despegue del área. */}
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

                    {/* Zonas de contacto anchas: el objetivo es mayor que la marca. */}
                    {datos.map((d, i) => (
                        <rect
                            key={d.etiqueta}
                            x={x(i) - paso / 2}
                            y={MARGEN.arriba}
                            width={Math.max(paso, 8)}
                            height={altoUtil}
                            fill="transparent"
                            onMouseEnter={() => setActivo(i)}
                        >
                            <title>{`${d.etiqueta}: ${d.valor}`}</title>
                        </rect>
                    ))}
                </svg>

                {activo !== null && (
                    <p
                        className="superficie pointer-events-none absolute top-0 px-2 py-1 text-xs"
                        style={{ left: `${(x(activo) / ANCHO) * 100}%`, transform: 'translateX(-50%)' }}
                    >
                        <b>{datos[activo].valor}</b> el {datos[activo].etiqueta}
                    </p>
                )}
            </div>
        </section>
    );
}
