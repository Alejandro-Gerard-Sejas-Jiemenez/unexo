import { useEffect, useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import cliente from '../api/cliente';

/**
 * Búsqueda predictiva del encabezado. El servidor decide qué puede ver cada
 * rol, así que aquí no se filtra nada: se pinta lo que llega.
 */
export default function BuscadorGlobal() {
    const navegar = useNavigate();
    const [termino, setTermino] = useState('');
    const [datos, setDatos] = useState(null);
    const [buscando, setBuscando] = useState(false);
    const [abierto, setAbierto] = useState(false);
    const contenedor = useRef(null);

    // Se espera a que deje de escribir: una petición por pausa, no por tecla.
    useEffect(() => {
        if (termino.trim().length < 2) {
            setDatos(null);

            return undefined;
        }

        const control = new AbortController();
        const id = setTimeout(async () => {
            setBuscando(true);
            try {
                const { data } = await cliente.get('/buscar', {
                    params: { q: termino },
                    signal: control.signal,
                });
                setDatos(data);
                setAbierto(true);
            } catch {
                // Una búsqueda cancelada o fallida no merece molestar al usuario.
            } finally {
                setBuscando(false);
            }
        }, 250);

        return () => {
            clearTimeout(id);
            control.abort();
        };
    }, [termino]);

    useEffect(() => {
        const alClic = (e) => {
            if (!contenedor.current?.contains(e.target)) setAbierto(false);
        };
        document.addEventListener('mousedown', alClic);

        return () => document.removeEventListener('mousedown', alClic);
    }, []);

    function ir(ruta) {
        setAbierto(false);
        setTermino('');
        navegar(ruta);
    }

    const hayResultados =
        (datos?.resultados?.length ?? 0) > 0 || (datos?.secciones?.length ?? 0) > 0;

    return (
        <div ref={contenedor} className="relative w-full max-w-md">
            <label htmlFor="buscador" className="sr-only">
                Buscar en el sistema
            </label>
            <input
                id="buscador"
                type="search"
                role="combobox"
                aria-expanded={abierto}
                aria-controls="resultados-busqueda"
                autoComplete="off"
                placeholder="Buscar programas, módulos, grupos…"
                value={termino}
                onChange={(e) => setTermino(e.target.value)}
                onFocus={() => datos && setAbierto(true)}
                onKeyDown={(e) => e.key === 'Escape' && setAbierto(false)}
                className="superficie w-full px-3 py-2 text-sm"
            />

            {abierto && datos && (
                <div
                    id="resultados-busqueda"
                    className="superficie absolute z-10 mt-2 max-h-96 w-full overflow-auto p-2"
                >
                    {!hayResultados && (
                        <p className="p-3 text-sm text-[var(--color-texto-suave)]">
                            No encontramos nada para «{termino}».
                        </p>
                    )}

                    {datos.secciones?.length > 0 && (
                        <>
                            <p className="px-3 pt-2 pb-1 text-xs font-semibold tracking-wide text-[var(--color-texto-suave)] uppercase">
                                Ir a
                            </p>
                            {datos.secciones.map((s) => (
                                <button
                                    key={s.ruta}
                                    type="button"
                                    onClick={() => ir(s.ruta)}
                                    className="block w-full border-0 bg-transparent px-3 py-2 text-left text-sm"
                                >
                                    → {s.etiqueta}
                                </button>
                            ))}
                        </>
                    )}

                    {datos.resultados?.map((bloque) => (
                        <div key={bloque.titulo}>
                            <p className="px-3 pt-3 pb-1 text-xs font-semibold tracking-wide text-[var(--color-texto-suave)] uppercase">
                                {bloque.titulo}
                            </p>
                            {bloque.items.map((item, i) => (
                                <button
                                    key={`${bloque.titulo}-${i}`}
                                    type="button"
                                    onClick={() => ir(item.ruta)}
                                    className="block w-full border-0 bg-transparent px-3 py-2 text-left"
                                >
                                    <span className="block text-sm">{item.titulo}</span>
                                    <span className="block text-xs text-[var(--color-texto-suave)]">
                                        {item.detalle}
                                    </span>
                                </button>
                            ))}
                        </div>
                    ))}
                </div>
            )}

            <span aria-live="polite" className="sr-only">
                {buscando ? 'Buscando…' : hayResultados ? 'Hay resultados disponibles.' : ''}
            </span>
        </div>
    );
}
