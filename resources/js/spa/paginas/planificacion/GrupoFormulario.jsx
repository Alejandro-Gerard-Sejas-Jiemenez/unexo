import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import cliente, { ErrorDeValidacion } from '../../api/cliente';
import Campo, { claseControl } from '../../componentes/Campo';

const VACIO = {
    modulo_id: '',
    cohorte_id: '',
    docente_id: '',
    aula_id: '',
    modalidad: 'virtual',
    enlace_videoconferencia: '',
    cupo_minimo: '',
    estado: 'programado',
    fecha_inicio: '',
    fecha_fin: '',
};

const HORARIO_NUEVO = { dia_semana: 1, hora_inicio: '19:00', hora_fin: '21:00' };

/** CU5: configuración logística del grupo (docente, modalidad, horarios, aula o enlace). */
export default function GrupoFormulario() {
    const { id } = useParams();
    const navegar = useNavigate();
    const editando = Boolean(id);

    const [datos, setDatos] = useState(VACIO);
    const [horarios, setHorarios] = useState([{ ...HORARIO_NUEVO }]);
    const [opciones, setOpciones] = useState({
        modulos: [], cohortes: [], docentes: [], aulas: [], estados: [], modalidades: [], dias: [], limites: {},
    });
    const [errores, setErrores] = useState({});
    const [aviso, setAviso] = useState(null);
    const [cargando, setCargando] = useState(true);
    const [enviando, setEnviando] = useState(false);

    useEffect(() => {
        let vigente = true;

        (async () => {
            try {
                const peticiones = [cliente.get('/planificacion/opciones')];
                if (editando) peticiones.push(cliente.get(`/grupos/${id}`));

                const [resOpciones, resGrupo] = await Promise.all(peticiones);
                if (!vigente) return;

                setOpciones(resOpciones.data);

                if (resGrupo) {
                    const g = resGrupo.data.data;
                    setDatos({
                        ...VACIO,
                        ...Object.fromEntries(Object.keys(VACIO).map((c) => [c, g[c] ?? ''])),
                    });
                    if (g.horarios?.length) {
                        setHorarios(g.horarios.map((h) => ({
                            dia_semana: h.dia_semana,
                            hora_inicio: h.hora_inicio,
                            hora_fin: h.hora_fin,
                        })));
                    }
                }
            } catch (error) {
                if (vigente) setAviso(error.message);
            } finally {
                if (vigente) setCargando(false);
            }
        })();

        return () => {
            vigente = false;
        };
    }, [editando, id]);

    const cambiar = (campo) => (e) => setDatos((p) => ({ ...p, [campo]: e.target.value }));

    const cambiarHorario = (i, campo) => (e) =>
        setHorarios((previos) =>
            previos.map((h, j) =>
                j === i
                    ? { ...h, [campo]: campo === 'dia_semana' ? Number(e.target.value) : e.target.value }
                    : h,
            ),
        );

    async function enviar(e) {
        e.preventDefault();
        setErrores({});
        setAviso(null);
        setEnviando(true);

        // Los vacíos van como null: el servidor distingue "sin aula" de "aula ''".
        const cuerpo = {
            ...Object.fromEntries(
                Object.entries(datos).map(([k, v]) => [k, v === '' ? null : v]),
            ),
            horarios,
        };

        try {
            if (editando) {
                await cliente.put(`/grupos/${id}`, cuerpo);
            } else {
                await cliente.post('/grupos', cuerpo);
            }
            navegar('/app/grupos');
        } catch (error) {
            if (error instanceof ErrorDeValidacion) {
                setErrores(
                    Object.fromEntries(Object.entries(error.errores).map(([c, l]) => [c, l[0]])),
                );
                setAviso('Revisá los campos marcados.');
            } else {
                setAviso(error.message);
            }
        } finally {
            setEnviando(false);
        }
    }

    if (cargando) return <p className="text-[var(--color-texto-suave)]">Cargando…</p>;

    const esPresencial = datos.modalidad === 'presencial';
    const esVirtual = datos.modalidad === 'virtual';
    const maxHorarios = opciones.limites?.horarios_max ?? 14;

    return (
        <section className="max-w-3xl">
            <h1 className="mb-6 text-2xl font-semibold">
                {editando ? 'Configurar grupo' : 'Nuevo grupo'}
            </h1>

            {aviso && <p role="alert" className="mb-4 text-sm text-[var(--color-error)]">{aviso}</p>}

            <form onSubmit={enviar} noValidate className="superficie p-4">
                <div className="grid grid-cols-2 gap-4">
                    <Campo id="modulo_id" etiqueta="Módulo" error={errores.modulo_id} requerido>
                        {(p) => (
                            <select {...p} className={claseControl} value={datos.modulo_id}
                                onChange={cambiar('modulo_id')} disabled={editando}>
                                <option value="">Elegí un módulo…</option>
                                {opciones.modulos.map((m) => (
                                    <option key={m.id} value={m.id}>{m.nombre} ({m.codigo})</option>
                                ))}
                            </select>
                        )}
                    </Campo>

                    <Campo id="cohorte_id" etiqueta="Edición" error={errores.cohorte_id}
                        ayuda="Vacío = módulo suelto, sin edición.">
                        {(p) => (
                            <select {...p} className={claseControl} value={datos.cohorte_id ?? ''}
                                onChange={cambiar('cohorte_id')}>
                                <option value="">Módulo suelto</option>
                                {opciones.cohortes.map((c) => (
                                    <option key={c.id} value={c.id}>{c.nombre}</option>
                                ))}
                            </select>
                        )}
                    </Campo>
                </div>

                <div className="grid grid-cols-3 gap-4">
                    <Campo id="docente_id" etiqueta="Docente" error={errores.docente_id} requerido>
                        {(p) => (
                            <select {...p} className={claseControl} value={datos.docente_id}
                                onChange={cambiar('docente_id')}>
                                <option value="">Elegí un docente…</option>
                                {opciones.docentes.map((d) => (
                                    <option key={d.id} value={d.id}>{d.nombre}</option>
                                ))}
                            </select>
                        )}
                    </Campo>

                    <Campo id="modalidad" etiqueta="Modalidad" error={errores.modalidad} requerido>
                        {(p) => (
                            <select {...p} className={claseControl} value={datos.modalidad}
                                onChange={cambiar('modalidad')}>
                                {opciones.modalidades.map((m) => (
                                    <option key={m.valor} value={m.valor}>{m.etiqueta}</option>
                                ))}
                            </select>
                        )}
                    </Campo>

                    <Campo id="estado" etiqueta="Estado" error={errores.estado} requerido>
                        {(p) => (
                            <select {...p} className={claseControl} value={datos.estado}
                                onChange={cambiar('estado')}>
                                {opciones.estados.map((e) => (
                                    <option key={e.valor} value={e.valor}>{e.etiqueta}</option>
                                ))}
                            </select>
                        )}
                    </Campo>
                </div>

                {!esVirtual && (
                    <Campo id="aula_id" etiqueta="Aula física" error={errores.aula_id}
                        requerido={esPresencial}
                        ayuda="Obligatoria en modalidad presencial.">
                        {(p) => (
                            <select {...p} className={claseControl} value={datos.aula_id ?? ''}
                                onChange={cambiar('aula_id')}>
                                <option value="">Sin aula</option>
                                {opciones.aulas.map((a) => (
                                    <option key={a.id} value={a.id}>
                                        {a.nombre}{a.capacidad ? ` (${a.capacidad} lugares)` : ''}
                                    </option>
                                ))}
                            </select>
                        )}
                    </Campo>
                )}

                {!esPresencial && (
                    <Campo id="enlace_videoconferencia" etiqueta="Enlace de videoconferencia"
                        error={errores.enlace_videoconferencia}>
                        {(p) => (
                            <input {...p} type="url" placeholder="https://meet.google.com/…"
                                className={claseControl} value={datos.enlace_videoconferencia ?? ''}
                                onChange={cambiar('enlace_videoconferencia')} />
                        )}
                    </Campo>
                )}

                <div className="grid grid-cols-3 gap-4">
                    <Campo id="fecha_inicio" etiqueta="Inicio" error={errores.fecha_inicio}>
                        {(p) => (
                            <input {...p} type="date" className={claseControl}
                                value={datos.fecha_inicio ?? ''} onChange={cambiar('fecha_inicio')} />
                        )}
                    </Campo>
                    <Campo id="fecha_fin" etiqueta="Fin" error={errores.fecha_fin}>
                        {(p) => (
                            <input {...p} type="date" className={claseControl}
                                value={datos.fecha_fin ?? ''} onChange={cambiar('fecha_fin')} />
                        )}
                    </Campo>
                    <Campo id="cupo_minimo" etiqueta="Cupo mínimo" error={errores.cupo_minimo}>
                        {(p) => (
                            <input {...p} type="number" min="0" className={claseControl}
                                value={datos.cupo_minimo ?? ''} onChange={cambiar('cupo_minimo')} />
                        )}
                    </Campo>
                </div>

                <fieldset className="mt-2 border-0 p-0">
                    <legend className="mb-2 text-sm font-medium">Horarios semanales</legend>
                    {errores.horarios && (
                        <p className="mb-2 text-sm text-[var(--color-error)]">{errores.horarios}</p>
                    )}

                    {horarios.map((h, i) => (
                        <div key={i} className="mb-2 flex items-end gap-2">
                            <label className="flex-1">
                                <span className="sr-only">Día del horario {i + 1}</span>
                                <select className={claseControl} value={h.dia_semana}
                                    onChange={cambiarHorario(i, 'dia_semana')}>
                                    {opciones.dias.map((d) => (
                                        <option key={d.valor} value={d.valor}>{d.etiqueta}</option>
                                    ))}
                                </select>
                            </label>
                            <label>
                                <span className="sr-only">Hora de inicio del horario {i + 1}</span>
                                <input type="time" className={claseControl} value={h.hora_inicio}
                                    onChange={cambiarHorario(i, 'hora_inicio')} />
                            </label>
                            <label>
                                <span className="sr-only">Hora de fin del horario {i + 1}</span>
                                <input type="time" className={claseControl} value={h.hora_fin}
                                    onChange={cambiarHorario(i, 'hora_fin')} />
                            </label>
                            <button type="button" disabled={horarios.length === 1}
                                onClick={() => setHorarios((p) => p.filter((_, j) => j !== i))}
                                className="superficie px-3 py-2 text-sm disabled:opacity-40">
                                Quitar
                            </button>
                        </div>
                    ))}

                    {horarios.some((_, i) => errores[`horarios.${i}.hora_fin`]) && (
                        <p className="text-sm text-[var(--color-error)]">
                            Revisá las horas: la de fin debe ser posterior a la de inicio.
                        </p>
                    )}

                    <button type="button" disabled={horarios.length >= maxHorarios}
                        onClick={() => setHorarios((p) => [...p, { ...HORARIO_NUEVO }])}
                        className="superficie mt-1 px-3 py-1.5 text-sm disabled:opacity-40">
                        Agregar horario
                    </button>
                </fieldset>

                <div className="mt-6 flex gap-3">
                    <button type="submit" disabled={enviando}
                        className="rounded-lg bg-[var(--color-acento)] px-4 py-2 text-sm font-medium text-[var(--color-acento-texto)] disabled:opacity-60">
                        {enviando ? 'Guardando…' : 'Guardar'}
                    </button>
                    <button type="button" onClick={() => navegar('/app/grupos')}
                        className="superficie px-4 py-2 text-sm">
                        Cancelar
                    </button>
                </div>
            </form>
        </section>
    );
}
