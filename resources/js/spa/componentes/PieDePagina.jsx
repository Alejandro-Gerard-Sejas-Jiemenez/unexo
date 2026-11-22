import { useEffect, useState } from 'react';
import { useLocation } from 'react-router-dom';
import cliente from '../api/cliente';

/**
 * Contador de visitas persistente, en el pie de todas las vistas del sitio,
 * como exige el requisito 7. El conteo lo lleva el servidor.
 */
export default function PieDePagina() {
    const { pathname } = useLocation();
    const [visitas, setVisitas] = useState(null);

    useEffect(() => {
        let vigente = true;

        cliente
            .get('/visitas', { params: { ruta: pathname } })
            .then(({ data }) => vigente && setVisitas(data))
            .catch(() => {
                // El contador es informativo: si falla, el pie se muestra sin él.
            });

        return () => {
            vigente = false;
        };
    }, [pathname]);

    return (
        <footer className="border-t border-[var(--color-borde)] px-6 py-3 text-xs text-[var(--color-texto-suave)]">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <span>NEXO SIS · Universidad Autónoma Gabriel René Moreno</span>
                {visitas && (
                    <span>
                        Visitas de esta página:{' '}
                        <b>{visitas.pagina.toLocaleString('es-BO')}</b> · Total del sitio:{' '}
                        <b>{visitas.total.toLocaleString('es-BO')}</b>
                    </span>
                )}
            </div>
        </footer>
    );
}
