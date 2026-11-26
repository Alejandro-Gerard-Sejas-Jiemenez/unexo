import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import cliente from '../../api/cliente';
import Cargador from '../../componentes/Cargador';
import Icono from '../../componentes/Icono';
import Insignia, { tonoEstado } from '../../componentes/Insignia';
import TablaDatos from '../../componentes/TablaDatos';

export default function MiGrupoDetalle() {
    const { id } = useParams();
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

    if (cargando) return <Cargador texto="Cargando" />;
    if (aviso) {
        return (
            <p role="alert" className="superficie border-(--color-error) p-4 text-sm text-(--color-error)">
                {aviso}
            </p>
        );
    }

    const g = datos.grupo;
    const horarios = g.horarios ?? [];
    const subtitulo = [g.cohorte?.programa, g.cohorte?.nombre ?? 'Módulo suelto', g.nombre]
        .filter(Boolean)
        .join(' · ');

    return (
        <section>
            <div className="mb-4">
                <Link
                    to="/mis-grupos"
                    className="inline-flex items-center gap-1.5 text-sm font-medium text-(--color-texto-suave) no-underline transition-colors hover:text-(--color-acento)"
                >
                    <Icono nombre="volver" className="h-4 w-4" />
                    Volver a mis grupos
                </Link>
            </div>

            <div className="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                <div>
                    <h1 className="text-3xl font-extrabold tracking-tight">{g.modulo?.nombre}</h1>
                    <p className="mt-2 text-lg text-(--color-texto-suave)">{subtitulo}</p>
                </div>
                <div>
                    <Insignia tono={tonoEstado(g.fase)} className="px-3! py-1! text-sm! shadow-sm">
                        {g.fase_etiqueta ?? g.estado_label}
                    </Insignia>
                </div>
            </div>

            <div className="superficie mb-10 max-w-3xl overflow-hidden rounded-xl shadow-sm">
                <div className="border-b border-(--color-borde) bg-(--color-superficie-cabecera) px-6 py-5">
                    <h2 className="text-lg font-semibold">Detalles del grupo</h2>
                </div>
                <div className="p-6">
                    <dl className="grid grid-cols-1 gap-x-6 gap-y-6 sm:grid-cols-2">
                        <div>
                            <dt className="mb-1 text-sm font-medium text-(--color-texto-suave)">Período</dt>
                            <dd className="text-base tabular-nums">{g.periodo_texto || '—'}</dd>
                        </div>
                        <div>
                            <dt className="mb-1 text-sm font-medium text-(--color-texto-suave)">Modalidad</dt>
                            <dd className="text-base">
                                {g.modalidad_label}
                                {g.aula ? ` · ${g.aula}` : ''}
                            </dd>
                        </div>
                        <div className="sm:col-span-2">
                            <dt className="mb-2 text-sm font-medium text-(--color-texto-suave)">Horarios</dt>
                            <dd>
                                {horarios.length === 0 ? (
                                    <p className="text-sm text-(--color-texto-suave)">Sin horarios registrados.</p>
                                ) : (
                                    <TablaDatos
                                        caption="Horarios semanales del grupo"
                                        columnas={[{ etiqueta: 'Día' }, { etiqueta: 'Horario' }]}
                                    >
                                        {horarios.map((h, i) => (
                                            <tr key={i}>
                                                <td className="px-6 py-3 text-sm font-medium">{h.dia_label}</td>
                                                <td className="px-6 py-3 text-sm text-(--color-texto-suave) tabular-nums">
                                                    {h.hora_inicio} – {h.hora_fin}
                                                </td>
                                            </tr>
                                        ))}
                                    </TablaDatos>
                                )}
                            </dd>
                        </div>
                        {g.enlace_videoconferencia && (
                            <div className="sm:col-span-2">
                                <dt className="mb-1 text-sm font-medium text-(--color-texto-suave)">
                                    Enlace de la clase
                                </dt>
                                <dd className="text-base break-all">
                                    <a
                                        href={g.enlace_videoconferencia}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="inline-flex items-center gap-1.5 text-(--color-acento) hover:underline"
                                    >
                                        <Icono nombre="video" className="h-4 w-4" />
                                        {g.enlace_videoconferencia}
                                    </a>
                                </dd>
                            </div>
                        )}
                    </dl>
                </div>
            </div>

            <div className="mb-6 flex items-center gap-3">
                <h2 className="text-2xl font-bold">Estudiantes inscritos</h2>
                <span className="inline-flex items-center justify-center rounded-full bg-(--color-superficie-2) px-2.5 py-0.5 text-sm font-medium text-(--color-texto-suave)">
                    {datos.estudiantes.length}
                </span>
            </div>

            <TablaDatos
                caption="Estudiantes inscritos en el grupo"
                columnas={[
                    { etiqueta: 'Estudiante' },
                    { etiqueta: 'Correo institucional' },
                    { etiqueta: 'Estado de matrícula', alinear: 'derecha' },
                ]}
                vacio={datos.estudiantes.length === 0}
                vacioIcono="usuarios"
                vacioTitulo="No hay estudiantes inscritos"
                vacioDescripcion="Aparecerán aquí cuando la administración los inscriba."
            >
                {datos.estudiantes.map((e) => (
                    <tr key={e.id} className="transition-colors hover:bg-(--color-superficie-2)/60">
                        <td className="px-6 py-4 text-sm font-medium">{e.nombre ?? '—'}</td>
                        <td className="px-6 py-4 text-sm text-(--color-texto-suave)">
                            {e.correo_institucional || e.email || '—'}
                        </td>
                        <td className="px-6 py-4 text-right whitespace-nowrap">
                            <Insignia tono={tonoEstado(e.estado_matricula)}>
                                {e.estado_matricula_label ?? e.estado_matricula ?? '—'}
                            </Insignia>
                        </td>
                    </tr>
                ))}
            </TablaDatos>
        </section>
    );
}
