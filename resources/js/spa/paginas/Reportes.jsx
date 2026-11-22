import { useCallback, useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import cliente, { ErrorDeValidacion } from '../api/cliente';
import { claseControl } from '../componentes/Campo';
import { conBase } from '../base';

const REPORTES = [
    { clave: 'matriculas', etiqueta: 'Matrículas', fechas: true, programa: true, cohorte: true },
    { clave: 'ocupacion', etiqueta: 'Ocupación de grupos', fechas: true, programa: true, cohorte: true },
    { clave: 'catalogo', etiqueta: 'Catálogo', fechas: false, programa: false, cohorte: false },
];

/** CU8: los tres reportes, con sus filtros y las descargas en PDF y Excel. */
export default function Reportes() {
    const [parametros, setParametros] = useSearchParams();
    const activo = parametros.get('r') ?? 'matriculas';
    const definicion = REPORTES.find((r) => r.clave === activo) ?? REPORTES[0];

    const [reporte, setReporte] = useState(null);
    const [errores, setErrores] = useState({});
    const [aviso, setAviso] = useState(null);
    const [cargando, setCargando] = useState(true);

    /** Los filtros viajan en la URL, así que el reporte se puede compartir. */
    const consulta = new URLSearchParams();
    for (const campo of ['desde', 'hasta', 'programa_id', 'cohorte_id']) {
        const valor = parametros.get(campo);
        if (valor) consulta.set(campo, valor);
    }
    const query = consulta.toString();

    const cargar = useCallback(
        async (clave, q) => {
            setCargando(true);
            setErrores({});
            setAviso(null);
            try {
                const { data } = await cliente.get(`/reportes/${clave}?${q}`);
                setReporte(data);
            } catch (error) {
                setReporte(null);
                if (error instanceof ErrorDeValidacion) {
                    setErrores(
                        Object.fromEntries(
                            Object.entries(error.errores).map(([c, l]) => [c, l[0]]),
                        ),
                    );
                    setAviso('Revisá los filtros.');
                } else {
                    setAviso(error.message);
                }
            } finally {
                setCargando(false);
            }
        },
        [],
    );

    useEffect(() => {
        cargar(activo, query);
    }, [cargar, activo, query]);

    function cambiar(campo, valor) {
        const siguiente = new URLSearchParams(parametros);
        valor ? siguiente.set(campo, valor) : siguiente.delete(campo);
        setParametros(siguiente);
    }

    function cambiarReporte(clave) {
        // Los filtros no se arrastran entre reportes: cada uno acepta los suyos.
        setParametros(new URLSearchParams({ r: clave }));
    }

    const opciones = reporte?.opciones ?? {};
    const descarga = (formato) => conBase(`/reportes/${activo}/${formato}?${query}`);

    return (
        <section>
            <h1 className="mb-4 text-2xl font-semibold">Reportes</h1>

            <div role="tablist" aria-label="Reportes disponibles" className="mb-4 flex gap-2">
                {REPORTES.map((r) => (
                    <button
                        key={r.clave}
                        type="button"
                        role="tab"
                        aria-selected={activo === r.clave}
                        onClick={() => cambiarReporte(r.clave)}
                        className={
                            'rounded-lg border px-4 py-2 text-sm ' +
                            (activo === r.clave
                                ? 'border-[var(--color-acento)] bg-[var(--color-acento)] text-[var(--color-acento-texto)]'
                                : 'border-[var(--color-borde)] bg-transparent')
                        }
                    >
                        {r.etiqueta}
                    </button>
                ))}
            </div>

            {(definicion.fechas || definicion.programa) && (
                <div className="superficie mb-4 flex flex-wrap items-end gap-3 p-4">
                    {definicion.fechas && (
                        <>
                            <label>
                                <span className="mb-1 block text-sm font-medium">Desde</span>
                                <input type="date" className={claseControl}
                                    defaultValue={parametros.get('desde') ?? ''}
                                    onChange={(e) => cambiar('desde', e.target.value)} />
                                {errores.desde && (
                                    <span className="mt-1 block text-sm text-[var(--color-error)]">
                                        {errores.desde}
                                    </span>
                                )}
                            </label>
                            <label>
                                <span className="mb-1 block text-sm font-medium">Hasta</span>
                                <input type="date" className={claseControl}
                                    defaultValue={parametros.get('hasta') ?? ''}
                                    onChange={(e) => cambiar('hasta', e.target.value)} />
                                {errores.hasta && (
                                    <span className="mt-1 block text-sm text-[var(--color-error)]">
                                        {errores.hasta}
                                    </span>
                                )}
                            </label>
                        </>
                    )}

                    {definicion.programa && (
                        <label className="flex-1">
                            <span className="mb-1 block text-sm font-medium">Programa</span>
                            <select className={claseControl} value={parametros.get('programa_id') ?? ''}
                                onChange={(e) => cambiar('programa_id', e.target.value)}>
                                <option value="">Todos</option>
                                {(opciones.programas ?? []).map((p) => (
                                    <option key={p.id} value={p.id}>{p.nombre}</option>
                                ))}
                            </select>
                        </label>
                    )}

                    {definicion.cohorte && (
                        <label className="flex-1">
                            <span className="mb-1 block text-sm font-medium">Edición</span>
                            <select className={claseControl} value={parametros.get('cohorte_id') ?? ''}
                                onChange={(e) => cambiar('cohorte_id', e.target.value)}>
                                <option value="">Todas</option>
                                {(opciones.cohortes ?? []).map((c) => (
                                    <option key={c.id} value={c.id}>{c.nombre}</option>
                                ))}
                            </select>
                        </label>
                    )}
                </div>
            )}

            <div className="mb-4 flex gap-3">
                <a href={descarga('pdf')} className="superficie px-4 py-2 text-sm no-underline">
                    Descargar PDF
                </a>
                <a href={descarga('excel')} className="superficie px-4 py-2 text-sm no-underline">
                    Descargar Excel
                </a>
            </div>

            {aviso && <p role="alert" className="mb-4 text-sm text-[var(--color-error)]">{aviso}</p>}

            {cargando && (
                <p role="status" className="text-[var(--color-texto-suave)]">Generando reporte…</p>
            )}

            {!cargando && reporte && (
                <div aria-live="polite">
                    <p className="mb-4 text-sm text-[var(--color-texto-suave)]">
                        {reporte.titulo} · generado el {reporte.generado_en}
                        {reporte.filtros_aplicados?.length > 0 && (
                            <>
                                {' · '}
                                {reporte.filtros_aplicados
                                    .map((f) => `${f.etiqueta}: ${f.valor}`)
                                    .join(' · ')}
                            </>
                        )}
                    </p>

                    {reporte.sin_datos && (
                        <p className="superficie mb-4 p-4 text-sm">{reporte.aviso_sin_datos}</p>
                    )}

                    {reporte.totales?.length > 0 && (
                        <div className="mb-6 grid grid-cols-2 gap-3 md:grid-cols-4">
                            {reporte.totales.map((t) => (
                                <div key={t.etiqueta} className="superficie p-4">
                                    <p className="text-xs tracking-wide text-[var(--color-texto-suave)] uppercase">
                                        {t.etiqueta}
                                    </p>
                                    <p className="mt-1 text-2xl font-semibold tabular-nums">{t.valor}</p>
                                </div>
                            ))}
                        </div>
                    )}

                    {reporte.secciones.map((seccion) => (
                        <div key={seccion.titulo} className="mb-6">
                            <h2 className="mb-2 text-lg font-semibold">{seccion.titulo}</h2>
                            <div className="superficie overflow-x-auto">
                                <table className="w-full border-collapse text-sm">
                                    <caption className="sr-only">{seccion.titulo}</caption>
                                    <thead>
                                        <tr className="border-b border-[var(--color-borde)] text-left">
                                            {seccion.columnas.map((c) => (
                                                <th key={c.titulo} scope="col"
                                                    className={
                                                        'p-3 ' +
                                                        (c.tipo === 'entero' || c.tipo === 'porcentaje'
                                                            ? 'text-right'
                                                            : '')
                                                    }>
                                                    {c.titulo}
                                                </th>
                                            ))}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {seccion.filas.length === 0 && (
                                            <tr>
                                                <td colSpan={seccion.columnas.length}
                                                    className="p-6 text-center text-[var(--color-texto-suave)]">
                                                    {seccion.vacio ?? reporte.aviso_sin_datos}
                                                </td>
                                            </tr>
                                        )}
                                        {seccion.filas.map((fila, i) => (
                                            <tr key={i} className="border-b border-[var(--color-borde)]">
                                                {fila.map((celda, j) => (
                                                    <td key={j}
                                                        className={
                                                            'p-3 ' +
                                                            (seccion.columnas[j]?.tipo === 'entero' ||
                                                            seccion.columnas[j]?.tipo === 'porcentaje'
                                                                ? 'text-right tabular-nums'
                                                                : '')
                                                        }>
                                                        {celda}
                                                    </td>
                                                ))}
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </section>
    );
}
