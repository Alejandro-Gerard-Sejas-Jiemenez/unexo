import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import BarraHerramientas from '../../componentes/BarraHerramientas';
import BotonAccion from '../../componentes/BotonAccion';
import Cargador from '../../componentes/Cargador';
import EncabezadoPagina from '../../componentes/EncabezadoPagina';
import Icono from '../../componentes/Icono';
import Paginacion from '../../componentes/Paginacion';
import TablaDatos from '../../componentes/TablaDatos';
import useListado from '../../hooks/useListado';

const TIPOS = [
    ['', 'Todos'],
    ['cohorte', 'De Edición'],
    ['suelto', 'Módulos Sueltos'],
];

const ESTADOS = [
    { valor: '', etiqueta: 'Todos estados' },
    { valor: 'programado', etiqueta: 'Programado' },
    { valor: 'en_curso', etiqueta: 'En curso' },
    { valor: 'finalizado', etiqueta: 'Finalizado' },
    { valor: 'cancelado', etiqueta: 'Cancelado' },
];

function inicialesDocente(nombre) {
    if (!nombre) return '—';
    const partes = nombre.trim().split(/\s+/);
    return ((partes[0]?.[0] ?? '') + (partes[1]?.[0] ?? '')).toUpperCase() || 'DO';
}

export default function GruposLista() {
    const { filas, meta, cargando, aviso, valorDe, filtrar, filtrarConRetraso, limpiarFiltros, irAPagina } =
        useListado('/grupos');

    const [busqueda, setBusqueda] = useState(valorDe('q'));

    useEffect(() => {
        setBusqueda(valorDe('q'));
    }, [valorDe]);

    const tipoActual = valorDe('tipo');

    const columnas = [
        { etiqueta: 'Módulo' },
        { etiqueta: 'Grupo' },
        { etiqueta: 'Edición / Programa' },
        { etiqueta: 'Período' },
        { etiqueta: 'Docente' },
        { etiqueta: 'Inscritos' },
        { etiqueta: 'Estado' },
        { etiqueta: 'Acciones', srOnly: true, alinear: 'derecha' },
    ];

    return (
        <section>
            <EncabezadoPagina
                titulo="Grupos"
                acciones={
                    <>
                        <Link to="/aulas" className="btn btn-secundario">
                            Aulas
                        </Link>
                        <Link to="/grupos/nuevo" className="btn btn-primario">
                            <Icono nombre="mas" className="h-5 w-5" />
                            Nuevo grupo
                        </Link>
                    </>
                }
            />

            <BarraHerramientas
                pildoras={ESTADOS}
                pildoraActiva={valorDe('estado')}
                alSeleccionarPildora={(val) => filtrar('estado', val)}
                extraIzquierda={
                    <div className="flex items-center gap-2">
                        <span className="hidden h-4 w-px bg-[var(--color-borde)] sm:block" aria-hidden="true" />
                        <div className="inline-flex rounded-lg bg-[var(--color-superficie-2)] p-1">
                            {TIPOS.map(([valor, etiqueta]) => (
                                <button
                                    key={etiqueta}
                                    type="button"
                                    onClick={() => filtrar('tipo', valor)}
                                    className={
                                        'cursor-pointer rounded-md px-3 py-1 text-xs font-semibold transition-all ' +
                                        (tipoActual === valor
                                            ? 'bg-[var(--color-superficie)] text-[var(--color-acento)] shadow-xs'
                                            : 'text-[var(--color-texto-suave)] hover:text-[var(--color-texto)]')
                                    }
                                >
                                    {etiqueta}
                                </button>
                            ))}
                        </div>
                    </div>
                }
                busqueda={busqueda}
                placeholder="Buscar grupo, módulo o docente…"
                alCambiarBusqueda={(val) => {
                    setBusqueda(val);
                    filtrarConRetraso('q', val);
                }}
                alBuscar={(val) => filtrar('q', val)}
                alLimpiarBusqueda={() => {
                    setBusqueda('');
                    limpiarFiltros();
                }}
            />

            {aviso && (
                <p role="alert" className="superficie mb-4 border-[var(--color-error)] p-3 text-sm text-[var(--color-error)]">
                    {aviso}
                </p>
            )}

            {cargando && <Cargador texto="Cargando" />}

            <TablaDatos
                caption="Listado de grupos operativos"
                columnas={columnas}
                compacto
                vacio={!cargando && filas.length === 0}
                vacioIcono="usuarios"
                vacioTitulo="No se encontraron grupos"
                vacioDescripcion="Intenta cambiar los filtros o la búsqueda."
            >
                {filas.map((g) => {
                    const cubierto = g.cupo_minimo > 0 && (g.inscripciones_count ?? 0) >= g.cupo_minimo;

                    return (
                        <tr key={g.id} className="transition-colors hover:bg-[var(--color-superficie-2)]/60">
                            <td className="px-4 py-3.5">
                                <div className="text-sm font-semibold">{g.modulo?.nombre}</div>
                                {g.modulo?.codigo && (
                                    <div className="font-mono text-xs text-[var(--color-texto-suave)]">
                                        {g.modulo.codigo}
                                    </div>
                                )}
                            </td>
                            <td className="px-4 py-3.5">
                                <span className="text-sm font-bold text-[var(--color-acento)]">{g.nombre}</span>
                            </td>
                            <td className="px-4 py-3.5">
                                {g.cohorte ? (
                                    <>
                                        <Link
                                            to={`/cohortes/${g.cohorte.id}`}
                                            className="text-sm font-semibold text-[var(--color-acento)] no-underline hover:underline"
                                        >
                                            {g.cohorte.nombre}
                                        </Link>
                                        {g.cohorte.programa && (
                                            <div className="max-w-[180px] truncate text-xs text-[var(--color-texto-suave)]">
                                                {g.cohorte.programa}
                                            </div>
                                        )}
                                    </>
                                ) : (
                                    <span className="inline-flex rounded bg-[var(--color-superficie-2)] px-2 py-0.5 text-xs font-medium text-[var(--color-texto-suave)]">
                                        Módulo suelto
                                    </span>
                                )}
                            </td>
                            <td className="px-4 py-3.5 text-xs text-[var(--color-texto-suave)] whitespace-nowrap tabular-nums">
                                {g.periodo_texto || '—'}
                            </td>
                            <td className="px-4 py-3.5">
                                <div className="flex items-center gap-2">
                                    <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[var(--color-superficie-2)] text-[10px] font-bold text-[var(--color-texto-suave)]">
                                        {inicialesDocente(g.docente)}
                                    </span>
                                    <span className="text-sm">{g.docente ?? 'Sin asignar'}</span>
                                </div>
                            </td>
                            <td className="px-4 py-3.5 text-xs whitespace-nowrap tabular-nums">
                                <span className="inline-flex rounded-full bg-[var(--color-superficie-2)] px-2.5 py-0.5 font-semibold">
                                    {g.inscripciones_count ?? 0}{' '}
                                    {(g.inscripciones_count ?? 0) === 1 ? 'estudiante' : 'estudiantes'}
                                </span>
                                {g.cupo_minimo > 0 && (
                                    <span className={`ml-1.5 font-medium ${cubierto ? 'text-[var(--color-exito)]' : 'text-[var(--color-texto-suave)]'}`}>
                                        (mín. {g.cupo_minimo})
                                    </span>
                                )}
                            </td>
                            <td className="px-4 py-3.5 whitespace-nowrap">
                                <span
                                    className={
                                        'inline-flex rounded-md px-2.5 py-1 text-xs font-medium ring-1 ring-inset ' +
                                        (g.estado === 'en_curso'
                                            ? 'bg-[var(--color-exito-fondo)] text-[var(--color-exito)] ring-[color-mix(in_srgb,var(--color-exito)_30%,transparent)]'
                                            : 'bg-[var(--color-superficie-2)] text-[var(--color-texto-suave)] ring-[var(--color-borde)]')
                                    }
                                >
                                    {g.estado_label}
                                </span>
                            </td>
                            <td className="px-4 py-3.5 text-right whitespace-nowrap">
                                <div className="inline-flex items-center gap-1.5">
                                    <BotonAccion to={`/grupos/${g.id}/ver`} icono="usuarios">
                                        Ver
                                    </BotonAccion>
                                    <BotonAccion to={`/grupos/${g.id}`} icono="editar">
                                        Editar
                                    </BotonAccion>
                                </div>
                            </td>
                        </tr>
                    );
                })}
            </TablaDatos>

            <Paginacion meta={meta} irAPagina={irAPagina} sustantivo="grupos" />
        </section>
    );
}
