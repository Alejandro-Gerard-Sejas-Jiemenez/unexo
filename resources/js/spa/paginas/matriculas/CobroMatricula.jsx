import { useState } from 'react';
import Campo, { claseControl } from '../../componentes/Campo';
import Icono from '../../componentes/Icono';
import { dividirEnCuotas } from '../../lib/pagos';

const CUOTAS_RAPIDAS = [2, 3, 6];

const Nota = ({ children }) => <p className="superficie rounded-xl p-4 text-sm">{children}</p>;

/** Muestra solo lo que corresponde según la situación de los pagos. */
export default function CobroMatricula({
    cuentas,
    retirada,
    esStripe,
    moneda,
    cobro,
    setCobro,
    errores,
    metodos,
    enviando,
    alEnviar,
}) {
    const [esPlan, setEsPlan] = useState(false);

    if (retirada) return <Nota>Matrícula retirada: se conserva como historial y no admite cobros.</Nota>;
    if (cuentas.situacion === 'pagado') return <Nota>Todos los cargos están pagados. No queda nada por cobrar.</Nota>;

    if (cuentas.situacion === 'por_pagar') {
        const proximo = cuentas.proximo;
        const concepto = proximo.numero_cuota
            ? `Cuota ${proximo.numero_cuota} de ${proximo.total_cuotas}`
            : 'Pago único';

        return (
            <Nota>
                <b>Próximo por pagar:</b> {concepto}, {Number(proximo.monto).toFixed(2)} {moneda} (
                {proximo.estado_label}).{' '}
                {esStripe ? 'El estudiante lo paga desde «Mis pagos».' : 'Está en la tabla de abajo.'}
            </Nota>
        );
    }

    const vista = esPlan ? dividirEnCuotas(cobro.monto, cobro.cuotas) : [];

    function elegirTipo(plan) {
        setEsPlan(plan);
        setCobro((c) => ({ ...c, cuotas: plan ? '2' : '' }));
    }

    return (
        <div className="superficie overflow-hidden rounded-xl shadow-sm">
            <div className="flex flex-wrap items-center justify-between gap-2 border-b border-(--color-borde) bg-(--color-superficie-cabecera) px-6 py-4">
                <h2 className="text-lg font-semibold">Registrar cobro</h2>
                {esStripe && (
                    <span className="text-xs text-(--color-texto-suave)">
                        Quedará por pagar: el estudiante paga en línea con Stripe (modo de prueba).
                    </span>
                )}
            </div>

            <form onSubmit={alEnviar} noValidate className="space-y-4 p-6">
                <div className="flex flex-wrap gap-2">
                    {[
                        [false, 'Pago único'],
                        [true, 'Plan de cuotas'],
                    ].map(([plan, etiqueta]) => (
                        <button
                            key={etiqueta}
                            type="button"
                            aria-pressed={esPlan === plan}
                            onClick={() => elegirTipo(plan)}
                            className={`btn min-h-9! px-3! text-xs ${esPlan === plan ? 'btn-primario' : 'btn-secundario'}`}
                        >
                            {etiqueta}
                        </button>
                    ))}
                </div>

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
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

                    {!esStripe && (
                        <Campo
                            id="metodo_pago_id"
                            etiqueta="Método del estudiante"
                            error={errores.metodo_pago_id}
                            ayuda={esPlan ? undefined : 'Con un método, el pago único queda aprobado al registrarlo.'}
                            esSelect
                        >
                            {(p) => (
                                <select
                                    {...p}
                                    className={claseControl}
                                    value={cobro.metodo_pago_id}
                                    onChange={(e) => setCobro((c) => ({ ...c, metodo_pago_id: e.target.value }))}
                                >
                                    <option value="">Sin método (queda por pagar)</option>
                                    {metodos.map((m) => (
                                        <option key={m.id} value={m.id}>
                                            {m.descripcion} — {m.titular}
                                        </option>
                                    ))}
                                </select>
                            )}
                        </Campo>
                    )}
                </div>

                {esPlan && (
                    <div className="space-y-3">
                        <div className="flex flex-wrap items-end gap-2">
                            {CUOTAS_RAPIDAS.map((numero) => (
                                <button
                                    key={numero}
                                    type="button"
                                    aria-pressed={String(cobro.cuotas) === String(numero)}
                                    onClick={() => setCobro((c) => ({ ...c, cuotas: String(numero) }))}
                                    className={`btn min-h-9! px-3! text-xs ${String(cobro.cuotas) === String(numero) ? 'btn-primario' : 'btn-secundario'}`}
                                >
                                    {numero} cuotas
                                </button>
                            ))}
                            <label className="text-xs">
                                <span className="mb-1 block text-(--color-texto-suave)">Otra cantidad (2 a 36)</span>
                                <input
                                    type="number"
                                    min="2"
                                    max="36"
                                    className={`${claseControl} w-28!`}
                                    value={cobro.cuotas}
                                    onChange={(e) => setCobro((c) => ({ ...c, cuotas: e.target.value }))}
                                />
                            </label>
                        </div>
                        {errores.cuotas && <p className="text-xs text-(--color-error)">{errores.cuotas}</p>}

                        {vista.length > 0 ? (
                            <ul aria-label="Vista previa de las cuotas" className="flex flex-wrap gap-2 text-xs">
                                {vista.map((monto, i) => (
                                    <li key={i} className="rounded-lg border border-(--color-borde) px-3 py-1.5 tabular-nums">
                                        Cuota {i + 1}: <b>{monto.toFixed(2)}</b> {moneda}
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <p className="text-xs text-(--color-texto-suave)">
                                Escribe un monto y entre 2 y 36 cuotas para ver el reparto.
                            </p>
                        )}
                    </div>
                )}

                <button type="submit" disabled={enviando || (esPlan && vista.length === 0)} className="btn btn-primario">
                    <Icono nombre="tarjeta" />
                    {enviando ? 'Procesando…' : esPlan ? 'Generar plan de cuotas' : 'Registrar cargo'}
                </button>
            </form>
        </div>
    );
}
