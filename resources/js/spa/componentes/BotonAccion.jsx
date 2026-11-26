import { Link } from 'react-router-dom';
import Icono from './Icono';

const VARIANTES = {
    neutro: 'border-[var(--color-borde)] bg-[var(--color-superficie)] text-[var(--color-texto-suave)] hover:bg-[var(--color-superficie-2)] hover:text-[var(--color-acento)]',
    peligro: 'border-[color-mix(in_srgb,var(--color-error)_35%,transparent)] bg-[var(--color-superficie)] text-[var(--color-error)] hover:bg-[var(--color-error-fondo)]',
    exito: 'border-[color-mix(in_srgb,var(--color-exito)_35%,transparent)] bg-[var(--color-superficie)] text-[var(--color-exito)] hover:bg-[var(--color-exito-fondo)]',
    primario: 'border-transparent bg-[var(--color-acento)] text-[var(--color-acento-texto)] hover:bg-[var(--color-acento-hover)]',
};

export default function BotonAccion({
    to = null,
    onClick,
    icono = null,
    variante = 'neutro',
    children,
    className = '',
    title,
    disabled = false,
}) {
    const clases = `inline-flex items-center gap-1.5 rounded-md border px-3 py-1.5 text-xs font-medium no-underline shadow-sm transition-colors ${VARIANTES[variante] ?? VARIANTES.neutro} ${className}`;

    const contenido = (
        <>
            {icono && <Icono nombre={icono} className="h-4 w-4 shrink-0 opacity-75" />}
            {children}
        </>
    );

    if (to) {
        return (
            <Link to={to} className={clases} title={title}>
                {contenido}
            </Link>
        );
    }

    return (
        <button type="button" onClick={onClick} disabled={disabled} title={title} className={clases}>
            {contenido}
        </button>
    );
}
