import { useCallback, useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import cliente, { ErrorDeValidacion } from '../../api/cliente';
import Campo, { claseControl } from '../../componentes/Campo';

const VACIO = {
    programa_id: '',
    nombre: '',
    fecha_inicio: '',
    fecha_fin: '',
    estado: 'programado',
};

/**
 * Alta y detalle de una edición. En el detalle se aperturan los grupos del plan
 * y se cambia el estado, opcionalmente propagándolo a los grupos.
 */
export default function EdicionDetalle() {
    const { id } = useParams();
    const navegar = useNavigate();
    const editando = Boolean(id);

    const [datos, setDatos] = useState(VACIO);
    const [edicion, setEdicion] = useState(null);
    const [pendientes, setPendientes] = useState([]);
    const [opciones, setOpciones] = useState({ programas: [], docentes: [], estados: [], modalidades: [] });
    const [apertura, setApertura] = useState({ docente_id: '', modalidad: 'virtual', cupo_minimo: '' });
    const [propagar, setPropagar] = useState(false);
    const [errores, setErrores] = useState({});
    const [aviso, setAviso] = useState(null);
    const [exito, setExito] = useState(null);
    const [cargando, setCargando] = useState(true);
    const [enviando, setEnviando] = useState(false);

    const cargarDetalle = useCallback(async () => {
        const { data } = await cliente.get(`/cohortes/${id}`);
        setEdicion(data.data);
        setPendientes(data.modulos_pendientes ?? []);
        setDatos({
            programa_id: data.data.programa_id,
            nombre: data.data.nombre,
            fecha_inicio: data.data.fecha_inicio ?? '',
            fecha_fin: data.data.fecha_fin ?? '',
            estado: data.data.estado,
        });
    }, [id]);

    useEffect(() => {
        let vigente = true;

        (async () => {
            try {
                const { data } = await cliente.get('/planificacion/opciones');
                if (!vigente) return;
                setOpciones(data);
                if (editando) await cargarDetalle();
            } catch (error) {
                if (vigente) setAviso(error.message);
            } finally {
                if (vigente) setCargando(false);
            }
        })();

        return () => {
            vigente = false;
        };
    }, [cargarDetalle, editando]);

    const cambiar = (campo) => (e) => setDatos((p) => ({ ...p, [campo]: e.target.value }));

    async function guardar(e) {
        e.preventDefault();
        setErrores({});
        setAviso(null);
        setExito(null);
        setEnviando(true);

        try {
            if (editando) {
                const { data } = await cliente.put(`/cohortes/${id}`, datos);
                setExito(data.mensaje);
                await cargarDetalle();
            } else {
                const { data } = await cliente.post('/cohortes', datos);
                navegar(`/app/cohortes/${data.cohorte.id}`);
            }
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

    async function aperturar() {
        setAviso(null);
        setExito(null);
        try {
            const { data } = await cliente.post(`/cohortes/${id}/aperturar-grupos`, apertura);
            setExito(data.mensaje);
            await cargarDetalle();
        } catch (error) {
            setAviso(error.errores ? Object.values(error.errores)[0][0] : error.message);
        }
    }

    async function cambiarEstado(estado) {
        setAviso(null);
        setExito(null);
        try {
            const { data } = await cliente.patch(`/cohortes/${id}/estado`, {
                estado,
                propagar_a_grupos: propagar,
            });
            setExito(data.mensaje);
            await cargarDetalle();
        } catch (error) {
            setAviso(error.message);
        }
    }

    if (cargando) return <p className="text-[var(--color-texto-suave)]">Cargando…</p>;

    return (
        <div className="max-w-4xl space-y-8">
            <section>
                <h1 className="mb-6 text-2xl font-semibold">
                    {editando ? edicion?.nombre : 'Nueva edición'}
                </h1>

                {aviso && (
                    <p role="alert" className="mb-4 text-sm text-[var(--color-error)]">{aviso}</p>
                )}
                {exito && (
                    <p role="status" className="mb-4 text-sm text-[var(--color-exito)]">{exito}</p>
                )}

                <form onSubmit={guardar} noValidate className="superficie p-4">
                    <Campo id="programa_id" etiqueta="Programa" error={errores.programa_id} requerido>
                        {(p) => (
                            <select {...p} className={claseControl} value={datos.programa_id}
                                onChange={cambiar('programa_id')}>
                                <option value="">Elegí un programa…</option>
                                {opciones.programas.map((pr) => (
                                    <option key={pr.id} value={pr.id}>
                                        {pr.nombre} (v{pr.version})
                                    </option>
                                ))}
                            </select>
                        )}
                    </Campo>

                    <Campo id="nombre" etiqueta="Nombre de la edición" error={errores.nombre}
                        requerido={editando}
                        ayuda={editando ? undefined : 'Si lo dejás vacío, el sistema lo genera.'}>
                        {(p) => (
                            <input {...p} type="text" className={claseControl}
                                value={datos.nombre} onChange={cambiar('nombre')} />
                        )}
                    </Campo>

                    <div className="grid grid-cols-3 gap-4">
                        <Campo id="fecha_inicio" etiqueta="Inicio" error={errores.fecha_inicio} requerido>
                            {(p) => (
                                <input {...p} type="date" className={claseControl}
                                    value={datos.fecha_inicio} onChange={cambiar('fecha_inicio')} />
                            )}
                        </Campo>
                        <Campo id="fecha_fin" etiqueta="Fin" error={errores.fecha_fin} requerido>
                            {(p) => (
                                <input {...p} type="date" className={claseControl}
                                    value={datos.fecha_fin} onChange={cambiar('fecha_fin')} />
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

                    <div className="mt-4 flex gap-3">
                        <button type="submit" disabled={enviando}
                            className="rounded-lg bg-[var(--color-acento)] px-4 py-2 text-sm font-medium text-[var(--color-acento-texto)] disabled:opacity-60">
                            {enviando ? 'Guardando…' : 'Guardar'}
                        </button>
                        <button type="button" onClick={() => navegar('/app/cohortes')}
                            className="superficie px-4 py-2 text-sm">
                            Volver
                        </button>
                    </div>
                </form>
            </section>

            {editando && (
                <>
                    <section>
                        <h2 className="mb-1 text-xl font-semibold">Apertura de grupos</h2>
                        <p className="mb-4 text-sm text-[var(--color-texto-suave)]">
                            Genera un grupo por cada módulo del plan que todavía no lo tenga.
                            {pendientes.length > 0
                                ? ` Faltan ${pendientes.length}: ${pendientes.map((m) => m.nombre).join(', ')}.`
                                : ' Todos los módulos del plan ya tienen grupo.'}
                        </p>

                        <div className="superficie flex flex-wrap items-end gap-3 p-4">
                            <label className="flex-1">
                                <span className="mb-1 block text-sm font-medium">Docente por defecto</span>
                                <select className={claseControl} value={apertura.docente_id}
                                    onChange={(e) => setApertura((p) => ({ ...p, docente_id: e.target.value }))}>
                                    <option value="">El sistema elige uno</option>
                                    {opciones.docentes.map((d) => (
                                        <option key={d.id} value={d.id}>{d.nombre}</option>
                                    ))}
                                </select>
                            </label>
                            <label>
                                <span className="mb-1 block text-sm font-medium">Modalidad</span>
                                <select className={claseControl} value={apertura.modalidad}
                                    onChange={(e) => setApertura((p) => ({ ...p, modalidad: e.target.value }))}>
                                    {opciones.modalidades.map((m) => (
                                        <option key={m.valor} value={m.valor}>{m.etiqueta}</option>
                                    ))}
                                </select>
                            </label>
                            <label>
                                <span className="mb-1 block text-sm font-medium">Cupo mínimo</span>
                                <input type="number" min="0" className={claseControl}
                                    value={apertura.cupo_minimo}
                                    onChange={(e) => setApertura((p) => ({ ...p, cupo_minimo: e.target.value }))} />
                            </label>
                            <button type="button" onClick={aperturar} disabled={pendientes.length === 0}
                                className="rounded-lg bg-[var(--color-acento)] px-4 py-2 text-sm font-medium text-[var(--color-acento-texto)] disabled:opacity-60">
                                Aperturar grupos
                            </button>
                        </div>
                    </section>

                    <section>
                        <h2 className="mb-4 text-xl font-semibold">
                            Grupos de la edición ({edicion?.grupos?.length ?? 0})
                        </h2>

                        <div className="superficie overflow-hidden">
                            <table className="w-full border-collapse text-sm">
                                <caption className="sr-only">Grupos operativos de esta edición</caption>
                                <thead>
                                    <tr className="border-b border-[var(--color-borde)] text-left">
                                        <th scope="col" className="p-3">Grupo</th>
                                        <th scope="col" className="p-3">Módulo</th>
                                        <th scope="col" className="p-3">Docente</th>
                                        <th scope="col" className="p-3">Modalidad</th>
                                        <th scope="col" className="p-3">Inscritos</th>
                                        <th scope="col" className="p-3">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {(edicion?.grupos ?? []).length === 0 && (
                                        <tr>
                                            <td colSpan={6} className="p-6 text-center text-[var(--color-texto-suave)]">
                                                Todavía no se aperturaron grupos.
                                            </td>
                                        </tr>
                                    )}
                                    {(edicion?.grupos ?? []).map((g) => (
                                        <tr key={g.id} className="border-b border-[var(--color-borde)]">
                                            <td className="p-3">{g.nombre}</td>
                                            <td className="p-3">{g.modulo?.nombre ?? '—'}</td>
                                            <td className="p-3">{g.docente ?? '—'}</td>
                                            <td className="p-3">{g.modalidad_label}</td>
                                            <td className="p-3 tabular-nums">
                                                {g.inscripciones_count ?? 0}
                                                {g.cupo_minimo ? ` / ${g.cupo_minimo}` : ''}
                                            </td>
                                            <td className="p-3">
                                                <Link to={`/app/grupos/${g.id}`}>Configurar</Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section>
                        <h2 className="mb-4 text-xl font-semibold">Cambiar estado</h2>
                        <div className="superficie p-4">
                            <label className="mb-3 flex items-center gap-2 text-sm">
                                <input type="checkbox" checked={propagar}
                                    onChange={(e) => setPropagar(e.target.checked)} />
                                Aplicar el mismo estado a todos los grupos de la edición
                            </label>
                            <div className="flex flex-wrap gap-2">
                                {opciones.estados.map((e) => (
                                    <button key={e.valor} type="button"
                                        disabled={edicion?.estado === e.valor}
                                        onClick={() => cambiarEstado(e.valor)}
                                        className="superficie px-3 py-1.5 text-sm disabled:opacity-40">
                                        {e.etiqueta}
                                    </button>
                                ))}
                            </div>
                        </div>
                    </section>
                </>
            )}
        </div>
    );
}
