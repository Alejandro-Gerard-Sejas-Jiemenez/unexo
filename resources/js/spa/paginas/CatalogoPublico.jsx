import { useCallback, useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import cliente from '../api/cliente';
import { claseControl } from '../componentes/Campo';

/** Catálogo público: la misma oferta abierta que consume el CRM. */
export default function CatalogoPublico() {
    const [parametros, setParametros] = useSearchParams();
    const tab = parametros.get('tab') ?? 'programas';
    const q = parametros.get('q') ?? '';

    const [datos, setDatos] = useState({ programas: [], modulos: [] });
    const [cargando, setCargando] = useState(true);
    const [aviso, setAviso] = useState(null);

    const cargar = useCallback(async (busqueda) => {
        setCargando(true);
        try {
            const { data } = await cliente.get('/catalogo-publico', { params: { q: busqueda } });
            setDatos(data);
            setAviso(null);
        } catch (error) {
            setAviso(error.message);
        } finally {
            setCargando(false);
        }
    }, []);

    useEffect(() => {
        cargar(q);
    }, [cargar, q]);

    function cambiar(campo, valor) {
        const siguiente = new URLSearchParams(parametros);
        valor ? siguiente.set(campo, valor) : siguiente.delete(campo);
        setParametros(siguiente);
    }

    const enProgramas = tab === 'programas';

    return (
        <section>
            <h1 className="mb-1 text-2xl font-semibold">Catálogo</h1>
            <p className="mb-4 text-sm text-[var(--color-texto-suave)]">
                La oferta abierta para inscripción. Es la misma que el CRM consulta por la API.
            </p>

            <div className="mb-4 flex flex-wrap items-center gap-3">
                <div role="tablist" aria-label="Tipo de oferta" className="flex gap-2">
                    {[
                        ['programas', `Programas (${datos.programas.length})`],
                        ['modulos', `Módulos sueltos (${datos.modulos.length})`],
                    ].map(([clave, etiqueta]) => (
                        <button key={clave} type="button" role="tab"
                            aria-selected={tab === clave}
                            onClick={() => cambiar('tab', clave)}
                            className={
                                'rounded-lg border px-4 py-2 text-sm ' +
                                (tab === clave
                                    ? 'border-[var(--color-acento)] bg-[var(--color-acento)] text-[var(--color-acento-texto)]'
                                    : 'border-[var(--color-borde)] bg-transparent')
                            }>
                            {etiqueta}
                        </button>
                    ))}
                </div>

                <label className="flex-1">
                    <span className="sr-only">Buscar en el catálogo</span>
                    <input type="search" placeholder="Buscar por nombre, código o descripción"
                        className={claseControl} defaultValue={q}
                        onChange={(e) => cambiar('q', e.target.value)} />
                </label>
            </div>

            {aviso && <p role="alert" className="mb-4 text-sm text-[var(--color-error)]">{aviso}</p>}

            {cargando && (
                <p role="status" className="text-[var(--color-texto-suave)]">Cargando catálogo…</p>
            )}

            {!cargando && enProgramas && (
                <div className="grid gap-4 lg:grid-cols-2">
                    {datos.programas.length === 0 && (
                        <p className="superficie p-6 text-center text-[var(--color-texto-suave)]">
                            No hay programas abiertos que coincidan con la búsqueda.
                        </p>
                    )}
                    {datos.programas.map((p) => (
                        <article key={p.id} className="superficie p-4">
                            <h2 className="text-lg font-semibold">{p.nombre}</h2>
                            <p className="mb-2 text-xs text-[var(--color-texto-suave)]">
                                {p.codigo} · versión {p.version}
                                {p.certificado ? ` · ${p.certificado}` : ''}
                                {p.precio != null ? ` · Bs ${p.precio.toFixed(2)}` : ''}
                            </p>
                            {p.descripcion && <p className="mb-3 text-sm">{p.descripcion}</p>}

                            <h3 className="text-sm font-semibold">Plan de estudio</h3>
                            <ol className="mb-3 list-decimal pl-5 text-sm">
                                {p.modulos.length === 0 && (
                                    <li className="list-none text-[var(--color-texto-suave)]">
                                        Sin módulos abiertos por ahora.
                                    </li>
                                )}
                                {p.modulos.map((m) => (
                                    <li key={m.id}>
                                        {m.nombre}
                                        {m.duracion_horas ? ` · ${m.duracion_horas} h` : ''}
                                    </li>
                                ))}
                            </ol>

                            <h3 className="text-sm font-semibold">Ediciones programadas</h3>
                            {p.ediciones.length === 0 ? (
                                <p className="text-sm text-[var(--color-texto-suave)]">
                                    Todavía no hay ediciones con fecha.
                                </p>
                            ) : (
                                <ul className="list-none p-0 text-sm">
                                    {p.ediciones.map((e) => (
                                        <li key={e.id}>
                                            {e.nombre}: {e.fecha_inicio} → {e.fecha_fin} ({e.grupos} grupos)
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </article>
                    ))}
                </div>
            )}

            {!cargando && !enProgramas && (
                <div className="grid gap-4 lg:grid-cols-2">
                    {datos.modulos.length === 0 && (
                        <p className="superficie p-6 text-center text-[var(--color-texto-suave)]">
                            No hay módulos abiertos que coincidan con la búsqueda.
                        </p>
                    )}
                    {datos.modulos.map((m) => (
                        <article key={m.id} className="superficie p-4">
                            <h2 className="text-lg font-semibold">{m.nombre}</h2>
                            <p className="mb-2 text-xs text-[var(--color-texto-suave)]">
                                {m.codigo}
                                {m.duracion_horas ? ` · ${m.duracion_horas} h` : ''}
                            </p>
                            {m.descripcion && <p className="mb-2 text-sm">{m.descripcion}</p>}
                            {m.prerrequisito && (
                                <p className="mb-2 text-sm">
                                    <b>Conocimientos recomendados:</b> {m.prerrequisito}
                                </p>
                            )}
                            {m.programas.length > 0 && (
                                <p className="mb-2 text-xs text-[var(--color-texto-suave)]">
                                    También forma parte de: {m.programas.join(', ')}
                                </p>
                            )}

                            <h3 className="text-sm font-semibold">Grupos para cursarlo suelto</h3>
                            {m.grupos_sueltos.length === 0 ? (
                                <p className="text-sm text-[var(--color-texto-suave)]">
                                    Sin grupos sueltos programados.
                                </p>
                            ) : (
                                <ul className="list-none p-0 text-sm">
                                    {m.grupos_sueltos.map((g) => (
                                        <li key={g.id}>
                                            {g.nombre} · {g.modalidad_label}
                                            {g.fecha_inicio ? ` · desde el ${g.fecha_inicio}` : ''}
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </article>
                    ))}
                </div>
            )}
        </section>
    );
}
