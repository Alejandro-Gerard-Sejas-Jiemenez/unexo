import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import cliente from '../api/cliente';

/** Detalle de un grupo del docente, con la lista de estudiantes inscritos. */
export default function MiGrupoDetalle() {
    const { id } = useParams();
    const navegar = useNavigate();
    const [datos, setDatos] = useState(null);
    const [aviso, setAviso] = useState(null);
    const [cargando, setCargando] = useState(true);

    useEffect(() => {
        let vigente = true;

        cliente
            .get(`/mis-grupos/${id}`)
            .then(({ data }) => vigente && setDatos(data))
            .catch((e) => vigente && setAviso(e.message))
            .finally(() => vigente && setCargando(false));

        return () => {
            vigente = false;
        };
    }, [id]);

    if (cargando) return <p role="status" className="text-[var(--color-texto-suave)]">Cargando…</p>;
    if (aviso) return <p role="alert" className="text-sm text-[var(--color-error)]">{aviso}</p>;

    const g = datos.grupo;

    return (
        <section className="max-w-3xl">
            <div className="mb-4 flex items-start justify-between">
                <div>
                    <h1 className="text-2xl font-semibold">{g.modulo?.nombre}</h1>
                    <p className="text-sm text-[var(--color-texto-suave)]">
                        {g.nombre} · {g.modalidad_label} · {g.estado_label}
                        {g.cohorte?.nombre ? ` · ${g.cohorte.nombre}` : ''}
                    </p>
                </div>
                <button type="button" onClick={() => navegar('/app/mis-grupos')}
                    className="superficie px-4 py-2 text-sm">
                    Volver
                </button>
            </div>

            <div className="superficie mb-6 p-4 text-sm">
                <p><b>Periodo:</b> {g.fecha_inicio ?? '—'} → {g.fecha_fin ?? '—'}</p>
                {(g.horarios ?? []).length > 0 && (
                    <p className="mt-1">
                        <b>Horarios:</b>{' '}
                        {g.horarios.map((h) => `${h.dia_label} ${h.hora_inicio}–${h.hora_fin}`).join(' · ')}
                    </p>
                )}
                {g.aula && <p className="mt-1"><b>Aula:</b> {g.aula}</p>}
                {g.enlace_videoconferencia && (
                    <p className="mt-1">
                        <b>Videoconferencia:</b>{' '}
                        <a href={g.enlace_videoconferencia} target="_blank" rel="noreferrer">
                            {g.enlace_videoconferencia}
                        </a>
                    </p>
                )}
            </div>

            <h2 className="mb-2 text-lg font-semibold">
                Estudiantes inscritos ({datos.estudiantes.length})
            </h2>
            <div className="superficie overflow-hidden">
                <table className="w-full border-collapse text-sm">
                    <caption className="sr-only">Estudiantes inscritos en el grupo</caption>
                    <thead>
                        <tr className="border-b border-[var(--color-borde)] text-left">
                            <th scope="col" className="p-3">Estudiante</th>
                            <th scope="col" className="p-3">Correo</th>
                            <th scope="col" className="p-3">Matrícula</th>
                        </tr>
                    </thead>
                    <tbody>
                        {datos.estudiantes.length === 0 && (
                            <tr>
                                <td colSpan={3} className="p-6 text-center text-[var(--color-texto-suave)]">
                                    Todavía no hay estudiantes inscritos.
                                </td>
                            </tr>
                        )}
                        {datos.estudiantes.map((e) => (
                            <tr key={e.id} className="border-b border-[var(--color-borde)]">
                                <td className="p-3">{e.nombre ?? '—'}</td>
                                <td className="p-3">{e.email ?? '—'}</td>
                                <td className="p-3">{e.estado_matricula ?? '—'}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </section>
    );
}
