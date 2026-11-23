import { useState } from 'react';
import { useAuth } from '../auth/AuthContext';
import { ErrorDeValidacion } from '../api/cliente';
import { conBase } from '../base';
import Campo, { claseControl } from '../componentes/Campo';
import PanelAccesibilidad from '../componentes/PanelAccesibilidad';

export default function Login() {
    const { entrar } = useAuth();
    const [datos, setDatos] = useState({ usuario: '', password: '', recordar: false });
    const [verClave, setVerClave] = useState(false);
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

        // Validación en el cliente: ayuda a quien escribe. La del servidor manda.
        const locales = {};
        if (!datos.usuario.trim()) locales.usuario = 'Ingresá tu correo.';
        if (!datos.password) locales.password = 'Ingresá tu contraseña.';

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
        <div className="grid min-h-screen lg:grid-cols-2">
            {/* Panel de marca: decorativo, por eso se oculta en pantallas chicas. */}
            <aside
                className="relative hidden flex-col items-center justify-center gap-8 p-12 text-center lg:flex"
                style={{
                    background:
                        'linear-gradient(150deg, #0d4066 0%, #082f4e 45%, #061f36 100%)',
                    color: '#ffffff',
                }}
            >
                <img
                    src={conBase('/images/logo.png')}
                    alt=""
                    width="150"
                    height="150"
                    className="h-[150px] w-[150px] rounded-3xl bg-white/95 object-contain p-4"
                />

                <div>
                    <h1 className="text-4xl leading-tight font-extrabold tracking-tight">
                        Sistema de Administración Académica
                    </h1>
                    <p className="mt-4 text-lg text-white/70">
                        La unidad de cursos y consultorías, en un solo lugar.
                    </p>
                </div>

                <p className="absolute bottom-8 text-xs text-white/50">
                    © 2026 NEXO SIS · Universidad Autónoma Gabriel René Moreno
                </p>
            </aside>

            {/* Formulario */}
            <main className="flex items-center justify-center p-6">
                <div className="w-full max-w-sm">
                    <div className="mb-8 flex items-center justify-between">
                        <div className="flex items-center gap-3 lg:hidden">
                            <img
                                src={conBase('/images/logo.png')}
                                alt=""
                                width="40"
                                height="40"
                                className="h-10 w-10 object-contain"
                            />
                            <span className="text-lg font-bold">NEXO SIS</span>
                        </div>
                        <div className="ml-auto">
                            <PanelAccesibilidad />
                        </div>
                    </div>

                    <h2 className="text-3xl font-extrabold tracking-tight">Te damos la bienvenida</h2>
                    <p className="mt-2 mb-8 text-sm text-[var(--color-texto-suave)]">
                        Ingresá con tu correo institucional o personal.
                    </p>

                    <form onSubmit={enviar} noValidate>
                        {aviso && (
                            <p
                                role="alert"
                                className="mb-4 rounded-xl border border-[var(--color-error)] px-3 py-2 text-sm text-[var(--color-error)]"
                            >
                                {aviso}
                            </p>
                        )}

                        <Campo id="usuario" etiqueta="Correo electrónico" error={errores.usuario} requerido>
                            {(props) => (
                                <input
                                    {...props}
                                    type="text"
                                    autoComplete="username"
                                    placeholder="nombre@correo.com"
                                    className={claseControl + ' py-2.5'}
                                    value={datos.usuario}
                                    onChange={cambiar('usuario')}
                                />
                            )}
                        </Campo>

                        <Campo id="password" etiqueta="Contraseña" error={errores.password} requerido>
                            {(props) => (
                                <div className="relative">
                                    <input
                                        {...props}
                                        type={verClave ? 'text' : 'password'}
                                        autoComplete="current-password"
                                        placeholder="Escribí tu contraseña"
                                        className={claseControl + ' py-2.5 pr-11'}
                                        value={datos.password}
                                        onChange={cambiar('password')}
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setVerClave((v) => !v)}
                                        aria-pressed={verClave}
                                        className="absolute inset-y-0 right-0 border-0 bg-transparent px-3 text-xs text-[var(--color-texto-suave)]"
                                    >
                                        {verClave ? 'Ocultar' : 'Ver'}
                                        <span className="sr-only"> la contraseña</span>
                                    </button>
                                </div>
                            )}
                        </Campo>

                        <label className="mb-6 flex items-center gap-2 text-sm">
                            <input type="checkbox" checked={datos.recordar} onChange={cambiar('recordar')} />
                            Mantener la sesión iniciada
                        </label>

                        <button
                            type="submit"
                            disabled={enviando}
                            className="w-full rounded-xl bg-[var(--color-acento)] px-4 py-3 text-sm font-semibold text-[var(--color-acento-texto)] disabled:opacity-60"
                        >
                            {enviando ? 'Entrando…' : 'Acceder'}
                        </button>
                    </form>

                    <div className="superficie mt-8 p-4 text-center text-sm text-[var(--color-texto-suave)]">
                        ¿No podés ingresar? Comunicate con la administración de NEXO SIS para
                        recuperar tu acceso.
                    </div>
                </div>
            </main>
        </div>
    );
}
