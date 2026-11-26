import { useEffect, useRef } from 'react';
import Icono from './Icono';

export default function DialogoConfirmacion({
    abierto,
    titulo,
    mensaje,
    confirmarTexto = 'Confirmar',
    peligro = false,
    alConfirmar,
    alCancelar,
}) {
    const botonCancelar = useRef(null);

    useEffect(() => {
        if (!abierto) return undefined;
        botonCancelar.current?.focus();

        const alTecla = (e) => {
            if (e.key === 'Escape') alCancelar();
        };
        document.addEventListener('keydown', alTecla);
        return () => document.removeEventListener('keydown', alTecla);
    }, [abierto, alCancelar]);

    if (!abierto) return null;

    return (
        <div
            className="fixed inset-0 z-50 grid place-items-center bg-black/45 p-4 backdrop-blur-[2px]"
            role="dialog"
            aria-modal="true"
            aria-labelledby="dialogo-confirmacion-titulo"
        >
            <div className="superficie animate-fade-in w-full max-w-md p-6">
                <div className="flex items-start gap-3">
                    <span
                        className={
                            'grid h-10 w-10 shrink-0 place-items-center rounded-full ' +
                            (peligro
                                ? 'bg-[var(--color-error-fondo)] text-[var(--color-error)]'
                                : 'bg-[var(--color-acento-suave)] text-[var(--color-acento)]')
                        }
                    >
                        <Icono nombre="alerta" className="h-5 w-5" />
                    </span>
                    <div className="min-w-0 flex-1">
                        <h2 id="dialogo-confirmacion-titulo" className="text-lg font-semibold">
                            {titulo}
                        </h2>
                        <p className="mt-1 text-sm text-[var(--color-texto-suave)]">{mensaje}</p>
                    </div>
                </div>

                <div className="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button
                        ref={botonCancelar}
                        type="button"
                        onClick={alCancelar}
                        className="btn btn-secundario"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        onClick={alConfirmar}
                        className={peligro ? 'btn btn-peligro' : 'btn btn-primario'}
                    >
                        {confirmarTexto}
                    </button>
                </div>
            </div>
        </div>
    );
}
