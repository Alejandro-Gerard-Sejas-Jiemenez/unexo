import { useCallback, useEffect, useState } from 'react';
import cliente, { ErrorDeValidacion } from '../api/cliente';
import Campo, { claseControl } from '../componentes/Campo';

const VACIO = { tipo: 'tarjeta', titular: '', numero: '', vencimiento: '', etiqueta: '' };

/**
 * Métodos de pago del usuario. Del número de tarjeta el servidor solo conserva
 * los últimos cuatro dígitos; aquí tampoco se guarda nada en el navegador.
 */
export default function Pagos() {
    const [metodos, setMetodos] = useState([]);
    const [datos, setDatos] = useState(VACIO);
    const [errores, setErrores] = useState({});
    const [aviso, setAviso] = useState(null);
    const [exito, setExito] = useState(null);
    const [cargando, setCargando] = useState(true);
    const [enviando, setEnviando] = useState(false);

    const cargar = useCallback(async () => {
        try {
            const { data } = await cliente.get('/metodos-pago');
            setMetodos(data.data);
        } catch (error) {
            setAviso(error.message);
        } finally {
            setCargando(false);
        }
    }, []);

    useEffect(() => {
        cargar();
    }, [cargar]);

    const cambiar = (campo) => (e) => setDatos((p) => ({ ...p, [campo]: e.target.value }));

    async function enviar(e) {
        e.preventDefault();
        setErrores({});
        setAviso(null);
        setExito(null);
        setEnviando(true);

        try {
            const { data } = await cliente.post('/metodos-pago', datos);
            setDatos(VACIO);
            setExito(data.mensaje);
            await cargar();
        } catch (error) {
            if (error instanceof ErrorDeValidacion) {
                setErrores(
                    Object.fromEntries(
                        Object.entries(error.errores).map(([c, l]) => [c, l[0]]),
                    ),
                );
            } else {
                setAviso(error.message);
            }
        } finally {
            setEnviando(false);
        }
    }

    async function eliminar(metodo) {
        setAviso(null);
        try {
            await cliente.delete(`/metodos-pago/${metodo.id}`);
            await cargar();
        } catch (error) {
            setAviso(error.message);
        }
    }

    const esTarjeta = datos.tipo === 'tarjeta';

    return (
        <section className="max-w-3xl">
            <h1 className="mb-1 text-2xl font-semibold">Métodos de pago</h1>
            <p className="mb-6 text-sm text-[var(--color-texto-suave)]">
                Del número de tarjeta solo se guardan los últimos cuatro dígitos.
            </p>

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

            <div className="superficie mb-6 overflow-hidden">
                <table className="w-full border-collapse text-sm">
                    <caption className="sr-only">Métodos de pago registrados</caption>
                    <thead>
                        <tr className="border-b border-[var(--color-borde)] text-left">
                            <th scope="col" className="p-3">Método</th>
                            <th scope="col" className="p-3">Titular</th>
                            <th scope="col" className="p-3">Vence</th>
                            <th scope="col" className="p-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        {cargando && (
                            <tr>
                                <td colSpan={4} className="p-6 text-center text-[var(--color-texto-suave)]">
                                    Cargando…
                                </td>
                            </tr>
                        )}
                        {!cargando && metodos.length === 0 && (
                            <tr>
                                <td colSpan={4} className="p-6 text-center text-[var(--color-texto-suave)]">
                                    Todavía no registraste ningún método de pago.
                                </td>
                            </tr>
                        )}
                        {metodos.map((m) => (
                            <tr key={m.id} className="border-b border-[var(--color-borde)]">
                                <td className="p-3">
                                    {m.descripcion}
                                    {m.predeterminado && (
                                        <span className="ml-2 rounded-full bg-[var(--color-acento-suave)] px-2 py-0.5 text-xs">
                                            Predeterminado
                                        </span>
                                    )}
                                </td>
                                <td className="p-3">{m.titular}</td>
                                <td className="p-3 tabular-nums">{m.vencimiento ?? '—'}</td>
                                <td className="p-3">
                                    <button
                                        type="button"
                                        onClick={() => eliminar(m)}
                                        className="border-0 bg-transparent p-0 underline"
                                    >
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <form onSubmit={enviar} noValidate className="superficie p-4">
                <h2 className="mb-4 text-lg font-semibold">Registrar un método</h2>

                <Campo id="tipo" etiqueta="Tipo" error={errores.tipo} requerido>
                    {(props) => (
                        <select {...props} className={claseControl} value={datos.tipo} onChange={cambiar('tipo')}>
                            <option value="tarjeta">Tarjeta</option>
                            <option value="qr">Pago con QR</option>
                            <option value="transferencia">Transferencia bancaria</option>
                        </select>
                    )}
                </Campo>

                <Campo id="titular" etiqueta="Titular" error={errores.titular} requerido>
                    {(props) => (
                        <input
                            {...props}
                            type="text"
                            maxLength={120}
                            className={claseControl}
                            value={datos.titular}
                            onChange={cambiar('titular')}
                        />
                    )}
                </Campo>

                {esTarjeta && (
                    <div className="grid grid-cols-2 gap-4">
                        <Campo
                            id="numero"
                            etiqueta="Número de tarjeta"
                            error={errores.numero}
                            requerido
                            ayuda="Solo se conservan los últimos cuatro dígitos."
                        >
                            {(props) => (
                                <input
                                    {...props}
                                    type="text"
                                    inputMode="numeric"
                                    autoComplete="off"
                                    maxLength={23}
                                    className={claseControl}
                                    value={datos.numero}
                                    onChange={cambiar('numero')}
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
                            {(props) => (
                                <input
                                    {...props}
                                    type="text"
                                    placeholder="09/29"
                                    maxLength={5}
                                    className={claseControl}
                                    value={datos.vencimiento}
                                    onChange={cambiar('vencimiento')}
                                />
                            )}
                        </Campo>
                    </div>
                )}

                <Campo id="etiqueta" etiqueta="Etiqueta" error={errores.etiqueta} ayuda="Opcional.">
                    {(props) => (
                        <input
                            {...props}
                            type="text"
                            maxLength={60}
                            className={claseControl}
                            value={datos.etiqueta}
                            onChange={cambiar('etiqueta')}
                        />
                    )}
                </Campo>

                <button
                    type="submit"
                    disabled={enviando}
                    className="mt-2 rounded-lg bg-[var(--color-acento)] px-4 py-2 text-sm font-medium text-[var(--color-acento-texto)] disabled:opacity-60"
                >
                    {enviando ? 'Guardando…' : 'Registrar método'}
                </button>
            </form>
        </section>
    );
}
