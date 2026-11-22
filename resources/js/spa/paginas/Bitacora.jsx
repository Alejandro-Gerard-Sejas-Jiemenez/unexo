import { useCallback, useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import cliente from '../api/cliente';
import { claseControl } from '../componentes/Campo';

const EVENTOS = [
    { valor: '', etiqueta: 'Todos los eventos' },
    { valor: 'login_exitoso', etiqueta: 'Inicio de sesión' },
    { valor: 'login_fallido', etiqueta: 'Intento fallido' },
    { valor: 'logout', etiqueta: 'Cierre de sesión' },
    { valor: 'acceso_recurso', etiqueta: 'Acceso a recurso' },
];

/** Bitácora de auditoría: quién entró, desde dónde y a qué accedió. */
export default function Bitacora() {
    const [parametros, setParametros] = useSearchParams();
    const [pagina, setPagina] = useState(null);
    const [cargando, setCargando] = useState(true);
    const [aviso, setAviso] = useState(null);

    const clave = parametros.toString();

    const cargar = useCallback(async (query) => {
        setCargando(true);
        try {
            const { data } = await cliente.get(`/bitacora?${query}`);
            setPagina(data);
            setAviso(null);
        } catch (error) {
            setAviso(error.message);
        } finally {
            setCargando(false);
        }
    }, []);

    useEffect(() => {
        cargar(clave);
    }, [cargar, clave]);

    function filtrar(campo, valor) {
        const siguiente = new URLSearchParams(parametros);
        valor ? siguiente.set(campo, valor) : siguiente.delete(campo);
        siguiente.delete('page');
        setParametros(siguiente);
    }

    function irAPagina(numero) {
        const siguiente = new URLSearchParams(parametros);
        siguiente.set('page', String(numero));
        setParametros(siguiente);
    }

    const meta = pagina?.meta;

    return (
        <section>
            <h1 className="mb-1 text-2xl font-semibold">Bitácora de auditoría</h1>
            <p className="mb-6 text-sm text-[var(--color-texto-suave)]">
                Registro de inicios de sesión, intentos fallidos y accesos a los recursos.
            </p>

            <div className="mb-4 flex flex-wrap gap-3">
                <label className="flex-1">
                    <span className="sr-only">Buscar por recurso, IP o correo</span>
                    <input
                        type="search"
                        placeholder="Buscar por recurso, IP o correo"
                        className={claseControl}
                        defaultValue={parametros.get('q') ?? ''}
                        onChange={(e) => filtrar('q', e.target.value)}
                    />
                </label>

                <label>
                    <span className="sr-only">Filtrar por evento</span>
                    <select
                        className={claseControl}
                        value={parametros.get('evento') ?? ''}
                        onChange={(e) => filtrar('evento', e.target.value)}
                    >
                        {EVENTOS.map((e) => (
                            <option key={e.valor} value={e.valor}>
                                {e.etiqueta}
                            </option>
                        ))}
                    </select>
                </label>

                <label className="flex items-center gap-2 text-sm">
                    Desde
                    <input
                        type="date"
                        className={claseControl}
                        defaultValue={parametros.get('desde') ?? ''}
                        onChange={(e) => filtrar('desde', e.target.value)}
                    />
                </label>

                <label className="flex items-center gap-2 text-sm">
                    Hasta
                    <input
                        type="date"
                        className={claseControl}
                        defaultValue={parametros.get('hasta') ?? ''}
                        onChange={(e) => filtrar('hasta', e.target.value)}
                    />
                </label>
            </div>

            {aviso && (
                <p role="alert" className="mb-4 text-sm text-[var(--color-error)]">
                    {aviso}
                </p>
            )}

            <div className="superficie overflow-hidden" aria-live="polite">
                <table className="w-full border-collapse text-sm">
                    <caption className="sr-only">Eventos registrados en la bitácora</caption>
                    <thead>
                        <tr className="border-b border-[var(--color-borde)] text-left">
                            <th scope="col" className="p-3">Fecha</th>
                            <th scope="col" className="p-3">Evento</th>
                            <th scope="col" className="p-3">Usuario</th>
                            <th scope="col" className="p-3">Recurso</th>
                            <th scope="col" className="p-3">IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        {cargando && (
                            <tr>
                                <td colSpan={5} className="p-6 text-center text-[var(--color-texto-suave)]">
                                    Cargando registros…
                                </td>
                            </tr>
                        )}

                        {!cargando && pagina?.data.length === 0 && (
                            <tr>
                                <td colSpan={5} className="p-6 text-center text-[var(--color-texto-suave)]">
                                    No hay eventos que coincidan con el filtro.
                                </td>
                            </tr>
                        )}

                        {!cargando &&
                            pagina?.data.map((fila) => (
                                <tr key={fila.id} className="border-b border-[var(--color-borde)]">
                                    <td className="p-3 whitespace-nowrap tabular-nums">{fila.fecha}</td>
                                    <td className="p-3">{fila.evento_label}</td>
                                    <td className="p-3">{fila.usuario}</td>
                                    <td className="p-3 break-all">{fila.recurso}</td>
                                    <td className="p-3 tabular-nums">{fila.ip}</td>
                                </tr>
                            ))}
                    </tbody>
                </table>
            </div>

            {meta && meta.last_page > 1 && (
                <nav aria-label="Paginación" className="mt-4 flex items-center gap-3">
                    <button
                        type="button"
                        disabled={meta.current_page <= 1}
                        onClick={() => irAPagina(meta.current_page - 1)}
                        className="superficie px-3 py-1.5 text-sm disabled:opacity-50"
                    >
                        Anterior
                    </button>
                    <span className="text-sm text-[var(--color-texto-suave)]">
                        Página {meta.current_page} de {meta.last_page} · {meta.total} eventos
                    </span>
                    <button
                        type="button"
                        disabled={meta.current_page >= meta.last_page}
                        onClick={() => irAPagina(meta.current_page + 1)}
                        className="superficie px-3 py-1.5 text-sm disabled:opacity-50"
                    >
                        Siguiente
                    </button>
                </nav>
            )}
        </section>
    );
}
