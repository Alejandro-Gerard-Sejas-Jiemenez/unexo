import { useCallback, useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { claveCache, guardarCache, leerCache } from '../api/cache';
import cliente, { ErrorDeValidacion } from '../api/cliente';
import { claseControl } from '../componentes/Campo';
import Cargador from '../componentes/Cargador';
import EncabezadoPagina from '../componentes/EncabezadoPagina';
import Icono from '../componentes/Icono';
import TablaDatos from '../componentes/TablaDatos';
import { useToast } from '../componentes/ToastContext';

const REPORTES = [
    {
        clave: 'matriculas',
        etiqueta: 'Matrículas',
        subtitulo: 'Distribución por estado, origen y cohorte según el período elegido.',
        fechas: true,
        labelDesde: 'Fecha de matrícula desde',
        programa: true,
        estado: false,
        cohorte: true,
    },
    {
        clave: 'ocupacion',
        etiqueta: 'Ocupación de grupos',
        subtitulo: 'Inscritos activos frente al cupo mínimo definido por grupo.',
        fechas: true,
        labelDesde: 'Período desde',
        programa: false,
        estado: true,
        cohorte: true,
    },
    {
        clave: 'catalogo',
        etiqueta: 'Catálogo y oferta',
        subtitulo: 'Estado comercial de programas, módulos libres y oferta visible para el CRM.',
        fechas: false,
        programa: false,
        estado: false,
        cohorte: false,
    },
];

const ETIQUETA_ESTADO = {
    programado: 'Programado',
    en_curso: 'En curso',
    finalizado: 'Finalizado',
    cancelado: 'Cancelado',
};

export default function Reportes() {
    const { mostrar } = useToast();
    const [parametros, setParametros] = useSearchParams();
    const activo = parametros.get('r') ?? 'matriculas';
    const definicion = REPORTES.find((r) => r.clave === activo) ?? REPORTES[0];

    const [borrador, setBorrador] = useState({
        desde: parametros.get('desde') ?? '',
        hasta: parametros.get('hasta') ?? '',
        programa_id: parametros.get('programa_id') ?? '',
        estado: parametros.get('estado') ?? '',
        cohorte_id: parametros.get('cohorte_id') ?? '',
    });

    const [reporte, setReporte] = useState(null);
    const [opciones, setOpciones] = useState({});
    const [errores, setErrores] = useState({});
    const [aviso, setAviso] = useState(null);
    const [cargando, setCargando] = useState(true);
    const [descargando, setDescargando] = useState(null);

    useEffect(() => {
        setBorrador({
            desde: parametros.get('desde') ?? '',
            hasta: parametros.get('hasta') ?? '',
            programa_id: parametros.get('programa_id') ?? '',
            estado: parametros.get('estado') ?? '',
            cohorte_id: parametros.get('cohorte_id') ?? '',
        });
    }, [parametros]);

    const consulta = new URLSearchParams();
    for (const campo of ['desde', 'hasta', 'programa_id', 'estado', 'cohorte_id']) {
        const valor = parametros.get(campo);
        if (valor) consulta.set(campo, valor);
    }
    const query = consulta.toString();

    const cargar = useCallback(async (clave, q) => {
        const llave = claveCache(`/reportes/${clave}`, q);
        const enCache = leerCache(llave);
        if (enCache) {
            setReporte(enCache);
            if (enCache.opciones) setOpciones(enCache.opciones);
            setCargando(false);
        } else {
            setCargando(true);
        }
        setErrores({});
        setAviso(null);
        try {
            const { data } = await cliente.get(`/reportes/${clave}${q ? `?${q}` : ''}`);
            guardarCache(llave, data);
            setReporte(data);
            if (data.opciones) setOpciones(data.opciones);
        } catch (error) {
            if (!enCache) setReporte(null);
            if (error instanceof ErrorDeValidacion) {
                setErrores(Object.fromEntries(Object.entries(error.errores).map(([c, l]) => [c, l[0]])));
                setAviso('Revisa los filtros seleccionados.');
            } else if (!enCache) {
                setAviso(error.message);
            }
        } finally {
            setCargando(false);
        }
    }, []);

    useEffect(() => {
        cargar(activo, query);
    }, [cargar, activo, query]);

    function aplicarFiltros(e) {
        e.preventDefault();
        const siguiente = new URLSearchParams({ r: activo });
        for (const campo of ['desde', 'hasta', 'programa_id', 'estado', 'cohorte_id']) {
            if (borrador[campo]) siguiente.set(campo, borrador[campo]);
        }
        setParametros(siguiente);
    }

    function quitarFiltros() {
        setParametros(new URLSearchParams({ r: activo }));
    }

    function cambiarReporte(clave) {
        setOpciones({});
        setParametros(new URLSearchParams({ r: clave }));
    }

    async function descargar(formato) {
        setDescargando(formato);
        setAviso(null);
        try {
            const { data, headers } = await cliente.get(
                `/reportes/${activo}/${formato}${query ? `?${query}` : ''}`,
                { responseType: 'blob' },
            );
            const disposicion = headers['content-disposition'] ?? '';
            const coincidencia = disposicion.match(/filename="?([^"]+)"?/i);
            const ext = formato === 'excel' ? 'xlsx' : 'pdf';
            const nombre = coincidencia?.[1] ?? `reporte-${activo}.${ext}`;

            const url = URL.createObjectURL(data);
            const enlace = document.createElement('a');
            enlace.href = url;
            enlace.download = nombre;
            document.body.appendChild(enlace);
            enlace.click();
            document.body.removeChild(enlace);
            URL.revokeObjectURL(url);
            mostrar(`Archivo ${ext.toUpperCase()} descargado.`);
        } catch (error) {
            setAviso(error.message || 'No pudimos generar el reporte.');
        } finally {
            setDescargando(null);
        }
    }

    return (
        <section>
            <EncabezadoPagina
                titulo="Reportes"
                subtitulo={definicion.subtitulo}
            />

            <nav
                role="tablist"
                aria-label="Reportes disponibles"
                className="mb-6 flex flex-wrap gap-1 border-b border-[var(--color-borde)]"
            >
                {REPORTES.map((r) => {
                    const seleccionado = activo === r.clave;
                    return (
                        <button
                            key={r.clave}
                            type="button"
                            role="tab"
                            aria-selected={seleccionado}
                            onClick={() => cambiarReporte(r.clave)}
                            className={
                                'cursor-pointer border-0 border-b-2 bg-transparent px-4 py-2.5 text-sm font-semibold transition-colors ' +
                                (seleccionado
                                    ? 'border-[var(--color-acento)] text-[var(--color-acento)]'
                                    : 'border-transparent text-[var(--color-texto-suave)] hover:text-[var(--color-texto)]')
                            }
                        >
                            {r.etiqueta}
                        </button>
                    );
                })}
            </nav>

            {(definicion.fechas || definicion.programa || definicion.estado || definicion.cohorte) && (
                <form onSubmit={aplicarFiltros} noValidate className="superficie mb-6 rounded-xl p-6 shadow-sm">
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        {definicion.fechas && (
                            <>
                                <label>
                                    <span className="mb-1.5 block text-xs font-semibold text-[var(--color-texto-suave)]">
                                        {definicion.labelDesde}
                                    </span>
                                    <input
                                        type="date"
                                        className={claseControl}
                                        value={borrador.desde}
                                        onChange={(e) => setBorrador((b) => ({ ...b, desde: e.target.value }))}
                                    />
                                    {errores.desde && (
                                        <span className="mt-1 block text-xs text-[var(--color-error)]">{errores.desde}</span>
                                    )}
                                </label>
                                <label>
                                    <span className="mb-1.5 block text-xs font-semibold text-[var(--color-texto-suave)]">
                                        Hasta
                                    </span>
                                    <input
                                        type="date"
                                        className={claseControl}
                                        value={borrador.hasta}
                                        onChange={(e) => setBorrador((b) => ({ ...b, hasta: e.target.value }))}
                                    />
                                    {errores.hasta && (
                                        <span className="mt-1 block text-xs text-[var(--color-error)]">{errores.hasta}</span>
                                    )}
                                </label>
                            </>
                        )}

                        {definicion.programa && (
                            <label>
                                <span className="mb-1.5 block text-xs font-semibold text-[var(--color-texto-suave)]">
                                    Programa
                                </span>
                                <select
                                    className={claseControl}
                                    value={borrador.programa_id}
                                    onChange={(e) => setBorrador((b) => ({ ...b, programa_id: e.target.value }))}
                                >
                                    <option value="">Todos</option>
                                    {(opciones.programas ?? []).map((p) => (
                                        <option key={p.id} value={p.id}>
                                            {p.nombre}
                                        </option>
                                    ))}
                                </select>
                                {errores.programa_id && (
                                    <span className="mt-1 block text-xs text-[var(--color-error)]">{errores.programa_id}</span>
                                )}
                            </label>
                        )}

                        {definicion.estado && (
                            <label>
                                <span className="mb-1.5 block text-xs font-semibold text-[var(--color-texto-suave)]">
                                    Estado del grupo
                                </span>
                                <select
                                    className={claseControl}
                                    value={borrador.estado}
                                    onChange={(e) => setBorrador((b) => ({ ...b, estado: e.target.value }))}
                                >
                                    <option value="">Todos</option>
                                    {(opciones.estados ?? []).map((est) => {
                                        const valor = typeof est === 'string' ? est : est.value;
                                        return (
                                            <option key={valor} value={valor}>
                                                {ETIQUETA_ESTADO[valor] ?? valor}
                                            </option>
                                        );
                                    })}
                                </select>
                                {errores.estado && (
                                    <span className="mt-1 block text-xs text-[var(--color-error)]">{errores.estado}</span>
                                )}
                            </label>
                        )}

                        {definicion.cohorte && (
                            <label>
                                <span className="mb-1.5 block text-xs font-semibold text-[var(--color-texto-suave)]">
                                    Cohorte
                                </span>
                                <select
                                    className={claseControl}
                                    value={borrador.cohorte_id}
                                    onChange={(e) => setBorrador((b) => ({ ...b, cohorte_id: e.target.value }))}
                                >
                                    <option value="">Todas</option>
                                    {(opciones.cohortes ?? []).map((c) => (
                                        <option key={c.id} value={c.id}>
                                            {c.programa?.nombre ? `${c.programa.nombre} · ${c.nombre}` : c.nombre}
                                        </option>
                                    ))}
                                </select>
                                {errores.cohorte_id && (
                                    <span className="mt-1 block text-xs text-[var(--color-error)]">{errores.cohorte_id}</span>
                                )}
                            </label>
                        )}
                    </div>

                    <div className="mt-5 flex flex-wrap items-center gap-3 border-t border-[var(--color-borde)] pt-4">
                        <button type="submit" className="btn btn-primario">
                            Aplicar filtros
                        </button>
                        <button type="button" onClick={quitarFiltros} className="btn btn-secundario">
                            Quitar filtros
                        </button>
                    </div>
                </form>
            )}

            {!cargando && reporte && !aviso && (
                <div className="mb-6 flex flex-wrap gap-3">
                    <button
                        type="button"
                        disabled={descargando !== null}
                        onClick={() => descargar('pdf')}
                        className="btn btn-secundario"
                    >
                        <Icono nombre="documento" className="h-4 w-4 opacity-70" />
                        {descargando === 'pdf' ? 'Generando PDF…' : 'Descargar PDF'}
                    </button>
                    <button
                        type="button"
                        disabled={descargando !== null}
                        onClick={() => descargar('excel')}
                        className="btn btn-secundario"
                    >
                        <Icono nombre="guardar" className="h-4 w-4 opacity-70" />
                        {descargando === 'excel' ? 'Generando Excel…' : 'Descargar Excel'}
                    </button>
                </div>
            )}

            {aviso && (
                <p role="alert" className="superficie mb-6 border-[var(--color-error)] p-4 text-sm text-[var(--color-error)]">
                    {aviso}
                </p>
            )}

            {cargando && <Cargador texto="Cargando" />}

            {!cargando && reporte && (
                <div aria-live="polite" className="space-y-8">
                    <p className="text-sm text-[var(--color-texto-suave)]">
                        {reporte.titulo} · generado el {reporte.generado_en}
                        {reporte.filtros_aplicados?.length > 0 && (
                            <>
                                {' · '}
                                {reporte.filtros_aplicados.map((f) => `${f.etiqueta}: ${f.valor}`).join(' · ')}
                            </>
                        )}
                    </p>

                    {reporte.sin_datos && (
                        <div
                            role="status"
                            className="rounded-xl border border-[color-mix(in_srgb,var(--color-aviso)_35%,transparent)] bg-[var(--color-aviso-fondo)] px-5 py-4 text-sm font-medium text-[var(--color-aviso)]"
                        >
                            {reporte.aviso_sin_datos}
                        </div>
                    )}

                    {reporte.totales?.length > 0 && (
                        <dl className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            {reporte.totales.map((t) => (
                                <div
                                    key={t.etiqueta}
                                    className="superficie flex items-center justify-between gap-4 rounded-xl p-5 shadow-sm"
                                >
                                    <dt className="text-sm font-medium text-[var(--color-texto-suave)]">
                                        {t.etiqueta}
                                    </dt>
                                    <dd className="text-xl font-semibold tabular-nums">{t.valor}</dd>
                                </div>
                            ))}
                        </dl>
                    )}

                    {reporte.secciones.map((seccion) => (
                        <div key={seccion.titulo}>
                            <h2 className="mb-3 text-lg font-semibold">{seccion.titulo}</h2>
                            <TablaDatos
                                caption={seccion.titulo}
                                columnas={seccion.columnas.map((c) => ({
                                    etiqueta: c.titulo,
                                    alinear: c.tipo === 'entero' || c.tipo === 'porcentaje' ? 'derecha' : 'izquierda',
                                }))}
                                vacio={seccion.filas.length === 0}
                                vacioIcono="reportes"
                                vacioTitulo="Sin filas"
                                vacioDescripcion={seccion.vacio ?? reporte.aviso_sin_datos}
                            >
                                {seccion.filas.map((fila, i) => (
                                    <tr key={i} className="transition-colors hover:bg-[var(--color-superficie-2)]/60">
                                        {fila.map((celda, j) => {
                                            const esNum =
                                                seccion.columnas[j]?.tipo === 'entero' ||
                                                seccion.columnas[j]?.tipo === 'porcentaje';
                                            return (
                                                <td
                                                    key={j}
                                                    className={`px-6 py-4 text-sm ${esNum ? 'text-right tabular-nums' : ''}`}
                                                >
                                                    {celda}
                                                </td>
                                            );
                                        })}
                                    </tr>
                                ))}
                            </TablaDatos>
                        </div>
                    ))}
                </div>
            )}
        </section>
    );
}
