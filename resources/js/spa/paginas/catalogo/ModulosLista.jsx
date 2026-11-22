import { Link } from 'react-router-dom';
import useListado from '../../hooks/useListado';
import Paginacion from '../../componentes/Paginacion';
import { claseControl } from '../../componentes/Campo';
import { useAuth } from '../../auth/AuthContext';

export default function ModulosLista() {
    const { usuario } = useAuth();
    const esAdmin = usuario?.rol === 'administrador';
    const { filas, meta, cargando, aviso, valorDe, filtrar, irAPagina } =
        useListado('/catalogo/modulos');

    return (
        <section>
            <div className="mb-6 flex items-center justify-between">
                <h1 className="text-2xl font-semibold">Módulos</h1>
                {esAdmin && (
                    <Link
                        to="/app/modulos/nuevo"
                        className="rounded-lg bg-[var(--color-acento)] px-4 py-2 text-sm font-medium text-[var(--color-acento-texto)] no-underline"
                    >
                        Nuevo módulo
                    </Link>
                )}
            </div>

            <div className="mb-4 flex flex-wrap gap-3">
                <label className="flex-1">
                    <span className="sr-only">Buscar módulo</span>
                    <input
                        type="search"
                        placeholder="Buscar por nombre o código"
                        className={claseControl}
                        defaultValue={valorDe('q')}
                        onChange={(e) => filtrar('q', e.target.value)}
                    />
                </label>

                <label>
                    <span className="sr-only">Filtrar por estado comercial</span>
                    <select
                        className={claseControl}
                        value={valorDe('estado')}
                        onChange={(e) => filtrar('estado', e.target.value)}
                    >
                        <option value="">Todos los estados</option>
                        <option value="borrador">Borrador</option>
                        <option value="abierto">Abierto</option>
                        <option value="cerrado">Cerrado</option>
                    </select>
                </label>
            </div>

            {aviso && (
                <p role="alert" className="mb-4 text-sm text-[var(--color-error)]">
                    {aviso}
                </p>
            )}

            <div className="superficie overflow-hidden" aria-live="polite">
                <table className="w-full border-collapse text-sm">
                    <caption className="sr-only">Módulos del catálogo académico</caption>
                    <thead>
                        <tr className="border-b border-[var(--color-borde)] text-left">
                            <th scope="col" className="p-3">Código</th>
                            <th scope="col" className="p-3">Nombre</th>
                            <th scope="col" className="p-3">Horas</th>
                            <th scope="col" className="p-3">Programas</th>
                            <th scope="col" className="p-3">Estado</th>
                            {esAdmin && <th scope="col" className="p-3">Acciones</th>}
                        </tr>
                    </thead>
                    <tbody>
                        {cargando && (
                            <tr>
                                <td colSpan={6} className="p-6 text-center text-[var(--color-texto-suave)]">
                                    Cargando módulos…
                                </td>
                            </tr>
                        )}

                        {!cargando && filas.length === 0 && (
                            <tr>
                                <td colSpan={6} className="p-6 text-center text-[var(--color-texto-suave)]">
                                    No hay módulos que coincidan con la búsqueda.
                                </td>
                            </tr>
                        )}

                        {!cargando &&
                            filas.map((m) => (
                                <tr key={m.id} className="border-b border-[var(--color-borde)]">
                                    <td className="p-3 tabular-nums">{m.codigo}</td>
                                    <td className="p-3">{m.nombre}</td>
                                    <td className="p-3 tabular-nums">{m.duracion_horas ?? '—'}</td>
                                    <td className="p-3 tabular-nums">{m.programas_count ?? 0}</td>
                                    <td className="p-3">{m.estado_label}</td>
                                    {esAdmin && (
                                        <td className="p-3">
                                            <Link to={`/app/modulos/${m.id}`}>Editar</Link>
                                        </td>
                                    )}
                                </tr>
                            ))}
                    </tbody>
                </table>
            </div>

            <Paginacion meta={meta} irAPagina={irAPagina} sustantivo="módulos" />
        </section>
    );
}
