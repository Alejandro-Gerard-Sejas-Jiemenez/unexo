import { Link } from 'react-router-dom';
import Icono from './Icono';

export default function EncabezadoPagina({
    titulo,
    subtitulo = null,
    volverA = null,
    volverTexto = 'Volver',
    extraTitulo = null,
    acciones = null,
    grande = false,
}) {
    return (
        <div className="mb-8">
            {volverA && (
                <Link
                    to={volverA}
                    className="mb-2 inline-flex items-center gap-1.5 text-sm font-medium text-(--color-texto-suave) no-underline transition-colors hover:text-(--color-acento)"
                >
                    <Icono nombre="volver" className="h-4 w-4" />
                    {volverTexto}
                </Link>
            )}

            <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <div className="flex flex-wrap items-center gap-3">
                        <h1
                            className={
                                grande
                                    ? 'text-3xl font-extrabold tracking-tight'
                                    : 'text-3xl font-bold tracking-tight'
                            }
                        >
                            {titulo}
                        </h1>
                        {extraTitulo}
                    </div>
                    {subtitulo && (
                        <p className="mt-1.5 text-sm text-(--color-texto-suave) sm:text-base">
                            {subtitulo}
                        </p>
                    )}
                </div>

                {acciones && <div className="flex flex-wrap items-center gap-3">{acciones}</div>}
            </div>
        </div>
    );
}
