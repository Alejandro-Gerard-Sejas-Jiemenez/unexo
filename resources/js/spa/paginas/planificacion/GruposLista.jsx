import { Link } from 'react-router-dom';
import useListado from '../../hooks/useListado';
import Paginacion from '../../componentes/Paginacion';
import { claseControl } from '../../componentes/Campo';

export default function GruposLista() {
    const { filas, meta, cargando, aviso, valorDe, filtrar, irAPagina } = useListado('/grupos');

    return (
        <section>
            <div className="mb-6 flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-semibold">Grupos</h1>
                    <p className="text-sm text-[var(--color-texto-suave)]">
                        Un grupo es la ejecución de un módulo: docente, horario y aula o enlace.
                    </p>
                </div>
                <Link
                    to="/app/grupos/nuevo"
                    className="rounded-lg bg-[var(--color-acento)] px-4 py-2 text-sm font-medium text-[var(--color-acento-texto)] no-underline"
                >
                    Nuevo grupo
                </Link>
            </div>

            <div className="mb-4 flex flex-wrap gap-3">
                <label className="flex-1">
                    <span className="sr-only">Buscar grupo</span>
                    <input
                        type="search"
                        placeholder="Buscar por grupo o módulo"
                        className={claseControl}
                        defaultValue={valorDe('q')}
                        onChange={(e) => filtrar('q', e.target.value)}
                    />
                </label>
                <label>
                    <span className="sr-only">Filtrar por tipo</span>
                    <select className={claseControl} value={valorDe('tipo')}
                        onChange={(e) => filtrar('tipo', e.target.value)}>
                        <option value="">Todos los grupos</option>
                        <option value="cohorte">De una edición</option>
                        <option value="suelto">Módulo suelto</option>
                    </select>
                </label>
                <label>
                    <span className="sr-only">Filtrar por estado</span>
                    <select className={claseControl} value={valorDe('estado')}
                        onChange={(e) => filtrar('estado', e.target.value)}>
                        <option value="">Todos los estados</option>
                        <option value="programado">Programado</option>
                        <option value="en_curso">En curso</option>
                        <option value="finalizado">Finalizado</option>
                        <option value="cancelado">Cancelado</option>
                    </select>
                </label>
            </div>

            {aviso && (
                <p role="alert" className="mb-4 text-sm text-[var(--color-error)]">{aviso}</p>
            )}

            <div className="superficie overflow-hidden" aria-live="polite">
                <table className="w-full border-collapse text-sm">
                    <caption className="sr-only">Grupos operativos</caption>
                    <thead>
                        <tr className="border-b border-[var(--color-borde)] text-left">
                            <th scope="col" className="p-3">Grupo</th>
                            <th scope="col" className="p-3">Módulo</th>
                            <th scope="col" className="p-3">Edición</th>
                            <th scope="col" className="p-3">Docente</th>
                            <th scope="col" className="p-3">Horarios</th>
                            <th scope="col" className="p-3">Inscritos</th>
                            <th scope="col" className="p-3">Estado</th>
                            <th scope="col" className="p-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        {cargando && (
                            <tr>
                                <td colSpan={8} className="p-6 text-center text-[var(--color-texto-suave)]">
                                    Cargando grupos…
                                </td>
                            </tr>
                        )}
                        {!cargando && filas.length === 0 && (
                            <tr>
                                <td colSpan={8} className="p-6 text-center text-[var(--color-texto-suave)]">
                                    No hay grupos que coincidan con la búsqueda.
                                </td>
                            </tr>
                        )}
                        {!cargando &&
                            filas.map((g) => (
                                <tr key={g.id} className="border-b border-[var(--color-borde)]">
                                    <td className="p-3">{g.nombre}</td>
                                    <td className="p-3">{g.modulo?.nombre ?? '—'}</td>
                                    <td className="p-3">{g.cohorte?.nombre ?? 'Suelto'}</td>
                                    <td className="p-3">{g.docente ?? '—'}</td>
                                    <td className="p-3 text-xs">
                                        {(g.horarios ?? []).length === 0
                                            ? '—'
                                            : g.horarios
                                                  .map((h) => `${h.dia_label} ${h.hora_inicio}`)
                                                  .join(' · ')}
                                    </td>
                                    <td className="p-3 tabular-nums">
                                        {g.inscripciones_count ?? 0}
                                        {g.cupo_minimo ? ` / ${g.cupo_minimo}` : ''}
                                    </td>
                                    <td className="p-3">{g.estado_label}</td>
                                    <td className="p-3">
                                        <Link to={`/app/grupos/${g.id}`}>Configurar</Link>
                                    </td>
                                </tr>
                            ))}
                    </tbody>
                </table>
            </div>

            <Paginacion meta={meta} irAPagina={irAPagina} sustantivo="grupos" />
        </section>
    );
}
