import { useCallback, useEffect, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import cliente from '../../api/cliente';
import { claseControl } from '../../componentes/Campo';

export default function UsuariosLista() {
    const [parametros, setParametros] = useSearchParams();
    const [pagina, setPagina] = useState(null);
    const [cargando, setCargando] = useState(true);
    const [aviso, setAviso] = useState(null);

    const consulta = {
        q: parametros.get('q') ?? '',
        rol: parametros.get('rol') ?? '',
        estado: parametros.get('estado') ?? '',
        page: parametros.get('page') ?? '1',
    };
    const clave = parametros.toString();

    const cargar = useCallback(async (query) => {
        setCargando(true);
        setAviso(null);
        try {
            const { data } = await cliente.get(`/usuarios?${query}`);
            setPagina(data);
        } catch (error) {
            setAviso(error.message);
        } finally {
            setCargando(false);
        }
    }, []);

    useEffect(() => {
        cargar(clave);
    }, [cargar, clave]);

    /** Al cambiar un filtro volvemos a la primera página: si no, se ve vacío. */
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

    async function alternarEstado(usuario) {
        setAviso(null);
        try {
            await cliente.patch(`/usuarios/${usuario.id}/estado`);
            await cargar(clave);
        } catch (error) {
            setAviso(error.errores?.usuario?.[0] ?? error.message);
        }
    }

    const meta = pagina?.meta;

    return (
        <section>
            <div className="mb-6 flex items-center justify-between">
                <h1 className="text-2xl font-semibold">Usuarios</h1>
                <Link
                    to="/app/usuarios/nuevo"
                    className="rounded-lg bg-[var(--color-acento)] px-4 py-2 text-sm font-medium text-[var(--color-acento-texto)] no-underline"
                >
                    Nuevo usuario
                </Link>
            </div>

            <div className="mb-4 flex flex-wrap gap-3">
                <label className="flex-1">
                    <span className="sr-only">Buscar usuario</span>
                    <input
                        type="search"
                        placeholder="Buscar por nombre o correo"
                        className={claseControl}
                        defaultValue={consulta.q}
                        onChange={(e) => filtrar('q', e.target.value)}
                    />
                </label>

                <label>
                    <span className="sr-only">Filtrar por rol</span>
                    <select
                        className={claseControl}
                        value={consulta.rol}
                        onChange={(e) => filtrar('rol', e.target.value)}
                    >
                        <option value="">Todos los roles</option>
                        <option value="administrador">Administrador</option>
                        <option value="docente">Docente</option>
                        <option value="estudiante">Estudiante</option>
                    </select>
                </label>

                <label>
                    <span className="sr-only">Filtrar por estado</span>
                    <select
                        className={claseControl}
                        value={consulta.estado}
                        onChange={(e) => filtrar('estado', e.target.value)}
                    >
                        <option value="">Todos los estados</option>
                        <option value="inactivo">Solo inactivos</option>
                    </select>
                </label>
            </div>

            {aviso && (
                <p role="alert" className="mb-4 text-sm text-[var(--color-error)]">
                    {aviso}
                </p>
            )}

            <div
                aria-live="polite"
                className="overflow-hidden rounded-xl border border-[var(--color-borde)] bg-[var(--color-superficie)]"
            >
                <table className="w-full border-collapse text-sm">
                    <caption className="sr-only">Listado de usuarios del sistema</caption>
                    <thead>
                        <tr className="border-b border-[var(--color-borde)] text-left">
                            <th scope="col" className="p-3">Nombre</th>
                            <th scope="col" className="p-3">Rol</th>
                            <th scope="col" className="p-3">Correo</th>
                            <th scope="col" className="p-3">Estado</th>
                            <th scope="col" className="p-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        {cargando && (
                            <tr>
                                <td colSpan={5} className="p-6 text-center text-[var(--color-texto-suave)]">
                                    Cargando usuarios…
                                </td>
                            </tr>
                        )}

                        {!cargando && pagina?.data.length === 0 && (
                            <tr>
                                <td colSpan={5} className="p-6 text-center text-[var(--color-texto-suave)]">
                                    No hay usuarios que coincidan con la búsqueda.
                                </td>
                            </tr>
                        )}

                        {!cargando &&
                            pagina?.data.map((usuario) => (
                                <tr key={usuario.id} className="border-b border-[var(--color-borde)]">
                                    <td className="p-3">{usuario.nombre_completo}</td>
                                    <td className="p-3">{usuario.rol_label}</td>
                                    <td className="p-3">{usuario.email}</td>
                                    <td className="p-3">{usuario.activo ? 'Activo' : 'Inactivo'}</td>
                                    <td className="p-3">
                                        <Link to={`/app/usuarios/${usuario.id}`} className="mr-3">
                                            Editar
                                        </Link>
                                        <button
                                            type="button"
                                            onClick={() => alternarEstado(usuario)}
                                            className="border-0 bg-transparent p-0 underline"
                                        >
                                            {usuario.activo ? 'Desactivar' : 'Activar'}
                                        </button>
                                    </td>
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
                        className="rounded-lg border border-[var(--color-borde)] bg-transparent px-3 py-1.5 text-sm disabled:opacity-50"
                    >
                        Anterior
                    </button>
                    <span className="text-sm text-[var(--color-texto-suave)]">
                        Página {meta.current_page} de {meta.last_page} · {meta.total} usuarios
                    </span>
                    <button
                        type="button"
                        disabled={meta.current_page >= meta.last_page}
                        onClick={() => irAPagina(meta.current_page + 1)}
                        className="rounded-lg border border-[var(--color-borde)] bg-transparent px-3 py-1.5 text-sm disabled:opacity-50"
                    >
                        Siguiente
                    </button>
                </nav>
            )}
        </section>
    );
}
