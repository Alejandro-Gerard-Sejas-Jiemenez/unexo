import { useCallback, useEffect, useState } from 'react';
import { claveCache, guardarCache, leerCache } from '../api/cache';
import cliente, { ErrorDeValidacion } from '../api/cliente';
import { useAuth } from '../auth/AuthContext';
import Campo, { claseControl } from '../componentes/Campo';
import Cargador from '../componentes/Cargador';
import DialogoConfirmacion from '../componentes/DialogoConfirmacion';
import EncabezadoPagina from '../componentes/EncabezadoPagina';
import Icono from '../componentes/Icono';
import Insignia, { tonoEstado } from '../componentes/Insignia';
import TablaDatos from '../componentes/TablaDatos';
import TablaResponsiva from '../componentes/TablaResponsiva';
import { useToast } from '../componentes/ToastContext';
import { formatoFecha } from '../lib/fechas';

const VACIO = {
    tipo: 'tarjeta',
    titular: '',
    numero: '',
    vencimiento: '',
    etiqueta: '',
    predeterminado: false,
};

function formatearTarjeta(valor) {
    const digitos = valor.replace(/\D/g, '').slice(0, 19);
    return digitos.replace(/(.{4})/g, '$1 ').trim();
}

function formatearVencimiento(valor) {
    const digitos = valor.replace(/\D/g, '').slice(0, 4);
    if (digitos.length <= 2) return digitos;
    return `${digitos.slice(0, 2)}/${digitos.slice(2)}`;
}

function validarVencimiento(valor) {
    const match = /^(\d{2})\/(\d{2})$/.exec(valor.trim());
    if (!match) return 'La fecha de vencimiento debe tener el formato MM/AA.';
    const mes = Number(match[1]);
    const anio = 2000 + Number(match[2]);
    if (mes < 1 || mes > 12) return 'El mes de vencimiento debe estar entre 01 y 12.';
    const ahora = new Date();
    const anioActual = ahora.getFullYear();
    const mesActual = ahora.getMonth() + 1;
    if (anio < anioActual || (anio === anioActual && mes < mesActual)) {
        return 'La tarjeta ingresada está vencida.';
    }
    return null;
}

export default function Pagos() {
    const { usuario } = useAuth();
    const { mostrar } = useToast();
    const esEstudiante = usuario?.rol === 'estudiante';

    const cacheMetodos = leerCache(claveCache('/metodos-pago'));
    const cacheMatriculas = esEstudiante ? leerCache(claveCache('/mis-matriculas')) : null;

    const [pestana, setPestana] = useState(esEstudiante ? 'cuotas' : 'metodos');
    const [metodos, setMetodos] = useState(() => cacheMetodos?.data ?? []);
    const [matriculas, setMatriculas] = useState(() => cacheMatriculas?.data ?? []);
    const [pasarela, setPasarela] = useState(() => cacheMatriculas?.pasarela ?? null);
    const [metodoElegido, setMetodoElegido] = useState({});
    const [datos, setDatos] = useState(VACIO);
    const [errores, setErrores] = useState({});
    const [aviso, setAviso] = useState(null);
    const [cargando, setCargando] = useState(() => !(cacheMetodos && (!esEstudiante || cacheMatriculas)));
    const [enviando, setEnviando] = useState(false);
    const [pagandoClave, setPagandoClave] = useState(null);
    const [confirmando, setConfirmando] = useState(null);

    const cargar = useCallback(async () => {
        try {
            const peticiones = [cliente.get('/metodos-pago')];
            if (esEstudiante) {
                peticiones.push(cliente.get('/mis-matriculas'));
            }
            const [resMetodos, resMatriculas] = await Promise.all(peticiones);
            guardarCache(claveCache('/metodos-pago'), resMetodos.data);
            const listaMetodos = resMetodos.data.data ?? [];
            setMetodos(listaMetodos);

            setPasarela(resMetodos.data.pasarela ?? null);
            if (resMetodos.data.pasarela?.driver === 'stripe') {
                setDatos((d) => (d.tipo === 'tarjeta' ? { ...d, tipo: 'qr' } : d));
            }

            if (resMatriculas) {
                guardarCache(claveCache('/mis-matriculas'), resMatriculas.data);
                const listaMat = resMatriculas.data.data ?? [];
                setMatriculas(listaMat);
                setPasarela(resMatriculas.data.pasarela ?? null);
                const predeterminado = listaMetodos.find((m) => m.predeterminado) ?? listaMetodos[0];
                if (predeterminado) {
                    setMetodoElegido((prev) => {
                        const next = { ...prev };
                        listaMat.forEach((item) => {
                            if (!next[item.matricula.id]) next[item.matricula.id] = predeterminado.id;
                        });
                        return next;
                    });
                }
            }
            setAviso(null);
        } catch (error) {
            if (!cacheMetodos) setAviso(error.message);
        } finally {
            setCargando(false);
        }
    }, [esEstudiante]);

    useEffect(() => {
        cargar();
    }, [cargar]);

    // Al volver de Stripe solo se informa el estado registrado: volver no aprueba el pago.
    const parametros = new URLSearchParams(window.location.search);
    const cargoDeRetorno = matriculas
        .flatMap((item) => item.transacciones)
        .find((t) => t.id === parametros.get('cargo'));
    let avisoRetorno = null;
    if (parametros.get('pago') && cargoDeRetorno) {
        if (cargoDeRetorno.estado === 'aprobada') {
            avisoRetorno = 'Pago confirmado.';
        } else if (parametros.get('pago') === 'cancelado') {
            avisoRetorno = 'Volviste sin completar el pago. Puedes continuarlo cuando quieras.';
        } else if (cargoDeRetorno.estado === 'procesando') {
            avisoRetorno = 'Estamos confirmando tu pago.';
        } else {
            avisoRetorno = 'El pago no se completó.';
        }
    }

    function continuarPago(data) {
        if (data.url) {
            window.location.assign(data.url);
            return true;
        }
        mostrar(data.mensaje);
        return false;
    }

    async function enviarMetodo(e) {
        e.preventDefault();
        setErrores({});
        setAviso(null);

        const digitos = datos.numero.replace(/\D/g, '');
        if (datos.tipo === 'tarjeta') {
            const errLocales = {};
            if (!datos.titular.trim()) errLocales.titular = 'El nombre del titular es obligatorio.';
            if (digitos.length < 13 || digitos.length > 19) {
                errLocales.numero = 'Ingresa un número de tarjeta válido de entre 13 y 19 dígitos.';
            }
            const errVenc = validarVencimiento(datos.vencimiento);
            if (errVenc) errLocales.vencimiento = errVenc;
            if (Object.keys(errLocales).length > 0) {
                setErrores(errLocales);
                setAviso('Revisa los campos marcados.');
                return;
            }
        }

        setEnviando(true);

        try {
            const carga = {
                ...datos,
                numero: digitos,
            };
            const { data } = await cliente.post('/metodos-pago', carga);
            setDatos(VACIO);
            mostrar(data.mensaje);
            await cargar();
        } catch (error) {
            if (error instanceof ErrorDeValidacion) {
                const mapeados = Object.fromEntries(Object.entries(error.errores).map(([c, l]) => [c, l[0]]));
                setErrores(mapeados);
                setAviso(mapeados.stripe_payment_method_id ?? 'Revisa los campos marcados.');
            } else {
                setAviso(error.message);
            }
        } finally {
            setEnviando(false);
        }
    }

    async function confirmarEliminar() {
        if (!confirmando) return;
        const objetivo = confirmando;
        setConfirmando(null);
        setAviso(null);
        try {
            const { data } = await cliente.delete(`/metodos-pago/${objetivo.id}`);
            mostrar(data.mensaje ?? 'Método de pago eliminado.');
            await cargar();
        } catch (error) {
            setAviso(error.message);
        }
    }

    async function pagarMatricula(item) {
        const mId = item.matricula.id;
        setPagandoClave(`m-${mId}`);
        setAviso(null);
        try {
            const { data } = await cliente.post(`/matriculas/${mId}/pagar`, {
                metodo_pago_id: metodoElegido[mId] || null,
            });
            if (continuarPago(data)) return;
            await cargar();
        } catch (error) {
            if (error instanceof ErrorDeValidacion) {
                const primero = Object.values(error.errores).flat()[0];
                setAviso(primero ?? error.message);
            } else {
                setAviso(error.message);
            }
        } finally {
            setPagandoClave(null);
        }
    }

    async function pagarCuota(mId, transaccionId) {
        setPagandoClave(`t-${transaccionId}`);
        setAviso(null);
        try {
            const { data } = await cliente.post(`/transacciones/${transaccionId}/pagar`, {
                metodo_pago_id: metodoElegido[mId] || null,
            });
            if (continuarPago(data)) return;
            await cargar();
        } catch (error) {
            if (error instanceof ErrorDeValidacion) {
                const primero = Object.values(error.errores).flat()[0];
                setAviso(primero ?? error.message);
            } else {
                setAviso(error.message);
            }
        } finally {
            setPagandoClave(null);
        }
    }

    const esStripe = pasarela?.driver === 'stripe';
    const esTarjeta = !esStripe && datos.tipo === 'tarjeta';
    const moneda = pasarela?.moneda ?? 'BOB';

    return (
        <section>
            <EncabezadoPagina
                grande
                titulo="Pagos electrónicos"
                subtitulo="Administra tus métodos de pago y consulta tus pagos únicos o planes de cuotas."
                acciones={
                    pasarela && (
                        <Insignia tono="acento">
                            Pasarela: {esStripe ? 'Stripe · sin dinero real' : 'Simulada'} · {moneda}
                        </Insignia>
                    )
                }
            />

            {esEstudiante && (
                <div role="tablist" aria-label="Secciones de pagos" className="flex flex-wrap gap-2">
                    <button
                        type="button"
                        role="tab"
                        aria-selected={pestana === 'cuotas'}
                        onClick={() => setPestana('cuotas')}
                        className={`btn min-h-9! px-4! text-sm ${pestana === 'cuotas' ? 'btn-primario' : 'btn-secundario'}`}
                    >
                        Mis pagos y cuotas
                    </button>
                    <button
                        type="button"
                        role="tab"
                        aria-selected={pestana === 'metodos'}
                        onClick={() => setPestana('metodos')}
                        className={`btn min-h-9! px-4! text-sm ${pestana === 'metodos' ? 'btn-primario' : 'btn-secundario'}`}
                    >
                        Métodos de pago ({metodos.length})
                    </button>
                </div>
            )}

            {aviso && (
                <p role="alert" className="superficie border-(--color-error) p-3 text-sm text-(--color-error)">
                    {aviso}
                </p>
            )}

            {avisoRetorno && (
                <p role="status" className="superficie p-3 text-sm">
                    {avisoRetorno}
                </p>
            )}

            {cargando ? (
                <Cargador texto="Cargando" />
            ) : (
                <>
                    {esEstudiante && pestana === 'cuotas' && (
                        <div className="space-y-5">
                            {matriculas.length === 0 ? (
                                <p className="superficie p-6 text-center text-sm text-(--color-texto-suave)">
                                    Todavía no tienes matrículas registradas para gestionar pagos.
                                </p>
                            ) : (
                                matriculas.map(({ matricula, transacciones, resumen }) => {
                                    const puedePagoCompleto =
                                        matricula.estado === 'pendiente' &&
                                        transacciones.length === 0 &&
                                        Number(matricula.cohorte?.precio ?? 0) > 0;

                                    return (
                                        <div key={matricula.id} className="superficie space-y-4 p-5">
                                            <div className="flex flex-wrap items-start justify-between gap-3">
                                                <div>
                                                    <h2 className="text-lg font-semibold">
                                                        {matricula.cohorte?.programa ?? 'Módulo suelto'}
                                                    </h2>
                                                    <p className="text-sm text-(--color-texto-suave)">
                                                        {matricula.cohorte?.nombre ?? 'Inscripción individual'} ·
                                                        matriculado el {formatoFecha(matricula.fecha_matricula)}
                                                    </p>
                                                </div>
                                                <Insignia tono={tonoEstado(matricula.estado)}>
                                                    {matricula.estado_label}
                                                </Insignia>
                                            </div>

                                            <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                                <div className="rounded-lg border border-(--color-borde) bg-(--color-superficie-2) p-3">
                                                    <p className="text-xs text-(--color-texto-suave) uppercase">
                                                        Pagado
                                                    </p>
                                                    <p className="mt-1 text-lg font-semibold tabular-nums">
                                                        {Number(resumen.pagado ?? 0).toFixed(2)} {moneda}
                                                    </p>
                                                </div>
                                                <div className="rounded-lg border border-(--color-borde) bg-(--color-superficie-2) p-3">
                                                    <p className="text-xs text-(--color-texto-suave) uppercase">
                                                        Saldo pendiente
                                                    </p>
                                                    <p className="mt-1 text-lg font-semibold tabular-nums">
                                                        {Number(resumen.pendiente ?? 0).toFixed(2)} {moneda}
                                                    </p>
                                                </div>
                                                {!esStripe && (
                                                    <div className="rounded-lg border border-(--color-borde) bg-(--color-superficie-2) p-3">
                                                        <label className="block text-xs text-(--color-texto-suave)">
                                                            Método para pagar
                                                            <select
                                                                className={`${claseControl} mt-1 min-h-8! text-xs`}
                                                                value={metodoElegido[matricula.id] ?? ''}
                                                                onChange={(e) =>
                                                                    setMetodoElegido((p) => ({
                                                                        ...p,
                                                                        [matricula.id]: e.target.value,
                                                                    }))
                                                                }
                                                            >
                                                                <option value="">Selecciona o usa predeterminado</option>
                                                                {metodos.map((m) => (
                                                                    <option key={m.id} value={m.id}>
                                                                        {m.descripcion}
                                                                    </option>
                                                                ))}
                                                            </select>
                                                        </label>
                                                    </div>
                                                )}
                                            </div>

                                            {puedePagoCompleto && (
                                                <div className="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-(--color-borde) p-3">
                                                    <p className="text-sm">
                                                        Monto del programa:{' '}
                                                        <b className="tabular-nums">
                                                            {Number(matricula.cohorte.precio).toFixed(2)} {moneda}
                                                        </b>
                                                    </p>
                                                    <button
                                                        type="button"
                                                        disabled={pagandoClave === `m-${matricula.id}`}
                                                        onClick={() => pagarMatricula({ matricula })}
                                                        className="btn btn-primario min-h-9! px-4! text-xs"
                                                    >
                                                        <Icono nombre="tarjeta" className="h-4 w-4" />
                                                        {pagandoClave === `m-${matricula.id}`
                                                            ? 'Procesando…'
                                                            : 'Pagar matrícula ahora'}
                                                    </button>
                                                </div>
                                            )}

                                            {transacciones.length > 0 && (
                                                <TablaResponsiva>
                                                    <table className="w-full border-collapse text-sm">
                                                        <thead>
                                                            <tr className="border-b border-(--color-borde) text-left">
                                                                <th scope="col" className="p-2.5">Concepto</th>
                                                                <th scope="col" className="p-2.5">Vence</th>
                                                                <th scope="col" className="p-2.5">Monto</th>
                                                                <th scope="col" className="p-2.5">Estado</th>
                                                                <th scope="col" className="p-2.5 text-right">Acción</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            {transacciones.map((t) => (
                                                                <tr
                                                                    key={t.id}
                                                                    className="border-b border-(--color-borde) last:border-b-0"
                                                                >
                                                                    <td className="p-2.5">
                                                                        {t.numero_cuota
                                                                            ? `Cuota ${t.numero_cuota} / ${t.total_cuotas}`
                                                                            : 'Pago único'}
                                                                    </td>
                                                                    <td className="p-2.5 tabular-nums">
                                                                        {formatoFecha(t.fecha_vencimiento)}
                                                                    </td>
                                                                    <td className="p-2.5 tabular-nums">
                                                                        {Number(t.monto ?? 0).toFixed(2)} {moneda}
                                                                    </td>
                                                                    <td className="p-2.5">
                                                                        <Insignia tono={tonoEstado(t.estado)}>
                                                                            {t.estado_label}
                                                                        </Insignia>
                                                                    </td>
                                                                    <td className="p-2.5 text-right">
                                                                        {['pendiente', 'rechazada', 'procesando'].includes(t.estado) &&
                                                                            matricula.estado !== 'retirado' && (
                                                                                <button
                                                                                    type="button"
                                                                                    disabled={pagandoClave === `t-${t.id}`}
                                                                                    onClick={() =>
                                                                                        pagarCuota(matricula.id, t.id)
                                                                                    }
                                                                                    className="btn btn-primario min-h-8! px-3! text-xs"
                                                                                >
                                                                                    {pagandoClave === `t-${t.id}`
                                                                                        ? 'Procesando…'
                                                                                        : t.estado === 'procesando'
                                                                                          ? 'Continuar pago'
                                                                                          : 'Pagar'}
                                                                                </button>
                                                                            )}
                                                                    </td>
                                                                </tr>
                                                            ))}
                                                        </tbody>
                                                    </table>
                                                </TablaResponsiva>
                                            )}
                                        </div>
                                    );
                                })
                            )}
                        </div>
                    )}

                    {(!esEstudiante || pestana === 'metodos') && (
                        <div className="space-y-6">
                            <TablaDatos
                                caption="Métodos de pago registrados"
                                columnas={[
                                    { etiqueta: 'Método' },
                                    { etiqueta: 'Titular' },
                                    { etiqueta: 'Vence' },
                                    { etiqueta: 'Acciones', alinear: 'derecha' },
                                ]}
                                vacio={metodos.length === 0}
                                vacioIcono="tarjeta"
                                vacioTitulo="Sin métodos de pago"
                                vacioDescripcion="Todavía no registraste ningún método de pago en tu cuenta."
                            >
                                {metodos.map((m) => (
                                    <tr key={m.id} className="transition-colors hover:bg-(--color-superficie-2)/60">
                                        <td className="px-6 py-4 text-sm">
                                            <span className="font-medium">{m.descripcion}</span>
                                            {m.predeterminado && (
                                                <Insignia tono="acento" className="ml-2">
                                                    Predeterminado
                                                </Insignia>
                                            )}
                                        </td>
                                        <td className="px-6 py-4 text-sm">{m.titular}</td>
                                        <td className="px-6 py-4 text-sm tabular-nums">{m.vencimiento ?? '—'}</td>
                                        <td className="px-6 py-4 text-right whitespace-nowrap">
                                            <button
                                                type="button"
                                                onClick={() => setConfirmando(m)}
                                                className="border-0 bg-transparent p-0 text-xs font-semibold text-(--color-error) underline"
                                            >
                                                Eliminar
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </TablaDatos>

                            <form onSubmit={enviarMetodo} noValidate className="superficie p-5">
                                <h2 className="mb-1 text-lg font-semibold">Registrar método de pago</h2>
                                <p className="mb-4 text-xs text-(--color-texto-suave)">
                                    {esStripe
                                        ? 'Las tarjetas se ingresan solo en la página de Stripe.'
                                        : 'Por seguridad, el servidor solo conserva los últimos cuatro dígitos de tu tarjeta.'}
                                </p>

                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <Campo id="tipo" etiqueta="Tipo" error={errores.tipo} requerido esSelect>
                                        {(p) => (
                                            <select
                                                {...p}
                                                className={claseControl}
                                                value={datos.tipo}
                                                onChange={(e) => setDatos((d) => ({ ...d, tipo: e.target.value }))}
                                            >
                                                {!esStripe && <option value="tarjeta">Tarjeta</option>}
                                                <option value="qr">Pago con QR</option>
                                                <option value="transferencia">Transferencia bancaria</option>
                                            </select>
                                        )}
                                    </Campo>

                                    <Campo id="titular" etiqueta="Titular" error={errores.titular} requerido>
                                        {(p) => (
                                            <input
                                                {...p}
                                                type="text"
                                                maxLength={120}
                                                className={claseControl}
                                                value={datos.titular}
                                                onChange={(e) => setDatos((d) => ({ ...d, titular: e.target.value }))}
                                            />
                                        )}
                                    </Campo>
                                </div>

                                {esTarjeta && (
                                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <Campo
                                            id="numero"
                                            etiqueta="Número de tarjeta"
                                            error={errores.numero}
                                            requerido
                                            icono="tarjeta"
                                            ayuda="Solo se conservan los últimos cuatro dígitos."
                                        >
                                            {(p) => (
                                                <input
                                                    {...p}
                                                    type="text"
                                                    inputMode="numeric"
                                                    autoComplete="off"
                                                    maxLength={23}
                                                    placeholder="4242 4242 4242 4242"
                                                    className={claseControl}
                                                    value={datos.numero}
                                                    onChange={(e) =>
                                                        setDatos((d) => ({
                                                            ...d,
                                                            numero: formatearTarjeta(e.target.value),
                                                        }))
                                                    }
                                                />
                                            )}
                                        </Campo>

                                        <Campo
                                            id="vencimiento"
                                            etiqueta="Vencimiento"
                                            error={errores.vencimiento}
                                            requerido
                                            ayuda="Formato MM/AA."
                                        >
                                            {(p) => (
                                                <input
                                                    {...p}
                                                    type="text"
                                                    inputMode="numeric"
                                                    placeholder="09/29"
                                                    maxLength={5}
                                                    className={claseControl}
                                                    value={datos.vencimiento}
                                                    onChange={(e) =>
                                                        setDatos((d) => ({
                                                            ...d,
                                                            vencimiento: formatearVencimiento(e.target.value),
                                                        }))
                                                    }
                                                />
                                            )}
                                        </Campo>
                                    </div>
                                )}

                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <Campo id="etiqueta" etiqueta="Etiqueta" error={errores.etiqueta} ayuda="Opcional (ej. Personal).">
                                        {(p) => (
                                            <input
                                                {...p}
                                                type="text"
                                                maxLength={60}
                                                className={claseControl}
                                                value={datos.etiqueta}
                                                onChange={(e) => setDatos((d) => ({ ...d, etiqueta: e.target.value }))}
                                            />
                                        )}
                                    </Campo>

                                    <label className="mt-2 flex cursor-pointer items-center gap-2 text-sm sm:mt-7">
                                        <input
                                            type="checkbox"
                                            className="campo-check"
                                            checked={datos.predeterminado}
                                            onChange={(e) =>
                                                setDatos((d) => ({ ...d, predeterminado: e.target.checked }))
                                            }
                                        />
                                        Usar como método predeterminado
                                    </label>
                                </div>

                                <button type="submit" disabled={enviando} className="btn btn-primario mt-2">
                                    <Icono nombre="guardar" />
                                    {enviando ? 'Guardando…' : 'Registrar método'}
                                </button>
                            </form>
                        </div>
                    )}
                </>
            )}

            <DialogoConfirmacion
                abierto={Boolean(confirmando)}
                titulo="¿Eliminar este método de pago?"
                mensaje={confirmando ? `Se quitará «${confirmando.descripcion}» de tu cuenta.` : ''}
                confirmarTexto="Eliminar"
                peligro
                alConfirmar={confirmarEliminar}
                alCancelar={() => setConfirmando(null)}
            />
        </section>
    );
}
