import { NavLink, Outlet } from 'react-router-dom';
import { useAuth } from '../auth/AuthContext';
import BuscadorGlobal from './BuscadorGlobal';
import PanelAccesibilidad from './PanelAccesibilidad';
import PieDePagina from './PieDePagina';

function Enlace({ entrada }) {
    return (
        <NavLink
            to={entrada.ruta}
            end={entrada.ruta === '/app'}
            className={({ isActive }) =>
                'block rounded-lg px-3 py-2 text-sm no-underline ' +
                (isActive ? 'bg-[var(--color-acento)] text-white' : 'hover:bg-black/5')
            }
        >
            {entrada.etiqueta}
        </NavLink>
    );
}

export default function Layout() {
    const { usuario, menu, salir } = useAuth();

    return (
        <div className="flex min-h-screen">
            <a href="#contenido" className="sr-only">
                Saltar al contenido
            </a>

            <nav
                aria-label="Navegación principal"
                className="w-64 shrink-0 border-r border-[var(--color-borde)] bg-[var(--color-superficie)] p-4"
            >
                <p className="mb-6 text-lg font-semibold">NEXO SIS</p>

                <ul className="list-none space-y-1 p-0">
                    {menu.map((entrada) => (
                        <li key={entrada.etiqueta}>
                            {entrada.tipo === 'submenu' ? (
                                <>
                                    <p className="mt-4 mb-1 px-3 text-xs font-semibold tracking-wide text-[var(--color-texto-suave)] uppercase">
                                        {entrada.etiqueta}
                                    </p>
                                    <ul className="list-none space-y-1 p-0">
                                        {entrada.items.map((item) => (
                                            <li key={item.ruta}>
                                                <Enlace entrada={item} />
                                            </li>
                                        ))}
                                    </ul>
                                </>
                            ) : (
                                <Enlace entrada={entrada} />
                            )}
                        </li>
                    ))}
                </ul>
            </nav>

            <div className="flex min-w-0 flex-1 flex-col">
                <header className="flex items-center gap-4 border-b border-[var(--color-borde)] bg-[var(--color-superficie)] px-6 py-3">
                    <BuscadorGlobal />

                    <div className="ml-auto flex items-center gap-3">
                        <p className="text-sm text-[var(--color-texto-suave)]">
                            {usuario?.nombre_completo}{' '}
                            <span className="rounded-full bg-[var(--color-acento-suave)] px-2 py-0.5 text-[var(--color-texto)]">
                                {usuario?.rol_label}
                            </span>
                        </p>
                        <PanelAccesibilidad />
                        <button
                            type="button"
                            onClick={salir}
                            className="superficie px-3 py-1.5 text-sm"
                        >
                            Cerrar sesión
                        </button>
                    </div>
                </header>

                <main id="contenido" className="min-w-0 flex-1 p-6">
                    <Outlet />
                </main>

                <PieDePagina />
            </div>
        </div>
    );
}
