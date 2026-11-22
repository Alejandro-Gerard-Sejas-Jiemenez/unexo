import { Link } from 'react-router-dom';
import useListado from '../../hooks/useListado';
import Paginacion from '../../componentes/Paginacion';
import { claseControl } from '../../componentes/Campo';

export default function EdicionesLista() {
    const { filas, meta, cargando, aviso, valorDe, filtrar, irAPagina } = useListado('/cohortes');

    return (
        <section>
            <div className="mb-6 flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-semibold">Ediciones</h1>
                    <p className="text-sm text-[var(--color-texto-suave)]">
                        Cada edición es una ejecución del programa en el tiempo.
                    </p>
                </div>
                <Link
                    to="/app/cohortes/nueva"
                    className="rounded-lg bg-[var(--color-acento)] px-4 py-2 text-sm font-medium text-[var(--color-acento-texto)] no-underline"
                >
                    Nueva edición
                </Link>
            </div>

            <div className="mb-4 flex flex-wrap gap-3">
                <label className="flex-1">
                    <span className="sr-only">Buscar edición</span>
                    <input
                        type="search"
                        placeholder="Buscar por edición o programa"
                        className={claseControl}
                        defaultValue={valorDe('q')}
                        onChange={(e) => filtrar('q', e.target.value)}
                    />
                </label>
                <label>
                    <span className="sr-only">Filtrar por estado</span>
                    <select
                        className={claseControl}
                        value={valorDe('estado')}
                        onChange={(e) => filtrar('estado', e.target.value)}
                    >
                        <option value="">Todos los estados</option>
                        <option value="programado">Programado</option>
                        <option value="en_curso">En curso</option>
                        <option value="finalizado">Finalizado</option>
                        <option value="cancelado">Cancelado</option>
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
                    <caption className="sr-only">Ediciones programadas</caption>
                    <thead>
                        <tr className="border-b border-[var(--color-borde)] text-left">
                            <th scope="col" className="p-3">Edición</th>
                            <th scope="col" className="p-3">Programa</th>
                            <th scope="col" className="p-3">Periodo</th>
                            <th scope="col" className="p-3">Grupos</th>
                            <th scope="col" className="p-3">Matrículas</th>
                            <th scope="col" className="p-3">Estado</th>
                            <th scope="col" className="p-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        {cargando && (
                            <tr>
                                <td colSpan={7} className="p-6 text-center text-[var(--color-texto-suave)]">
                                    Cargando ediciones…
                                </td>
                            </tr>
                        )}
                        {!cargando && filas.length === 0 && (
                            <tr>
                                <td colSpan={7} className="p-6 text-center text-[var(--color-texto-suave)]">
                                    No hay ediciones que coincidan con la búsqueda.
                                </td>
                            </tr>
                        )}
                        {!cargando &&
                            filas.map((c) => (
                                <tr key={c.id} className="border-b border-[var(--color-borde)]">
                                    <td className="p-3">{c.nombre}</td>
                                    <td className="p-3">{c.programa?.nombre ?? '—'}</td>
                                    <td className="p-3 whitespace-nowrap tabular-nums">
                                        {c.fecha_inicio} → {c.fecha_fin}
                                    </td>
                                    <td className="p-3 tabular-nums">{c.grupos_count ?? 0}</td>
                                    <td className="p-3 tabular-nums">{c.matriculas_count ?? 0}</td>
                                    <td className="p-3">{c.estado_label}</td>
                                    <td className="p-3">
                                        <Link to={`/app/cohortes/${c.id}`}>Abrir</Link>
                                    </td>
                                </tr>
                            ))}
                    </tbody>
                </table>
            </div>

            <Paginacion meta={meta} irAPagina={irAPagina} sustantivo="ediciones" />
        </section>
    );
}
