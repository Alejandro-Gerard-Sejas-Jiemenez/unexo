import { Link } from 'react-router-dom';
import useListado from '../../hooks/useListado';
import Paginacion from '../../componentes/Paginacion';
import { claseControl } from '../../componentes/Campo';
import { useAuth } from '../../auth/AuthContext';

export default function ProgramasLista() {
    const { usuario } = useAuth();
    const esAdmin = usuario?.rol === 'administrador';
    const { filas, meta, cargando, aviso, valorDe, filtrar, irAPagina } =
        useListado('/catalogo/programas');

    return (
        <section>
            <div className="mb-6 flex items-center justify-between">
                <h1 className="text-2xl font-semibold">Programas</h1>
                {esAdmin && (
                    <Link
                        to="/app/programas/nuevo"
                        className="rounded-lg bg-[var(--color-acento)] px-4 py-2 text-sm font-medium text-[var(--color-acento-texto)] no-underline"
                    >
                        Nuevo programa
                    </Link>
                )}
            </div>

            <div className="mb-4 flex flex-wrap gap-3">
                <label className="flex-1">
                    <span className="sr-only">Buscar programa</span>
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

                <label className="flex items-center gap-2 text-sm">
                    <input
                        type="checkbox"
                        checked={valorDe('atencion') === 'sin-modulos-abiertos'}
                        onChange={(e) =>
                            filtrar('atencion', e.target.checked ? 'sin-modulos-abiertos' : '')
                        }
                    />
                    Abiertos sin módulos abiertos
                </label>
            </div>

            {aviso && (
                <p role="alert" className="mb-4 text-sm text-[var(--color-error)]">
                    {aviso}
                </p>
            )}

            <div className="superficie overflow-hidden" aria-live="polite">
                <table className="w-full border-collapse text-sm">
                    <caption className="sr-only">Programas del catálogo académico</caption>
                    <thead>
                        <tr className="border-b border-[var(--color-borde)] text-left">
                            <th scope="col" className="p-3">Código</th>
                            <th scope="col" className="p-3">Nombre</th>
                            <th scope="col" className="p-3">Versión</th>
                            <th scope="col" className="p-3">Módulos</th>
                            <th scope="col" className="p-3">Precio</th>
                            <th scope="col" className="p-3">Estado</th>
                            {esAdmin && <th scope="col" className="p-3">Acciones</th>}
                        </tr>
                    </thead>
                    <tbody>
                        {cargando && (
                            <tr>
                                <td colSpan={7} className="p-6 text-center text-[var(--color-texto-suave)]">
                                    Cargando programas…
                                </td>
                            </tr>
                        )}

                        {!cargando && filas.length === 0 && (
                            <tr>
                                <td colSpan={7} className="p-6 text-center text-[var(--color-texto-suave)]">
                                    No hay programas que coincidan con la búsqueda.
                                </td>
                            </tr>
                        )}

                        {!cargando &&
                            filas.map((p) => (
                                <tr key={p.id} className="border-b border-[var(--color-borde)]">
                                    <td className="p-3 tabular-nums">{p.codigo}</td>
                                    <td className="p-3">{p.nombre}</td>
                                    <td className="p-3 tabular-nums">{p.version}</td>
                                    <td className="p-3 tabular-nums">{p.modulos_count ?? 0}</td>
                                    <td className="p-3 tabular-nums">
                                        {p.precio != null ? `Bs ${p.precio.toFixed(2)}` : '—'}
                                    </td>
                                    <td className="p-3">{p.estado_label}</td>
                                    {esAdmin && (
                                        <td className="p-3">
                                            <Link to={`/app/programas/${p.id}`}>Editar y plan</Link>
                                        </td>
                                    )}
                                </tr>
                            ))}
                    </tbody>
                </table>
            </div>

            <Paginacion meta={meta} irAPagina={irAPagina} sustantivo="programas" />
        </section>
    );
}
