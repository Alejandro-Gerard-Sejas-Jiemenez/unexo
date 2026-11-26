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

export default function ProgramasLista() {
    const { usuario } = useAuth();
    const esAdmin = usuario?.rol === 'administrador';
    const { filas, meta, cargando, aviso, valorDe, filtrarVarios, filtrarConRetraso, limpiarFiltros, irAPagina, cambiarTamano } =
        useListado('/catalogo/programas');

    const [busqueda, setBusqueda] = useState(valorDe('q'));

    useEffect(() => {
        setBusqueda(valorDe('q'));
    }, [valorDe]);

    const estadoActual = valorDe('estado');
    const atencionActual = valorDe('atencion');

    const pildoras = [
        {
            etiqueta: 'Todos',
            activo: !estadoActual && !atencionActual,
            onClick: () => filtrarVarios({ atencion: '', estado: '' }),
        },
        {
            etiqueta: 'Borrador',
            activo: estadoActual === 'borrador',
            onClick: () => filtrarVarios({ atencion: '', estado: 'borrador' }),
        },
        {
            etiqueta: 'Abierto',
            activo: estadoActual === 'abierto' && !atencionActual,
            onClick: () => filtrarVarios({ atencion: '', estado: 'abierto' }),
        },
        {
            etiqueta: 'Cerrado',
            activo: estadoActual === 'cerrado',
            onClick: () => filtrarVarios({ atencion: '', estado: 'cerrado' }),
        },
        {
            etiqueta: 'Abiertos sin módulos abiertos',
            activo: atencionActual === 'sin-modulos-abiertos',
            onClick: () =>
                filtrarVarios({
                    estado: '',
                    atencion: atencionActual === 'sin-modulos-abiertos' ? '' : 'sin-modulos-abiertos',
                }),
        },
    ];

    const columnas = [
        { etiqueta: 'Código' },
        { etiqueta: 'Nombre' },
        { etiqueta: 'Versión' },
        { etiqueta: 'Módulos' },
        { etiqueta: 'Estado comercial' },
        ...(esAdmin ? [{ etiqueta: 'Acciones', srOnly: true, alinear: 'derecha' }] : []),
    ];

    return (
        <section>
            <EncabezadoPagina
                titulo="Programas"
                acciones={
                    esAdmin && (
                        <Link to="/programas/nuevo" className="btn btn-primario">
                            <Icono nombre="mas" className="h-5 w-5" />
                            Nuevo programa
                        </Link>
                    )
                }
            />

            <BarraHerramientas
                pildoras={pildoras}
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
                <p role="alert" className="superficie mb-4 border-(--color-error) p-3 text-sm text-(--color-error)">
                    {aviso}
                </p>
            )}

            {cargando && <Cargador texto="Cargando" />}

            <TablaDatos
                caption="Programas del catálogo académico"
                columnas={columnas}
                vacio={!cargando && filas.length === 0}
                vacioIcono="programa"
                vacioTitulo="No se encontraron programas"
                vacioDescripcion="Intenta cambiar los filtros o la búsqueda."
            >
                {filas.map((p) => (
                    <tr key={p.id} className="transition-colors hover:bg-(--color-superficie-2)/60">
                        <td className="px-6 py-4 text-sm font-medium text-(--color-texto-suave) whitespace-nowrap">
                            {p.codigo}
                        </td>
                        <td className="px-6 py-4 text-sm font-semibold">
                            {esAdmin ? (
                                <Link to={`/programas/${p.id}/ver`} className="no-underline hover:text-(--color-acento) hover:underline">
                                    {p.nombre}
                                </Link>
                            ) : (
                                p.nombre
                            )}
                        </td>
                        <td className="px-6 py-4 text-sm text-(--color-texto-suave) whitespace-nowrap">
                            v{p.version}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                            <span className="inline-flex items-center justify-center rounded-full bg-(--color-superficie-2) px-2.5 py-1 text-xs font-bold">
                                {p.modulos_count ?? 0}
                            </span>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                            <span
                                className={
                                    'inline-flex rounded-md px-2.5 py-1 text-xs font-medium ring-1 ring-inset ' +
                                    (p.estado_comercial === 'abierto'
                                        ? 'bg-(--color-exito-fondo) text-(--color-exito) ring-[color-mix(in_srgb,var(--color-exito)_30%,transparent)]'
                                        : 'bg-(--color-superficie-2) text-(--color-texto-suave) ring-(--color-borde)')
                                }
                            >
                                {p.estado_label}
                            </span>
                        </td>
                        {esAdmin && (
                            <td className="px-6 py-4 text-right text-sm font-medium whitespace-nowrap">
                                <div className="inline-flex items-center gap-2">
                                    <BotonAccion to={`/programas/${p.id}/ver`} icono="modulos">
                                        Módulos
                                    </BotonAccion>
                                    <BotonAccion to={`/programas/${p.id}`} icono="editar">
                                        Editar
                                    </BotonAccion>
                                </div>
                            </td>
                        )}
                    </tr>
                ))}
            </TablaDatos>

            <Paginacion meta={meta} irAPagina={irAPagina} cambiarTamano={cambiarTamano} sustantivo="programas" />
        </section>
    );
}
