const ESTILOS = {
    exito: 'bg-(--color-exito-fondo) text-(--color-exito) border-[color-mix(in_srgb,var(--color-exito)_35%,transparent)]',
    aviso: 'bg-(--color-aviso-fondo) text-(--color-aviso) border-[color-mix(in_srgb,var(--color-aviso)_35%,transparent)]',
    error: 'bg-(--color-error-fondo) text-(--color-error) border-[color-mix(in_srgb,var(--color-error)_35%,transparent)]',
    acento: 'bg-(--color-acento-suave) text-(--color-acento) border-[color-mix(in_srgb,var(--color-acento)_30%,transparent)]',
    neutro: 'bg-(--color-superficie-2) text-(--color-texto-suave) border-(--color-borde)',
};

export default function Insignia({ children, tono = 'neutro', className = '' }) {
    return (
        <span
            className={`inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-xs font-semibold whitespace-nowrap ${ESTILOS[tono] ?? ESTILOS.neutro} ${className}`}
        >
            {children}
        </span>
    );
}

export function tonoEstado(valor) {
    switch (valor) {
        case 'abierto':
        case 'activo':
        case 'aprobada':
        case 'aprobado':
        case 'en-curso':
        case 'en_curso':
        case 'login_exitoso':
            return 'exito';
        case 'borrador':
        case 'pendiente':
        case 'programado':
        case 'por-iniciar':
        case 'procesando':
        case 'requiere_accion':
            return 'aviso';
        case 'cerrado':
        case 'inactivo':
        case 'retirado':
        case 'rechazada':
        case 'rechazado':
        case 'cancelado':
        case 'cancelada':
        case 'login_fallido':
            return 'error';
        default:
            return 'neutro';
    }
}
