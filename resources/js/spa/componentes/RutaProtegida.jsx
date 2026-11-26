import { Link } from 'react-router-dom';
import { useAuth } from '../auth/AuthContext';
import Icono from './Icono';

export default function RutaProtegida({ roles, children }) {
    const { usuario } = useAuth();

    if (!usuario || (roles && !roles.includes(usuario.rol))) {
        return (
            <section className="superficie animate-fade-in mx-auto max-w-lg p-6 text-center">
                <div className="mx-auto mb-3 grid h-12 w-12 place-items-center rounded-full bg-[var(--color-error-fondo)] text-[var(--color-error)]">
                    <Icono nombre="candado" className="h-6 w-6" />
                </div>
                <h1 className="text-xl font-semibold">Acceso restringido</h1>
                <p className="mt-2 text-sm text-[var(--color-texto-suave)]">
                    Tu rol actual ({usuario?.rol_label ?? 'Invitado'}) no tiene permiso para ver esta sección.
                </p>
                <Link to="/" className="btn btn-primario mt-4">
                    <Icono nombre="inicio" className="h-4 w-4" />
                    Volver al inicio
                </Link>
            </section>
        );
    }

    return children;
}
