import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import cliente, { ErrorDeValidacion } from '../../api/cliente';
import Campo, { claseControl } from '../../componentes/Campo';

const VACIO = {
    codigo: '',
    nombre: '',
    descripcion: '',
    prerrequisito: '',
    duracion_horas: '',
    estado_comercial: 'borrador',
    pdf_temario_url: '',
};

export default function ModuloFormulario() {
    const { id } = useParams();
    const navegar = useNavigate();
    const editando = Boolean(id);

    const [datos, setDatos] = useState(VACIO);
    const [opciones, setOpciones] = useState({ estados: [], limites: {} });
    const [errores, setErrores] = useState({});
    const [aviso, setAviso] = useState(null);
    const [cargando, setCargando] = useState(true);
    const [enviando, setEnviando] = useState(false);

    useEffect(() => {
        let vigente = true;

        (async () => {
            try {
                const peticiones = [cliente.get('/catalogo/opciones')];
                if (editando) peticiones.push(cliente.get(`/catalogo/modulos/${id}`));

                const [resOpciones, resModulo] = await Promise.all(peticiones);
                if (!vigente) return;

                setOpciones(resOpciones.data);

                if (resModulo) {
                    const m = resModulo.data.data;
                    setDatos({
                        ...VACIO,
                        ...Object.fromEntries(Object.keys(VACIO).map((c) => [c, m[c] ?? ''])),
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

    const cambiar = (campo) => (e) => setDatos((p) => ({ ...p, [campo]: e.target.value }));

    async function enviar(e) {
        e.preventDefault();
        setErrores({});
        setAviso(null);
        setEnviando(true);

        try {
            if (editando) {
                await cliente.put(`/catalogo/modulos/${id}`, datos);
            } else {
                await cliente.post('/catalogo/modulos', datos);
            }
            navegar('/app/modulos');
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

    if (cargando) {
        return <p className="text-[var(--color-texto-suave)]">Cargando…</p>;
    }

    const limites = opciones.limites?.modulos ?? {};

    return (
        <section className="max-w-2xl">
            <h1 className="mb-6 text-2xl font-semibold">
                {editando ? 'Editar módulo' : 'Nuevo módulo'}
            </h1>

            {aviso && (
                <p role="alert" className="mb-4 text-sm text-[var(--color-error)]">
                    {aviso}
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

                    <Campo id="duracion_horas" etiqueta="Duración (horas)" error={errores.duracion_horas}>
                        {(p) => (
                            <input {...p} type="number" min="1" max={limites.duracion_horas}
                                className={claseControl} value={datos.duracion_horas}
                                onChange={cambiar('duracion_horas')} />
                        )}
                    </Campo>

                    <Campo id="estado_comercial" etiqueta="Estado" error={errores.estado_comercial} requerido>
                        {(p) => (
                            <select {...p} className={claseControl} value={datos.estado_comercial}
                                onChange={cambiar('estado_comercial')}>
                                {opciones.estados.map((e) => (
                                    <option key={e.valor} value={e.valor}>{e.etiqueta}</option>
                                ))}
                            </select>
                        )}
                    </Campo>
                </div>

                <Campo id="nombre" etiqueta="Nombre" error={errores.nombre} requerido>
                    {(p) => (
                        <input {...p} type="text" maxLength={limites.nombre} className={claseControl}
                            value={datos.nombre} onChange={cambiar('nombre')} />
                    )}
                </Campo>

                <Campo id="descripcion" etiqueta="Descripción" error={errores.descripcion}>
                    {(p) => (
                        <textarea {...p} rows={3} className={claseControl}
                            value={datos.descripcion} onChange={cambiar('descripcion')} />
                    )}
                </Campo>

                <Campo
                    id="prerrequisito"
                    etiqueta="Conocimientos recomendados"
                    error={errores.prerrequisito}
                    ayuda="Es informativo: no bloquea la inscripción."
                >
                    {(p) => (
                        <textarea {...p} rows={2} maxLength={limites.prerrequisito} className={claseControl}
                            value={datos.prerrequisito} onChange={cambiar('prerrequisito')} />
                    )}
                </Campo>

                <Campo id="pdf_temario_url" etiqueta="Temario (URL)" error={errores.pdf_temario_url}>
                    {(p) => (
                        <input {...p} type="url" className={claseControl}
                            value={datos.pdf_temario_url} onChange={cambiar('pdf_temario_url')} />
                    )}
                </Campo>

                <div className="mt-4 flex gap-3">
                    <button type="submit" disabled={enviando}
                        className="rounded-lg bg-[var(--color-acento)] px-4 py-2 text-sm font-medium text-[var(--color-acento-texto)] disabled:opacity-60">
                        {enviando ? 'Guardando…' : 'Guardar'}
                    </button>
                    <button type="button" onClick={() => navegar('/app/modulos')}
                        className="superficie px-4 py-2 text-sm">
                        Cancelar
                    </button>
                </div>
            </form>
        </section>
    );
}
