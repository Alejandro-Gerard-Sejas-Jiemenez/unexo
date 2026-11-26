import { useEffect, useRef, useState } from 'react';
import { ESCALA_MAX, ESCALA_MIN, MODOS, TEMAS, useTema } from '../tema/TemaContext';
import Icono from './Icono';

export default function PanelAccesibilidad() {
    const { tema, modo, modoEfectivo, escala, contraste, actualizar, restablecer } = useTema();
    const [abierto, setAbierto] = useState(false);
    const contenedor = useRef(null);
    const botonDisparador = useRef(null);
    const primerControl = useRef(null);

    useEffect(() => {
        if (!abierto) return undefined;
        primerControl.current?.focus();

        const alClic = (e) => {
            if (!contenedor.current?.contains(e.target)) setAbierto(false);
        };
        const alTeclado = (e) => {
            if (e.key === 'Escape') {
                setAbierto(false);
                botonDisparador.current?.focus();
            }
        };

        document.addEventListener('mousedown', alClic);
        document.addEventListener('keydown', alTeclado);
        return () => {
            document.removeEventListener('mousedown', alClic);
            document.removeEventListener('keydown', alTeclado);
        };
    }, [abierto]);

    const porcentaje = Math.round(escala * 100);
    const ajustarEscala = (delta) => {
        const siguiente = Math.min(ESCALA_MAX, Math.max(ESCALA_MIN, Math.round((escala + delta) * 10) / 10));
        actualizar({ escala: siguiente });
    };

    return (
        <div ref={contenedor} className="relative">
            <button
                ref={botonDisparador}
                type="button"
                onClick={() => setAbierto((v) => !v)}
                aria-expanded={abierto}
                aria-haspopup="dialog"
                className="btn btn-secundario min-h-9! px-2.5! sm:!px-3"
            >
                <Icono nombre="sol" className="h-4 w-4 sm:hidden" />
                <span className="hidden text-sm sm:inline">Apariencia</span>
            </button>

            {abierto && (
                <div
                    role="dialog"
                    aria-label="Apariencia y accesibilidad"
                    className="superficie animate-fade-in fixed top-16 right-3 left-3 z-50 max-h-[80vh] overflow-y-auto p-4 sm:absolute sm:top-auto sm:right-0 sm:left-auto sm:mt-2 sm:w-80"
                >
                    <fieldset className="mb-4 border-0 p-0">
                        <legend className="mb-2 text-sm font-semibold">Tema visual</legend>
                        <div className="flex gap-2">
                            {TEMAS.map((t, idx) => (
                                <button
                                    key={t.valor}
                                    ref={idx === 0 ? primerControl : undefined}
                                    type="button"
                                    aria-pressed={tema === t.valor}
                                    onClick={() => actualizar({ tema: t.valor })}
                                    className={
                                        'flex flex-1 flex-col items-center gap-1 rounded-lg border px-2 py-1.5 text-xs font-medium ' +
                                        (tema === t.valor
                                            ? 'border-(--color-acento) bg-(--color-acento) text-(--color-acento-texto)'
                                            : 'border-(--color-borde) bg-transparent')
                                    }
                                >
                                    <span className="flex gap-1" aria-hidden="true">
                                        <span
                                            className="h-3 w-3 rounded-full border border-white/60"
                                            style={{ backgroundColor: t.muestra }}
                                        />
                                        <span
                                            className="h-3 w-3 rounded-full border border-white/60"
                                            style={{ backgroundColor: t.acento }}
                                        />
                                    </span>
                                    <span>{t.etiqueta}</span>
                                </button>
                            ))}
                        </div>
                    </fieldset>

                    <fieldset className="mb-4 border-0 p-0">
                        <legend className="mb-2 text-sm font-semibold">
                            Modo
                            {modo === 'auto' && (
                                <span className="ml-1 font-normal text-(--color-texto-suave)">
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
                                        'flex-1 rounded-lg border px-2 py-1.5 text-xs font-medium ' +
                                        (modo === m.valor
                                            ? 'border-(--color-acento) bg-(--color-acento) text-(--color-acento-texto)'
                                            : 'border-(--color-borde) bg-transparent')
                                    }
                                >
                                    {m.etiqueta}
                                </button>
                            ))}
                        </div>
                    </fieldset>

                    <div className="mb-4">
                        <div className="mb-2 flex items-center justify-between gap-2">
                            <label htmlFor="escala-texto" className="text-sm font-semibold">
                                Texto: {porcentaje} %
                            </label>
                            <div className="flex gap-1">
                                <button
                                    type="button"
                                    onClick={() => ajustarEscala(-0.1)}
                                    disabled={escala <= ESCALA_MIN}
                                    aria-label="Reducir texto"
                                    className="btn btn-secundario min-h-7! px-2! text-xs"
                                >
                                    A-
                                </button>
                                <button
                                    type="button"
                                    onClick={() => actualizar({ escala: 1 })}
                                    aria-label="Restablecer tamaño normal"
                                    className="btn btn-secundario min-h-7! px-2! text-xs"
                                >
                                    100%
                                </button>
                                <button
                                    type="button"
                                    onClick={() => ajustarEscala(0.1)}
                                    disabled={escala >= ESCALA_MAX}
                                    aria-label="Aumentar texto"
                                    className="btn btn-secundario min-h-7! px-2! text-xs"
                                >
                                    A+
                                </button>
                            </div>
                        </div>
                        <input
                            id="escala-texto"
                            type="range"
                            min={ESCALA_MIN}
                            max={ESCALA_MAX}
                            step="0.1"
                            value={escala}
                            onChange={(e) => actualizar({ escala: Number(e.target.value) })}
                            className="w-full"
                            aria-valuetext={`${porcentaje} por ciento`}
                        />
                    </div>

                    <label className="campo-check mb-4 py-2.5! text-sm">
                        <input
                            type="checkbox"
                            checked={contraste === 'alto'}
                            onChange={(e) =>
                                actualizar({ contraste: e.target.checked ? 'alto' : 'normal' })
                            }
                        />
                        <span>Alto contraste (WCAG AA+)</span>
                    </label>

                    <button
                        type="button"
                        onClick={restablecer}
                        className="btn btn-secundario w-full min-h-9! text-xs"
                    >
                        Restablecer preferencias
                    </button>
                </div>
            )}
        </div>
    );
}
