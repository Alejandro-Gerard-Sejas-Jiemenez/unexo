import { useLocation } from 'react-router-dom';

/**
 * Marcador para los módulos que todavía se atienden desde el panel Blade.
 * Se borra a medida que cada módulo se migra a React.
 */
export default function EnConstruccion() {
    const { pathname } = useLocation();
    const rutaBlade = pathname.replace(/^\/app/, '') || '/panel';

    return (
        <section>
            <h1 className="mb-2 text-2xl font-semibold">Módulo en migración</h1>
            <p className="mb-4 text-[var(--color-texto-suave)]">
                Esta sección todavía no está migrada a la nueva interfaz. Mientras tanto podés
                usarla en el panel actual.
            </p>
            <a
                href={rutaBlade}
                className="inline-block rounded-lg bg-[var(--color-acento)] px-4 py-2 text-sm font-medium text-[var(--color-acento-texto)] no-underline"
            >
                Abrir en el panel actual
            </a>
        </section>
    );
}
