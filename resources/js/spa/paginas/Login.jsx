import { useState } from 'react';
import { useAuth } from '../auth/AuthContext';
import { ErrorDeValidacion } from '../api/cliente';
import Campo, { claseControl } from '../componentes/Campo';

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

        // Validación en el cliente: ayuda a quien escribe. La del servidor es la que manda.
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
        <main className="flex min-h-screen items-center justify-center p-6">
            <form
                onSubmit={enviar}
                noValidate
                className="w-full max-w-sm rounded-2xl border border-[var(--color-borde)] bg-[var(--color-superficie)] p-8"
            >
                <h1 className="mb-1 text-2xl font-semibold">NEXO SIS</h1>
                <p className="mb-6 text-sm text-[var(--color-texto-suave)]">
                    Sistema de Administración Académica
                </p>

                {aviso && (
                    <p role="alert" className="mb-4 text-sm text-[var(--color-error)]">
                        {aviso}
                    </p>
                )}

                <Campo id="usuario" etiqueta="Correo" error={errores.usuario} requerido>
                    {(props) => (
                        <input
                            {...props}
                            type="text"
                            autoComplete="username"
                            className={claseControl}
                            value={datos.usuario}
                            onChange={cambiar('usuario')}
                        />
                    )}
                </Campo>

                <Campo id="password" etiqueta="Contraseña" error={errores.password} requerido>
                    {(props) => (
                        <input
                            {...props}
                            type="password"
                            autoComplete="current-password"
                            className={claseControl}
                            value={datos.password}
                            onChange={cambiar('password')}
                        />
                    )}
                </Campo>

                <label className="mb-6 flex items-center gap-2 text-sm">
                    <input type="checkbox" checked={datos.recordar} onChange={cambiar('recordar')} />
                    Recordarme
                </label>

                <button
                    type="submit"
                    disabled={enviando}
                    className="w-full rounded-lg bg-[var(--color-acento)] px-4 py-2.5 text-sm font-medium text-[var(--color-acento-texto)] disabled:opacity-60"
                >
                    {enviando ? 'Entrando…' : 'Entrar'}
                </button>
            </form>
        </main>
    );
}
