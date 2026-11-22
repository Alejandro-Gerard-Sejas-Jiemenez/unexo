/**
 * Campo de formulario con su etiqueta y el error del servidor debajo.
 * El error se asocia por aria-describedby para que un lector de pantalla lo lea.
 */
export default function Campo({ id, etiqueta, error, requerido = false, children, ayuda }) {
    const idError = error ? `${id}-error` : undefined;
    const idAyuda = ayuda ? `${id}-ayuda` : undefined;
    const descrito = [idAyuda, idError].filter(Boolean).join(' ') || undefined;

    return (
        <div className="mb-4">
            <label htmlFor={id} className="mb-1 block text-sm font-medium">
                {etiqueta}
                {requerido && (
                    <span className="text-[var(--color-error)]" aria-hidden="true">
                        {' '}
                        *
                    </span>
                )}
            </label>

            {children({ id, 'aria-describedby': descrito, 'aria-invalid': error ? true : undefined })}

            {ayuda && (
                <p id={idAyuda} className="mt-1 text-xs text-[var(--color-texto-suave)]">
                    {ayuda}
                </p>
            )}

            {error && (
                <p id={idError} className="mt-1 text-sm text-[var(--color-error)]">
                    {error}
                </p>
            )}
        </div>
    );
}

/** Clases compartidas por todos los controles, para que el formulario se vea parejo. */
export const claseControl =
    'w-full rounded-lg border border-[var(--color-borde)] bg-[var(--color-superficie)] px-3 py-2 text-sm';
