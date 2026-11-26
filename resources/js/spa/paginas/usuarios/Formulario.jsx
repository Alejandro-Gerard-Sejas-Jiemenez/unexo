import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import cliente, { ErrorDeValidacion } from '../../api/cliente';
import Campo, { claseControl } from '../../componentes/Campo';
import Cargador from '../../componentes/Cargador';
import EncabezadoPagina from '../../componentes/EncabezadoPagina';
import Icono from '../../componentes/Icono';
import { useToast } from '../../componentes/ToastContext';
import ZonaArchivo from '../../componentes/ZonaArchivo';

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
    const { mostrar } = useToast();
    const editando = Boolean(id);

    const [datos, setDatos] = useState(VACIO);
    const [archivoFoto, setArchivoFoto] = useState(null);
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
            const fd = new FormData();
            Object.entries(datos).forEach(([k, v]) => {
                if (k === 'password' && editando && !v) return;
                fd.append(k, v ?? '');
            });
            if (archivoFoto) {
                fd.append('foto_perfil', archivoFoto);
            }

            if (editando) {
                fd.append('_method', 'PUT');
                const { data } = await cliente.post(`/usuarios/${id}`, fd, {
                    headers: { 'Content-Type': 'multipart/form-data' },
                });
                mostrar(data.mensaje);
            } else {
                const { data } = await cliente.post('/usuarios', fd, {
                    headers: { 'Content-Type': 'multipart/form-data' },
                });
                mostrar(data.mensaje);
            }
            navegar('/usuarios');
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
        return <Cargador texto="Cargando" />;
    }

    const limites = opciones.limites ?? {};

    return (
        <section>
            <EncabezadoPagina
                volverA="/usuarios"
                volverTexto="Volver a usuarios"
                titulo={editando ? 'Editar usuario' : 'Nuevo usuario'}
            />

            {aviso && (
                <p role="alert" className="superficie mb-4 border-(--color-error) p-3 text-sm text-(--color-error)">
                    {aviso}
                </p>
            )}

            <div className="superficie max-w-4xl overflow-hidden rounded-xl shadow-sm">
                <form onSubmit={enviar} noValidate className="p-6 sm:p-8">
                    <div className="grid grid-cols-1 gap-x-8 gap-y-4 md:grid-cols-2">
                        <div className="space-y-4">
                            <h3 className="mb-4 border-b border-(--color-borde) pb-2 text-lg font-medium">
                                Información de la cuenta
                            </h3>

                            <Campo id="rol" etiqueta="Rol" error={errores.rol} esSelect requerido>
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

                            <Campo
                                id="correo_institucional"
                                etiqueta="Correo institucional"
                                error={errores.correo_institucional}
                                icono="correo"
                            >
                                {(props) => (
                                    <input
                                        {...props}
                                        type="email"
                                        autoComplete="off"
                                        maxLength={limites.correo_institucional}
                                        className={claseControl}
                                        value={datos.correo_institucional}
                                        onChange={cambiar('correo_institucional')}
                                    />
                                )}
                            </Campo>

                            <Campo id="email" etiqueta="Correo personal" error={errores.email} icono="correo" requerido>
                                {(props) => (
                                    <input
                                        {...props}
                                        type="email"
                                        autoComplete="off"
                                        maxLength={limites.email}
                                        className={claseControl}
                                        value={datos.email}
                                        onChange={cambiar('email')}
                                    />
                                )}
                            </Campo>

                            <Campo
                                id="password"
                                etiqueta="Contraseña"
                                error={errores.password}
                                icono="candado"
                                esPassword
                                requerido={!editando}
                                ayuda={editando ? 'Déjala vacía para no cambiarla.' : 'Mínimo 8 caracteres.'}
                            >
                                {(props) => (
                                    <input
                                        {...props}
                                        autoComplete="new-password"
                                        maxLength={limites.password}
                                        className={claseControl}
                                        value={datos.password}
                                        onChange={cambiar('password')}
                                    />
                                )}
                            </Campo>
                        </div>

                        <div className="mt-8 space-y-4 md:mt-0">
                            <h3 className="mb-4 border-b border-(--color-borde) pb-2 text-lg font-medium">
                                Datos personales
                            </h3>

                            <Campo
                                id="documento_identidad"
                                etiqueta="Documento de identidad"
                                error={errores.documento_identidad}
                                icono="hash"
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

                            <Campo id="nombres" etiqueta="Nombres" error={errores.nombres} icono="usuarios" requerido>
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

                            <Campo id="apellidos" etiqueta="Apellidos" error={errores.apellidos} icono="usuarios" requerido>
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

                            <Campo id="telefono" etiqueta="Teléfono" error={errores.telefono} icono="telefono">
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
                    </div>

                    <div className="mt-6 border-t border-(--color-borde) pt-6">
                        <ZonaArchivo
                            etiqueta="Foto de perfil (opcional)"
                            ayuda="JPG, PNG o WebP de hasta 2 MB."
                            urlActual={datos.foto_perfil_url}
                            archivo={archivoFoto}
                            alCambiar={setArchivoFoto}
                            error={errores.foto_perfil || errores.foto_perfil_url}
                        />
                    </div>

                    <div className="form-acciones">
                        <button
                            type="button"
                            onClick={() => navegar('/usuarios')}
                            className="btn btn-secundario"
                        >
                            Cancelar
                        </button>
                        <button type="submit" disabled={enviando} className="btn btn-primario">
                            <Icono nombre="guardar" />
                            {enviando ? 'Guardando…' : 'Guardar'}
                        </button>
                    </div>
                </form>
            </div>
        </section>
    );
}
