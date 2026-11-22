import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import cliente, { ErrorDeValidacion } from '../../api/cliente';
import Campo, { claseControl } from '../../componentes/Campo';

/** CU6: matriculación flexible, a una edición completa o a un módulo suelto. */
export default function MatriculaNueva() {
    const navegar = useNavigate();
    const [datos, setDatos] = useState({ estudiante_id: '', tipo: 'cohorte', cohorte_id: '', grupo_id: '' });
    const [opciones, setOpciones] = useState({ estudiantes: [], cohortes: [], grupos_sueltos: [] });
    const [errores, setErrores] = useState({});
    const [aviso, setAviso] = useState(null);
    const [cargando, setCargando] = useState(true);
    const [enviando, setEnviando] = useState(false);

    useEffect(() => {
        let vigente = true;

        cliente
            .get('/matriculas/opciones')
            .then(({ data }) => vigente && setOpciones(data))
            .catch((e) => vigente && setAviso(e.message))
            .finally(() => vigente && setCargando(false));

        return () => {
            vigente = false;
        };
    }, []);

    const cambiar = (campo) => (e) => setDatos((p) => ({ ...p, [campo]: e.target.value }));

    async function enviar(e) {
        e.preventDefault();
        setErrores({});
        setAviso(null);
        setEnviando(true);

        // Se manda solo el destino que corresponde al tipo elegido.
        const cuerpo = {
            estudiante_id: datos.estudiante_id,
            tipo: datos.tipo,
            cohorte_id: datos.tipo === 'cohorte' ? datos.cohorte_id : null,
            grupo_id: datos.tipo === 'suelto' ? datos.grupo_id : null,
        };

        try {
            const { data } = await cliente.post('/matriculas', cuerpo);
            navegar(`/app/matriculas/${data.matricula.id}`);
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

    const aCohorte = datos.tipo === 'cohorte';

    return (
        <section className="max-w-2xl">
            <h1 className="mb-1 text-2xl font-semibold">Nueva matrícula</h1>
            <p className="mb-6 text-sm text-[var(--color-texto-suave)]">
                A una edición completa se generan las inscripciones de todos sus grupos;
                a un módulo suelto, solo la de ese grupo.
            </p>

            {aviso && <p role="alert" className="mb-4 text-sm text-[var(--color-error)]">{aviso}</p>}

            <form onSubmit={enviar} noValidate className="superficie p-4">
                <Campo id="estudiante_id" etiqueta="Estudiante" error={errores.estudiante_id} requerido>
                    {(p) => (
                        <select {...p} className={claseControl} value={datos.estudiante_id}
                            onChange={cambiar('estudiante_id')}>
                            <option value="">Elegí un estudiante…</option>
                            {opciones.estudiantes.map((e) => (
                                <option key={e.id} value={e.id}>{e.nombre} — {e.email}</option>
                            ))}
                        </select>
                    )}
                </Campo>

                <fieldset className="mb-4 border-0 p-0">
                    <legend className="mb-2 text-sm font-medium">Tipo de matriculación</legend>
                    <div className="flex gap-4">
                        <label className="flex items-center gap-2 text-sm">
                            <input type="radio" name="tipo" value="cohorte" checked={aCohorte}
                                onChange={cambiar('tipo')} />
                            Edición completa de un programa
                        </label>
                        <label className="flex items-center gap-2 text-sm">
                            <input type="radio" name="tipo" value="suelto" checked={!aCohorte}
                                onChange={cambiar('tipo')} />
                            Módulo suelto
                        </label>
                    </div>
                </fieldset>

                {aCohorte ? (
                    <Campo id="cohorte_id" etiqueta="Edición" error={errores.cohorte_id} requerido>
                        {(p) => (
                            <select {...p} className={claseControl} value={datos.cohorte_id}
                                onChange={cambiar('cohorte_id')}>
                                <option value="">Elegí una edición…</option>
                                {opciones.cohortes.map((c) => (
                                    <option key={c.id} value={c.id}>
                                        {c.nombre} — {c.programa} ({c.grupos} grupos)
                                    </option>
                                ))}
                            </select>
                        )}
                    </Campo>
                ) : (
                    <Campo id="grupo_id" etiqueta="Grupo suelto" error={errores.grupo_id} requerido>
                        {(p) => (
                            <select {...p} className={claseControl} value={datos.grupo_id}
                                onChange={cambiar('grupo_id')}>
                                <option value="">Elegí un grupo…</option>
                                {opciones.grupos_sueltos.map((g) => (
                                    <option key={g.id} value={g.id}>{g.nombre} — {g.modulo}</option>
                                ))}
                            </select>
                        )}
                    </Campo>
                )}

                <div className="mt-4 flex gap-3">
                    <button type="submit" disabled={enviando}
                        className="rounded-lg bg-[var(--color-acento)] px-4 py-2 text-sm font-medium text-[var(--color-acento-texto)] disabled:opacity-60">
                        {enviando ? 'Registrando…' : 'Registrar matrícula'}
                    </button>
                    <button type="button" onClick={() => navegar('/app/matriculas')}
                        className="superficie px-4 py-2 text-sm">
                        Cancelar
                    </button>
                </div>
            </form>
        </section>
    );
}
