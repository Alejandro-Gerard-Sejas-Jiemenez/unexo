import { useCallback, useEffect, useState } from 'react';
import { Link, useParams, useSearchParams } from 'react-router-dom';
import cliente, { ErrorDeValidacion } from '../../api/cliente';
import BotonAccion from '../../componentes/BotonAccion';
import Cargador from '../../componentes/Cargador';
import DialogoConfirmacion from '../../componentes/DialogoConfirmacion';
import Icono from '../../componentes/Icono';
import Insignia, { tonoEstado } from '../../componentes/Insignia';
import TablaDatos from '../../componentes/TablaDatos';
import { useToast } from '../../componentes/ToastContext';
import { formatoFecha } from '../../lib/fechas';
import { resumirPagos } from '../../lib/pagos';
import CobroMatricula from './CobroMatricula';

const PESTANAS = ['pagos', 'grupos', 'comprobantes'];

export default function MatriculaDetalle() {
    const { id } = useParams();
    const [busqueda, setBusqueda] = useSearchParams();
    const pestana = PESTANAS.includes(busqueda.get('tab')) ? busqueda.get('tab') : 'pagos';
    const { mostrar } = useToast();

    const [matricula, setMatricula] = useState(null);
    const [pagos, setPagos] = useState([]);
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
            if (accion.tipo === 'estado') {
                const { data } = await cliente.patch(`/matriculas/${id}/estado`, { estado: accion.estado });
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

    function pedirCambioDeEstado(estado, hayDeudas) {
        const textos = {
            activo: {
                titulo: '¿Activar esta matrícula a mano?',
                mensaje: hayDeudas
                    ? 'Quedan cargos sin pagar. No se modificarán y el estudiante todavía podrá pagarlos.'
                    : 'La matrícula quedará activa.',
                confirmarTexto: 'Sí, activar',
            },
            pendiente: {
                titulo: '¿Marcar como pendiente de pago?',
                mensaje: 'Cambia solo el estado de la matrícula. Los pagos registrados no se modifican.',
                confirmarTexto: 'Sí, marcar pendiente',
            },
            retirado: {
                titulo: '¿Retirar al estudiante de esta matrícula?',
                mensaje: 'Se conserva el historial académico y se cerrará el acceso a los grupos.',
                confirmarTexto: 'Sí, retirar estudiante',
                peligro: true,
            },
        };
        setConfirmacion({ tipo: 'estado', estado, ...textos[estado] });
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
                setAviso(Object.values(error.errores).flat()[0] ?? 'Revisa los datos del cobro.');
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
    const esStripe = pasarela?.driver === 'stripe';
    const cuentas = resumirPagos(pagos, matricula.cohorte?.precio);
    const importe = (valor) => (valor === null ? 'Sin importe definido' : `${valor.toFixed(2)} ${moneda}`);
    const etiquetas = {
        pagos: 'Pagos',
        grupos: `Grupos (${inscripciones.length})`,
        comprobantes: `Comprobantes (${comprobantes.length})`,
    };

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

            <div className="mb-5 flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                <div>
                    <div className="flex flex-wrap items-center gap-3">
                        <h1 className="text-3xl font-extrabold tracking-tight">{matricula.estudiante?.nombre}</h1>
                        <Insignia tono={tonoEstado(matricula.estado)} className="px-3! py-1! text-sm! shadow-sm">
                            {matricula.estado_label}
                        </Insignia>
                    </div>
                    <p className="mt-1 text-lg text-(--color-texto-suave)">
                        {matricula.cohorte
                            ? `${matricula.cohorte.programa ? `${matricula.cohorte.programa} · ` : ''}${matricula.cohorte.nombre}`
                            : 'Módulo suelto'}
                    </p>
                    <p className="mt-1 text-sm break-all text-(--color-texto-suave)">
                        {matricula.estudiante?.correo_institucional || matricula.estudiante?.email || '—'} ·{' '}
                        {matricula.origen_label} · matriculado el {formatoFecha(matricula.fecha_matricula)}
                        {matricula.fecha_retiro && ` · retirado el ${formatoFecha(matricula.fecha_retiro)}`}
                    </p>
                </div>

                <nav aria-label="Atajos" className="flex flex-wrap gap-2">
                    <BotonAccion to={`/usuarios/${matricula.estudiante_id}`} icono="usuarios">
                        Ver estudiante
                    </BotonAccion>
                    {matricula.cohorte_id && (
                        <BotonAccion to={`/cohortes/${matricula.cohorte_id}`} icono="catalogo">
                            Ver edición
                        </BotonAccion>
                    )}
                    <BotonAccion to={`/matriculas/nueva?estudiante_id=${matricula.estudiante_id}`} icono="mas">
                        Nueva matrícula
                    </BotonAccion>
                </nav>
            </div>

            {aviso && (
                <p role="alert" className="superficie mb-4 border-(--color-error) p-3 text-sm text-(--color-error)">
                    {aviso}
                </p>
            )}

            <dl className="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-3">
                {[
                    ['Total', cuentas.total, ''],
                    ['Pagado', cuentas.pagado, 'text-(--color-exito)'],
                    ['Pendiente', cuentas.pendiente, ''],
                ].map(([etiqueta, valor, color]) => (
                    <div key={etiqueta} className="superficie rounded-xl p-4 shadow-sm">
                        <dt className="text-xs font-semibold tracking-wider text-(--color-texto-suave) uppercase">
                            {etiqueta}
                        </dt>
                        <dd className={`mt-1 text-xl font-bold tabular-nums ${color}`}>{importe(valor)}</dd>
                    </div>
                ))}
            </dl>

            <div role="tablist" aria-label="Secciones de la matrícula" className="mb-4 flex flex-wrap gap-2">
                {PESTANAS.map((clave) => (
                    <button
                        key={clave}
                        type="button"
                        role="tab"
                        aria-selected={pestana === clave}
                        onClick={() => setBusqueda({ tab: clave }, { replace: true })}
                        className={`btn min-h-9! px-4! text-sm ${pestana === clave ? 'btn-primario' : 'btn-secundario'}`}
                    >
                        {etiquetas[clave]}
                    </button>
                ))}
            </div>

            <div role="tabpanel" className="mb-8">
                {pestana === 'pagos' && (
                    <div className="space-y-6">
                        <CobroMatricula
                            cuentas={cuentas}
                            retirada={estaRetirado}
                            esStripe={esStripe}
                            moneda={moneda}
                            cobro={cobro}
                            setCobro={setCobro}
                            errores={errores}
                            metodos={metodosEstudiante}
                            enviando={enviandoAccion}
                            alEnviar={registrarCobro}
                        />
                        {pagos.length > 0 && (
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
                        )}
                    </div>
                )}

                {pestana === 'grupos' && (
                    <TablaDatos
                        caption="Grupos inscritos en la matrícula"
                        columnas={[
                            { etiqueta: 'Módulo' },
                            { etiqueta: 'Grupo' },
                            { etiqueta: 'Docente' },
                            { etiqueta: 'Fechas' },
                            { etiqueta: 'Acciones', srOnly: true, alinear: 'derecha' },
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
                                <td className="px-6 py-4 text-right whitespace-nowrap">
                                    <BotonAccion to={`/grupos/${i.grupo_id}/ver`} icono="usuarios">
                                        Ver grupo
                                    </BotonAccion>
                                </td>
                            </tr>
                        ))}
                    </TablaDatos>
                )}

                {pestana === 'comprobantes' && (
                    <div className="superficie overflow-hidden rounded-xl shadow-sm">
                        {comprobantes.length === 0 ? (
                            <p className="px-6 py-4 text-sm text-(--color-texto-suave)">
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
                                                        peligro: true,
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
                )}
            </div>

            {!estaRetirado && (
                <div className="flex flex-wrap justify-end gap-2">
                    {matricula.estado === 'pendiente' && (
                        <button
                            type="button"
                            disabled={enviandoAccion}
                            onClick={() => pedirCambioDeEstado('activo', cuentas.situacion === 'por_pagar')}
                            className="btn btn-secundario min-h-9! px-3! text-xs"
                        >
                            <Icono nombre="usuario-activar" className="h-4 w-4" />
                            Activar a mano
                        </button>
                    )}
                    {matricula.estado === 'activo' && (
                        <button
                            type="button"
                            disabled={enviandoAccion}
                            onClick={() => pedirCambioDeEstado('pendiente')}
                            className="btn btn-secundario min-h-9! px-3! text-xs"
                        >
                            Marcar pendiente
                        </button>
                    )}
                    <button
                        type="button"
                        disabled={enviandoAccion}
                        onClick={() => pedirCambioDeEstado('retirado')}
                        className="btn btn-secundario min-h-9! px-3! text-xs text-(--color-error)!"
                    >
                        <Icono nombre="usuario-desactivar" className="h-4 w-4" />
                        Retirar
                    </button>
                </div>
            )}

            <DialogoConfirmacion
                abierto={Boolean(confirmacion)}
                titulo={confirmacion?.titulo ?? ''}
                mensaje={confirmacion?.mensaje ?? ''}
                confirmarTexto={confirmacion?.confirmarTexto ?? 'Confirmar'}
                peligro={Boolean(confirmacion?.peligro)}
                alConfirmar={ejecutarConfirmacion}
                alCancelar={() => setConfirmacion(null)}
            />
        </section>
    );
}
