import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../../auth/AuthContext';
import BarraHerramientas from '../../componentes/BarraHerramientas';
import BotonAccion from '../../componentes/BotonAccion';
import Cargador from '../../componentes/Cargador';
import EncabezadoPagina from '../../componentes/EncabezadoPagina';
import Icono from '../../componentes/Icono';
import Paginacion from '../../componentes/Paginacion';
import TablaDatos from '../../componentes/TablaDatos';
import useListado from '../../hooks/useListado';

export default function ModulosLista() {
    const { usuario } = useAuth();
    const esAdmin = usuario?.rol === 'administrador';
    const { filas, meta, cargando, aviso, valorDe, filtrar, filtrarConRetraso, limpiarFiltros, irAPagina } =
        useListado('/catalogo/modulos');

    const [busqueda, setBusqueda] = useState(valorDe('q'));

    useEffect(() => {
        setBusqueda(valorDe('q'));
    }, [valorDe]);

    const pildoras = [
        { valor: '', etiqueta: 'Todos' },
        { valor: 'borrador', etiqueta: 'Borrador' },
        { valor: 'abierto', etiqueta: 'Abierto' },
        { valor: 'cerrado', etiqueta: 'Cerrado' },
    ];

    const columnas = [
        { etiqueta: 'Código' },
        { etiqueta: 'Nombre' },
        { etiqueta: 'Horas' },
        { etiqueta: 'En programas' },
        { etiqueta: 'Estado comercial' },
        ...(esAdmin ? [{ etiqueta: 'Acciones', srOnly: true, alinear: 'derecha' }] : []),
    ];

    return (
        <section>
            <EncabezadoPagina
                titulo="Módulos"
                acciones={
                    esAdmin && (
                        <Link to="/modulos/nuevo" className="btn btn-primario">
                            <Icono nombre="mas" className="h-5 w-5" />
                            Nuevo módulo
                        </Link>
                    )
                }
            />

            <BarraHerramientas
                pildoras={pildoras}
                pildoraActiva={valorDe('estado')}
                alSeleccionarPildora={(val) => filtrar('estado', val)}
                busqueda={busqueda}
                placeholder="Buscar por código o nombre…"
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
                caption="Módulos del catálogo académico"
                columnas={columnas}
                vacio={!cargando && filas.length === 0}
                vacioIcono="modulos"
                vacioTitulo="No se encontraron módulos"
                vacioDescripcion="Intenta cambiar los filtros de búsqueda."
            >
                {filas.map((m) => (
                    <tr key={m.id} className="transition-colors hover:bg-[var(--color-superficie-2)]/60">
                        <td className="px-6 py-4 text-sm font-medium text-[var(--color-texto-suave)] whitespace-nowrap">
                            {m.codigo}
                        </td>
                        <td className="px-6 py-4 text-sm font-medium">{m.nombre}</td>
                        <td className="px-6 py-4 text-sm text-[var(--color-texto-suave)] whitespace-nowrap tabular-nums">
                            {m.duracion_horas ?? '—'}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                            <span className="inline-flex items-center justify-center rounded-full bg-[var(--color-superficie-2)] px-2.5 py-1 text-xs font-bold">
                                {m.programas_count ?? 0}
                            </span>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                            <span
                                className={
                                    'inline-flex rounded-md px-2.5 py-1 text-xs font-medium ring-1 ring-inset ' +
                                    (m.estado_comercial === 'abierto'
                                        ? 'bg-[var(--color-exito-fondo)] text-[var(--color-exito)] ring-[color-mix(in_srgb,var(--color-exito)_30%,transparent)]'
                                        : 'bg-[var(--color-superficie-2)] text-[var(--color-texto-suave)] ring-[var(--color-borde)]')
                                }
                            >
                                {m.estado_label}
                            </span>
                        </td>
                        {esAdmin && (
                            <td className="px-6 py-4 text-right text-sm font-medium whitespace-nowrap">
                                <BotonAccion to={`/modulos/${m.id}`} icono="editar">
                                    Editar
                                </BotonAccion>
                            </td>
                        )}
                    </tr>
                ))}
            </TablaDatos>

            <Paginacion meta={meta} irAPagina={irAPagina} sustantivo="módulos" />
        </section>
    );
}
