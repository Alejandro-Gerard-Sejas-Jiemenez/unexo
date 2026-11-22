/**
 * Stat tile: una cifra que se lee de un vistazo.
 * Un número suelto no es un gráfico de una barra, así que no lo dibujamos.
 */
export default function Tarjeta({ etiqueta, valor }) {
    return (
        <div className="superficie p-4">
            <p className="text-xs tracking-wide text-[var(--color-texto-suave)] uppercase">
                {etiqueta}
            </p>
            <p className="mt-1 text-3xl font-semibold tabular-nums">
                {Number(valor).toLocaleString('es-BO')}
            </p>
        </div>
    );
}
