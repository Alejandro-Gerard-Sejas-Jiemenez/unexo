import { useCallback, useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import cliente, { ErrorDeValidacion } from '../../api/cliente';
import Campo, { claseControl } from '../../componentes/Campo';

const VACIO = {
    codigo: '',
    nombre: '',
    version: '1.0',
    descripcion: '',
    estado_comercial: 'borrador',
    precio: '',
    requisitos_ingreso: '',
    certificado_otorga: '',
};

export default function ProgramaFormulario() {
    const { id } = useParams();
    const navegar = useNavigate();
    const editando = Boolean(id);

    const [datos, setDatos] = useState(VACIO);
    const [plan, setPlan] = useState([]);
    const [opciones, setOpciones] = useState({ estados: [], certificados: [], limites: {}, modulos_disponibles: [] });
    const [aAgregar, setAAgregar] = useState('');
    const [errores, setErrores] = useState({});
    const [aviso, setAviso] = useState(null);
    const [exito, setExito] = useState(null);
    const [cargando, setCargando] = useState(true);
    const [enviando, setEnviando] = useState(false);

    const cargarOpciones = useCallback(async () => {
        const { data } = await cliente.get('/catalogo/opciones', {
            params: editando ? { programa: id } : {},
        });
        setOpciones(data);
    }, [editando, id]);

    useEffect(() => {
        let vigente = true;

        (async () => {
            try {
                await cargarOpciones();

                if (editando) {
                    const { data } = await cliente.get(`/catalogo/programas/${id}`);
                    if (!vigente) return;
                    const p = data.data;
                    setDatos({
                        ...VACIO,
                        ...Object.fromEntries(
                            Object.keys(VACIO).map((c) => [c, p[c] ?? '']),
                        ),
                    });
                    setPlan(p.modulos ?? []);
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
    }, [cargarOpciones, editando, id]);

    const cambiar = (campo) => (e) => setDatos((p) => ({ ...p, [campo]: e.target.value }));

    async function enviar(e) {
        e.preventDefault();
        setErrores({});
        setAviso(null);
        setExito(null);
        setEnviando(true);

        try {
            if (editando) {
                const { data } = await cliente.put(`/catalogo/programas/${id}`, datos);
                setExito(data.mensaje);
            } else {
                const { data } = await cliente.post('/catalogo/programas', datos);
                navegar(`/app/programas/${data.programa.id}`);
            }
        } catch (error) {
            if (error instanceof ErrorDeValidacion) {
                setErrores(
                    Object.fromEntries(Object.entries(error.errores).map(([c, l]) => [c, l[0]])),
                );
                setAviso('Revisa los campos marcados.');
            } else {
                setAviso(error.message);
            }
        } finally {
            setEnviando(false);
        }
    }

    /** Las tres operaciones del plan devuelven el programa completo y recargado. */
    async function operarPlan(peticion) {
        setAviso(null);
        try {
            const { data } = await peticion();
            setPlan(data.programa.modulos ?? []);
            await cargarOpciones();
        } catch (error) {
            setAviso(error.errores?.modulo_id?.[0] ?? error.message);
        }
    }

    if (cargando) {
        return <p className="text-[var(--color-texto-suave)]">Cargando…</p>;
    }

    const limites = opciones.limites?.programas ?? {};

    return (
        <div className="max-w-3xl space-y-8">
            <section>
                <h1 className="mb-6 text-2xl font-semibold">
                    {editando ? 'Editar programa' : 'Nuevo programa'}
                </h1>

                {aviso && (
                    <p role="alert" className="mb-4 text-sm text-[var(--color-error)]">
                        {aviso}
                    </p>
                )}
                {exito && (
                    <p role="status" className="mb-4 text-sm text-[var(--color-exito)]">
                        {exito}
                    </p>
                )}

                <form onSubmit={enviar} noValidate className="superficie p-4">
                    <div className="grid grid-cols-3 gap-4">
                        <Campo id="codigo" etiqueta="Código" error={errores.codigo} requerido>
                            {(p) => (
                                <input {...p} type="text" maxLength={limites.codigo} className={claseControl}
                                    value={datos.codigo} onChange={cambiar('codigo')} />
                            )}
                        </Campo>
                        <Campo id="version" etiqueta="Versión" error={errores.version} requerido>
                            {(p) => (
                                <input {...p} type="text" maxLength={limites.version} className={claseControl}
                                    value={datos.version} onChange={cambiar('version')} />
                            )}
                        </Campo>
                        <Campo id="precio" etiqueta="Precio (Bs)" error={errores.precio}>
                            {(p) => (
                                <input {...p} type="number" min="0" step="0.01" className={claseControl}
                                    value={datos.precio} onChange={cambiar('precio')} />
                            )}
                        </Campo>
                    </div>

                    <Campo id="nombre" etiqueta="Nombre" error={errores.nombre} requerido
                        ayuda="El nombre no puede repetirse dentro de la misma versión.">
                        {(p) => (
                            <input {...p} type="text" maxLength={limites.nombre} className={claseControl}
                                value={datos.nombre} onChange={cambiar('nombre')} />
                        )}
                    </Campo>

                    <Campo id="descripcion" etiqueta="Descripción" error={errores.descripcion}>
                        {(p) => (
                            <textarea {...p} rows={3} maxLength={limites.descripcion} className={claseControl}
                                value={datos.descripcion} onChange={cambiar('descripcion')} />
                        )}
                    </Campo>

                    <div className="grid grid-cols-2 gap-4">
                        <Campo id="estado_comercial" etiqueta="Estado comercial" error={errores.estado_comercial}
                            requerido ayuda="Lo que queda abierto es lo que el CRM consulta por API.">
                            {(p) => (
                                <select {...p} className={claseControl} value={datos.estado_comercial}
                                    onChange={cambiar('estado_comercial')}>
                                    {opciones.estados.map((e) => (
                                        <option key={e.valor} value={e.valor}>{e.etiqueta}</option>
                                    ))}
                                </select>
                            )}
                        </Campo>

                        <Campo id="certificado_otorga" etiqueta="Certificado" error={errores.certificado_otorga}>
                            {(p) => (
                                <select {...p} className={claseControl} value={datos.certificado_otorga}
                                    onChange={cambiar('certificado_otorga')}>
                                    <option value="">No definido</option>
                                    {opciones.certificados.map((c) => (
                                        <option key={c.valor} value={c.valor}>{c.etiqueta}</option>
                                    ))}
                                </select>
                            )}
                        </Campo>
                    </div>

                    <Campo id="requisitos_ingreso" etiqueta="Requisitos de ingreso" error={errores.requisitos_ingreso}>
                        {(p) => (
                            <textarea {...p} rows={2} className={claseControl}
                                value={datos.requisitos_ingreso} onChange={cambiar('requisitos_ingreso')} />
                        )}
                    </Campo>

                    <div className="mt-4 flex gap-3">
                        <button type="submit" disabled={enviando}
                            className="rounded-lg bg-[var(--color-acento)] px-4 py-2 text-sm font-medium text-[var(--color-acento-texto)] disabled:opacity-60">
                            {enviando ? 'Guardando…' : 'Guardar'}
                        </button>
                        <button type="button" onClick={() => navegar('/app/programas')}
                            className="superficie px-4 py-2 text-sm">
                            Volver
                        </button>
                    </div>
                </form>
            </section>

            {editando && (
                <section>
                    <h2 className="mb-1 text-xl font-semibold">Plan de estudio</h2>
                    <p className="mb-4 text-sm text-[var(--color-texto-suave)]">
                        Un módulo puede pertenecer a varios programas. El orden es el del plan,
                        no una propiedad del módulo.
                    </p>

                    <div className="superficie mb-4 overflow-hidden">
                        <table className="w-full border-collapse text-sm">
                            <caption className="sr-only">Módulos del plan de estudio</caption>
                            <thead>
                                <tr className="border-b border-[var(--color-borde)] text-left">
                                    <th scope="col" className="p-3">#</th>
                                    <th scope="col" className="p-3">Módulo</th>
                                    <th scope="col" className="p-3">Horas</th>
                                    <th scope="col" className="p-3">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                {plan.length === 0 && (
                                    <tr>
                                        <td colSpan={4} className="p-6 text-center text-[var(--color-texto-suave)]">
                                            El programa todavía no tiene módulos.
                                        </td>
                                    </tr>
                                )}
                                {plan.map((m, i) => (
                                    <tr key={m.id} className="border-b border-[var(--color-borde)]">
                                        <td className="p-3 tabular-nums">{i + 1}</td>
                                        <td className="p-3">
                                            {m.nombre}{' '}
                                            <span className="text-[var(--color-texto-suave)]">({m.codigo})</span>
                                        </td>
                                        <td className="p-3 tabular-nums">{m.duracion_horas ?? '—'}</td>
                                        <td className="flex gap-3 p-3">
                                            <button type="button" disabled={i === 0}
                                                onClick={() => operarPlan(() => cliente.patch(
                                                    `/catalogo/programas/${id}/modulos/${m.id}/mover`,
                                                    { direccion: 'subir' },
                                                ))}
                                                className="border-0 bg-transparent p-0 underline disabled:opacity-40">
                                                Subir
                                            </button>
                                            <button type="button" disabled={i === plan.length - 1}
                                                onClick={() => operarPlan(() => cliente.patch(
                                                    `/catalogo/programas/${id}/modulos/${m.id}/mover`,
                                                    { direccion: 'bajar' },
                                                ))}
                                                className="border-0 bg-transparent p-0 underline disabled:opacity-40">
                                                Bajar
                                            </button>
                                            <button type="button"
                                                onClick={() => operarPlan(() => cliente.delete(
                                                    `/catalogo/programas/${id}/modulos/${m.id}`,
                                                ))}
                                                className="border-0 bg-transparent p-0 underline">
                                                Quitar
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    <div className="flex gap-3">
                        <label className="flex-1">
                            <span className="sr-only">Módulo a agregar</span>
                            <select className={claseControl} value={aAgregar}
                                onChange={(e) => setAAgregar(e.target.value)}>
                                <option value="">Elegí un módulo para agregar…</option>
                                {opciones.modulos_disponibles.map((m) => (
                                    <option key={m.id} value={m.id}>
                                        {m.nombre} ({m.codigo})
                                    </option>
                                ))}
                            </select>
                        </label>
                        <button type="button" disabled={!aAgregar}
                            onClick={async () => {
                                await operarPlan(() => cliente.post(
                                    `/catalogo/programas/${id}/modulos`,
                                    { modulo_id: aAgregar },
                                ));
                                setAAgregar('');
                            }}
                            className="rounded-lg bg-[var(--color-acento)] px-4 py-2 text-sm font-medium text-[var(--color-acento-texto)] disabled:opacity-60">
                            Agregar al plan
                        </button>
                    </div>
                </section>
            )}
        </div>
    );
}
