import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import cliente, { ErrorDeValidacion } from '../../api/cliente';
import Campo, { claseControl } from '../../componentes/Campo';

const VACIO = {
    rol: 'estudiante',
    nombres: '',
    apellidos: '',
    documento_identidad: '',
    email: '',
    correo_institucional: '',
    telefono: '',
    password: '',
    foto_perfil_url: '',
};

export default function UsuarioFormulario() {
    const { id } = useParams();
    const navegar = useNavigate();
    const editando = Boolean(id);

    const [datos, setDatos] = useState(VACIO);
    const [opciones, setOpciones] = useState({ roles: [], limites: {} });
    const [errores, setErrores] = useState({});
    const [aviso, setAviso] = useState(null);
    const [cargando, setCargando] = useState(true);
    const [enviando, setEnviando] = useState(false);

    useEffect(() => {
        let vigente = true;

        (async () => {
            try {
                const peticiones = [cliente.get('/usuarios/opciones')];
                if (editando) peticiones.push(cliente.get(`/usuarios/${id}`));

                const [respOpciones, respUsuario] = await Promise.all(peticiones);
                if (!vigente) return;

                setOpciones(respOpciones.data);

                if (respUsuario) {
                    const u = respUsuario.data.data;
                    setDatos({
                        ...VACIO,
                        ...Object.fromEntries(
                            Object.keys(VACIO).map((campo) => [campo, u[campo] ?? '']),
                        ),
                        password: '',
                    });
                }
            } catch (error) {
                if (vigente) setAviso(error.message);
            } finally {
                if (vigente) setCargando(false);
            }
        })();

        return () => {
            vigente = false;
        };
    }, [editando, id]);

    const cambiar = (campo) => (evento) =>
        setDatos((previo) => ({ ...previo, [campo]: evento.target.value }));

    async function enviar(evento) {
        evento.preventDefault();
        setErrores({});
        setAviso(null);
        setEnviando(true);

        try {
            if (editando) {
                await cliente.put(`/usuarios/${id}`, datos);
            } else {
                await cliente.post('/usuarios', datos);
            }
            navegar('/app/usuarios');
        } catch (error) {
            if (error instanceof ErrorDeValidacion) {
                setErrores(
                    Object.fromEntries(
                        Object.entries(error.errores).map(([campo, lista]) => [campo, lista[0]]),
                    ),
                );
                setAviso('Revisa los campos marcados.');
            } else {
                setAviso(error.message);
            }
        } finally {
            setEnviando(false);
        }
    }

    if (cargando) {
        return <p className="text-[var(--color-texto-suave)]">Cargando…</p>;
    }

    const limites = opciones.limites ?? {};

    return (
        <section className="max-w-2xl">
            <h1 className="mb-6 text-2xl font-semibold">
                {editando ? 'Editar usuario' : 'Nuevo usuario'}
            </h1>

            {aviso && (
                <p role="alert" className="mb-4 text-sm text-[var(--color-error)]">
                    {aviso}
                </p>
            )}

            <form onSubmit={enviar} noValidate>
                <Campo id="rol" etiqueta="Rol" error={errores.rol} requerido>
                    {(props) => (
                        <select {...props} className={claseControl} value={datos.rol} onChange={cambiar('rol')}>
                            {opciones.roles.map((rol) => (
                                <option key={rol.valor} value={rol.valor}>
                                    {rol.etiqueta}
                                </option>
                            ))}
                        </select>
                    )}
                </Campo>

                <div className="grid grid-cols-2 gap-4">
                    <Campo id="nombres" etiqueta="Nombres" error={errores.nombres} requerido>
                        {(props) => (
                            <input
                                {...props}
                                type="text"
                                maxLength={limites.nombres}
                                className={claseControl}
                                value={datos.nombres}
                                onChange={cambiar('nombres')}
                            />
                        )}
                    </Campo>

                    <Campo id="apellidos" etiqueta="Apellidos" error={errores.apellidos} requerido>
                        {(props) => (
                            <input
                                {...props}
                                type="text"
                                maxLength={limites.apellidos}
                                className={claseControl}
                                value={datos.apellidos}
                                onChange={cambiar('apellidos')}
                            />
                        )}
                    </Campo>
                </div>

                <Campo id="email" etiqueta="Correo electrónico" error={errores.email} requerido>
                    {(props) => (
                        <input
                            {...props}
                            type="email"
                            maxLength={limites.email}
                            className={claseControl}
                            value={datos.email}
                            onChange={cambiar('email')}
                        />
                    )}
                </Campo>

                <Campo
                    id="correo_institucional"
                    etiqueta="Correo institucional"
                    error={errores.correo_institucional}
                    ayuda="Opcional. Se usa para el acceso a las salas virtuales."
                >
                    {(props) => (
                        <input
                            {...props}
                            type="email"
                            maxLength={limites.correo_institucional}
                            className={claseControl}
                            value={datos.correo_institucional}
                            onChange={cambiar('correo_institucional')}
                        />
                    )}
                </Campo>

                <div className="grid grid-cols-2 gap-4">
                    <Campo
                        id="documento_identidad"
                        etiqueta="Documento de identidad"
                        error={errores.documento_identidad}
                    >
                        {(props) => (
                            <input
                                {...props}
                                type="text"
                                maxLength={limites.documento_identidad}
                                className={claseControl}
                                value={datos.documento_identidad}
                                onChange={cambiar('documento_identidad')}
                            />
                        )}
                    </Campo>

                    <Campo id="telefono" etiqueta="Teléfono" error={errores.telefono}>
                        {(props) => (
                            <input
                                {...props}
                                type="tel"
                                maxLength={limites.telefono}
                                className={claseControl}
                                value={datos.telefono}
                                onChange={cambiar('telefono')}
                            />
                        )}
                    </Campo>
                </div>

                <Campo
                    id="foto_perfil_url"
                    etiqueta="Foto de perfil"
                    error={errores.foto_perfil_url}
                    ayuda="Dirección de la imagen."
                >
                    {(props) => (
                        <input
                            {...props}
                            type="url"
                            className={claseControl}
                            value={datos.foto_perfil_url}
                            onChange={cambiar('foto_perfil_url')}
                        />
                    )}
                </Campo>

                <Campo
                    id="password"
                    etiqueta="Contraseña"
                    error={errores.password}
                    requerido={!editando}
                    ayuda={
                        editando
                            ? 'Déjala en blanco para conservar la contraseña actual.'
                            : 'Mínimo 8 caracteres.'
                    }
                >
                    {(props) => (
                        <input
                            {...props}
                            type="password"
                            autoComplete="new-password"
                            maxLength={limites.password}
                            className={claseControl}
                            value={datos.password}
                            onChange={cambiar('password')}
                        />
                    )}
                </Campo>

                <div className="mt-6 flex gap-3">
                    <button
                        type="submit"
                        disabled={enviando}
                        className="rounded-lg bg-[var(--color-acento)] px-4 py-2 text-sm font-medium text-[var(--color-acento-texto)] disabled:opacity-60"
                    >
                        {enviando ? 'Guardando…' : 'Guardar'}
                    </button>
                    <button
                        type="button"
                        onClick={() => navegar('/app/usuarios')}
                        className="rounded-lg border border-[var(--color-borde)] bg-transparent px-4 py-2 text-sm"
                    >
                        Cancelar
                    </button>
                </div>
            </form>
        </section>
    );
}
