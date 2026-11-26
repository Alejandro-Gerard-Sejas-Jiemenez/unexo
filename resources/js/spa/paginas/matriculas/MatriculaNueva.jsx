import { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import cliente, { ErrorDeValidacion } from '../../api/cliente';
import Campo, { claseControl } from '../../componentes/Campo';
import Cargador from '../../componentes/Cargador';
import EncabezadoPagina from '../../componentes/EncabezadoPagina';
import Icono from '../../componentes/Icono';
import { useToast } from '../../componentes/ToastContext';

export default function MatriculaNueva() {
    const navegar = useNavigate();
    const { mostrar } = useToast();
    const [opciones, setOpciones] = useState({ estudiantes: [], cohortes: [], grupos_sueltos: [] });
    const [datos, setDatos] = useState({ estudiante_id: '', tipo: 'cohorte', cohorte_id: '', grupo_id: '' });
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

        try {
            const { data } = await cliente.post('/matriculas', datos);
            mostrar(data.mensaje);
            navegar(`/matriculas/${data.matricula.id}`, { replace: true });
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

    const esCohorte = datos.tipo === 'cohorte';

    return (
        <section>
            <EncabezadoPagina
                volverA="/matriculas"
                volverTexto="Volver a matrículas"
                titulo="Nueva matrícula"
            />

            {aviso && (
                <p role="alert" className="superficie mb-4 border-[var(--color-error)] p-3 text-sm text-[var(--color-error)]">
                    {aviso}
                </p>
            )}

            <div className="superficie max-w-3xl overflow-hidden rounded-xl shadow-sm">
                <form onSubmit={enviar} noValidate className="p-6 sm:p-8">
                    <div className="mb-6 border-b border-[var(--color-borde)] pb-6">
                        <Campo id="estudiante_id" etiqueta="Estudiante" error={errores.estudiante_id} requerido esSelect>
                            {(p) => (
                                <select
                                    {...p}
                                    className={claseControl}
                                    value={datos.estudiante_id}
                                    onChange={cambiar('estudiante_id')}
                                >
                                    <option value="">Selecciona un estudiante</option>
                                    {opciones.estudiantes.map((e) => (
                                        <option key={e.id} value={e.id}>
                                            {e.nombre} ({e.email})
                                        </option>
                                    ))}
                                </select>
                            )}
                        </Campo>
                    </div>

                    <div className="grid grid-cols-1 gap-x-6 gap-y-4 md:grid-cols-2">
                        <Campo id="tipo" etiqueta="Tipo de matrícula" error={errores.tipo} requerido esSelect>
                            {(p) => (
                                <select {...p} className={claseControl} value={datos.tipo} onChange={cambiar('tipo')}>
                                    <option value="cohorte">Edición completa</option>
                                    <option value="suelto">Módulo suelto</option>
                                </select>
                            )}
                        </Campo>

                        {esCohorte ? (
                            opciones.cohortes.length === 0 ? (
                                <div className="rounded-lg border border-[color-mix(in_srgb,var(--color-aviso)_35%,transparent)] bg-[var(--color-aviso-fondo)] p-3 text-sm text-[var(--color-aviso)]">
                                    No hay ediciones disponibles.{' '}
                                    <Link to="/cohortes/nueva" className="font-semibold underline">
                                        Crear edición
                                    </Link>
                                </div>
                            ) : (
                                <Campo id="cohorte_id" etiqueta="Edición" error={errores.cohorte_id} requerido esSelect>
                                    {(p) => (
                                        <select
                                            {...p}
                                            className={claseControl}
                                            value={datos.cohorte_id}
                                            onChange={cambiar('cohorte_id')}
                                        >
                                            <option value="">Selecciona una edición</option>
                                            {opciones.cohortes.map((c) => (
                                                <option key={c.id} value={c.id}>
                                                    {c.programa ? `${c.programa} — ` : ''}
                                                    {c.nombre} ({c.grupos} grupos)
                                                </option>
                                            ))}
                                        </select>
                                    )}
                                </Campo>
                            )
                        ) : opciones.grupos_sueltos.length === 0 ? (
                            <div className="rounded-lg border border-[color-mix(in_srgb,var(--color-aviso)_35%,transparent)] bg-[var(--color-aviso-fondo)] p-3 text-sm text-[var(--color-aviso)]">
                                No hay grupos sueltos disponibles.{' '}
                                <Link to="/grupos/nuevo" className="font-semibold underline">
                                    Crear grupo suelto
                                </Link>
                            </div>
                        ) : (
                            <Campo id="grupo_id" etiqueta="Grupo suelto" error={errores.grupo_id} requerido esSelect>
                                {(p) => (
                                    <select
                                        {...p}
                                        className={claseControl}
                                        value={datos.grupo_id}
                                        onChange={cambiar('grupo_id')}
                                    >
                                        <option value="">Selecciona un grupo</option>
                                        {opciones.grupos_sueltos.map((g) => (
                                            <option key={g.id} value={g.id}>
                                                {g.modulo ? `${g.modulo} — ` : ''}
                                                {g.nombre}
                                            </option>
                                        ))}
                                    </select>
                                )}
                            </Campo>
                        )}
                    </div>

                    <div className="mt-4 flex items-start gap-3 rounded-xl border border-[color-mix(in_srgb,var(--color-acento)_25%,transparent)] bg-[var(--color-acento-suave)]/70 p-4 text-xs text-[var(--color-texto-suave)] sm:text-sm">
                        <Icono nombre="info" className="mt-0.5 h-5 w-5 shrink-0 text-[var(--color-acento)]" />
                        <p>
                            La matrícula se registrará como <b>Pendiente de pago</b>. Desde su ficha podrás aprobar
                            el comprobante subido, registrar un cobro único o generar un plan de cuotas.
                        </p>
                    </div>

                    <div className="form-acciones">
                        <button type="button" onClick={() => navegar('/matriculas')} className="btn btn-secundario">
                            Cancelar
                        </button>
                        <button type="submit" disabled={enviando} className="btn btn-primario">
                            <Icono nombre="guardar" />
                            {enviando ? 'Matriculando…' : 'Registrar matrícula'}
                        </button>
                    </div>
                </form>
            </div>
        </section>
    );
}
