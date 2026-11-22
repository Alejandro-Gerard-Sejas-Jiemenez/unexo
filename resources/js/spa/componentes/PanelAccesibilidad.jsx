import { useEffect, useRef, useState } from 'react';
import { ESCALA_MAX, ESCALA_MIN, MODOS, TEMAS, useTema } from '../tema/TemaContext';

/**
 * Selector de tema y herramientas de accesibilidad. Vive en el encabezado, así
 * que está disponible desde cualquier vista del sitio.
 */
export default function PanelAccesibilidad() {
    const { tema, modo, modoEfectivo, escala, contraste, actualizar, restablecer } = useTema();
    const [abierto, setAbierto] = useState(false);
    const contenedor = useRef(null);

    // Se cierra al hacer clic fuera o con Escape: lo que espera cualquier menú.
    useEffect(() => {
        if (!abierto) return undefined;

        const alClic = (e) => {
            if (!contenedor.current?.contains(e.target)) setAbierto(false);
        };
        const alTeclado = (e) => {
            if (e.key === 'Escape') setAbierto(false);
        };

        document.addEventListener('mousedown', alClic);
        document.addEventListener('keydown', alTeclado);

        return () => {
            document.removeEventListener('mousedown', alClic);
            document.removeEventListener('keydown', alTeclado);
        };
    }, [abierto]);

    const porcentaje = Math.round(escala * 100);

    return (
        <div ref={contenedor} className="relative">
            <button
                type="button"
                onClick={() => setAbierto((v) => !v)}
                aria-expanded={abierto}
                aria-haspopup="dialog"
                className="superficie px-3 py-1.5 text-sm"
            >
                Apariencia
            </button>

            {abierto && (
                <div
                    role="dialog"
                    aria-label="Apariencia y accesibilidad"
                    className="superficie absolute right-0 z-10 mt-2 w-80 p-4"
                >
                    <fieldset className="mb-4 border-0 p-0">
                        <legend className="mb-2 text-sm font-semibold">Tema</legend>
                        <div className="flex gap-2">
                            {TEMAS.map((t) => (
                                <button
                                    key={t.valor}
                                    type="button"
                                    aria-pressed={tema === t.valor}
                                    onClick={() => actualizar({ tema: t.valor })}
                                    className={
                                        'flex-1 rounded-lg border px-2 py-1.5 text-sm ' +
                                        (tema === t.valor
                                            ? 'border-[var(--color-acento)] bg-[var(--color-acento)] text-[var(--color-acento-texto)]'
                                            : 'border-[var(--color-borde)] bg-transparent')
                                    }
                                >
                                    {t.etiqueta}
                                </button>
                            ))}
                        </div>
                    </fieldset>

                    <fieldset className="mb-4 border-0 p-0">
                        <legend className="mb-2 text-sm font-semibold">
                            Modo
                            {modo === 'auto' && (
                                <span className="ml-1 font-normal text-[var(--color-texto-suave)]">
                                    (ahora: {modoEfectivo === 'noche' ? 'noche' : 'día'})
                                </span>
                            )}
                        </legend>
                        <div className="flex gap-2">
                            {MODOS.map((m) => (
                                <button
                                    key={m.valor}
                                    type="button"
                                    aria-pressed={modo === m.valor}
                                    onClick={() => actualizar({ modo: m.valor })}
                                    className={
                                        'flex-1 rounded-lg border px-2 py-1.5 text-sm ' +
                                        (modo === m.valor
                                            ? 'border-[var(--color-acento)] bg-[var(--color-acento)] text-[var(--color-acento-texto)]'
                                            : 'border-[var(--color-borde)] bg-transparent')
                                    }
                                >
                                    {m.etiqueta}
                                </button>
                            ))}
                        </div>
                    </fieldset>

                    <div className="mb-4">
                        <label htmlFor="escala" className="mb-2 block text-sm font-semibold">
                            Tamaño del texto: {porcentaje} %
                        </label>
                        <input
                            id="escala"
                            type="range"
                            min={ESCALA_MIN}
                            max={ESCALA_MAX}
                            step="0.1"
                            value={escala}
                            onChange={(e) => actualizar({ escala: Number(e.target.value) })}
                            className="w-full"
                            aria-valuetext={`${porcentaje} por ciento`}
                        />
                        <p className="mt-1 text-xs text-[var(--color-texto-suave)]">
                            Hasta el 200 %, sin perder contenido ni funcionalidad.
                        </p>
                    </div>

                    <label className="mb-4 flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            checked={contraste === 'alto'}
                            onChange={(e) =>
                                actualizar({ contraste: e.target.checked ? 'alto' : 'normal' })
                            }
                        />
                        Alto contraste
                    </label>

                    <button
                        type="button"
                        onClick={restablecer}
                        className="w-full rounded-lg border border-[var(--color-borde)] bg-transparent px-3 py-1.5 text-sm"
                    >
                        Restablecer
                    </button>
                </div>
            )}
        </div>
    );
}
