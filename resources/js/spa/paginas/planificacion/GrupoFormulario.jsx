import { useEffect, useState } from 'react';
import { useNavigate, useParams, useSearchParams } from 'react-router-dom';
import cliente, { ErrorDeValidacion } from '../../api/cliente';
import Campo, { claseControl } from '../../componentes/Campo';
import Cargador from '../../componentes/Cargador';
import EncabezadoPagina from '../../componentes/EncabezadoPagina';
import Icono from '../../componentes/Icono';
import { useToast } from '../../componentes/ToastContext';

const VACIO = {
    cohorte_id: '',
    modulo_id: '',
    docente_id: '',
    aula_id: '',
    modalidad: 'virtual',
    estado: 'programado',
    cupo_minimo: '',
    fecha_inicio: '',
    fecha_fin: '',
    enlace_videoconferencia: '',
    horarios: [{ dia_semana: '1', hora_inicio: '19:00', hora_fin: '21:00' }],
};

export default function GrupoFormulario() {
    const { id } = useParams();
    const [busqueda] = useSearchParams();
    const navegar = useNavigate();
    const { mostrar } = useToast();
    const editando = Boolean(id);

    const [datos, setDatos] = useState(() => ({
        ...VACIO,
        cohorte_id: busqueda.get('cohorte_id') ?? '',
        modulo_id: busqueda.get('modulo_id') ?? '',
    }));
    const [opciones, setOpciones] = useState({
        modulos: [],
        docentes: [],
        aulas: [],
        cohortes: [],
        estados: [],
        modalidades: [],
        dias: [],
    });
    const [errores, setErrores] = useState({});
    const [aviso, setAviso] = useState(null);
    const [cargando, setCargando] = useState(true);
    const [enviando, setEnviando] = useState(false);

    useEffect(() => {
        let vigente = true;

        (async () => {
            try {
                const { data: op } = await cliente.get('/planificacion/opciones');
                if (!vigente) return;
                setOpciones(op);

                if (editando) {
                    const { data } = await cliente.get(`/grupos/${id}`);
                    if (!vigente) return;
                    const g = data.data;
                    const sinHorarioPermitido = g.estado === 'finalizado' || g.estado === 'cancelado';
                    setDatos({
                        cohorte_id: g.cohorte_id ?? '',
                        modulo_id: g.modulo_id ?? '',
                        docente_id: g.docente_id ?? '',
                        aula_id: g.aula_id ?? '',
                        modalidad: g.modalidad ?? 'virtual',
                        estado: g.estado ?? 'programado',
                        cupo_minimo: g.cupo_minimo ?? '',
                        fecha_inicio: g.fecha_inicio ?? '',
                        fecha_fin: g.fecha_fin ?? '',
                        enlace_videoconferencia: g.enlace_videoconferencia ?? '',
                        horarios: g.horarios?.length
                            ? g.horarios.map((h) => ({
                                  dia_semana: String(h.dia_semana),
                                  hora_inicio: h.hora_inicio,
                                  hora_fin: h.hora_fin,
                              }))
                            : sinHorarioPermitido
                              ? []
                              : [{ dia_semana: '1', hora_inicio: '19:00', hora_fin: '21:00' }],
                    });
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

    const cambiar = (campo) => (e) => {
        const valor = e.target.value;
        setDatos((prev) => {
            const next = { ...prev, [campo]: valor };
            if (campo === 'cohorte_id') {
                const cohorte = opciones.cohortes.find((c) => c.id === valor);
                if (cohorte && cohorte.modulo_ids?.length && !cohorte.modulo_ids.includes(prev.modulo_id)) {
                    next.modulo_id = '';
                }
            }
            return next;
        });
    };

    function cambiarHorario(indice, campo, valor) {
        setDatos((p) => ({
            ...p,
            horarios: p.horarios.map((h, i) => (i === indice ? { ...h, [campo]: valor } : h)),
        }));
    }

    const maxHorarios = opciones.limites?.horarios_max ?? 14;

    function agregarHorario() {
        setDatos((p) => {
            if (p.horarios.length >= maxHorarios) return p;
            return {
                ...p,
                horarios: [...p.horarios, { dia_semana: '1', hora_inicio: '19:00', hora_fin: '21:00' }],
            };
        });
    }

    function quitarHorario(indice) {
        setDatos((p) => ({
            ...p,
            horarios: p.horarios.filter((_, i) => i !== indice),
        }));
    }

    const cohorteSeleccionada = opciones.cohortes.find((c) => c.id === datos.cohorte_id) ?? null;
    const modulosDisponibles = cohorteSeleccionada?.modulo_ids?.length
        ? opciones.modulos.filter((m) => cohorteSeleccionada.modulo_ids.includes(m.id))
        : opciones.modulos;

    const usaEdicion = Boolean(datos.cohorte_id);
    const necesitaAula = datos.modalidad === 'presencial' || datos.modalidad === 'mixta';
    const necesitaEnlace = datos.modalidad === 'virtual' || datos.modalidad === 'mixta';
    const permiteSinHorarios = datos.estado === 'finalizado' || datos.estado === 'cancelado';

    async function enviar(e) {
        e.preventDefault();
        setErrores({});
        setAviso(null);
        setEnviando(true);

        const carga = {
            ...datos,
            cohorte_id: datos.cohorte_id || null,
            docente_id: datos.docente_id || null,
            cupo_minimo: datos.cupo_minimo === '' ? null : datos.cupo_minimo,
            aula_id: necesitaAula ? datos.aula_id || null : null,
            enlace_videoconferencia: necesitaEnlace ? datos.enlace_videoconferencia || null : null,
            fecha_inicio: usaEdicion ? null : datos.fecha_inicio || null,
            fecha_fin: usaEdicion ? null : datos.fecha_fin || null,
        };

        try {
            if (editando) {
                const { data } = await cliente.put(`/grupos/${id}`, carga);
                mostrar(data.mensaje ?? 'Grupo actualizado correctamente.');
            } else {
                const { data } = await cliente.post('/grupos', carga);
                mostrar(data.mensaje ?? 'Grupo creado correctamente.');
            }
            navegar(usaEdicion ? `/cohortes/${datos.cohorte_id}` : '/grupos');
        } catch (error) {
            if (error instanceof ErrorDeValidacion) {
                setErrores(Object.fromEntries(Object.entries(error.errores).map(([c, l]) => [c, l[0]])));
            } else {
                setAviso(error.message);
            }
        } finally {
            setEnviando(false);
        }
    }

    if (cargando) return <Cargador texto="Cargando" />;

    return (
        <section>
            <EncabezadoPagina
                volverA={usaEdicion ? `/cohortes/${datos.cohorte_id}` : '/grupos'}
                volverTexto={
                    cohorteSeleccionada
                        ? `Volver a ${cohorteSeleccionada.nombre}`
                        : 'Volver a grupos'
                }
                titulo={editando ? 'Editar grupo' : 'Nuevo grupo'}
            />

            {aviso && (
                <p role="alert" className="superficie mb-4 border-[var(--color-error)] p-3 text-sm text-[var(--color-error)]">
                    {aviso}
                </p>
            )}

            <div className="superficie max-w-4xl overflow-hidden rounded-xl shadow-sm">
                <form onSubmit={enviar} noValidate className="p-6 sm:p-8">
                    {cohorteSeleccionada && (
                        <div className="mb-6 flex items-start gap-3 rounded-xl border border-[color-mix(in_srgb,var(--color-acento)_25%,transparent)] bg-[var(--color-acento-suave)]/70 p-4 text-sm">
                            <Icono nombre="info" className="mt-0.5 h-5 w-5 shrink-0 text-[var(--color-acento)]" />
                            <div>
                                <p className="font-semibold text-[var(--color-acento)]">
                                    Edición vinculada: {cohorteSeleccionada.nombre}
                                </p>
                                <p className="mt-0.5 text-xs text-[var(--color-texto-suave)]">
                                    {cohorteSeleccionada.programa ? `${cohorteSeleccionada.programa} · ` : ''}
                                    Las fechas de inicio y fin se heredan automáticamente de la edición.
                                </p>
                            </div>
                        </div>
                    )}

                    <div className="grid grid-cols-1 gap-x-6 gap-y-2 sm:grid-cols-2">
                        <Campo id="modulo_id" etiqueta="Módulo" error={errores.modulo_id} requerido esSelect>
                            {(p) => (
                                <select
                                    {...p}
                                    className={claseControl}
                                    disabled={editando}
                                    value={datos.modulo_id}
                                    onChange={cambiar('modulo_id')}
                                >
                                    <option value="">Selecciona un módulo</option>
                                    {modulosDisponibles.map((m) => (
                                        <option key={m.id} value={m.id}>
                                            {m.codigo} · {m.nombre}
                                        </option>
                                    ))}
                                </select>
                            )}
                        </Campo>

                        <Campo
                            id="cohorte_id"
                            etiqueta="Edición"
                            error={errores.cohorte_id}
                            esSelect
                            ayuda="Déjalo vacío para crear un grupo suelto."
                        >
                            {(p) => (
                                <select
                                    {...p}
                                    className={claseControl}
                                    disabled={editando}
                                    value={datos.cohorte_id}
                                    onChange={cambiar('cohorte_id')}
                                >
                                    <option value="">Grupo suelto (sin edición)</option>
                                    {opciones.cohortes.map((c) => (
                                        <option key={c.id} value={c.id}>
                                            {c.programa ? `${c.programa} · ${c.nombre}` : c.nombre}
                                        </option>
                                    ))}
                                </select>
                            )}
                        </Campo>
                    </div>

                    <div className="mt-4 grid grid-cols-1 gap-x-6 gap-y-2 border-t border-[var(--color-borde)] pt-4 sm:grid-cols-2">
                        <Campo id="docente_id" etiqueta="Docente" error={errores.docente_id} requerido esSelect>
                            {(p) => (
                                <select {...p} className={claseControl} value={datos.docente_id} onChange={cambiar('docente_id')}>
                                    <option value="">Selecciona un docente</option>
                                    {opciones.docentes.map((d) => (
                                        <option key={d.id} value={d.id}>
                                            {d.nombre} ({d.grupos_activos_count ?? 0} activos)
                                        </option>
                                    ))}
                                </select>
                            )}
                        </Campo>

                        <Campo id="modalidad" etiqueta="Modalidad" error={errores.modalidad} requerido esSelect>
                            {(p) => (
                                <select {...p} className={claseControl} value={datos.modalidad} onChange={cambiar('modalidad')}>
                                    {opciones.modalidades.map((m) => (
                                        <option key={m.valor} value={m.valor}>
                                            {m.etiqueta}
                                        </option>
                                    ))}
                                </select>
                            )}
                        </Campo>
                    </div>

                    {!usaEdicion && (
                        <div className="mt-4 grid grid-cols-1 gap-x-6 gap-y-2 border-t border-[var(--color-borde)] pt-4 sm:grid-cols-2">
                            <Campo id="fecha_inicio" etiqueta="Fecha de inicio" error={errores.fecha_inicio} requerido>
                                {(p) => (
                                    <input
                                        {...p}
                                        type="date"
                                        className={claseControl}
                                        value={datos.fecha_inicio}
                                        onChange={cambiar('fecha_inicio')}
                                    />
                                )}
                            </Campo>
                            <Campo id="fecha_fin" etiqueta="Fecha de fin" error={errores.fecha_fin} requerido>
                                {(p) => (
                                    <input
                                        {...p}
                                        type="date"
                                        className={claseControl}
                                        value={datos.fecha_fin}
                                        onChange={cambiar('fecha_fin')}
                                    />
                                )}
                            </Campo>
                        </div>
                    )}

                    {(necesitaAula || necesitaEnlace) && (
                        <div className="mt-4 grid grid-cols-1 gap-x-6 gap-y-2 border-t border-[var(--color-borde)] pt-4 sm:grid-cols-2">
                            {necesitaAula && (
                                <Campo
                                    id="aula_id"
                                    etiqueta="Aula física"
                                    error={errores.aula_id}
                                    requerido={datos.modalidad === 'presencial'}
                                    esSelect
                                >
                                    {(p) => (
                                        <select {...p} className={claseControl} value={datos.aula_id} onChange={cambiar('aula_id')}>
                                            <option value="">Selecciona un aula</option>
                                            {opciones.aulas.map((a) => (
                                                <option key={a.id} value={a.id}>
                                                    {a.nombre} (capacidad {a.capacidad})
                                                </option>
                                            ))}
                                        </select>
                                    )}
                                </Campo>
                            )}

                            {necesitaEnlace && (
                                <Campo
                                    id="enlace_videoconferencia"
                                    etiqueta="Enlace de videoconferencia"
                                    error={errores.enlace_videoconferencia}
                                    icono="enlace"
                                >
                                    {(p) => (
                                        <input
                                            {...p}
                                            type="url"
                                            placeholder="https://meet.google.com/..."
                                            className={claseControl}
                                            value={datos.enlace_videoconferencia}
                                            onChange={cambiar('enlace_videoconferencia')}
                                        />
                                    )}
                                </Campo>
                            )}
                        </div>
                    )}

                    <div className="mt-4 grid grid-cols-1 gap-x-6 gap-y-2 border-t border-[var(--color-borde)] pt-4 sm:grid-cols-2">
                        <Campo id="cupo_minimo" etiqueta="Cupo mínimo" error={errores.cupo_minimo} ayuda="Opcional">
                            {(p) => (
                                <input
                                    {...p}
                                    type="number"
                                    min="0"
                                    className={claseControl}
                                    value={datos.cupo_minimo}
                                    onChange={cambiar('cupo_minimo')}
                                />
                            )}
                        </Campo>

                        <Campo id="estado" etiqueta="Estado" error={errores.estado} requerido esSelect>
                            {(p) => (
                                <select {...p} className={claseControl} value={datos.estado} onChange={cambiar('estado')}>
                                    {opciones.estados.map((e) => (
                                        <option key={e.valor} value={e.valor}>
                                            {e.etiqueta}
                                        </option>
                                    ))}
                                </select>
                            )}
                        </Campo>
                    </div>

                    <fieldset className="mt-4 border-t border-[var(--color-borde)] pt-5">
                        <legend className="text-base font-semibold">Horarios semanales</legend>
                        <p className="mb-4 text-xs text-[var(--color-texto-suave)]">
                            Hora oficial de Bolivia (BOT, UTC-4).
                        </p>

                        {errores.horarios && (
                            <p className="mb-3 text-xs text-[var(--color-error)]">{errores.horarios}</p>
                        )}

                        {datos.horarios.length === 0 && (
                            <p className="mb-3 text-xs text-[var(--color-texto-suave)]">
                                {permiteSinHorarios
                                    ? 'Sin horarios definidos (permitido en grupos finalizados o cancelados).'
                                    : 'Agrega al menos un horario semanal.'}
                            </p>
                        )}

                        <div className="space-y-3">
                            {datos.horarios.map((h, i) => {
                                const errDia = errores[`horarios.${i}.dia_semana`];
                                const errIni = errores[`horarios.${i}.hora_inicio`];
                                const errFin = errores[`horarios.${i}.hora_fin`];
                                const errFila = [errDia, errIni, errFin].filter(Boolean);
                                const idErr = errFila.length > 0 ? `err-horario-${i}` : undefined;

                                return (
                                    <div key={i}>
                                        <div className="horario-fila">
                                            <label>
                                                <span className="mb-1 block text-xs text-[var(--color-texto-suave)]">Día</span>
                                                <select
                                                    aria-label={`Día de la semana ${i + 1}`}
                                                    aria-invalid={errDia ? 'true' : undefined}
                                                    aria-describedby={idErr}
                                                    className={claseControl}
                                                    value={h.dia_semana}
                                                    onChange={(e) => cambiarHorario(i, 'dia_semana', e.target.value)}
                                                >
                                                    {opciones.dias.map((d) => (
                                                        <option key={d.valor} value={d.valor}>
                                                            {d.etiqueta}
                                                        </option>
                                                    ))}
                                                </select>
                                            </label>

                                            <label>
                                                <span className="mb-1 block text-xs text-[var(--color-texto-suave)]">Inicio</span>
                                                <input
                                                    type="time"
                                                    aria-label={`Hora de inicio ${i + 1}`}
                                                    aria-invalid={errIni ? 'true' : undefined}
                                                    aria-describedby={idErr}
                                                    className={claseControl}
                                                    value={h.hora_inicio}
                                                    onChange={(e) => cambiarHorario(i, 'hora_inicio', e.target.value)}
                                                />
                                            </label>

                                            <label>
                                                <span className="mb-1 block text-xs text-[var(--color-texto-suave)]">Fin</span>
                                                <input
                                                    type="time"
                                                    aria-label={`Hora de fin ${i + 1}`}
                                                    aria-invalid={errFin ? 'true' : undefined}
                                                    aria-describedby={idErr}
                                                    className={claseControl}
                                                    value={h.hora_fin}
                                                    onChange={(e) => cambiarHorario(i, 'hora_fin', e.target.value)}
                                                />
                                            </label>

                                            <button
                                                type="button"
                                                disabled={!permiteSinHorarios && datos.horarios.length === 1}
                                                onClick={() => quitarHorario(i)}
                                                aria-label={`Quitar horario ${i + 1}`}
                                                className="btn btn-secundario !min-h-9 !px-3 text-xs sm:mt-5 disabled:opacity-40"
                                            >
                                                Quitar
                                            </button>
                                        </div>

                                        {errFila.length > 0 && (
                                            <p id={idErr} className="-mt-1 mb-2 text-xs text-[var(--color-error)]">
                                                {errFila.join(' · ')}
                                            </p>
                                        )}
                                    </div>
                                );
                            })}
                        </div>

                        <button
                            type="button"
                            disabled={datos.horarios.length >= maxHorarios}
                            onClick={agregarHorario}
                            className="btn btn-secundario mt-2 !min-h-9 !px-3 text-xs disabled:opacity-40"
                        >
                            <Icono nombre="mas" className="h-4 w-4" />
                            Agregar horario
                        </button>
                    </fieldset>

                    <div className="form-acciones">
                        <button
                            type="button"
                            onClick={() => navegar(usaEdicion ? `/cohortes/${datos.cohorte_id}` : '/grupos')}
                            className="btn btn-secundario"
                        >
                            Cancelar
                        </button>
                        <button type="submit" disabled={enviando} className="btn btn-primario">
                            <Icono nombre="guardar" />
                            {enviando ? 'Guardando…' : 'Guardar'}
                        </button>
                    </div>
                </form>
            </div>
        </section>
    );
}
