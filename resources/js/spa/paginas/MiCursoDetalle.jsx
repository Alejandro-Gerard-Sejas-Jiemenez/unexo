import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import cliente from '../api/cliente';
import Cargador from '../componentes/Cargador';
import Icono from '../componentes/Icono';
import Insignia, { tonoEstado } from '../componentes/Insignia';
import TablaDatos from '../componentes/TablaDatos';

export default function MiCursoDetalle() {
    const { id } = useParams();
    const [datos, setDatos] = useState(null);
    const [aviso, setAviso] = useState(null);
    const [cargando, setCargando] = useState(true);

    useEffect(() => {
        let vigente = true;

        cliente
            .get(`/mis-cursos/${id}`)
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
            <p role="alert" className="superficie border-[var(--color-error)] p-4 text-sm text-[var(--color-error)]">
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
                    to="/mis-cursos"
                    className="inline-flex items-center gap-1.5 text-sm font-medium text-[var(--color-texto-suave)] no-underline transition-colors hover:text-[var(--color-acento)]"
                >
                    <Icono nombre="volver" className="h-4 w-4" />
                    Volver a mis cursos
                </Link>
            </div>

            <div className="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                <div>
                    <h1 className="text-3xl font-extrabold tracking-tight">{g.modulo?.nombre ?? g.nombre}</h1>
                    <p className="mt-2 text-lg text-[var(--color-texto-suave)]">{subtitulo}</p>
                </div>
                <div className="flex flex-wrap items-center gap-2">
                    <Insignia tono={tonoEstado(datos.matricula_estado)} className="!px-3 !py-1 !text-sm shadow-sm">
                        Matrícula {datos.matricula_estado_label}
                    </Insignia>
                    {g.fase_etiqueta && (
                        <Insignia tono={tonoEstado(g.fase)} className="!px-3 !py-1 !text-sm shadow-sm">
                            {g.fase_etiqueta}
                        </Insignia>
                    )}
                </div>
            </div>

            <div className="superficie max-w-3xl overflow-hidden rounded-xl shadow-sm">
                <div className="border-b border-[var(--color-borde)] bg-[var(--color-superficie-cabecera)] px-6 py-5">
                    <h2 className="text-lg font-semibold">Detalles del grupo</h2>
                </div>
                <div className="p-6">
                    <dl className="grid grid-cols-1 gap-x-6 gap-y-6 sm:grid-cols-2">
                        <div>
                            <dt className="mb-1 text-sm font-medium text-[var(--color-texto-suave)]">Docente</dt>
                            <dd className="text-base">{g.docente ?? 'Por asignar'}</dd>
                        </div>
                        <div>
                            <dt className="mb-1 text-sm font-medium text-[var(--color-texto-suave)]">Período</dt>
                            <dd className="text-base tabular-nums">{g.periodo_texto || '—'}</dd>
                        </div>
                        <div>
                            <dt className="mb-1 text-sm font-medium text-[var(--color-texto-suave)]">Modalidad</dt>
                            <dd className="text-base">
                                {g.modalidad_label}
                                {g.aula ? ` · ${g.aula}` : ''}
                            </dd>
                        </div>

                        <div className="sm:col-span-2">
                            <dt className="mb-2 text-sm font-medium text-[var(--color-texto-suave)]">Horarios</dt>
                            <dd>
                                {horarios.length === 0 ? (
                                    <p className="text-sm text-[var(--color-texto-suave)]">Sin horarios registrados.</p>
                                ) : (
                                    <TablaDatos
                                        caption="Horarios de clase"
                                        columnas={[{ etiqueta: 'Día' }, { etiqueta: 'Horario' }]}
                                    >
                                        {horarios.map((h, i) => (
                                            <tr key={i}>
                                                <td className="px-6 py-3 text-sm font-medium">{h.dia_label}</td>
                                                <td className="px-6 py-3 text-sm text-[var(--color-texto-suave)] tabular-nums">
                                                    {h.hora_inicio} – {h.hora_fin}
                                                </td>
                                            </tr>
                                        ))}
                                    </TablaDatos>
                                )}
                            </dd>
                        </div>

                        {g.enlace_videoconferencia ? (
                            <div className="sm:col-span-2">
                                <dt className="mb-1 text-sm font-medium text-[var(--color-texto-suave)]">
                                    Enlace de la clase
                                </dt>
                                <dd>
                                    <a
                                        href={g.enlace_videoconferencia}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="btn btn-primario"
                                    >
                                        <Icono nombre="video" className="h-4 w-4" />
                                        Entrar a la clase
                                    </a>
                                </dd>
                            </div>
                        ) : (
                            (g.modalidad === 'virtual' || g.modalidad === 'mixta') && (
                                <div className="sm:col-span-2">
                                    <div className="flex gap-2 rounded-lg border border-[color-mix(in_srgb,var(--color-aviso)_30%,transparent)] bg-[var(--color-aviso-fondo)] p-3 text-sm text-[var(--color-aviso)]">
                                        <Icono nombre="alerta" className="h-5 w-5 shrink-0" />
                                        <p>
                                            El enlace de videoconferencia estará disponible cuando tu matrícula esté activa y
                                            el grupo se encuentre por iniciar o en curso.
                                        </p>
                                    </div>
                                </div>
                            )
                        )}
                    </dl>
                </div>
            </div>
        </section>
    );
}
