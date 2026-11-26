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
import { formatoFecha } from '../../lib/fechas';

const ESTADOS = [
    { valor: '', etiqueta: 'Todas' },
    { valor: 'programado', etiqueta: 'Programado' },
    { valor: 'en_curso', etiqueta: 'En curso' },
    { valor: 'finalizado', etiqueta: 'Finalizado' },
    { valor: 'cancelado', etiqueta: 'Cancelado' },
];

function claseEstado(estado) {
    switch (estado) {
        case 'en_curso':
            return 'bg-[var(--color-exito-fondo)] text-[var(--color-exito)] ring-[color-mix(in_srgb,var(--color-exito)_30%,transparent)]';
        case 'programado':
            return 'bg-[var(--color-aviso-fondo)] text-[var(--color-aviso)] ring-[color-mix(in_srgb,var(--color-aviso)_30%,transparent)]';
        case 'finalizado':
            return 'bg-[var(--color-acento-suave)] text-[var(--color-acento)] ring-[color-mix(in_srgb,var(--color-acento)_25%,transparent)]';
        default:
            return 'bg-[var(--color-superficie-2)] text-[var(--color-texto-suave)] ring-[var(--color-borde)]';
    }
}

export default function EdicionesLista() {
    const { filas, meta, cargando, aviso, valorDe, filtrar, filtrarConRetraso, limpiarFiltros, irAPagina } =
        useListado('/cohortes');

    const [busqueda, setBusqueda] = useState(valorDe('q'));

    useEffect(() => {
        setBusqueda(valorDe('q'));
    }, [valorDe]);

    const columnas = [
        { etiqueta: 'Programa' },
        { etiqueta: 'Edición' },
        { etiqueta: 'Inicio' },
        { etiqueta: 'Fin' },
        { etiqueta: 'Grupos' },
        { etiqueta: 'Alumnos' },
        { etiqueta: 'Estado' },
        { etiqueta: 'Acciones', srOnly: true, alinear: 'derecha' },
    ];

    return (
        <section>
            <EncabezadoPagina
                titulo="Ediciones (Cohortes)"
                subtitulo="Aperturas de un programa académico en una fecha concreta."
                acciones={
                    <Link to="/cohortes/nueva" className="btn btn-primario">
                        <Icono nombre="mas" className="h-5 w-5" />
                        Nueva edición
                    </Link>
                }
            />

            <BarraHerramientas
                pildoras={ESTADOS}
                pildoraActiva={valorDe('estado')}
                alSeleccionarPildora={(val) => filtrar('estado', val)}
                busqueda={busqueda}
                placeholder="Buscar edición o programa…"
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
                caption="Listado de ediciones"
                columnas={columnas}
                compacto
                vacio={!cargando && filas.length === 0}
                vacioIcono="calendario"
                vacioTitulo="No hay ediciones registradas"
                vacioDescripcion="Crea una nueva edición para aperturar módulos de un programa."
            >
                {filas.map((c) => (
                    <tr key={c.id} className="transition-colors hover:bg-[var(--color-superficie-2)]/60">
                        <td className="px-4 py-3.5">
                            <div className="text-sm font-semibold">{c.programa?.nombre ?? '—'}</div>
                            {(c.programa?.version || c.programa?.codigo) && (
                                <div className="font-mono text-xs text-[var(--color-texto-suave)]">
                                    v{c.programa?.version}
                                    {c.programa?.codigo ? ` · ${c.programa.codigo}` : ''}
                                </div>
                            )}
                        </td>
                        <td className="px-4 py-3.5 whitespace-nowrap">
                            <Link
                                to={`/cohortes/${c.id}`}
                                className="text-sm font-bold text-[var(--color-acento)] no-underline hover:underline"
                            >
                                {c.nombre}
                            </Link>
                        </td>
                        <td className="px-4 py-3.5 text-sm text-[var(--color-texto-suave)] whitespace-nowrap tabular-nums">
                            {formatoFecha(c.fecha_inicio)}
                        </td>
                        <td className="px-4 py-3.5 text-sm text-[var(--color-texto-suave)] whitespace-nowrap tabular-nums">
                            {formatoFecha(c.fecha_fin)}
                        </td>
                        <td className="px-4 py-3.5 whitespace-nowrap">
                            <span className="inline-flex items-center rounded-md border border-[color-mix(in_srgb,var(--color-acento)_25%,transparent)] bg-[var(--color-acento-suave)] px-2.5 py-0.5 text-xs font-semibold text-[var(--color-acento)] tabular-nums">
                                {c.grupos_count ?? 0}
                            </span>
                        </td>
                        <td className="px-4 py-3.5 whitespace-nowrap">
                            <span className="inline-flex items-center rounded-md border border-[color-mix(in_srgb,var(--color-exito)_25%,transparent)] bg-[var(--color-exito-fondo)] px-2.5 py-0.5 text-xs font-semibold text-[var(--color-exito)] tabular-nums">
                                {c.matriculas_count ?? 0}
                            </span>
                        </td>
                        <td className="px-4 py-3.5 whitespace-nowrap">
                            <span className={`inline-flex rounded-md px-2.5 py-1 text-xs font-medium ring-1 ring-inset ${claseEstado(c.estado)}`}>
                                {c.estado_label}
                            </span>
                        </td>
                        <td className="px-4 py-3.5 text-right text-sm font-medium whitespace-nowrap">
                            <div className="inline-flex items-center gap-1.5">
                                <BotonAccion to={`/cohortes/${c.id}`} icono="modulos">
                                    Planificar
                                </BotonAccion>
                                <BotonAccion to={`/cohortes/${c.id}?editar=1`} icono="editar">
                                    Editar
                                </BotonAccion>
                            </div>
                        </td>
                    </tr>
                ))}
            </TablaDatos>

            <Paginacion meta={meta} irAPagina={irAPagina} sustantivo="ediciones" />
        </section>
    );
}
