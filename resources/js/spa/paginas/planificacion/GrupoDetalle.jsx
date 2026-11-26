import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import cliente from '../../api/cliente';
import BotonAccion from '../../componentes/BotonAccion';
import Cargador from '../../componentes/Cargador';
import EncabezadoPagina from '../../componentes/EncabezadoPagina';
import Icono from '../../componentes/Icono';
import Insignia, { tonoEstado } from '../../componentes/Insignia';
import TablaDatos from '../../componentes/TablaDatos';

export default function GrupoDetalle() {
    const { id } = useParams();
    const [grupo, setGrupo] = useState(null);
    const [aviso, setAviso] = useState(null);
    const [cargando, setCargando] = useState(true);

    useEffect(() => {
        let vigente = true;

        cliente
            .get(`/grupos/${id}`)
            .then(({ data }) => vigente && setGrupo(data.data))
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

    const inscritos = grupo.inscritos ?? [];
    const horarios = grupo.horarios ?? [];
    const subtitulo = [
        grupo.cohorte?.programa,
        grupo.cohorte?.nombre ?? 'Módulo suelto',
        grupo.nombre,
    ]
        .filter(Boolean)
        .join(' · ');

    return (
        <section className="space-y-8">
            <EncabezadoPagina
                volverA="/grupos"
                volverTexto="Volver a grupos"
                titulo={grupo.modulo?.nombre ?? grupo.nombre}
                subtitulo={subtitulo}
                acciones={
                    <>
                        <Insignia tono={tonoEstado(grupo.estado)}>{grupo.estado_label}</Insignia>
                        <BotonAccion to={`/grupos/${grupo.id}`} icono="editar" className="rounded-lg! px-4! py-2!">
                            Editar
                        </BotonAccion>
                    </>
                }
            />

            <div className="superficie overflow-hidden rounded-xl shadow-sm">
                <div className="border-b border-(--color-borde) bg-(--color-superficie-cabecera) px-6 py-4">
                    <h2 className="text-base font-bold">Detalles operativos</h2>
                </div>
                <dl className="grid grid-cols-1 gap-6 p-6 sm:grid-cols-3">
                    <div>
                        <dt className="text-xs font-semibold tracking-wider text-(--color-texto-suave) uppercase">
                            Docente
                        </dt>
                        <dd className="mt-1 text-sm font-medium">{grupo.docente ?? 'Sin asignar'}</dd>
                    </div>
                    <div>
                        <dt className="text-xs font-semibold tracking-wider text-(--color-texto-suave) uppercase">
                            Período
                        </dt>
                        <dd className="mt-1 text-sm font-medium tabular-nums">{grupo.periodo_texto || '—'}</dd>
                    </div>
                    <div>
                        <dt className="text-xs font-semibold tracking-wider text-(--color-texto-suave) uppercase">
                            Modalidad / Aula
                        </dt>
                        <dd className="mt-1 text-sm font-medium">
                            {grupo.modalidad_label}
                            {grupo.aula ? ` · ${grupo.aula}` : ''}
                        </dd>
                    </div>
                    <div>
                        <dt className="text-xs font-semibold tracking-wider text-(--color-texto-suave) uppercase">
                            Cupo mínimo
                        </dt>
                        <dd className="mt-1 text-sm font-medium tabular-nums">
                            {grupo.cupo_minimo ? `${grupo.cupo_minimo} estudiantes` : 'Sin cupo mínimo'}
                        </dd>
                    </div>
                    {grupo.enlace_videoconferencia && (
                        <div className="sm:col-span-2">
                            <dt className="text-xs font-semibold tracking-wider text-(--color-texto-suave) uppercase">
                                Enlace de videoconferencia
                            </dt>
                            <dd className="mt-1 text-sm break-all">
                                <a
                                    href={grupo.enlace_videoconferencia}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="inline-flex items-center gap-1.5 font-medium text-(--color-acento)"
                                >
                                    <Icono nombre="video" className="h-4 w-4 shrink-0" />
                                    {grupo.enlace_videoconferencia}
                                </a>
                            </dd>
                        </div>
                    )}
                </dl>
            </div>

            <div>
                <h2 className="mb-3 text-xl font-bold">Horarios</h2>
                <TablaDatos
                    caption="Horarios del grupo"
                    columnas={[{ etiqueta: 'Día' }, { etiqueta: 'Horario' }]}
                    vacio={horarios.length === 0}
                    vacioIcono="calendario"
                    vacioTitulo="Sin horarios asignados"
                    vacioDescripcion="Edita el grupo para agregar días y horas de clase."
                >
                    {horarios.map((h, i) => (
                        <tr key={i} className="transition-colors hover:bg-(--color-superficie-2)/60">
                            <td className="px-6 py-4 text-sm font-medium">{h.dia_label}</td>
                            <td className="px-6 py-4 text-sm text-(--color-texto-suave) tabular-nums">
                                {h.hora_inicio} – {h.hora_fin}
                            </td>
                        </tr>
                    ))}
                </TablaDatos>
            </div>

            <div>
                <div className="mb-3 flex items-center gap-3">
                    <h2 className="text-xl font-bold">Estudiantes inscritos</h2>
                    <span className="inline-flex rounded-full bg-(--color-superficie-2) px-2.5 py-0.5 text-sm font-medium text-(--color-texto-suave)">
                        {inscritos.length}
                    </span>
                </div>

                <TablaDatos
                    caption="Estudiantes inscritos en el grupo"
                    columnas={[
                        { etiqueta: 'Estudiante' },
                        { etiqueta: 'Correo institucional' },
                        { etiqueta: 'Estado de matrícula', alinear: 'derecha' },
                    ]}
                    vacio={inscritos.length === 0}
                    vacioIcono="usuarios"
                    vacioTitulo="Todavía no hay estudiantes inscritos"
                    vacioDescripcion="Puedes inscribir estudiantes desde el módulo de Matrículas."
                    vacioAccion={
                        <Link to="/matriculas/nueva" className="btn btn-primario text-xs">
                            Registrar matrícula
                        </Link>
                    }
                >
                    {inscritos.map((e) => (
                        <tr key={e.id} className="transition-colors hover:bg-(--color-superficie-2)/60">
                            <td className="px-6 py-4 text-sm font-semibold">
                                <Link to={`/matriculas/${e.matricula_id}`} className="no-underline hover:text-(--color-acento) hover:underline">
                                    {e.nombre ?? '—'}
                                </Link>
                            </td>
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
            </div>
        </section>
    );
}
