import Icono from '../Icono';

/**
 * Stat tile: una cifra que se lee de un vistazo, con su rótulo arriba y un
 * ícono al costado. Un número suelto no es un gráfico de una barra, así que
 * no lo dibujamos.
 */
export default function Tarjeta({ etiqueta, valor, detalle, icono }) {
    return (
        <div className="superficie p-5">
            <div className="mb-3 flex items-start justify-between gap-3">
                <p className="text-xs font-semibold tracking-wider text-[var(--color-texto-suave)] uppercase">
                    {etiqueta}
                </p>
                {icono && (
                    <span className="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-[var(--color-acento-suave)] text-[var(--color-acento)]">
                        <Icono nombre={icono} />
                    </span>
                )}
            </div>

            <p className="text-4xl leading-none font-extrabold tabular-nums">
                {typeof valor === 'number' ? valor.toLocaleString('es-BO') : valor}
            </p>

            {detalle && (
                <p className="mt-2 text-sm text-[var(--color-texto-suave)]">{detalle}</p>
            )}
        </div>
    );
}
