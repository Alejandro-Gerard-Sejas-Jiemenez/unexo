import { Link } from 'react-router-dom';
import Icono from './Icono';

export default function EstadoVacio({ icono = 'info', titulo, descripcion, accionTexto, accionRuta }) {
    return (
        <div className="superficie flex flex-col items-center justify-center px-6 py-10 text-center">
            <div className="mb-3 grid h-12 w-12 place-items-center rounded-full bg-(--color-acento-suave) text-(--color-acento)">
                <Icono nombre={icono} className="h-6 w-6" />
            </div>
            <p className="text-base font-semibold">{titulo}</p>
            {descripcion && (
                <p className="mt-1 max-w-md text-sm text-(--color-texto-suave)">{descripcion}</p>
            )}
            {accionTexto && accionRuta && (
                <Link to={accionRuta} className="btn btn-primario mt-4">
                    {accionTexto}
                </Link>
            )}
        </div>
    );
}
