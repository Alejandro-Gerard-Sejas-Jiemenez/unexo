import { Link } from 'react-router-dom';
import useListado from '../../hooks/useListado';
import Paginacion from '../../componentes/Paginacion';
import { claseControl } from '../../componentes/Campo';

export default function MatriculasLista() {
    const { filas, meta, cargando, aviso, valorDe, filtrar, irAPagina } = useListado('/matriculas');

    return (
        <section>
            <div className="mb-6 flex items-center justify-between">
                <h1 className="text-2xl font-semibold">Matrículas</h1>
                <Link
                    to="/app/matriculas/nueva"
                    className="rounded-lg bg-[var(--color-acento)] px-4 py-2 text-sm font-medium text-[var(--color-acento-texto)] no-underline"
                >
                    Nueva matrícula
                </Link>
            </div>

            <div className="mb-4 flex flex-wrap gap-3">
                <label className="flex-1">
                    <span className="sr-only">Buscar estudiante</span>
                    <input
                        type="search"
                        placeholder="Buscar por estudiante o correo"
                        className={claseControl}
                        defaultValue={valorDe('q')}
                        onChange={(e) => filtrar('q', e.target.value)}
                    />
                </label>
                <label>
                    <span className="sr-only">Filtrar por estado</span>
                    <select className={claseControl} value={valorDe('estado')}
                        onChange={(e) => filtrar('estado', e.target.value)}>
                        <option value="">Todos los estados</option>
                        <option value="pendiente">Pendiente de pago</option>
                        <option value="activo">Activa</option>
                        <option value="retirado">Retirada</option>
                    </select>
                </label>
                <label>
                    <span className="sr-only">Filtrar por origen</span>
                    <select className={claseControl} value={valorDe('origen')}
                        onChange={(e) => filtrar('origen', e.target.value)}>
                        <option value="">Todos los orígenes</option>
                        <option value="manual">Carga manual</option>
                        <option value="crm">Desde el CRM</option>
                    </select>
                </label>
            </div>

            {aviso && <p role="alert" className="mb-4 text-sm text-[var(--color-error)]">{aviso}</p>}

            <div className="superficie overflow-hidden" aria-live="polite">
                <table className="w-full border-collapse text-sm">
                    <caption className="sr-only">Matrículas registradas</caption>
                    <thead>
                        <tr className="border-b border-[var(--color-borde)] text-left">
                            <th scope="col" className="p-3">Estudiante</th>
                            <th scope="col" className="p-3">Edición</th>
                            <th scope="col" className="p-3">Grupos</th>
                            <th scope="col" className="p-3">Fecha</th>
                            <th scope="col" className="p-3">Origen</th>
                            <th scope="col" className="p-3">Estado</th>
                            <th scope="col" className="p-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        {cargando && (
                            <tr>
                                <td colSpan={7} className="p-6 text-center text-[var(--color-texto-suave)]">
                                    Cargando matrículas…
                                </td>
                            </tr>
                        )}
                        {!cargando && filas.length === 0 && (
                            <tr>
                                <td colSpan={7} className="p-6 text-center text-[var(--color-texto-suave)]">
                                    No hay matrículas que coincidan con la búsqueda.
                                </td>
                            </tr>
                        )}
                        {!cargando &&
                            filas.map((m) => (
                                <tr key={m.id} className="border-b border-[var(--color-borde)]">
                                    <td className="p-3">{m.estudiante?.nombre ?? '—'}</td>
                                    <td className="p-3">{m.cohorte?.nombre ?? 'Módulo suelto'}</td>
                                    <td className="p-3 tabular-nums">{m.inscripciones_count ?? 0}</td>
                                    <td className="p-3 whitespace-nowrap tabular-nums">{m.fecha_matricula}</td>
                                    <td className="p-3">{m.origen_label}</td>
                                    <td className="p-3">{m.estado_label}</td>
                                    <td className="p-3">
                                        <Link to={`/app/matriculas/${m.id}`}>Abrir</Link>
                                    </td>
                                </tr>
                            ))}
                    </tbody>
                </table>
            </div>

            <Paginacion meta={meta} irAPagina={irAPagina} sustantivo="matrículas" />
        </section>
    );
}
