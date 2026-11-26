import { Link } from 'react-router-dom';
import BotonAccion from '../../componentes/BotonAccion';
import Cargador from '../../componentes/Cargador';
import EncabezadoPagina from '../../componentes/EncabezadoPagina';
import Icono from '../../componentes/Icono';
import Paginacion from '../../componentes/Paginacion';
import TablaDatos from '../../componentes/TablaDatos';
import useListado from '../../hooks/useListado';

export default function Aulas() {
    const { filas: aulas, meta, cargando, aviso, irAPagina, cambiarTamano } = useListado('/aulas');

    return (
        <section>
            <EncabezadoPagina
                grande
                titulo="Aulas"
                subtitulo="Espacios físicos que se asignan a los grupos presenciales."
                acciones={
                    <Link to="/aulas/nueva" className="btn btn-primario">
                        <Icono nombre="mas" className="h-5 w-5" />
                        Nueva aula
                    </Link>
                }
            />

            {aviso && (
                <p role="alert" className="superficie mb-6 border-(--color-error) p-3 text-sm text-(--color-error)">
                    {aviso}
                </p>
            )}

            {cargando && <Cargador texto="Cargando" />}

            <TablaDatos
                caption="Aulas registradas"
                columnas={[
                    { etiqueta: 'Nombre' },
                    { etiqueta: 'Capacidad' },
                    { etiqueta: 'Grupos' },
                    { etiqueta: 'Acciones', alinear: 'derecha' },
                ]}
                vacio={!cargando && aulas.length === 0}
                vacioIcono="catalogo"
                vacioTitulo="No hay aulas registradas"
                vacioDescripcion="Comienza registrando la primera con el botón «Nueva aula»."
            >
                {aulas.map((a) => (
                    <tr key={a.id} className="transition-colors hover:bg-(--color-superficie-2)/60">
                        <td className="px-6 py-4 text-sm font-medium">{a.nombre}</td>
                        <td className="px-6 py-4 text-sm text-(--color-texto-suave) tabular-nums">
                            {a.capacidad ?? '—'}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                            <span className="inline-flex items-center rounded-full bg-(--color-superficie-2) px-2.5 py-0.5 text-xs font-medium">
                                {a.grupos_count ?? 0} grupos
                            </span>
                        </td>
                        <td className="px-6 py-4 text-right whitespace-nowrap">
                            <BotonAccion to={`/aulas/${a.id}`} icono="editar">
                                Editar
                            </BotonAccion>
                        </td>
                    </tr>
                ))}
            </TablaDatos>

            <Paginacion meta={meta} irAPagina={irAPagina} cambiarTamano={cambiarTamano} sustantivo="aulas" />
        </section>
    );
}
