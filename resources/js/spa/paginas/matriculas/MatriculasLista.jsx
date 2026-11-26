import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import BarraHerramientas from '../../componentes/BarraHerramientas';
import BotonAccion from '../../componentes/BotonAccion';
import { claseControl } from '../../componentes/Campo';
import Cargador from '../../componentes/Cargador';
import EncabezadoPagina from '../../componentes/EncabezadoPagina';
import Icono from '../../componentes/Icono';
import Insignia, { tonoEstado } from '../../componentes/Insignia';
import Paginacion from '../../componentes/Paginacion';
import TablaDatos from '../../componentes/TablaDatos';
import useListado from '../../hooks/useListado';
import { formatoFecha } from '../../lib/fechas';

function iniciales(nombre) {
    if (!nombre) return 'ES';
    const partes = nombre.trim().split(/\s+/);
    return ((partes[0]?.[0] ?? '') + (partes[1]?.[0] ?? '')).toUpperCase() || 'ES';
}

const ESTADOS = [
    { valor: '', etiqueta: 'Todas' },
    { valor: 'pendiente', etiqueta: 'Pendientes' },
    { valor: 'activo', etiqueta: 'Activas' },
    { valor: 'retirado', etiqueta: 'Retiradas' },
];

export default function MatriculasLista() {
    const { filas, meta, cargando, aviso, valorDe, filtrar, filtrarConRetraso, limpiarFiltros, irAPagina } =
        useListado('/matriculas');

    const [busqueda, setBusqueda] = useState(valorDe('q'));

    useEffect(() => {
        setBusqueda(valorDe('q'));
    }, [valorDe]);

    const columnas = [
        { etiqueta: 'Estudiante' },
        { etiqueta: 'Inscripción' },
        { etiqueta: 'Grupos' },
        { etiqueta: 'Origen' },
        { etiqueta: 'Fecha' },
        { etiqueta: 'Estado' },
        { etiqueta: 'Acciones', srOnly: true, alinear: 'derecha' },
    ];

    return (
        <section>
            <EncabezadoPagina
                titulo="Matrículas"
                acciones={
                    <Link to="/matriculas/nueva" className="btn btn-primario">
                        <Icono nombre="mas" className="h-5 w-5" />
                        Nueva matrícula
                    </Link>
                }
            />

            <BarraHerramientas
                pildoras={ESTADOS}
                pildoraActiva={valorDe('estado')}
                alSeleccionarPildora={(val) => filtrar('estado', val)}
                extraIzquierda={
                    <label>
                        <span className="sr-only">Filtrar por origen</span>
                        <select
                            className={`${claseControl} !min-h-8 !py-1 text-xs`}
                            value={valorDe('origen')}
                            onChange={(e) => filtrar('origen', e.target.value)}
                        >
                            <option value="">Todos los orígenes</option>
                            <option value="manual">Panel</option>
                            <option value="crm">CRM</option>
                        </select>
                    </label>
                }
                busqueda={busqueda}
                placeholder="Buscar por estudiante o correo…"
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
                caption="Listado de matrículas"
                columnas={columnas}
                vacio={!cargando && filas.length === 0}
                vacioIcono="documento"
                vacioTitulo="No hay matrículas registradas"
                vacioDescripcion="Intenta cambiar los filtros o registra una nueva matrícula."
            >
                {filas.map((m) => (
                    <tr key={m.id} className="transition-colors hover:bg-[var(--color-superficie-2)]/60">
                        <td className="px-6 py-4 whitespace-nowrap">
                            <div className="flex items-center gap-2.5">
                                <span
                                    aria-hidden="true"
                                    className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[var(--color-superficie-2)] text-xs font-bold text-[var(--color-texto-suave)]"
                                >
                                    {iniciales(m.estudiante?.nombre)}
                                </span>
                                <div>
                                    <p className="text-sm font-semibold">{m.estudiante?.nombre ?? '—'}</p>
                                    <p className="text-xs text-[var(--color-texto-suave)]">
                                        {m.estudiante?.correo_institucional ?? m.estudiante?.email}
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td className="px-6 py-4">
                            {m.cohorte ? (
                                <>
                                    <p className="text-sm font-medium">{m.cohorte.programa ?? m.cohorte.nombre}</p>
                                    {m.cohorte.programa && (
                                        <p className="text-xs text-[var(--color-texto-suave)]">{m.cohorte.nombre}</p>
                                    )}
                                </>
                            ) : (
                                <span className="text-sm text-[var(--color-texto-suave)]">Módulo suelto</span>
                            )}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                            <span className="inline-flex items-center justify-center rounded-full bg-[var(--color-superficie-2)] px-2.5 py-0.5 text-xs font-bold tabular-nums">
                                {m.inscripciones_count ?? 0}
                            </span>
                        </td>
                        <td className="px-6 py-4 text-sm text-[var(--color-texto-suave)] whitespace-nowrap">
                            {m.origen_label}
                        </td>
                        <td className="px-6 py-4 text-sm text-[var(--color-texto-suave)] whitespace-nowrap tabular-nums">
                            {formatoFecha(m.fecha_matricula)}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                            <Insignia tono={tonoEstado(m.estado)}>{m.estado_label}</Insignia>
                        </td>
                        <td className="px-6 py-4 text-right whitespace-nowrap">
                            <BotonAccion to={`/matriculas/${m.id}`} icono="usuarios">
                                Ver
                            </BotonAccion>
                        </td>
                    </tr>
                ))}
            </TablaDatos>

            <Paginacion meta={meta} irAPagina={irAPagina} sustantivo="matrículas" />
        </section>
    );
}
