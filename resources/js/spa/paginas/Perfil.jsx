import { useState } from 'react';
import cliente, { ErrorDeValidacion } from '../api/cliente';
import { useAuth } from '../auth/AuthContext';
import { conBase } from '../base';
import Campo, { claseControl } from '../componentes/Campo';
import EncabezadoPagina from '../componentes/EncabezadoPagina';
import Icono from '../componentes/Icono';
import { useToast } from '../componentes/ToastContext';
import ZonaArchivo from '../componentes/ZonaArchivo';

function iniciales(u) {
    return ((u?.nombres?.[0] ?? '') + (u?.apellidos?.[0] ?? '')).toUpperCase() || 'NX';
}

export default function Perfil() {
    const { usuario, recargarUsuario } = useAuth();
    const { mostrar } = useToast();

    const [telefono, setTelefono] = useState(usuario?.telefono ?? '');
    const [password, setPassword] = useState('');
    const [confirmacion, setConfirmacion] = useState('');
    const [archivoFoto, setArchivoFoto] = useState(null);
    const [errores, setErrores] = useState({});
    const [aviso, setAviso] = useState(null);
    const [enviando, setEnviando] = useState(false);

    async function enviar(e) {
        e.preventDefault();
        setErrores({});
        setAviso(null);

        if (password) {
            if (password.length < 8) {
                setErrores({ password: 'La contraseña debe tener al menos 8 caracteres.' });
                setAviso('Revisa los campos marcados.');
                return;
            }
            if (password !== confirmacion) {
                setErrores({ password_confirmation: 'Las contraseñas no coinciden.' });
                setAviso('Revisa los campos marcados.');
                return;
            }
        }

        setEnviando(true);

        try {
            const fd = new FormData();
            fd.append('_method', 'PUT');
            fd.append('telefono', telefono ?? '');
            if (password) {
                fd.append('password', password);
                fd.append('password_confirmation', confirmacion);
            }
            if (archivoFoto) {
                fd.append('foto_perfil', archivoFoto);
            }

            const { data } = await cliente.post('/perfil', fd, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            await recargarUsuario();
            setPassword('');
            setConfirmacion('');
            setArchivoFoto(null);
            mostrar(data.mensaje);
        } catch (error) {
            if (error instanceof ErrorDeValidacion) {
                setErrores(
                    Object.fromEntries(Object.entries(error.errores).map(([c, l]) => [c, l[0]])),
                );
                setAviso('Revisa los campos marcados.');
            } else {
                setAviso(error.message);
            }
        } finally {
            setEnviando(false);
        }
    }

    return (
        <section className="max-w-4xl">
            <EncabezadoPagina
                volverA="/"
                volverTexto="Volver al inicio"
                titulo="Mi perfil"
                subtitulo="Actualiza tu fotografía, teléfono o contraseña de acceso."
            />

            {aviso && (
                <p role="alert" className="superficie mb-4 border-(--color-error) p-3 text-sm text-(--color-error)">
                    {aviso}
                </p>
            )}

            <form onSubmit={enviar} noValidate className="superficie overflow-hidden rounded-xl shadow-sm">
                <div className="flex flex-wrap items-center gap-4 border-b border-(--color-borde) bg-(--color-superficie-cabecera) px-6 py-5 sm:px-8">
                    {usuario?.foto_perfil_url ? (
                        <img
                            src={conBase(usuario.foto_perfil_url)}
                            alt=""
                            className="h-14 w-14 rounded-full border-2 border-white object-cover shadow-sm"
                        />
                    ) : (
                        <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-full border-2 border-white bg-(--color-acento-suave) text-xl font-bold text-(--color-acento) shadow-sm">
                            {iniciales(usuario)}
                        </div>
                    )}
                    <div className="min-w-0">
                        <h2 className="text-lg font-bold">{usuario?.nombre_completo}</h2>
                        <p className="text-sm text-(--color-texto-suave)">
                            {usuario?.rol_label} · {usuario?.correo_institucional || usuario?.email}
                        </p>
                    </div>
                </div>

                <div className="space-y-6 p-6 sm:p-8">
                    <div className="grid grid-cols-1 gap-x-8 gap-y-4 md:grid-cols-2">
                        <div>
                            <ZonaArchivo
                                etiqueta="Foto de perfil"
                                ayuda="JPG, PNG o WebP de hasta 2 MB."
                                urlActual={usuario?.foto_perfil_url}
                                archivo={archivoFoto}
                                alCambiar={setArchivoFoto}
                                error={errores.foto_perfil || errores.foto_perfil_url}
                            />
                        </div>

                        <div>
                            <Campo id="telefono" etiqueta="Teléfono" error={errores.telefono} icono="telefono">
                                {(p) => (
                                    <input
                                        {...p}
                                        type="tel"
                                        maxLength={30}
                                        className={claseControl}
                                        value={telefono}
                                        onChange={(e) => setTelefono(e.target.value)}
                                    />
                                )}
                            </Campo>

                            <Campo
                                id="password"
                                etiqueta="Nueva contraseña"
                                error={errores.password}
                                icono="candado"
                                esPassword
                                ayuda="Mínimo 8 caracteres. Déjala en blanco para conservar la actual."
                            >
                                {(p) => (
                                    <input
                                        {...p}
                                        minLength={8}
                                        maxLength={100}
                                        autoComplete="new-password"
                                        className={claseControl}
                                        value={password}
                                        onChange={(e) => setPassword(e.target.value)}
                                    />
                                )}
                            </Campo>

                            <Campo
                                id="password_confirmation"
                                etiqueta="Confirmar contraseña"
                                error={errores.password_confirmation}
                                icono="candado"
                                esPassword
                            >
                                {(p) => (
                                    <input
                                        {...p}
                                        minLength={8}
                                        maxLength={100}
                                        autoComplete="new-password"
                                        className={claseControl}
                                        value={confirmacion}
                                        onChange={(e) => setConfirmacion(e.target.value)}
                                    />
                                )}
                            </Campo>
                        </div>
                    </div>

                    <div className="form-acciones">
                        <button type="submit" disabled={enviando} className="btn btn-primario">
                            <Icono nombre="guardar" />
                            {enviando ? 'Guardando…' : 'Guardar cambios'}
                        </button>
                    </div>
                </div>
            </form>
        </section>
    );
}
