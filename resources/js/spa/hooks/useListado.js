import { useCallback, useEffect, useRef, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { claveCache, guardarCache, leerCache } from '../api/cache';
import cliente from '../api/cliente';

export default function useListado(ruta) {
    const [parametros, setParametros] = useSearchParams();
    const clave = parametros.toString();
    const llave = claveCache(ruta, clave);

    const [pagina, setPagina] = useState(() => leerCache(llave));
    const [cargando, setCargando] = useState(() => !leerCache(llave));
    const [aviso, setAviso] = useState(null);
    const temporizador = useRef(null);

    const recargar = useCallback(
        async (query, forzar = false) => {
            const k = claveCache(ruta, query);
            const enCache = forzar ? null : leerCache(k);

            if (enCache) {
                setPagina(enCache);
                setCargando(false);
            } else {
                setCargando(true);
            }

            try {
                const url = query ? `${ruta}?${query}` : ruta;
                const { data } = await cliente.get(url);
                guardarCache(k, data);
                setPagina(data);
                setAviso(null);

                // Precarga silenciosa de la página siguiente en segundo plano.
                const actual = data?.meta?.current_page;
                const ultima = data?.meta?.last_page;
                if (actual && ultima && actual < ultima) {
                    const sig = new URLSearchParams(query);
                    sig.set('page', String(actual + 1));
                    const qSig = sig.toString();
                    const kSig = claveCache(ruta, qSig);
                    if (!leerCache(kSig)) {
                        cliente
                            .get(`${ruta}?${qSig}`)
                            .then((r) => guardarCache(kSig, r.data))
                            .catch(() => {});
                    }
                }
            } catch (error) {
                if (!enCache) setAviso(error.message);
            } finally {
                setCargando(false);
            }
        },
        [ruta],
    );

    useEffect(() => {
        recargar(clave);
    }, [recargar, clave]);

    useEffect(() => () => clearTimeout(temporizador.current), []);

    const filtrarVarios = useCallback(
        (cambios) => {
            const siguiente = new URLSearchParams(parametros);
            Object.entries(cambios).forEach(([campo, valor]) => {
                if (valor) {
                    siguiente.set(campo, valor);
                } else {
                    siguiente.delete(campo);
                }
            });
            siguiente.delete('page');
            setParametros(siguiente);
        },
        [parametros, setParametros],
    );

    const filtrar = useCallback(
        (campo, valor) => filtrarVarios({ [campo]: valor }),
        [filtrarVarios],
    );

    const filtrarConRetraso = useCallback(
        (campo, valor, espera = 220) => {
            clearTimeout(temporizador.current);
            temporizador.current = setTimeout(() => {
                filtrarVarios({ [campo]: valor });
            }, espera);
        },
        [filtrarVarios],
    );

    const limpiarFiltros = useCallback(() => {
        setParametros(new URLSearchParams());
    }, [setParametros]);

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
        extras: pagina,
        cargando,
        aviso,
        setAviso,
        valorDe: (campo) => parametros.get(campo) ?? '',
        filtrar,
        filtrarVarios,
        filtrarConRetraso,
        limpiarFiltros,
        irAPagina,
        refrescar: () => recargar(clave, true),
    };
}
