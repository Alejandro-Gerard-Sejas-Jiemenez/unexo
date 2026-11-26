import { useEffect, useMemo, useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import cliente from '../api/cliente';
import Icono from './Icono';

export default function BuscadorGlobal() {
    const navegar = useNavigate();
    const [termino, setTermino] = useState('');
    const [datos, setDatos] = useState(null);
    const [buscando, setBuscando] = useState(false);
    const [abierto, setAbierto] = useState(false);
    const [indiceActivo, setIndiceActivo] = useState(-1);
    const contenedor = useRef(null);
    const cacheBusqueda = useRef(new Map());

    useEffect(() => {
        const limpio = termino.trim();
        if (limpio.length < 2) {
            setDatos(null);
            setIndiceActivo(-1);
            return undefined;
        }

        const clave = limpio.toLowerCase();
        const enCache = cacheBusqueda.current.get(clave);
        if (enCache) {
            setDatos(enCache);
            setIndiceActivo(-1);
            setAbierto(true);
            return undefined;
        }

        const control = new AbortController();
        const id = setTimeout(async () => {
            setBuscando(true);
            try {
                const { data } = await cliente.get('/buscar', {
                    params: { q: limpio },
                    signal: control.signal,
                });
                if (cacheBusqueda.current.size > 60) {
                    const primera = cacheBusqueda.current.keys().next().value;
                    cacheBusqueda.current.delete(primera);
                }
                cacheBusqueda.current.set(clave, data);
                setDatos(data);
                setIndiceActivo(-1);
                setAbierto(true);
            } catch {
                // Cancelada o red caída.
            } finally {
                setBuscando(false);
            }
        }, 110);

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

    const opcionesPlanas = useMemo(() => {
        if (!datos) return [];
        const lista = [];
        for (const s of datos.secciones ?? []) {
            lista.push({ id: `sec-${s.ruta}`, ruta: s.ruta });
        }
        for (const b of datos.resultados ?? []) {
            b.items.forEach((item, idx) => {
                lista.push({ id: `res-${b.titulo}-${idx}`, ruta: item.ruta });
            });
        }
        return lista;
    }, [datos]);

    function ir(ruta) {
        setAbierto(false);
        setTermino('');
        setIndiceActivo(-1);
        navegar(ruta);
    }

    function manejarTeclas(e) {
        if (!abierto || opcionesPlanas.length === 0) {
            if (e.key === 'Escape') setAbierto(false);
            return;
        }
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            setIndiceActivo((i) => (i + 1) % opcionesPlanas.length);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setIndiceActivo((i) => (i <= 0 ? opcionesPlanas.length - 1 : i - 1));
        } else if (e.key === 'Enter' && indiceActivo >= 0) {
            e.preventDefault();
            ir(opcionesPlanas[indiceActivo].ruta);
        } else if (e.key === 'Escape') {
            setAbierto(false);
        }
    }

    const hayResultados = opcionesPlanas.length > 0;
    const opcionActivaId = indiceActivo >= 0 ? opcionesPlanas[indiceActivo]?.id : undefined;

    let puntero = -1;

    return (
        <div ref={contenedor} className="relative w-full max-w-md">
            <label htmlFor="buscador-global" className="sr-only">
                Buscar en el sistema
            </label>
            <div className="relative">
                <Icono
                    nombre="buscar"
                    className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-(--color-texto-suave)"
                />
                <input
                    id="buscador-global"
                    type="search"
                    role="combobox"
                    aria-expanded={abierto}
                    aria-controls="resultados-busqueda"
                    aria-activedescendant={opcionActivaId}
                    autoComplete="off"
                    placeholder="Buscar programas, módulos, grupos…"
                    value={termino}
                    onChange={(e) => setTermino(e.target.value)}
                    onFocus={() => datos && setAbierto(true)}
                    onKeyDown={manejarTeclas}
                    className="campo-control min-h-9! pl-9! pr-8! text-sm!"
                />
                {buscando && (
                    <span
                        aria-hidden="true"
                        className="absolute top-1/2 right-3 h-3.5 w-3.5 -translate-y-1/2 animate-spin rounded-full border-2 border-(--color-borde) border-t-(--color-acento)"
                    />
                )}
            </div>

            {abierto && datos && (
                <div
                    id="resultados-busqueda"
                    role="listbox"
                    className="superficie animate-fade-in absolute z-40 mt-2 max-h-96 w-full overflow-auto p-2"
                >
                    {!hayResultados && (
                        <p className="p-3 text-sm text-(--color-texto-suave)">
                            No encontramos resultados para «{termino}».
                        </p>
                    )}

                    {datos.secciones?.length > 0 && (
                        <>
                            <p className="px-3 pt-2 pb-1 text-xs font-semibold tracking-wide text-(--color-texto-suave) uppercase">
                                Ir a
                            </p>
                            {datos.secciones.map((s) => {
                                puntero += 1;
                                const activo = puntero === indiceActivo;
                                return (
                                    <button
                                        key={s.ruta}
                                        id={`sec-${s.ruta}`}
                                        role="option"
                                        aria-selected={activo}
                                        type="button"
                                        onClick={() => ir(s.ruta)}
                                        className={
                                            'flex w-full items-center gap-2 rounded-lg border-0 px-3 py-2 text-left text-sm ' +
                                            (activo
                                                ? 'bg-(--color-acento-suave) font-medium'
                                                : 'bg-transparent hover:bg-(--color-superficie-2)')
                                        }
                                    >
                                        <Icono nombre="siguiente" className="h-3.5 w-3.5 text-(--color-acento)" />
                                        <span>{s.etiqueta}</span>
                                    </button>
                                );
                            })}
                        </>
                    )}

                    {datos.resultados?.map((bloque) => (
                        <div key={bloque.titulo}>
                            <p className="px-3 pt-3 pb-1 text-xs font-semibold tracking-wide text-(--color-texto-suave) uppercase">
                                {bloque.titulo}
                            </p>
                            {bloque.items.map((item, i) => {
                                puntero += 1;
                                const activo = puntero === indiceActivo;
                                const idOp = `res-${bloque.titulo}-${i}`;
                                return (
                                    <button
                                        key={idOp}
                                        id={idOp}
                                        role="option"
                                        aria-selected={activo}
                                        type="button"
                                        onClick={() => ir(item.ruta)}
                                        className={
                                            'block w-full rounded-lg border-0 px-3 py-2 text-left ' +
                                            (activo
                                                ? 'bg-(--color-acento-suave)'
                                                : 'bg-transparent hover:bg-(--color-superficie-2)')
                                        }
                                    >
                                        <span className="block text-sm font-medium">{item.titulo}</span>
                                        <span className="block text-xs text-(--color-texto-suave)">
                                            {item.detalle}
                                        </span>
                                    </button>
                                );
                            })}
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
