import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';

const CLAVE = 'nexo-preferencias';

export const TEMAS = [
    { valor: 'ninos', etiqueta: 'Niños', muestra: '#0a6ba8', acento: '#c41920' },
    { valor: 'jovenes', etiqueta: 'Jóvenes', muestra: '#4b2fbd', acento: '#07b0b0' },
    { valor: 'adultos', etiqueta: 'Adultos', muestra: '#082f4e', acento: '#07b0b0' },
];

export const MODOS = [
    { valor: 'dia', etiqueta: 'Día' },
    { valor: 'noche', etiqueta: 'Noche' },
    { valor: 'auto', etiqueta: 'Automático' },
];

export const ESCALA_MIN = 1;
export const ESCALA_MAX = 2;

const POR_DEFECTO = { tema: 'adultos', modo: 'auto', escala: 1, contraste: 'normal' };

function modoAutomatico() {
    const hora = new Date().getHours();
    return hora >= 19 || hora < 7 ? 'noche' : 'dia';
}

function leerGuardadas() {
    try {
        const crudo = localStorage.getItem(CLAVE);
        return crudo ? { ...POR_DEFECTO, ...JSON.parse(crudo) } : POR_DEFECTO;
    } catch {
        return POR_DEFECTO;
    }
}

const TemaContext = createContext(null);

export function TemaProvider({ children }) {
    const [preferencias, setPreferencias] = useState(leerGuardadas);

    const modoEfectivo = preferencias.modo === 'auto' ? modoAutomatico() : preferencias.modo;

    useEffect(() => {
        const raiz = document.documentElement;
        raiz.dataset.tema = preferencias.tema;
        raiz.dataset.modo = modoEfectivo;
        raiz.dataset.contraste = preferencias.contraste;
        raiz.style.setProperty('--escala', String(preferencias.escala));
        raiz.lang = 'es';

        try {
            localStorage.setItem(CLAVE, JSON.stringify(preferencias));
        } catch {
            // Navegación privada.
        }
    }, [preferencias, modoEfectivo]);

    useEffect(() => {
        if (preferencias.modo !== 'auto') return undefined;
        const id = setInterval(() => setPreferencias((p) => ({ ...p })), 60_000);
        return () => clearInterval(id);
    }, [preferencias.modo]);

    const actualizar = useCallback(
        (cambios) => setPreferencias((previo) => ({ ...previo, ...cambios })),
        [],
    );

    const alternarModoRapido = useCallback(() => {
        setPreferencias((previo) => {
            const actual = previo.modo === 'auto' ? modoAutomatico() : previo.modo;
            return { ...previo, modo: actual === 'noche' ? 'dia' : 'noche' };
        });
    }, []);

    const valor = useMemo(
        () => ({
            ...preferencias,
            modoEfectivo,
            actualizar,
            alternarModoRapido,
            restablecer: () => setPreferencias(POR_DEFECTO),
        }),
        [preferencias, modoEfectivo, actualizar, alternarModoRapido],
    );

    return <TemaContext.Provider value={valor}>{children}</TemaContext.Provider>;
}

export function useTema() {
    const contexto = useContext(TemaContext);

    if (!contexto) {
        throw new Error('useTema se usó fuera de TemaProvider.');
    }

    return contexto;
}
