import { useAuth } from '../auth/AuthContext';

export default function Panel() {
    const { usuario } = useAuth();

    return (
        <section>
            <h1 className="mb-2 text-2xl font-semibold">Hola, {usuario?.nombres}</h1>
            <p className="text-[var(--color-texto-suave)]">
                Bienvenido al Sistema de Administración Académica de NEXO. Usa el menú de la
                izquierda para acceder a los módulos habilitados para tu rol.
            </p>
        </section>
    );
}
