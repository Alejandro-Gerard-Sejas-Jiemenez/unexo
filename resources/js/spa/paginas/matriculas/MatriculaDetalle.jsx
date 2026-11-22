import { useCallback, useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import cliente from '../../api/cliente';
import { claseControl } from '../../componentes/Campo';

/**
 * CU7: estado de la matrícula y sus pagos.
 * Reúne en una pantalla lo que el administrador necesita para cerrar el ciclo
 * comercial de un estudiante.
 */
export default function MatriculaDetalle() {
    const { id } = useParams();
    const navegar = useNavigate();

    const [matricula, setMatricula] = useState(null);
    const [pagos, setPagos] = useState({ data: [], resumen: null });
    const [metodos, setMetodos] = useState([]);
    const [cobro, setCobro] = useState({ monto: '', cuotas: '', metodo_pago_id: '' });
    const [aviso, setAviso] = useState(null);
    const [exito, setExito] = useState(null);
    const [cargando, setCargando] = useState(true);

    const cargar = useCallback(async () => {
        const [resM, resP, resMet] = await Promise.all([
            cliente.get(`/matriculas/${id}`),
            cliente.get(`/matriculas/${id}/transacciones`),
            cliente.get('/metodos-pago'),
        ]);
        setMatricula(resM.data.data);
        setPagos({ data: resP.data.data, resumen: resP.data.resumen });
        setMetodos(resMet.data.data);
    }, [id]);

    useEffect(() => {
        let vigente = true;

        cargar()
            .catch((e) => vigente && setAviso(e.message))
            .finally(() => vigente && setCargando(false));

        return () => {
            vigente = false;
        };
    }, [cargar]);

    async function accion(peticion) {
        setAviso(null);
        setExito(null);
        try {
            const { data } = await peticion();
            setExito(data.mensaje);
            await cargar();
        } catch (error) {
            setAviso(error.errores ? Object.values(error.errores)[0][0] : error.message);
        }
    }

    if (cargando) return <p className="text-[var(--color-texto-suave)]">Cargando…</p>;
    if (!matricula) return <p role="alert" className="text-[var(--color-error)]">{aviso}</p>;

    const resumen = pagos.resumen ?? {};

    return (
        <div className="max-w-4xl space-y-8">
            <section>
                <div className="mb-4 flex items-start justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">{matricula.estudiante?.nombre}</h1>
                        <p className="text-sm text-[var(--color-texto-suave)]">
                            {matricula.cohorte?.nombre ?? 'Módulo suelto'} ·{' '}
                            {matricula.origen_label} · matriculado el {matricula.fecha_matricula}
                        </p>
                    </div>
                    <button type="button" onClick={() => navegar('/app/matriculas')}
                        className="superficie px-4 py-2 text-sm">
                        Volver
                    </button>
                </div>

                {aviso && <p role="alert" className="mb-4 text-sm text-[var(--color-error)]">{aviso}</p>}
                {exito && <p role="status" className="mb-4 text-sm text-[var(--color-exito)]">{exito}</p>}

                <div className="grid grid-cols-3 gap-3">
                    <div className="superficie p-4">
                        <p className="text-xs tracking-wide text-[var(--color-texto-suave)] uppercase">Estado</p>
                        <p className="mt-1 text-xl font-semibold">{matricula.estado_label}</p>
                    </div>
                    <div className="superficie p-4">
                        <p className="text-xs tracking-wide text-[var(--color-texto-suave)] uppercase">Pagado</p>
                        <p className="mt-1 text-xl font-semibold tabular-nums">
                            Bs {Number(resumen.pagado ?? 0).toFixed(2)}
                        </p>
                    </div>
                    <div className="superficie p-4">
                        <p className="text-xs tracking-wide text-[var(--color-texto-suave)] uppercase">Pendiente</p>
                        <p className="mt-1 text-xl font-semibold tabular-nums">
                            Bs {Number(resumen.pendiente ?? 0).toFixed(2)}
                        </p>
                    </div>
                </div>
            </section>

            <section>
                <h2 className="mb-3 text-xl font-semibold">Inscripciones</h2>
                <div className="superficie overflow-hidden">
                    <table className="w-full border-collapse text-sm">
                        <caption className="sr-only">Grupos en los que está inscrito</caption>
                        <thead>
                            <tr className="border-b border-[var(--color-borde)] text-left">
                                <th scope="col" className="p-3">Grupo</th>
                                <th scope="col" className="p-3">Módulo</th>
                                <th scope="col" className="p-3">Docente</th>
                            </tr>
                        </thead>
                        <tbody>
                            {(matricula.inscripciones ?? []).length === 0 && (
                                <tr>
                                    <td colSpan={3} className="p-6 text-center text-[var(--color-texto-suave)]">
                                        Sin inscripciones.
                                    </td>
                                </tr>
                            )}
                            {(matricula.inscripciones ?? []).map((i) => (
                                <tr key={i.id} className="border-b border-[var(--color-borde)]">
                                    <td className="p-3">{i.grupo ?? '—'}</td>
                                    <td className="p-3">{i.modulo ?? '—'}</td>
                                    <td className="p-3">{i.docente ?? '—'}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </section>

            <section>
                <h2 className="mb-3 text-xl font-semibold">Pagos</h2>

                <div className="superficie mb-4 overflow-hidden">
                    <table className="w-full border-collapse text-sm">
                        <caption className="sr-only">Transacciones de la matrícula</caption>
                        <thead>
                            <tr className="border-b border-[var(--color-borde)] text-left">
                                <th scope="col" className="p-3">Concepto</th>
                                <th scope="col" className="p-3">Monto</th>
                                <th scope="col" className="p-3">Referencia</th>
                                <th scope="col" className="p-3">Estado</th>
                                <th scope="col" className="p-3">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            {pagos.data.length === 0 && (
                                <tr>
                                    <td colSpan={5} className="p-6 text-center text-[var(--color-texto-suave)]">
                                        Todavía no hay pagos registrados.
                                    </td>
                                </tr>
                            )}
                            {pagos.data.map((t) => (
                                <tr key={t.id} className="border-b border-[var(--color-borde)]">
                                    <td className="p-3">{t.concepto}</td>
                                    <td className="p-3 tabular-nums">Bs {t.monto.toFixed(2)}</td>
                                    <td className="p-3 text-xs">{t.referencia}</td>
                                    <td className="p-3">{t.estado_label}</td>
                                    <td className="flex gap-3 p-3">
                                        {t.estado === 'pendiente' && (
                                            <>
                                                <button type="button"
                                                    onClick={() => accion(() => cliente.patch(`/transacciones/${t.id}/aprobar`))}
                                                    className="border-0 bg-transparent p-0 underline">
                                                    Aprobar
                                                </button>
                                                <button type="button"
                                                    onClick={() => accion(() => cliente.patch(`/transacciones/${t.id}/rechazar`))}
                                                    className="border-0 bg-transparent p-0 underline">
                                                    Rechazar
                                                </button>
                                            </>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <div className="superficie flex flex-wrap items-end gap-3 p-4">
                    <label>
                        <span className="mb-1 block text-sm font-medium">Monto (Bs)</span>
                        <input type="number" min="0.01" step="0.01" className={claseControl}
                            value={cobro.monto}
                            onChange={(e) => setCobro((p) => ({ ...p, monto: e.target.value }))} />
                    </label>
                    <label>
                        <span className="mb-1 block text-sm font-medium">Cuotas</span>
                        <input type="number" min="2" max="36" placeholder="Pago único"
                            className={claseControl} value={cobro.cuotas}
                            onChange={(e) => setCobro((p) => ({ ...p, cuotas: e.target.value }))} />
                    </label>
                    <label className="flex-1">
                        <span className="mb-1 block text-sm font-medium">Método de pago</span>
                        <select className={claseControl} value={cobro.metodo_pago_id}
                            onChange={(e) => setCobro((p) => ({ ...p, metodo_pago_id: e.target.value }))}>
                            <option value="">Sin método asociado</option>
                            {metodos.map((m) => (
                                <option key={m.id} value={m.id}>{m.descripcion}</option>
                            ))}
                        </select>
                    </label>
                    <button type="button" disabled={!cobro.monto}
                        onClick={() => accion(() => cliente.post(`/matriculas/${id}/cobrar`, {
                            monto: Number(cobro.monto),
                            cuotas: cobro.cuotas ? Number(cobro.cuotas) : null,
                            metodo_pago_id: cobro.metodo_pago_id || null,
                        }))}
                        className="rounded-lg bg-[var(--color-acento)] px-4 py-2 text-sm font-medium text-[var(--color-acento-texto)] disabled:opacity-60">
                        Registrar cobro
                    </button>
                </div>
                <p className="mt-2 text-xs text-[var(--color-texto-suave)]">
                    Sin cuotas es un pago único que se aprueba al instante. Con cuotas se genera
                    el plan de pagos y la matrícula se activa al aprobarse la última.
                </p>
            </section>

            <section>
                <h2 className="mb-3 text-xl font-semibold">Comprobantes subidos</h2>
                <div className="superficie overflow-hidden">
                    <table className="w-full border-collapse text-sm">
                        <caption className="sr-only">Comprobantes de pago</caption>
                        <thead>
                            <tr className="border-b border-[var(--color-borde)] text-left">
                                <th scope="col" className="p-3">Subido</th>
                                <th scope="col" className="p-3">Por</th>
                                <th scope="col" className="p-3">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            {(matricula.comprobantes ?? []).length === 0 && (
                                <tr>
                                    <td colSpan={3} className="p-6 text-center text-[var(--color-texto-suave)]">
                                        El estudiante no subió ningún comprobante.
                                    </td>
                                </tr>
                            )}
                            {(matricula.comprobantes ?? []).map((c) => (
                                <tr key={c.id} className="border-b border-[var(--color-borde)]">
                                    <td className="p-3 tabular-nums">{c.creado}</td>
                                    <td className="p-3">{c.subido_por ?? '—'}</td>
                                    <td className="p-3">{c.estado}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </section>

            <section>
                <h2 className="mb-3 text-xl font-semibold">Cambiar estado</h2>
                <div className="superficie flex flex-wrap gap-2 p-4">
                    {[
                        ['pendiente', 'Pendiente de pago'],
                        ['activo', 'Activa (pagada)'],
                        ['retirado', 'Retirada'],
                    ].map(([valor, etiqueta]) => (
                        <button key={valor} type="button" disabled={matricula.estado === valor}
                            onClick={() => accion(() => cliente.patch(`/matriculas/${id}/estado`, { estado: valor }))}
                            className="superficie px-3 py-1.5 text-sm disabled:opacity-40">
                            {etiqueta}
                        </button>
                    ))}
                </div>
                <p className="mt-2 text-xs text-[var(--color-texto-suave)]">
                    Retirar conserva todo el historial de la matrícula.
                </p>
            </section>
        </div>
    );
}
