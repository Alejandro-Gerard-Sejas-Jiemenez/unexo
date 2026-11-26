import { Link } from 'react-router-dom';
import Icono from '../Icono';

export default function Tarjeta({
    etiqueta,
    valor,
    detalle,
    icono,
    ruta = null,
    destacado = false,
}) {
    const contenido = (
        <div className="superficie group h-full rounded-xl p-6 shadow-sm transition-all hover:border-(--color-acento) hover:shadow-md">
            <div className="mb-4 flex items-center justify-between gap-3">
                <span className="text-sm font-semibold tracking-wider text-(--color-texto-suave) uppercase">
                    {etiqueta}
                </span>
                {icono && (
                    <span
                        className={
                            'rounded-lg p-2 transition-colors ' +
                            (destacado
                                ? 'bg-(--color-acento-suave) text-(--color-acento) group-hover:bg-(--color-acento) group-hover:text-(--color-acento-texto)'
                                : 'bg-(--color-superficie-cabecera) text-(--color-texto-suave) group-hover:bg-(--color-acento) group-hover:text-(--color-acento-texto)')
                        }
                    >
                        <Icono nombre={icono} className="h-5 w-5" />
                    </span>
                )}
            </div>

            <div className="mb-1">
                <span className="text-3xl font-bold tabular-nums transition-colors group-hover:text-(--color-acento)">
                    {typeof valor === 'number' ? valor.toLocaleString('es-BO') : valor}
                </span>
            </div>

            {detalle && <p className="text-sm text-(--color-texto-suave)">{detalle}</p>}
        </div>
    );

    if (ruta) {
        return (
            <Link to={ruta} className="block no-underline">
                {contenido}
            </Link>
        );
    }

    return contenido;
}
