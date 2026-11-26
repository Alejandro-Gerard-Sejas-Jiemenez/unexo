import { useCallback, useEffect, useState } from 'react';
import { Link, useNavigate, useParams, useSearchParams } from 'react-router-dom';
import cliente, { ErrorDeValidacion } from '../../api/cliente';
import BotonAccion from '../../componentes/BotonAccion';
import Campo, { claseControl } from '../../componentes/Campo';
import Cargador from '../../componentes/Cargador';
import EncabezadoPagina from '../../componentes/EncabezadoPagina';
import Icono from '../../componentes/Icono';
import Insignia, { tonoEstado } from '../../componentes/Insignia';
import TablaDatos from '../../componentes/TablaDatos';
import { useToast } from '../../componentes/ToastContext';
import { formatoFecha, semanasEntre } from '../../lib/fechas';

const VACIO = { programa_id: '', nombre: '', fecha_inicio: '', fecha_fin: '', estado: 'programado' };

export default function EdicionDetalle() {
    const { id } = useParams();
    const [busqueda, setBusqueda] = useSearchParams();
    const navegar = useNavigate();
    const { mostrar } = useToast();
    const editando = Boolean(id);
    const modoFormulario = !editando || busqueda.get('editar') === '1';

    const [datos, setDatos] = useState(() => ({
        ...VACIO,
        programa_id: busqueda.get('programa_id') ?? '',
    }));
    const [cohorte, setCohorte] = useState(null);
    const [pendientes, setPendientes] = useState([]);
    const [opciones, setOpciones] = useState({ programas: [], docentes: [], estados: [], modalidades: [] });
    const [apertura, setApertura] = useState({ docente_id: '', cupo_minimo: '', modalidad: 'virtual' });
    const [errores, setErrores] = useState({});
    const [aviso, setAviso] = useState(null);
    const [cargando, setCargando] = useState(true);
    const [enviando, setEnviando] = useState(false);

    const cargar = useCallback(async () => {
        try {
            const { data: op } = await cliente.get('/planificacion/opciones');
            setOpciones(op);

            if (editando) {
                const { data } = await cliente.get(`/cohortes/${id}`);
                const c = data.data;
                setCohorte(c);
                setPendientes(data.modulos_pendientes ?? []);
                setDatos({
                    programa_id: c.programa_id ?? '',
                    nombre: c.nombre ?? '',
                    fecha_inicio: c.fecha_inicio ?? '',
                    fecha_fin: c.fecha_fin ?? '',
                    estado: c.estado ?? 'programado',
                });
            }
        } catch (error) {
            setAviso(error.message);
        } finally {
            setCargando(false);
        }
    }, [editando, id]);

    useEffect(() => {
        cargar();
    }, [cargar]);

    const cambiar = (campo) => (e) => setDatos((p) => ({ ...p, [campo]: e.target.value }));

    async function guardar(e) {
        e.preventDefault();
        setErrores({});
        setAviso(null);
        setEnviando(true);

        try {
            if (editando) {
                const { data } = await cliente.put(`/cohortes/${id}`, datos);
                mostrar(data.mensaje);
                setBusqueda({});
                await cargar();
            } else {
                const { data } = await cliente.post('/cohortes', datos);
                mostrar(data.mensaje);
                navegar(`/cohortes/${data.cohorte.id}`, { replace: true });
            }
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

    async function aperturar(e) {
        e.preventDefault();
        setErrores({});
        setAviso(null);
        setEnviando(true);

        try {
            const { data } = await cliente.post(`/cohortes/${id}/aperturar-grupos`, apertura);
            mostrar(data.mensaje);
            await cargar();
        } catch (error) {
            if (error instanceof ErrorDeValidacion) {
                const mapeados = Object.fromEntries(Object.entries(error.errores).map(([c, l]) => [c, l[0]]));
                setErrores(mapeados);
                setAviso(mapeados.cohorte ?? mapeados.docente ?? error.message);
            } else {
                setAviso(error.message);
            }
        } finally {
            setEnviando(false);
        }
    }

    async function cambiarEstado(estado) {
        setAviso(null);
        try {
            const { data } = await cliente.patch(`/cohortes/${id}/estado`, {
                estado,
                propagar_a_grupos: true,
            });
            mostrar(data.mensaje);
            await cargar();
        } catch (error) {
            setAviso(error.message);
        }
    }

    if (cargando) return <Cargador texto="Cargando" />;

    if (modoFormulario) {
        return (
            <section>
                <EncabezadoPagina
                    volverA={editando ? `/cohortes/${id}` : '/cohortes'}
                    volverTexto={editando ? `Volver a ${cohorte?.nombre ?? 'la edición'}` : 'Volver a ediciones'}
                    titulo={editando ? 'Editar edición' : 'Nueva edición'}
                />

                {aviso && (
                    <p role="alert" className="superficie mb-4 border-(--color-error) p-3 text-sm text-(--color-error)">
                        {aviso}
                    </p>
                )}

                <div className="superficie max-w-3xl overflow-hidden rounded-xl shadow-sm">
                    <form onSubmit={guardar} noValidate className="p-6 sm:p-8">
                        {!editando && (
                            <div className="mb-6 flex items-start gap-3 rounded-xl border border-(--color-borde) bg-(--color-superficie-cabecera) p-4">
                                <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-(--color-acento-suave) text-(--color-acento)">
                                    <Icono nombre="info" className="h-4 w-4" />
                                </span>
                                <p className="text-xs leading-relaxed text-(--color-texto-suave) sm:text-sm">
                                    El nombre se generará automáticamente de forma correlativa (<b>Edición 1</b>,{' '}
                                    <b>Edición 2</b>…) según el programa seleccionado. Luego podrás planificar o aperturar
                                    todos sus grupos en un clic.
                                </p>
                            </div>
                        )}

                        <Campo id="programa_id" etiqueta="Programa" error={errores.programa_id} requerido esSelect>
                            {(p) => (
                                <select
                                    {...p}
                                    className={claseControl}
                                    disabled={editando}
                                    value={datos.programa_id}
                                    onChange={cambiar('programa_id')}
                                >
                                    <option value="">Selecciona un programa</option>
                                    {opciones.programas.map((pr) => (
                                        <option key={pr.id} value={pr.id}>
                                            {pr.nombre} (v{pr.version})
                                        </option>
                                    ))}
                                </select>
                            )}
                        </Campo>

                        {editando && (
                            <Campo
                                id="nombre"
                                etiqueta="Nombre de la edición"
                                error={errores.nombre}
                                requerido
                            >
                                {(p) => (
                                    <input
                                        {...p}
                                        type="text"
                                        maxLength={150}
                                        className={claseControl}
                                        value={datos.nombre}
                                        onChange={cambiar('nombre')}
                                    />
                                )}
                            </Campo>
                        )}

                        <div className="grid grid-cols-1 gap-x-6 gap-y-2 sm:grid-cols-2">
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

                        <Campo id="estado" etiqueta="Estado" error={errores.estado} esSelect requerido>
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

                        <div className="form-acciones">
                            <button
                                type="button"
                                onClick={() => (editando ? setBusqueda({}) : navegar('/cohortes'))}
                                className="btn btn-secundario"
                            >
                                Cancelar
                            </button>
                            <button type="submit" disabled={enviando} className="btn btn-primario">
                                <Icono nombre="guardar" />
                                {enviando ? 'Guardando…' : editando ? 'Guardar cambios' : 'Crear edición'}
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        );
    }

    if (!cohorte) return null;

    const semanas = semanasEntre(cohorte.fecha_inicio, cohorte.fecha_fin);
    const grupos = cohorte.grupos ?? [];
    const modulosCubiertos = new Set(grupos.map((g) => g.modulo_id)).size;
    const totalModulos = new Set([...grupos.map((g) => g.modulo_id), ...pendientes.map((m) => m.id)]).size;
    const totalInscritos = grupos.reduce((acc, g) => acc + (g.inscripciones_count ?? 0), 0);

    return (
        <section>
            <div className="mb-4">
                <Link
                    to="/cohortes"
                    className="inline-flex items-center gap-1.5 text-sm font-medium text-(--color-texto-suave) no-underline transition-colors hover:text-(--color-acento)"
                >
                    <Icono nombre="volver" className="h-4 w-4" />
                    Volver a ediciones
                </Link>
            </div>

            {aviso && (
                <p role="alert" className="superficie mb-6 border-(--color-error) p-3 text-sm text-(--color-error)">
                    {aviso}
                </p>
            )}

            <div className="mb-8 flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
                <div>
                    <div className="flex flex-wrap items-center gap-3">
                        <h1 className="text-3xl font-bold tracking-tight">{cohorte.nombre}</h1>
                        <Insignia tono={tonoEstado(cohorte.estado)}>{cohorte.estado_label}</Insignia>
                    </div>
                    <p className="mt-1 text-sm text-(--color-texto-suave)">
                        Programa: <span className="font-semibold text-(--color-texto)">{cohorte.programa?.nombre}</span>{' '}
                        (v{cohorte.programa?.version})
                        {cohorte.programa?.codigo && (
                            <>
                                {' '}
                                · Código:{' '}
                                <code className="rounded bg-(--color-acento-suave) px-1.5 py-0.5 font-mono text-xs text-(--color-acento)">
                                    {cohorte.programa.codigo}
                                </code>
                            </>
                        )}
                    </p>
                </div>

                <div className="flex flex-wrap items-center gap-3">
                    <label className="inline-flex items-center gap-2 text-xs font-medium text-(--color-texto-suave)">
                        Estado:
                        <select
                            value={cohorte.estado}
                            onChange={(e) => cambiarEstado(e.target.value)}
                            className="rounded-lg border border-(--color-borde) bg-(--color-superficie) py-1.5 pr-8 pl-3 text-xs font-semibold shadow-sm focus:border-(--color-acento) focus:outline-none"
                        >
                            {opciones.estados.map((est) => (
                                <option key={est.valor} value={est.valor}>
                                    {est.etiqueta}
                                </option>
                            ))}
                        </select>
                    </label>

                    <BotonAccion
                        to={`/cohortes/${id}?editar=1`}
                        icono="editar"
                        className="rounded-lg! px-3.5! py-2!"
                    >
                        Editar edición
                    </BotonAccion>
                </div>
            </div>

            <div className="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div className="superficie rounded-xl p-5 shadow-sm">
                    <div className="mb-1 text-xs font-semibold tracking-wider text-(--color-texto-suave) uppercase">
                        Fechas Globales
                    </div>
                    <div className="text-base font-bold tabular-nums">
                        {formatoFecha(cohorte.fecha_inicio)} – {formatoFecha(cohorte.fecha_fin)}
                    </div>
                    {semanas && (
                        <div className="mt-1 text-xs text-(--color-texto-suave)">
                            {semanas} semanas de duración
                        </div>
                    )}
                </div>

                <div className="superficie rounded-xl p-5 shadow-sm">
                    <div className="mb-1 text-xs font-semibold tracking-wider text-(--color-texto-suave) uppercase">
                        Módulos del Programa
                    </div>
                    <div className="text-2xl font-bold text-(--color-acento) tabular-nums">
                        {modulosCubiertos} / {totalModulos}
                    </div>
                    <div className="mt-1 text-xs">
                        {pendientes.length === 0 ? (
                            <span className="inline-flex items-center gap-1 font-medium text-(--color-exito)">
                                <Icono nombre="check" className="h-3.5 w-3.5" />
                                Planificación completa
                            </span>
                        ) : (
                            <span className="font-medium text-(--color-aviso)">
                                Faltan {pendientes.length} por aperturar
                            </span>
                        )}
                    </div>
                </div>

                <div className="superficie rounded-xl p-5 shadow-sm">
                    <div className="mb-1 text-xs font-semibold tracking-wider text-(--color-texto-suave) uppercase">
                        Grupos Aperturados
                    </div>
                    <div className="text-2xl font-bold tabular-nums">{grupos.length}</div>
                    <div className="mt-1 text-xs text-(--color-texto-suave)">Grupos operativos activos</div>
                </div>

                <div className="superficie rounded-xl p-5 shadow-sm">
                    <div className="mb-1 text-xs font-semibold tracking-wider text-(--color-texto-suave) uppercase">
                        Alumnos Matriculados
                    </div>
                    <div className="text-2xl font-bold text-(--color-exito) tabular-nums">
                        {cohorte.matriculas_count ?? totalInscritos}
                    </div>
                    <div className="mt-1 text-xs text-(--color-texto-suave)">Estudiantes en la edición</div>
                </div>
            </div>

            {pendientes.length > 0 && (
                <form
                    onSubmit={aperturar}
                    className="mb-8 rounded-xl border border-[color-mix(in_srgb,var(--color-acento)_25%,transparent)] bg-(--color-acento-suave)/70 p-6 shadow-sm"
                >
                    <div className="flex flex-col justify-between gap-6 md:flex-row md:items-center">
                        <div className="max-w-2xl">
                            <div className="mb-1 flex items-center gap-2 text-base font-bold text-(--color-acento)">
                                <Icono nombre="modulos" className="h-5 w-5" />
                                <span>Apertura Asistida de Grupos Operativos</span>
                            </div>
                            <p className="text-xs text-(--color-texto-suave) sm:text-sm">
                                Esta edición tiene{' '}
                                <span className="font-bold text-(--color-acento)">
                                    {pendientes.length} módulo(s) pendiente(s)
                                </span>{' '}
                                de apertura. El generador creará un grupo por módulo con el período de esta edición.
                            </p>
                            <div className="mt-2.5 flex flex-wrap gap-1.5">
                                {pendientes.map((pend) => (
                                    <span
                                        key={pend.id}
                                        className="inline-flex items-center rounded border border-[color-mix(in_srgb,var(--color-acento)_25%,transparent)] bg-(--color-superficie) px-2 py-0.5 font-mono text-[11px] font-medium"
                                    >
                                        {pend.codigo}: {pend.nombre.slice(0, 26)}
                                    </span>
                                ))}
                            </div>
                        </div>

                        <div className="flex flex-wrap items-end gap-2.5">
                            <label className="text-xs font-medium">
                                <span className="mb-1 block text-(--color-texto-suave)">Docente inicial</span>
                                <select
                                    className={`${claseControl} min-h-9! text-xs`}
                                    value={apertura.docente_id}
                                    onChange={(e) => setApertura((a) => ({ ...a, docente_id: e.target.value }))}
                                >
                                    <option value="">Por asignar</option>
                                    {opciones.docentes.map((d) => (
                                        <option key={d.id} value={d.id}>
                                            {d.nombre} ({d.grupos_activos_count ?? 0})
                                        </option>
                                    ))}
                                </select>
                            </label>

                            <label className="text-xs font-medium">
                                <span className="mb-1 block text-(--color-texto-suave)">Modalidad</span>
                                <select
                                    className={`${claseControl} min-h-9! text-xs`}
                                    value={apertura.modalidad}
                                    onChange={(e) => setApertura((a) => ({ ...a, modalidad: e.target.value }))}
                                >
                                    {opciones.modalidades.map((m) => (
                                        <option key={m.valor} value={m.valor}>
                                            {m.etiqueta}
                                        </option>
                                    ))}
                                </select>
                            </label>

                            <button type="submit" disabled={enviando} className="btn btn-primario min-h-9! text-xs">
                                <Icono nombre="mas" className="h-4 w-4" />
                                Aperturar {pendientes.length} Grupos
                            </button>
                        </div>
                    </div>
                </form>
            )}

            <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 className="text-xl font-bold">Grupos de la edición</h2>
                    <p className="text-xs text-(--color-texto-suave)">
                        Puedes gestionar docentes, modalidades y horarios o agregar grupos paralelos.
                    </p>
                </div>
                <BotonAccion
                    to={`/grupos/nuevo?cohorte_id=${id}`}
                    icono="mas"
                    className="rounded-lg! px-3.5! py-2!"
                >
                    Añadir grupo paralelo
                </BotonAccion>
            </div>

            <TablaDatos
                caption="Grupos operativos de la edición"
                columnas={[
                    { etiqueta: 'Módulo' },
                    { etiqueta: 'Grupo' },
                    { etiqueta: 'Período' },
                    { etiqueta: 'Fase / Estado' },
                    { etiqueta: 'Inscritos' },
                    { etiqueta: 'Acciones', srOnly: true, alinear: 'derecha' },
                ]}
                vacio={grupos.length === 0}
                vacioIcono="modulos"
                vacioTitulo="Todavía no hay grupos aperturados en esta edición"
                vacioDescripcion="Usa la apertura asistida arriba o añade un grupo manualmente."
            >
                {grupos.map((g) => {
                    const cubierto = g.cupo_minimo > 0 && (g.inscripciones_count ?? 0) >= g.cupo_minimo;

                    return (
                        <tr key={g.id} className="transition-colors hover:bg-(--color-superficie-2)/60">
                            <td className="px-6 py-4">
                                <div className="text-sm font-semibold">{g.modulo?.nombre}</div>
                                <div className="font-mono text-xs text-(--color-texto-suave)">
                                    {g.modulo?.codigo}
                                    {g.modulo?.duracion_horas ? ` · ${g.modulo.duracion_horas}h` : ''}
                                </div>
                            </td>
                            <td className="px-6 py-4">
                                <div className="text-sm font-semibold text-(--color-acento)">{g.nombre}</div>
                                <div className="text-xs text-(--color-texto-suave)">
                                    {g.modalidad_label} · {g.docente ?? 'Sin asignar'}
                                </div>
                            </td>
                            <td className="px-6 py-4 text-xs text-(--color-texto-suave) whitespace-nowrap tabular-nums">
                                {g.periodo_texto || '—'}
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap">
                                <Insignia tono={tonoEstado(g.fase ?? g.estado)}>
                                    {g.fase_etiqueta ?? g.estado_label}
                                </Insignia>
                            </td>
                            <td className="px-6 py-4 text-sm whitespace-nowrap tabular-nums">
                                <span className="font-semibold">{g.inscripciones_count ?? 0}</span>
                                {g.cupo_minimo ? (
                                    <span className="ml-1 text-xs text-(--color-texto-suave)">
                                        (mín. {g.cupo_minimo})
                                    </span>
                                ) : null}
                                {cubierto && (
                                    <span className="ml-2 inline-flex items-center gap-0.5 rounded-full bg-(--color-exito-fondo) px-2 py-0.5 text-[11px] font-semibold text-(--color-exito)">
                                        <Icono nombre="check" className="h-3 w-3" />
                                        Cubierto
                                    </span>
                                )}
                            </td>
                            <td className="px-6 py-4 text-right whitespace-nowrap">
                                <div className="inline-flex items-center gap-2">
                                    <BotonAccion to={`/grupos/${g.id}/ver`} icono="usuarios">
                                        Ver
                                    </BotonAccion>
                                    <BotonAccion to={`/grupos/${g.id}`} icono="editar">
                                        Editar
                                    </BotonAccion>
                                </div>
                            </td>
                        </tr>
                    );
                })}
            </TablaDatos>
        </section>
    );
}
