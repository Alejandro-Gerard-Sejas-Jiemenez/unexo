import { useEffect, useState } from 'react';
import { NavLink, Outlet, useLocation } from 'react-router-dom';
import { useAuth } from '../auth/AuthContext';
import { conBase } from '../base';
import BuscadorGlobal from './BuscadorGlobal';
import PanelAccesibilidad from './PanelAccesibilidad';
import PieDePagina from './PieDePagina';
import Icono from './Icono';

const CLAVE_SIDEBAR = 'nexo-sidebar-abierta';

function iniciales(nombre = '') {
    return nombre
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((p) => p[0]?.toUpperCase() ?? '')
        .join('');
}

/** Un enlace del menú. Colapsado muestra solo el ícono, con el nombre en el title. */
function Enlace({ item, abierta, anidado = false }) {
    return (
        <NavLink
            to={item.ruta}
            end={item.ruta === '/app'}
            title={abierta ? undefined : item.etiqueta}
            className={({ isActive }) =>
                'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm no-underline transition-colors ' +
                (anidado && abierta ? 'ml-3 ' : '') +
                (abierta ? '' : 'justify-center ') +
                (isActive
                    ? 'bg-[var(--color-acento)] font-semibold text-[var(--color-acento-texto)]'
                    : 'hover:bg-[var(--color-acento-suave)]')
            }
        >
            <Icono nombre={item.icono} />
            {abierta && <span className="truncate">{item.etiqueta}</span>}
        </NavLink>
    );
}

/** Un grupo del menú: se despliega solo si alguna de sus rutas está activa. */
function Submenu({ entrada, abierta }) {
    const { pathname } = useLocation();
    const contieneActiva = entrada.items.some((i) => pathname.startsWith(i.ruta));
    const [desplegado, setDesplegado] = useState(contieneActiva);

    useEffect(() => {
        if (contieneActiva) setDesplegado(true);
    }, [contieneActiva]);

    // Colapsada, la barra no tiene sitio para el grupo: se listan los hijos.
    if (!abierta) {
        return (
            <li>
                <ul className="list-none space-y-1 p-0">
                    {entrada.items.map((i) => (
                        <li key={i.ruta}>
                            <Enlace item={i} abierta={false} />
                        </li>
                    ))}
                </ul>
            </li>
        );
    }

    return (
        <li>
            <button
                type="button"
                aria-expanded={desplegado}
                onClick={() => setDesplegado((v) => !v)}
                className="flex w-full items-center gap-3 rounded-xl border-0 bg-transparent px-3 py-2.5 text-left text-sm hover:bg-[var(--color-acento-suave)]"
            >
                <Icono nombre={entrada.icono} />
                <span className="flex-1 truncate">{entrada.etiqueta}</span>
                <svg
                    className={'h-4 w-4 transition-transform ' + (desplegado ? 'rotate-180' : '')}
                    viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"
                    strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"
                >
                    <path d="m6 9 6 6 6-6" />
                </svg>
            </button>

            {desplegado && (
                <ul className="mt-1 list-none space-y-1 border-l border-[var(--color-borde)] p-0 pl-2">
                    {entrada.items.map((i) => (
                        <li key={i.ruta}>
                            <Enlace item={i} abierta anidado />
                        </li>
                    ))}
                </ul>
            )}
        </li>
    );
}

export default function Layout() {
    const { usuario, menu, salir } = useAuth();
    const { pathname } = useLocation();

    const [abierta, setAbierta] = useState(() => {
        try {
            return localStorage.getItem(CLAVE_SIDEBAR) !== 'no';
        } catch {
            return true;
        }
    });

    useEffect(() => {
        try {
            localStorage.setItem(CLAVE_SIDEBAR, abierta ? 'si' : 'no');
        } catch {
            // Navegación privada: la preferencia vale solo para esta sesión.
        }
    }, [abierta]);

    /** Nombre de la sección actual, para la barra superior. */
    const seccion =
        menu
            .flatMap((e) => (e.tipo === 'submenu' ? e.items : [e]))
            .filter((i) => pathname.startsWith(i.ruta))
            .sort((a, b) => b.ruta.length - a.ruta.length)[0]?.etiqueta ?? 'Inicio';

    return (
        <div className="flex min-h-screen flex-col">
            <a
                href="#contenido"
                className="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-50 focus:rounded-lg focus:bg-[var(--color-acento)] focus:px-4 focus:py-2 focus:text-[var(--color-acento-texto)]"
            >
                Saltar al contenido
            </a>

            {/* ----------------------------------------------- barra superior */}
            <header className="flex items-center gap-4 border-b border-[var(--color-borde)] bg-[var(--color-superficie)] px-5 py-3">
                <img
                    src={conBase('/images/logo.png')}
                    alt=""
                    width="36"
                    height="36"
                    className="h-9 w-9 shrink-0 object-contain"
                />
                <p className="text-lg font-bold tracking-tight">NEXO SIS</p>
                <span aria-hidden="true" className="h-6 w-px bg-[var(--color-borde)]" />
                <p className="truncate text-sm text-[var(--color-texto-suave)]">{seccion}</p>

                <div className="mx-auto hidden max-w-md flex-1 md:block">
                    <BuscadorGlobal />
                </div>

                <div className="ml-auto flex items-center gap-3">
                    <PanelAccesibilidad />

                    <div className="flex items-center gap-2.5">
                        <span
                            aria-hidden="true"
                            className="grid h-9 w-9 place-items-center rounded-full bg-[var(--color-realce)] text-sm font-bold text-white"
                        >
                            {iniciales(usuario?.nombre_completo)}
                        </span>
                        <span className="hidden leading-tight sm:block">
                            <span className="block text-sm font-semibold">
                                {usuario?.nombre_completo}
                            </span>
                            <span className="block text-xs text-[var(--color-texto-suave)]">
                                {usuario?.rol_label}
                            </span>
                        </span>
                    </div>

                    <button
                        type="button"
                        onClick={salir}
                        className="superficie px-3 py-1.5 text-sm"
                    >
                        Salir
                    </button>
                </div>
            </header>

            <div className="flex flex-1 gap-5 p-5">
                {/* ------------------------------------------------- sidebar */}
                <nav
                    aria-label="Navegación principal"
                    className={
                        'superficie flex shrink-0 flex-col self-start p-3 transition-[width] duration-200 ' +
                        (abierta ? 'w-60' : 'w-[68px]')
                    }
                >
                    <button
                        type="button"
                        onClick={() => setAbierta((v) => !v)}
                        aria-expanded={abierta}
                        title={abierta ? 'Contraer el menú' : 'Expandir el menú'}
                        className="mb-2 flex items-center justify-center rounded-xl border-0 bg-transparent p-2.5 hover:bg-[var(--color-acento-suave)]"
                    >
                        <svg className="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" strokeWidth="1.7" strokeLinecap="round"
                            strokeLinejoin="round" aria-hidden="true">
                            <rect x="3" y="4" width="18" height="16" rx="2" />
                            <path d="M9 4v16" />
                        </svg>
                        <span className="sr-only">
                            {abierta ? 'Contraer el menú' : 'Expandir el menú'}
                        </span>
                    </button>

                    <ul className="list-none space-y-1 p-0">
                        {menu.map((entrada) =>
                            entrada.tipo === 'submenu' ? (
                                <Submenu key={entrada.etiqueta} entrada={entrada} abierta={abierta} />
                            ) : (
                                <li key={entrada.ruta}>
                                    <Enlace item={entrada} abierta={abierta} />
                                </li>
                            ),
                        )}
                    </ul>

                    {abierta && (
                        <p className="mt-6 px-3 text-xs text-[var(--color-texto-suave)]">
                            {usuario?.rol_label}
                        </p>
                    )}
                </nav>

                {/* ------------------------------------------------ contenido */}
                <div className="flex min-w-0 flex-1 flex-col">
                    <main id="contenido" className="min-w-0 flex-1">
                        <Outlet />
                    </main>
                </div>
            </div>

            <PieDePagina />
        </div>
    );
}
