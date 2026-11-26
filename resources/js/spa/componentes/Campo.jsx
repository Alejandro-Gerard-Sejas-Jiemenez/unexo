import { useState } from 'react';
import Icono from './Icono';

export default function Campo({
    id,
    etiqueta,
    error,
    requerido = false,
    children,
    ayuda,
    icono = null,
    esSelect = false,
    esPassword = false,
    conteo = null,
    maximo = null,
}) {
    const [verClave, setVerClave] = useState(false);
    const idError = error ? `${id}-error` : undefined;
    const idAyuda = ayuda ? `${id}-ayuda` : undefined;
    const descrito = [idAyuda, idError].filter(Boolean).join(' ') || undefined;

    return (
        <div className="campo">
            <label htmlFor={id} className="campo-etiqueta">
                <span>{etiqueta}</span>
                {requerido && (
                    <>
                        <span className="campo-requerido" aria-hidden="true" />
                        <span className="sr-only"> (obligatorio)</span>
                    </>
                )}
            </label>

            <div className={`campo-caja ${icono ? 'con-icono' : ''}`}>
                {icono && <Icono nombre={icono} className="campo-icono" />}

                {children({
                    id,
                    type: esPassword ? (verClave ? 'text' : 'password') : undefined,
                    'aria-describedby': descrito,
                    'aria-invalid': error ? true : undefined,
                })}

                {esSelect && <Icono nombre="chevron" className="campo-flecha" />}

                {esPassword && (
                    <button
                        type="button"
                        onClick={() => setVerClave((v) => !v)}
                        aria-label={verClave ? 'Ocultar contraseña' : 'Mostrar contraseña'}
                        className="campo-ojo"
                    >
                        <Icono nombre={verClave ? 'ojo-tachado' : 'ojo'} className="h-4 w-4" />
                    </button>
                )}
            </div>

            {conteo !== null && maximo && (
                <p className="mt-1 text-right text-xs tabular-nums text-(--color-texto-suave)">
                    {conteo} / {maximo}
                </p>
            )}

            {ayuda && (
                <p id={idAyuda} className="mt-1 text-xs text-(--color-texto-suave)">
                    {ayuda}
                </p>
            )}

            {error && (
                <p id={idError} className="campo-error">
                    <Icono nombre="alerta" className="mt-0.5 h-4 w-4 shrink-0" />
                    <span>{error}</span>
                </p>
            )}
        </div>
    );
}

export const claseControl = 'campo-control';
