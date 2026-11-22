import { useCallback, useEffect, useState } from 'react';
import cliente, { ErrorDeValidacion } from '../api/cliente';
import Campo, { claseControl } from '../componentes/Campo';

/** CU28: el administrador genera y revoca los tokens que usa el CRM. */
export default function ClientesApi() {
    const [clientes, setClientes] = useState([]);
    const [nombre, setNombre] = useState('');
    const [tokenNuevo, setTokenNuevo] = useState(null);
    const [errores, setErrores] = useState({});
    const [aviso, setAviso] = useState(null);
    const [exito, setExito] = useState(null);
    const [cargando, setCargando] = useState(true);
    const [enviando, setEnviando] = useState(false);

    const cargar = useCallback(async () => {
        try {
            const { data } = await cliente.get('/clientes-api');
            setClientes(data.data);
        } catch (error) {
            setAviso(error.message);
        } finally {
            setCargando(false);
        }
    }, []);

    useEffect(() => {
        cargar();
    }, [cargar]);

    async function crear(e) {
        e.preventDefault();
        setErrores({});
        setAviso(null);
        setExito(null);
        setEnviando(true);

        try {
            const { data } = await cliente.post('/clientes-api', { nombre });
            setTokenNuevo(data.token);
            setExito(data.mensaje);
            setNombre('');
            await cargar();
        } catch (error) {
            if (error instanceof ErrorDeValidacion) {
                setErrores(
                    Object.fromEntries(Object.entries(error.errores).map(([c, l]) => [c, l[0]])),
                );
            } else {
                setAviso(error.message);
            }
        } finally {
            setEnviando(false);
        }
    }

    async function revocar(c) {
        setAviso(null);
        setExito(null);
        try {
            const { data } = await cliente.delete(`/clientes-api/${c.id}`);
            setExito(data.mensaje);
            await cargar();
        } catch (error) {
            setAviso(error.message);
        }
    }

    return (
        <section className="max-w-3xl">
            <h1 className="mb-1 text-2xl font-semibold">Acceso a la API</h1>
            <p className="mb-6 text-sm text-[var(--color-texto-suave)]">
                Los tokens que usa el CRM para leer el catálogo publicado y enviar los
                prospectos convertidos. Un usuario del panel no entra por esta vía.
            </p>

            {aviso && <p role="alert" className="mb-4 text-sm text-[var(--color-error)]">{aviso}</p>}
            {exito && <p role="status" className="mb-4 text-sm text-[var(--color-exito)]">{exito}</p>}

            {tokenNuevo && (
                <div className="superficie mb-6 p-4">
                    <h2 className="mb-1 text-sm font-semibold">Token generado</h2>
                    <p className="mb-2 text-xs text-[var(--color-texto-suave)]">
                        Se muestra una sola vez: en la base queda solo su hash. Copialo ahora.
                    </p>
                    <code className="block break-all rounded bg-[var(--color-superficie-2)] p-3 text-xs">
                        {tokenNuevo}
                    </code>
                    <button type="button" onClick={() => setTokenNuevo(null)}
                        className="superficie mt-2 px-3 py-1.5 text-sm">
                        Ya lo copié
                    </button>
                </div>
            )}

            <div className="superficie mb-6 overflow-hidden">
                <table className="w-full border-collapse text-sm">
                    <caption className="sr-only">Clientes registrados de la API</caption>
                    <thead>
                        <tr className="border-b border-[var(--color-borde)] text-left">
                            <th scope="col" className="p-3">Cliente</th>
                            <th scope="col" className="p-3">Tokens activos</th>
                            <th scope="col" className="p-3">Creado</th>
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
                        {!cargando && clientes.length === 0 && (
                            <tr>
                                <td colSpan={4} className="p-6 text-center text-[var(--color-texto-suave)]">
                                    Todavía no hay clientes de API registrados.
                                </td>
                            </tr>
                        )}
                        {clientes.map((c) => (
                            <tr key={c.id} className="border-b border-[var(--color-borde)]">
                                <td className="p-3">{c.nombre}</td>
                                <td className="p-3 tabular-nums">{c.tokens_count}</td>
                                <td className="p-3 tabular-nums">{c.creado}</td>
                                <td className="p-3">
                                    <button type="button" onClick={() => revocar(c)}
                                        className="border-0 bg-transparent p-0 underline">
                                        Revocar acceso
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <form onSubmit={crear} noValidate className="superficie p-4">
                <h2 className="mb-4 text-lg font-semibold">Registrar un cliente</h2>

                <Campo id="nombre" etiqueta="Nombre del cliente" error={errores.nombre} requerido
                    ayuda="Por ejemplo, «CRM Comercial».">
                    {(p) => (
                        <input {...p} type="text" maxLength={80} className={claseControl}
                            value={nombre} onChange={(e) => setNombre(e.target.value)} />
                    )}
                </Campo>

                <button type="submit" disabled={enviando || !nombre.trim()}
                    className="rounded-lg bg-[var(--color-acento)] px-4 py-2 text-sm font-medium text-[var(--color-acento-texto)] disabled:opacity-60">
                    {enviando ? 'Generando…' : 'Crear y generar token'}
                </button>
            </form>
        </section>
    );
}
