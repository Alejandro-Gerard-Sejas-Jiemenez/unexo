import { Link } from 'react-router-dom';
import Icono from './Icono';

const VARIANTES = {
    neutro: 'border-(--color-borde) bg-(--color-superficie) text-(--color-texto-suave) hover:bg-(--color-superficie-2) hover:text-(--color-acento)',
    peligro: 'border-[color-mix(in_srgb,var(--color-error)_35%,transparent)] bg-(--color-superficie) text-(--color-error) hover:bg-(--color-error-fondo)',
    exito: 'border-[color-mix(in_srgb,var(--color-exito)_35%,transparent)] bg-(--color-superficie) text-(--color-exito) hover:bg-(--color-exito-fondo)',
    primario: 'border-transparent bg-(--color-acento) text-(--color-acento-texto) hover:bg-(--color-acento-hover)',
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
