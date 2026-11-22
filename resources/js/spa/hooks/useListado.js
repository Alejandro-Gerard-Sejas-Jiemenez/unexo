import { useCallback, useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import cliente from '../api/cliente';

/**
 * Listado paginado con filtros en la URL.
 *
 * Los filtros viven en la query string, no en el estado del componente: así el
 * enlace se puede compartir, el botón atrás funciona y recargar no pierde la
 * búsqueda. Lo comparten todas las pantallas de listado.
 */
export default function useListado(ruta) {
    const [parametros, setParametros] = useSearchParams();
    const [pagina, setPagina] = useState(null);
    const [cargando, setCargando] = useState(true);
    const [aviso, setAviso] = useState(null);

    const clave = parametros.toString();

    const recargar = useCallback(
        async (query) => {
            setCargando(true);
            try {
                const { data } = await cliente.get(`${ruta}?${query}`);
                setPagina(data);
                setAviso(null);
            } catch (error) {
                setAviso(error.message);
            } finally {
                setCargando(false);
            }
        },
        [ruta],
    );

    useEffect(() => {
        recargar(clave);
    }, [recargar, clave]);

    /** Al cambiar un filtro se vuelve a la página 1: si no, el listado sale vacío. */
    const filtrar = useCallback(
        (campo, valor) => {
            const siguiente = new URLSearchParams(parametros);
            valor ? siguiente.set(campo, valor) : siguiente.delete(campo);
            siguiente.delete('page');
            setParametros(siguiente);
        },
        [parametros, setParametros],
    );

    const irAPagina = useCallback(
        (numero) => {
            const siguiente = new URLSearchParams(parametros);
            siguiente.set('page', String(numero));
            setParametros(siguiente);
        },
        [parametros, setParametros],
    );

    return {
        filas: pagina?.data ?? [],
        meta: pagina?.meta,
        cargando,
        aviso,
        setAviso,
        valorDe: (campo) => parametros.get(campo) ?? '',
        filtrar,
        irAPagina,
        refrescar: () => recargar(clave),
    };
}
