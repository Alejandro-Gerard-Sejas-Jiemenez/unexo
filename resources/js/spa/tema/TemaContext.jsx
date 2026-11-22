import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';

const CLAVE = 'nexo-preferencias';

export const TEMAS = [
    { valor: 'ninos', etiqueta: 'Niños' },
    { valor: 'jovenes', etiqueta: 'Jóvenes' },
    { valor: 'adultos', etiqueta: 'Adultos' },
];

export const MODOS = [
    { valor: 'dia', etiqueta: 'Día' },
    { valor: 'noche', etiqueta: 'Noche' },
    { valor: 'auto', etiqueta: 'Automático' },
];

export const ESCALA_MIN = 1;
export const ESCALA_MAX = 2; // 200 %, el tope que pide la WCAG 2.1 AA.

const POR_DEFECTO = { tema: 'adultos', modo: 'auto', escala: 1, contraste: 'normal' };

/** De noche entre las 19:00 y las 6:59, cuando el modo está en automático. */
function modoSegunHora() {
    const hora = new Date().getHours();

    return hora >= 19 || hora < 7 ? 'noche' : 'dia';
}

function leerGuardadas() {
    try {
        const crudo = localStorage.getItem(CLAVE);

        return crudo ? { ...POR_DEFECTO, ...JSON.parse(crudo) } : POR_DEFECTO;
    } catch {
        // Navegación privada o almacenamiento bloqueado: se usa lo predeterminado.
        return POR_DEFECTO;
    }
}

const TemaContext = createContext(null);

export function TemaProvider({ children }) {
    const [preferencias, setPreferencias] = useState(leerGuardadas);

    const modoEfectivo = preferencias.modo === 'auto' ? modoSegunHora() : preferencias.modo;

    // El tema se aplica en el <html>, de donde cuelgan todas las variables CSS.
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
            // Si no se puede guardar, la preferencia vale solo para esta sesión.
        }
    }, [preferencias, modoEfectivo]);

    // En automático el cambio de franja horaria debe notarse sin recargar.
    useEffect(() => {
        if (preferencias.modo !== 'auto') return undefined;

        const id = setInterval(() => setPreferencias((p) => ({ ...p })), 60_000);

        return () => clearInterval(id);
    }, [preferencias.modo]);

    const actualizar = useCallback(
        (cambios) => setPreferencias((previo) => ({ ...previo, ...cambios })),
        [],
    );

    const valor = useMemo(
        () => ({
            ...preferencias,
            modoEfectivo,
            actualizar,
            restablecer: () => setPreferencias(POR_DEFECTO),
        }),
        [preferencias, modoEfectivo, actualizar],
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
