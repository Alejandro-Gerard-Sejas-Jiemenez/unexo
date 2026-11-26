import { useCallback, useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import cliente, { ErrorDeValidacion } from '../../api/cliente';
import Campo, { claseControl } from '../../componentes/Campo';
import Cargador from '../../componentes/Cargador';
import DialogoConfirmacion from '../../componentes/DialogoConfirmacion';
import Icono from '../../componentes/Icono';
import Insignia, { tonoEstado } from '../../componentes/Insignia';
import TablaDatos from '../../componentes/TablaDatos';
import { useToast } from '../../componentes/ToastContext';
import { formatoFecha } from '../../lib/fechas';

export default function MatriculaDetalle() {
    const { id } = useParams();
    const { mostrar } = useToast();

    const [matricula, setMatricula] = useState(null);
    const [pagos, setPagos] = useState([]);
    const [resumen, setResumen] = useState(null);
    const [pasarela, setPasarela] = useState(null);
    const [metodosEstudiante, setMetodosEstudiante] = useState([]);
    const [cobro, setCobro] = useState({ monto: '', cuotas: '', metodo_pago_id: '' });
    const [errores, setErrores] = useState({});
    const [aviso, setAviso] = useState(null);
    const [cargando, setCargando] = useState(true);
    const [enviandoAccion, setEnviandoAccion] = useState(false);
    const [confirmacion, setConfirmacion] = useState(null);

    const cargar = useCallback(async () => {
        try {
            const [resM, resP] = await Promise.all([
                cliente.get(`/matriculas/${id}`),
                cliente.get(`/matriculas/${id}/transacciones`),
            ]);
            const m = resM.data.data;
            setMatricula(m);
            setPagos(resP.data.data ?? []);
            setResumen(resP.data.resumen ?? null);
            setPasarela(resP.data.pasarela ?? null);
            setMetodosEstudiante(resP.data.metodos_estudiante ?? []);
            setCobro((prev) => ({
                ...prev,
                monto: prev.monto || (m.importe ? String(m.importe) : ''),
            }));
        } catch (error) {
            setAviso(error.message);
        } finally {
            setCargando(false);
        }
    }, [id]);

    useEffect(() => {
        cargar();
    }, [cargar]);

    async function ejecutarConfirmacion() {
        if (!confirmacion || enviandoAccion) return;
        const accion = confirmacion;
        setConfirmacion(null);
        setAviso(null);
        setEnviandoAccion(true);

        try {
            if (accion.tipo === 'retirar') {
                const { data } = await cliente.patch(`/matriculas/${id}/estado`, { estado: 'retirado' });
                mostrar(data.mensaje);
            } else if (accion.tipo === 'rechazar_comprobante') {
                const { data } = await cliente.patch(`/comprobantes/${accion.id}/rechazar`);
                mostrar(data.mensaje);
            }
            await cargar();
        } catch (error) {
            setAviso(error instanceof ErrorDeValidacion ? (Object.values(error.errores).flat()[0] ?? error.message) : error.message);
        } finally {
            setEnviandoAccion(false);
        }
    }

    async function cambiarEstado(estado) {
        if (enviandoAccion) return;
        if (estado === 'retirado') {
            setConfirmacion({
                tipo: 'retirar',
                titulo: '¿Retirar al estudiante de esta matrícula?',
                mensaje: 'Se conserva el historial académico y se cerrará el acceso a los grupos.',
                confirmarTexto: 'Sí, retirar estudiante',
            });
            return;
        }
        setAviso(null);
        setEnviandoAccion(true);
        try {
            const { data } = await cliente.patch(`/matriculas/${id}/estado`, { estado });
            mostrar(data.mensaje);
            await cargar();
        } catch (error) {
            setAviso(error instanceof ErrorDeValidacion ? (Object.values(error.errores).flat()[0] ?? error.message) : error.message);
        } finally {
            setEnviandoAccion(false);
        }
    }

    async function registrarCobro(e) {
        e.preventDefault();
        if (enviandoAccion) return;
        setErrores({});
        setAviso(null);
        setEnviandoAccion(true);
        try {
            const { data } = await cliente.post(`/matriculas/${id}/cobrar`, {
                monto: cobro.monto,
                cuotas: cobro.cuotas || null,
                metodo_pago_id: cobro.metodo_pago_id || null,
            });
            mostrar(data.mensaje);
            setCobro((c) => ({ ...c, cuotas: '' }));
            await cargar();
        } catch (error) {
            if (error instanceof ErrorDeValidacion) {
                setErrores(Object.fromEntries(Object.entries(error.errores).map(([c, l]) => [c, l[0]])));
                setAviso('Revisa los datos del cobro.');
            } else {
                setAviso(error.message);
            }
        } finally {
            setEnviandoAccion(false);
        }
    }

    async function resolverPago(transaccionId, accion) {
        if (enviandoAccion) return;
        setAviso(null);
        setEnviandoAccion(true);
        try {
            const { data } = await cliente.patch(`/transacciones/${transaccionId}/${accion}`);
            mostrar(data.mensaje);
            await cargar();
        } catch (error) {
            setAviso(error instanceof ErrorDeValidacion ? (Object.values(error.errores).flat()[0] ?? error.message) : error.message);
        } finally {
            setEnviandoAccion(false);
        }
    }

    async function aprobarComprobante(comprobanteId) {
        if (enviandoAccion) return;
        setAviso(null);
        setEnviandoAccion(true);
        try {
            const { data } = await cliente.patch(`/comprobantes/${comprobanteId}/aprobar`);
            mostrar(data.mensaje);
            await cargar();
        } catch (error) {
            setAviso(error instanceof ErrorDeValidacion ? (Object.values(error.errores).flat()[0] ?? error.message) : error.message);
        } finally {
            setEnviandoAccion(false);
        }
    }

    if (cargando) return <Cargador texto="Cargando" />;
    if (!matricula) {
        return (
            <p role="alert" className="superficie border-(--color-error) p-4 text-sm text-(--color-error)">
                {aviso ?? 'No se encontró la matrícula.'}
            </p>
        );
    }

    const estaRetirado = matricula.estado === 'retirado';
    const moneda = pasarela?.moneda ?? 'BOB';
    const inscripciones = matricula.inscripciones ?? [];
    const comprobantes = matricula.comprobantes ?? [];

    return (
        <section>
            <div className="mb-4">
                <Link
                    to="/matriculas"
                    className="inline-flex items-center gap-1.5 text-sm font-medium text-(--color-texto-suave) no-underline transition-colors hover:text-(--color-acento)"
                >
                    <Icono nombre="volver" className="h-4 w-4" />
                    Volver a matrículas
                </Link>
            </div>

            <div className="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                <div>
                    <h1 className="text-3xl font-extrabold tracking-tight">{matricula.estudiante?.nombre}</h1>
                    <p className="mt-2 text-lg text-(--color-texto-suave)">
                        {matricula.cohorte
                            ? `${matricula.cohorte.programa ? `${matricula.cohorte.programa} · ` : ''}${matricula.cohorte.nombre}`
                            : 'Módulo suelto'}
                    </p>
                </div>

                <div>
                    <Insignia tono={tonoEstado(matricula.estado)} className="px-3! py-1! text-sm! shadow-sm">
                        {matricula.estado_label}
                    </Insignia>
                </div>
            </div>

            {aviso && (
                <p role="alert" className="superficie mb-6 border-(--color-error) p-3 text-sm text-(--color-error)">
                    {aviso}
                </p>
            )}

            <div className="mb-10 grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div className="lg:col-span-2">
                    <div className="superficie h-full overflow-hidden rounded-xl shadow-sm">
                        <div className="border-b border-(--color-borde) bg-(--color-superficie-cabecera) px-6 py-5">
                            <h2 className="text-lg font-semibold">Detalles de la matrícula</h2>
                        </div>
                        <div className="p-6">
                            <dl className="grid grid-cols-1 gap-x-6 gap-y-6 sm:grid-cols-2">
                                <div>
                                    <dt className="mb-1 text-sm font-medium text-(--color-texto-suave)">
                                        Correo institucional
                                    </dt>
                                    <dd className="text-base break-all">
                                        {matricula.estudiante?.correo_institucional || matricula.estudiante?.email || '—'}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="mb-1 text-sm font-medium text-(--color-texto-suave)">
                                        Origen
                                    </dt>
                                    <dd className="text-base">{matricula.origen_label}</dd>
                                </div>
                                <div>
                                    <dt className="mb-1 text-sm font-medium text-(--color-texto-suave)">
                                        Fecha de matrícula
                                    </dt>
                                    <dd className="text-base tabular-nums">{formatoFecha(matricula.fecha_matricula)}</dd>
                                </div>
                                {matricula.fecha_retiro && (
                                    <div>
                                        <dt className="mb-1 text-sm font-medium text-(--color-texto-suave)">
                                            Fecha de retiro
                                        </dt>
                                        <dd className="text-base tabular-nums">{formatoFecha(matricula.fecha_retiro)}</dd>
                                    </div>
                                )}
                                {resumen && (
                                    <>
                                        <div>
                                            <dt className="mb-1 text-sm font-medium text-(--color-texto-suave)">
                                                Total pagado
                                            </dt>
                                            <dd className="text-base font-semibold text-(--color-exito) tabular-nums">
                                                {Number(resumen.pagado ?? 0).toFixed(2)} {moneda}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="mb-1 text-sm font-medium text-(--color-texto-suave)">
                                                Saldo pendiente
                                            </dt>
                                            <dd className="text-base font-semibold tabular-nums">
                                                {Number(resumen.pendiente ?? 0).toFixed(2)} {moneda}
                                            </dd>
                                        </div>
                                    </>
                                )}
                            </dl>
                        </div>
                    </div>
                </div>

                <div className="lg:col-span-1">
                    <div className="superficie h-full overflow-hidden rounded-xl shadow-sm">
                        <div className="border-b border-(--color-borde) bg-(--color-superficie-cabecera) px-6 py-5">
                            <h2 className="text-lg font-semibold">Acciones</h2>
                        </div>
                        <div className="space-y-3 p-6">
                            {estaRetirado && (
                                <p className="text-sm text-(--color-texto-suave)">
                                    Matrícula retirada: se conserva como historial y no admite cambios.
                                </p>
                            )}

                            {matricula.estado === 'pendiente' && (
                                <button
                                    type="button"
                                    onClick={() => cambiarEstado('activo')}
                                    className="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-(--color-exito) px-4 py-2.5 text-sm font-medium text-white shadow-sm transition-colors hover:brightness-95"
                                >
                                    <Icono nombre="usuario-activar" className="h-4 w-4" />
                                    Aprobar pago (activar)
                                </button>
                            )}

                            {matricula.estado === 'activo' && (
                                <button
                                    type="button"
                                    onClick={() => cambiarEstado('pendiente')}
                                    className="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-[color-mix(in_srgb,var(--color-aviso)_35%,transparent)] bg-(--color-aviso-fondo) px-4 py-2.5 text-sm font-medium text-(--color-aviso) shadow-sm transition-colors hover:brightness-95"
                                >
                                    Marcar pendiente
                                </button>
                            )}

                            {!estaRetirado && (
                                <button
                                    type="button"
                                    onClick={() => cambiarEstado('retirado')}
                                    className="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-lg border border-[color-mix(in_srgb,var(--color-error)_35%,transparent)] bg-(--color-superficie) px-4 py-2.5 text-sm font-medium text-(--color-error) shadow-sm transition-colors hover:bg-(--color-error-fondo)"
                                >
                                    <Icono nombre="usuario-desactivar" className="h-4 w-4" />
                                    Retirar
                                </button>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            <div className="mb-6">
                <h2 className="text-2xl font-bold">Comprobantes de pago</h2>
            </div>
            <div className="superficie mb-10 overflow-hidden rounded-xl shadow-sm">
                {comprobantes.length === 0 ? (
                    <p className="px-6 py-8 text-center text-sm text-(--color-texto-suave)">
                        El estudiante todavía no subió ningún comprobante.
                    </p>
                ) : (
                    comprobantes.map((c, idx) => (
                        <div
                            key={c.id}
                            className={`flex flex-wrap items-center gap-3 px-6 py-4 text-sm ${
                                idx < comprobantes.length - 1 ? 'border-b border-(--color-borde)' : ''
                            }`}
                        >
                            <a
                                href={c.url_archivo}
                                target="_blank"
                                rel="noreferrer"
                                className="font-medium text-(--color-acento) hover:underline"
                            >
                                Ver comprobante
                            </a>
                            <span className="text-(--color-texto-suave)">
                                {c.creado} · {c.subido_por}
                            </span>
                            <Insignia tono={tonoEstado(c.estado)}>{c.estado_label}</Insignia>
                            {c.estado === 'pendiente' && (
                                <div className="ml-auto flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        onClick={() => aprobarComprobante(c.id)}
                                        className="rounded-lg bg-(--color-exito) px-3 py-1.5 text-xs font-semibold text-white transition-colors hover:brightness-95"
                                    >
                                        Aprobar pago
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() =>
                                            setConfirmacion({
                                                tipo: 'rechazar_comprobante',
                                                id: c.id,
                                                titulo: '¿Rechazar este comprobante?',
                                                mensaje: 'El estudiante deberá subir un nuevo comprobante válido.',
                                                confirmarTexto: 'Rechazar',
                                            })
                                        }
                                        className="rounded-lg border border-[color-mix(in_srgb,var(--color-error)_35%,transparent)] px-3 py-1.5 text-xs font-semibold text-(--color-error) transition-colors hover:bg-(--color-error-fondo)"
                                    >
                                        Rechazar
                                    </button>
                                </div>
                            )}
                        </div>
                    ))
                )}
            </div>

            <div className="mb-6 flex items-center gap-3">
                <h2 className="text-2xl font-bold">Grupos de la matrícula</h2>
                <span className="inline-flex items-center justify-center rounded-full bg-(--color-superficie-2) px-2.5 py-0.5 text-sm font-medium text-(--color-texto-suave)">
                    {inscripciones.length}
                </span>
            </div>

            <div className="mb-10">
                <TablaDatos
                    caption="Grupos inscritos en la matrícula"
                    columnas={[
                        { etiqueta: 'Módulo' },
                        { etiqueta: 'Grupo' },
                        { etiqueta: 'Docente' },
                        { etiqueta: 'Fechas' },
                    ]}
                    vacio={inscripciones.length === 0}
                    vacioIcono="catalogo"
                    vacioTitulo="Sin grupos inscritos"
                    vacioDescripcion="Esta matrícula no tiene grupos asignados actualmente."
                >
                    {inscripciones.map((i) => (
                        <tr key={i.id} className="transition-colors hover:bg-(--color-superficie-2)/60">
                            <td className="px-6 py-4 text-sm font-medium">{i.modulo ?? '—'}</td>
                            <td className="px-6 py-4 text-sm">{i.grupo ?? '—'}</td>
                            <td className="px-6 py-4 text-sm text-(--color-texto-suave)">
                                {i.docente ?? 'Sin asignar'}
                            </td>
                            <td className="px-6 py-4 text-sm text-(--color-texto-suave) tabular-nums">
                                {i.periodo_texto ?? '—'}
                            </td>
                        </tr>
                    ))}
                </TablaDatos>
            </div>

            {!estaRetirado && (
                <div className="superficie mb-10 overflow-hidden rounded-xl shadow-sm">
                    <div className="flex flex-wrap items-center justify-between gap-2 border-b border-(--color-borde) bg-(--color-superficie-cabecera) px-6 py-4">
                        <h2 className="text-lg font-semibold">Cobrar o generar plan de cuotas</h2>
                        {pasarela && (
                            <span className="text-xs text-(--color-texto-suave)">
                                Pasarela activa: <b>{pasarela.driver}</b> ({moneda})
                            </span>
                        )}
                    </div>

                    <form onSubmit={registrarCobro} noValidate className="p-6">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <Campo id="monto" etiqueta={`Monto total (${moneda})`} error={errores.monto} requerido>
                                {(p) => (
                                    <input
                                        {...p}
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        className={claseControl}
                                        value={cobro.monto}
                                        onChange={(e) => setCobro((c) => ({ ...c, monto: e.target.value }))}
                                    />
                                )}
                            </Campo>

                            <Campo id="cuotas" etiqueta="Cuotas (vacío = pago único)" error={errores.cuotas}>
                                {(p) => (
                                    <input
                                        {...p}
                                        type="number"
                                        min="2"
                                        max="36"
                                        placeholder="Pago único"
                                        className={claseControl}
                                        value={cobro.cuotas}
                                        onChange={(e) => setCobro((c) => ({ ...c, cuotas: e.target.value }))}
                                    />
                                )}
                            </Campo>

                            <Campo id="metodo_pago_id" etiqueta="Método del estudiante" error={errores.metodo_pago_id} esSelect>
                                {(p) => (
                                    <select
                                        {...p}
                                        className={claseControl}
                                        value={cobro.metodo_pago_id}
                                        onChange={(e) => setCobro((c) => ({ ...c, metodo_pago_id: e.target.value }))}
                                    >
                                        <option value="">Sin método (queda por pagar)</option>
                                        {metodosEstudiante.map((m) => (
                                            <option key={m.id} value={m.id}>
                                                {m.descripcion} — {m.titular}
                                            </option>
                                        ))}
                                    </select>
                                )}
                            </Campo>
                        </div>

                        <button type="submit" disabled={enviandoAccion} className="btn btn-primario mt-2">
                            <Icono nombre="tarjeta" />
                            {enviandoAccion ? 'Procesando…' : cobro.cuotas ? 'Generar plan de cuotas' : 'Registrar cargo'}
                        </button>
                    </form>
                </div>
            )}

            <div className="mb-4">
                <h2 className="text-2xl font-bold">Transacciones</h2>
            </div>
            <TablaDatos
                caption="Transacciones y cuotas de la matrícula"
                columnas={[
                    { etiqueta: 'Concepto' },
                    { etiqueta: 'Método' },
                    { etiqueta: 'Monto' },
                    { etiqueta: 'Estado' },
                    { etiqueta: 'Acciones', alinear: 'derecha' },
                ]}
                vacio={pagos.length === 0}
                vacioIcono="tarjeta"
                vacioTitulo="Sin transacciones registradas"
                vacioDescripcion="Todavía no hay pagos ni planes de cuotas generados para esta matrícula."
            >
                {pagos.map((t) => (
                    <tr key={t.id} className="transition-colors hover:bg-(--color-superficie-2)/60">
                        <td className="px-6 py-4 text-sm font-medium">
                            {t.numero_cuota ? `Cuota ${t.numero_cuota} / ${t.total_cuotas}` : 'Pago único'}
                            {t.fecha_vencimiento && (
                                <span className="ml-2 text-xs text-(--color-texto-suave)">
                                    · vence {formatoFecha(t.fecha_vencimiento)}
                                </span>
                            )}
                        </td>
                        <td className="px-6 py-4 text-xs text-(--color-texto-suave)">{t.metodo ?? '—'}</td>
                        <td className="px-6 py-4 text-sm font-medium tabular-nums">
                            {Number(t.monto ?? 0).toFixed(2)} {moneda}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                            <Insignia tono={tonoEstado(t.estado)}>{t.estado_label}</Insignia>
                        </td>
                        <td className="px-6 py-4 text-right whitespace-nowrap">
                            {t.puede_resolver_manual && matricula.estado !== 'retirado' && (
                                <div className="inline-flex gap-3">
                                    <button
                                        type="button"
                                        onClick={() => resolverPago(t.id, 'aprobar')}
                                        className="border-0 bg-transparent p-0 text-xs font-semibold text-(--color-exito) underline"
                                    >
                                        Aprobar
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => resolverPago(t.id, 'rechazar')}
                                        className="border-0 bg-transparent p-0 text-xs font-semibold text-(--color-error) underline"
                                    >
                                        Rechazar
                                    </button>
                                </div>
                            )}
                        </td>
                    </tr>
                ))}
            </TablaDatos>

            <DialogoConfirmacion
                abierto={Boolean(confirmacion)}
                titulo={confirmacion?.titulo ?? ''}
                mensaje={confirmacion?.mensaje ?? ''}
                confirmarTexto={confirmacion?.confirmarTexto ?? 'Confirmar'}
                peligro
                alConfirmar={ejecutarConfirmacion}
                alCancelar={() => setConfirmacion(null)}
            />
        </section>
    );
}
