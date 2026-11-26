import { useEffect, useRef, useState } from 'react';
import { useLocation } from 'react-router-dom';
import cliente from '../api/cliente';

const cacheVisitas = new Map();

export default function PieDePagina({ rutaFija = null }) {
    const { pathname } = useLocation();
    const rutaActual = rutaFija ?? pathname;
    const [visitas, setVisitas] = useState(() => cacheVisitas.get(rutaActual) ?? null);
    const ultimaRegistrada = useRef(null);

    useEffect(() => {
        let vigente = true;
        const guardada = cacheVisitas.get(rutaActual);
        if (guardada) {
            setVisitas(guardada);
        }

        const guardar = (data) => {
            if (!data) return;
            cacheVisitas.set(rutaActual, data);
            if (vigente) setVisitas(data);
        };

        if (ultimaRegistrada.current === rutaActual) {
            cliente
                .get('/visitas', { params: { ruta: rutaActual } })
                .then(({ data }) => guardar(data))
                .catch(() => {});

            return () => {
                vigente = false;
            };
        }

        ultimaRegistrada.current = rutaActual;

        cliente
            .post('/visitas', { ruta: rutaActual })
            .then(({ data }) => guardar(data))
            .catch(() => {
                cliente
                    .get('/visitas', { params: { ruta: rutaActual } })
                    .then(({ data }) => guardar(data))
                    .catch(() => {});
            });

        return () => {
            vigente = false;
        };
    }, [rutaActual]);

    return (
        <footer className="border-t border-(--color-borde) bg-(--color-superficie) px-4 py-3 text-xs text-(--color-texto-suave) sm:px-6">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <span>NEXO SIS · Universidad Autónoma Gabriel René Moreno</span>
                {visitas && (
                    <span>
                        Visitas de esta página:{' '}
                        <b className="text-(--color-texto)">{visitas.pagina.toLocaleString('es-BO')}</b> · Total
                        del sitio:{' '}
                        <b className="text-(--color-texto)">{visitas.total.toLocaleString('es-BO')}</b>
                    </span>
                )}
            </div>
        </footer>
    );
}
