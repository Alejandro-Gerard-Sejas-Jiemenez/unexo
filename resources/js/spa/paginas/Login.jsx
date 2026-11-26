import { useState } from 'react';
import { useAuth } from '../auth/AuthContext';
import { ErrorDeValidacion } from '../api/cliente';
import { conBase } from '../base';
import Campo, { claseControl } from '../componentes/Campo';
import Icono from '../componentes/Icono';
import PanelAccesibilidad from '../componentes/PanelAccesibilidad';
import PieDePagina from '../componentes/PieDePagina';

export default function Login() {
    const { entrar } = useAuth();
    const [datos, setDatos] = useState({ usuario: '', password: '', recordar: false });
    const [errores, setErrores] = useState({});
    const [aviso, setAviso] = useState(null);
    const [enviando, setEnviando] = useState(false);

    const cambiar = (campo) => (evento) =>
        setDatos((previo) => ({
            ...previo,
            [campo]: evento.target.type === 'checkbox' ? evento.target.checked : evento.target.value,
        }));

    async function enviar(evento) {
        evento.preventDefault();
        setErrores({});
        setAviso(null);

        const locales = {};
        if (!datos.usuario.trim()) locales.usuario = 'Ingresa tu correo.';
        if (!datos.password) locales.password = 'Ingresa tu contraseña.';

        if (Object.keys(locales).length > 0) {
            setErrores(locales);
            return;
        }

        setEnviando(true);

        try {
            await entrar(datos);
        } catch (error) {
            if (error instanceof ErrorDeValidacion) {
                setErrores(
                    Object.fromEntries(
                        Object.entries(error.errores).map(([campo, lista]) => [campo, lista[0]]),
                    ),
                );
            } else {
                setAviso(error.message);
            }
        } finally {
            setEnviando(false);
        }
    }

    return (
        <div className="flex min-h-screen flex-col">
            <div className="flex w-full flex-1 flex-col md:flex-row">
                <section
                    className="relative hidden w-full flex-col justify-center overflow-hidden p-12 md:flex md:w-1/2 lg:w-5/12 lg:p-20"
                    style={{
                        background:
                            'linear-gradient(145deg, var(--color-acento) 0%, color-mix(in srgb, var(--color-acento) 72%, black) 100%)',
                        color: 'var(--color-acento-texto)',
                    }}
                    aria-label="Presentación"
                >
                    <div
                        className="pointer-events-none absolute -top-24 -left-24 h-96 w-96 rounded-full opacity-15 blur-3xl"
                        style={{ backgroundColor: '#ffffff' }}
                    />
                    <div
                        className="pointer-events-none absolute right-0 bottom-0 h-80 w-80 rounded-full opacity-20 blur-2xl"
                        style={{ backgroundColor: 'var(--color-realce)' }}
                    />

                    <div className="relative z-10 flex h-full flex-col justify-between">
                        <div className="mt-8 flex w-full justify-center">
                            <span className="mb-6 inline-flex items-center justify-center rounded-3xl bg-white p-6 shadow-lg">
                                <img
                                    src={conBase('/images/logo_v2.png')}
                                    alt="NEXO SIS"
                                    className="h-20 w-auto object-contain"
                                />
                            </span>
                        </div>

                        <div className="mt-5 mb-auto text-center">
                            <h2 className="mb-6 text-4xl leading-tight font-extrabold tracking-tight lg:text-5xl">
                                Sistema
                                <br />
                                de
                                <br />
                                Administración
                                <br />
                                Académica
                            </h2>
                            <p className="mx-auto mb-10 max-w-md text-lg font-medium opacity-85 lg:text-xl">
                                La unidad de cursos y consultorías en un solo lugar.
                            </p>
                        </div>

                        <div className="text-center text-sm font-medium opacity-75">
                            &copy; {new Date().getFullYear()} NEXO SIS. Todos los derechos reservados.
                        </div>
                    </div>
                </section>

                <section className="relative flex flex-1 flex-col justify-center bg-[var(--color-superficie)] px-4 py-12 sm:px-6 md:py-0 lg:w-7/12 lg:flex-none lg:px-20 xl:px-24">
                    <div className="absolute top-4 right-4 z-20 sm:top-6 sm:right-6">
                        <PanelAccesibilidad />
                    </div>

                    <div className="animate-fade-in relative z-10 mx-auto w-full max-w-sm lg:w-96">
                        <div className="mb-10 text-center md:hidden">
                            <span className="mx-auto inline-flex items-center justify-center rounded-2xl border border-[var(--color-borde)] bg-white p-4 shadow-sm">
                                <img
                                    src={conBase('/images/logo_v2.png')}
                                    alt="NEXO SIS"
                                    className="h-12 w-auto object-contain"
                                />
                            </span>
                        </div>

                        <div className="text-center">
                            <h1 className="text-3xl font-extrabold tracking-tight">
                                Te damos la bienvenida
                            </h1>
                            <p className="mt-3 text-sm font-medium text-[var(--color-texto-suave)]">
                                Ingresa con tu correo institucional o personal.
                            </p>
                        </div>

                        <div className="mt-8">
                            {aviso && (
                                <div
                                    role="alert"
                                    id="error-acceso"
                                    className="mb-6 rounded-lg border border-[color-mix(in_srgb,var(--color-error)_35%,transparent)] bg-[var(--color-error-fondo)] p-4 text-[var(--color-error)] shadow-sm"
                                >
                                    <div className="flex items-start gap-3">
                                        <Icono nombre="alerta" className="mt-0.5 h-5 w-5 shrink-0" />
                                        <div>
                                            <h3 className="text-sm font-semibold">
                                                Hubo un problema con tu acceso
                                            </h3>
                                            <p className="mt-1 text-sm font-medium">{aviso}</p>
                                        </div>
                                    </div>
                                </div>
                            )}

                            <form onSubmit={enviar} noValidate className="space-y-2">
                                <Campo
                                    id="usuario"
                                    etiqueta="Correo electrónico"
                                    error={errores.usuario}
                                    icono="correo"
                                    requerido
                                >
                                    {(props) => (
                                        <input
                                            {...props}
                                            type="email"
                                            autoComplete="username"
                                            placeholder="nombre@correo.com"
                                            className={claseControl}
                                            value={datos.usuario}
                                            onChange={cambiar('usuario')}
                                        />
                                    )}
                                </Campo>

                                <Campo
                                    id="password"
                                    etiqueta="Contraseña"
                                    error={errores.password}
                                    icono="candado"
                                    esPassword
                                    requerido
                                >
                                    {(props) => (
                                        <input
                                            {...props}
                                            autoComplete="current-password"
                                            placeholder="Escribe tu contraseña"
                                            className={claseControl}
                                            value={datos.password}
                                            onChange={cambiar('password')}
                                        />
                                    )}
                                </Campo>

                                <div className="flex items-center pt-1 pb-3">
                                    <input
                                        id="recordar"
                                        type="checkbox"
                                        className="h-4 w-4 cursor-pointer rounded border-[var(--color-borde)] accent-[var(--color-acento)]"
                                        checked={datos.recordar}
                                        onChange={cambiar('recordar')}
                                    />
                                    <label htmlFor="recordar" className="ml-2 block cursor-pointer text-sm font-medium">
                                        Mantener la sesión iniciada
                                    </label>
                                </div>

                                <button
                                    type="submit"
                                    disabled={enviando}
                                    className="btn btn-primario w-full !py-3 transition-all active:scale-[0.98]"
                                >
                                    <Icono nombre="entrar" className="h-5 w-5" />
                                    {enviando ? 'Entrando…' : 'Acceder'}
                                </button>
                            </form>

                            <div className="mt-10 border-t border-[var(--color-borde)] pt-6">
                                <p className="rounded-lg border border-[var(--color-borde)] bg-[var(--color-superficie-cabecera)] p-4 text-center text-sm font-medium text-[var(--color-texto-suave)]">
                                    ¿No puedes ingresar?
                                    <br />
                                    Comunícate con la administración de NEXO SIS para recuperar tu acceso.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <PieDePagina rutaFija="/login" />
        </div>
    );
}
