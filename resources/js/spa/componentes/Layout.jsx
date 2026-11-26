import { useEffect, useRef, useState } from 'react';
import { Link, NavLink, Outlet, useLocation } from 'react-router-dom';
import { useAuth } from '../auth/AuthContext';
import { conBase } from '../base';
import { useTema } from '../tema/TemaContext';
import BuscadorGlobal from './BuscadorGlobal';
import Icono from './Icono';
import PanelAccesibilidad from './PanelAccesibilidad';
import PieDePagina from './PieDePagina';

const CLAVE_SIDEBAR = 'nexo-sidebar-abierta';

function iniciales(nombre = '') {
    return nombre
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((p) => p[0]?.toUpperCase() ?? '')
        .join('');
}

function nombreCorto(usuario) {
    const p1 = (usuario?.nombres ?? '').trim().split(/\s+/)[0] ?? '';
    const p2 = (usuario?.apellidos ?? '').trim().split(/\s+/)[0] ?? '';
    return `${p1} ${p2}`.trim() || usuario?.nombre_completo || '';
}

function AvatarPerfil({ usuario }) {
    const [error, setError] = useState(false);

    useEffect(() => {
        setError(false);
    }, [usuario?.foto_perfil_url]);

    if (usuario?.foto_perfil_url && !error) {
        return (
            <img
                src={conBase(usuario.foto_perfil_url)}
                alt=""
                onError={() => setError(true)}
                className="h-9 w-9 rounded-full object-cover shadow-sm"
            />
        );
    }

    return (
        <span
            aria-hidden="true"
            className="flex h-9 w-9 items-center justify-center rounded-full text-sm font-bold shadow-sm"
            style={{
                background: 'linear-gradient(45deg, var(--color-acento), var(--color-realce))',
                color: 'var(--color-acento-texto)',
            }}
        >
            {iniciales(usuario?.nombre_completo)}
        </span>
    );
}

function Enlace({ item, expandida, anidado = false, alNavegar }) {
    if (anidado) {
        return (
            <NavLink
                to={item.ruta}
                end={item.ruta === '/'}
                title={expandida ? undefined : item.etiqueta}
                onClick={alNavegar}
                className={({ isActive }) => `sb-sub-fila ${isActive ? 'activo' : ''}`}
            >
                <span className="sb-sub-icono">
                    <Icono nombre={item.icono} className="h-4 w-4 shrink-0" />
                </span>
                <span className="truncate">{item.etiqueta}</span>
            </NavLink>
        );
    }

    return (
        <NavLink
            to={item.ruta}
            end={item.ruta === '/'}
            title={expandida ? undefined : item.etiqueta}
            onClick={alNavegar}
            className={({ isActive }) =>
                'sb-fila ' +
                (isActive
                    ? 'bg-[var(--color-acento)] text-[var(--color-acento-texto)] shadow-md'
                    : 'text-[var(--color-texto-suave)] hover:bg-[var(--color-acento-suave)] hover:text-[var(--color-texto)]')
            }
        >
            {({ isActive }) => (
                <>
                    <span className="sb-icono">
                        <Icono
                            nombre={item.icono}
                            className={`h-5 w-5 shrink-0 ${isActive ? '' : 'opacity-75'}`}
                        />
                    </span>
                    <span className="sb-etiqueta">{item.etiqueta}</span>
                </>
            )}
        </NavLink>
    );
}

function Submenu({ entrada, expandida, alExpandir, alNavegar }) {
    const { pathname } = useLocation();
    const contieneActiva = entrada.items.some((i) =>
        i.ruta === '/' ? pathname === '/' : pathname.startsWith(i.ruta),
    );
    const [desplegado, setDesplegado] = useState(contieneActiva);

    useEffect(() => {
        if (contieneActiva) setDesplegado(true);
    }, [contieneActiva]);

    function manejarClicGrupo() {
        if (!expandida) {
            alExpandir();
            setDesplegado(true);
            return;
        }
        setDesplegado((v) => !v);
    }

    return (
        <div>
            <button
                type="button"
                aria-expanded={expandida && desplegado}
                title={expandida ? undefined : entrada.etiqueta}
                onClick={manejarClicGrupo}
                className={
                    'sb-fila cursor-pointer border-0 ' +
                    (contieneActiva
                        ? 'bg-[var(--color-acento-suave)] text-[var(--color-acento)] font-semibold'
                        : 'bg-transparent text-[var(--color-texto-suave)] hover:bg-[var(--color-acento-suave)] hover:text-[var(--color-texto)]')
                }
            >
                <span className="sb-icono">
                    <Icono nombre={entrada.icono} className={`h-5 w-5 shrink-0 ${contieneActiva ? '' : 'opacity-75'}`} />
                </span>
                <span className="sb-etiqueta flex flex-1 items-center justify-between pr-3">
                    <span className="truncate">{entrada.etiqueta}</span>
                    <Icono
                        nombre="chevron"
                        className={`h-4 w-4 shrink-0 opacity-70 transition-transform duration-200 ${desplegado ? 'rotate-180' : ''}`}
                    />
                </span>
            </button>

            {expandida && desplegado && (
                <div className="sb-sub-lista">
                    {entrada.items.map((i) => (
                        <Enlace key={i.ruta} item={i} expandida anidado alNavegar={alNavegar} />
                    ))}
                </div>
            )}
        </div>
    );
}

export default function Layout() {
    const { usuario, menu, salir } = useAuth();
    const { modoEfectivo, alternarModoRapido } = useTema();
    const { pathname } = useLocation();

    const [fijada, setFijada] = useState(() => {
        try {
            return localStorage.getItem(CLAVE_SIDEBAR) !== 'no';
        } catch {
            return true;
        }
    });
    const [movilAbierto, setMovilAbierto] = useState(false);
    const [buscadorMovil, setBuscadorMovil] = useState(false);
    const [perfilAbierto, setPerfilAbierto] = useState(false);
    const perfilRef = useRef(null);

    useEffect(() => {
        try {
            localStorage.setItem(CLAVE_SIDEBAR, fijada ? 'si' : 'no');
        } catch {
            // Navegación privada.
        }
    }, [fijada]);

    useEffect(() => {
        setMovilAbierto(false);
        setPerfilAbierto(false);
    }, [pathname]);

    useEffect(() => {
        const alTecla = (e) => {
            if (e.key === 'Escape') {
                setMovilAbierto(false);
                setPerfilAbierto(false);
            }
        };
        const alClic = (e) => {
            if (!perfilRef.current?.contains(e.target)) setPerfilAbierto(false);
        };
        document.addEventListener('keydown', alTecla);
        document.addEventListener('mousedown', alClic);
        return () => {
            document.removeEventListener('keydown', alTecla);
            document.removeEventListener('mousedown', alClic);
        };
    }, []);

    const seccion =
        menu
            .flatMap((e) => (e.tipo === 'submenu' ? e.items : [e]))
            .filter((i) => (i.ruta === '/' ? pathname === '/' : pathname.startsWith(i.ruta)))
            .sort((a, b) => b.ruta.length - a.ruta.length)[0]?.etiqueta ?? 'Inicio';

    const expandida = fijada || movilAbierto;

    return (
        <div className="flex h-screen flex-col overflow-hidden">
            <a
                href="#contenido"
                className="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:rounded-lg focus:bg-[var(--color-acento)] focus:px-4 focus:py-2 focus:text-[var(--color-acento-texto)]"
            >
                Saltar al contenido
            </a>

            <header className="z-40 flex h-16 shrink-0 items-center gap-3 border-b border-[var(--color-borde)] bg-[var(--color-superficie)] px-4 sm:px-6">
                <button
                    type="button"
                    onClick={() => setMovilAbierto(true)}
                    aria-label="Abrir menú"
                    className="rounded-xl p-2 text-[var(--color-texto-suave)] transition-colors hover:bg-[var(--color-superficie-2)] md:hidden"
                >
                    <Icono nombre="menu" className="h-6 w-6" />
                </button>

                <Link to="/" className="flex shrink-0 items-center gap-2.5 no-underline">
                    <img
                        src={conBase('/images/logo_v2.png')}
                        alt="NEXO SIS"
                        className="h-9 w-9 object-contain"
                    />
                    <span className="hidden text-lg font-bold tracking-tight sm:inline">NEXO SIS</span>
                </Link>

                <span aria-hidden="true" className="hidden h-6 w-px bg-[var(--color-borde)] md:block" />
                <p className="hidden truncate text-sm font-semibold text-[var(--color-texto-suave)] md:block">
                    {seccion}
                </p>

                <div className="mx-auto hidden max-w-md flex-1 md:block">
                    <BuscadorGlobal />
                </div>

                <div className="ml-auto flex shrink-0 items-center gap-2">
                    <button
                        type="button"
                        onClick={() => setBuscadorMovil((v) => !v)}
                        aria-label="Buscar en el sistema"
                        aria-expanded={buscadorMovil}
                        className="btn btn-secundario !min-h-9 !px-2.5 md:hidden"
                    >
                        <Icono nombre="buscar" />
                    </button>

                    <button
                        type="button"
                        onClick={alternarModoRapido}
                        title={modoEfectivo === 'noche' ? 'Cambiar a modo día' : 'Cambiar a modo noche'}
                        aria-label={modoEfectivo === 'noche' ? 'Cambiar a modo día' : 'Cambiar a modo noche'}
                        className="btn btn-secundario !min-h-9 !px-2.5"
                    >
                        <Icono nombre={modoEfectivo === 'noche' ? 'sol' : 'luna'} />
                    </button>

                    <PanelAccesibilidad />

                    <div ref={perfilRef} className="relative">
                        <button
                            type="button"
                            onClick={() => setPerfilAbierto((v) => !v)}
                            aria-expanded={perfilAbierto}
                            aria-haspopup="menu"
                            className="flex cursor-pointer items-center gap-2.5 rounded-full p-1.5 pr-2.5 text-left transition-colors hover:bg-[var(--color-superficie-2)]"
                        >
                            {usuario?.foto_perfil_url ? (
                                <AvatarPerfil usuario={usuario} />
                            ) : (
                                <span
                                    aria-hidden="true"
                                    className="flex h-9 w-9 items-center justify-center rounded-full text-sm font-bold shadow-sm"
                                    style={{
                                        background: 'linear-gradient(45deg, var(--color-acento), var(--color-realce))',
                                        color: 'var(--color-acento-texto)',
                                    }}
                                >
                                    {iniciales(usuario?.nombre_completo)}
                                </span>
                            )}
                            <span className="hidden flex-col items-start leading-tight sm:flex">
                                <span className="text-sm font-semibold">{nombreCorto(usuario)}</span>
                                <span className="text-xs text-[var(--color-texto-suave)]">{usuario?.rol_label}</span>
                            </span>
                            <Icono nombre="bajar" className="hidden h-4 w-4 text-[var(--color-texto-suave)] sm:block" />
                        </button>

                        {perfilAbierto && (
                            <div
                                role="menu"
                                className="superficie animate-fade-in absolute right-0 z-50 mt-2 w-60 rounded-2xl p-2 shadow-xl"
                            >
                                <div className="mb-1 border-b border-[var(--color-borde)] px-3 py-2">
                                    <p className="truncate text-sm font-semibold">{usuario?.nombre_completo}</p>
                                    <p className="truncate text-xs text-[var(--color-texto-suave)]">
                                        {usuario?.correo_institucional || usuario?.email}
                                    </p>
                                </div>
                                <Link
                                    to="/perfil"
                                    role="menuitem"
                                    className="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium no-underline transition-colors hover:bg-[var(--color-acento-suave)]"
                                >
                                    <Icono nombre="usuarios" className="h-4 w-4" />
                                    Mi perfil
                                </Link>
                                <button
                                    type="button"
                                    role="menuitem"
                                    onClick={salir}
                                    className="flex w-full items-center gap-2.5 rounded-xl border-0 bg-transparent px-3 py-2 text-left text-sm font-medium text-[var(--color-error)] transition-colors hover:bg-[var(--color-error-fondo)]"
                                >
                                    <Icono nombre="salir" className="h-4 w-4" />
                                    Cerrar sesión
                                </button>
                            </div>
                        )}
                    </div>
                </div>
            </header>

            {buscadorMovil && (
                <div className="border-b border-[var(--color-borde)] bg-[var(--color-superficie)] px-4 py-2.5 md:hidden">
                    <BuscadorGlobal />
                </div>
            )}

            <div className="flex flex-1 overflow-hidden">
                <div
                    onClick={() => setMovilAbierto(false)}
                    className={`spa-sidebar-fondo ${movilAbierto ? 'abierto' : ''}`}
                    aria-hidden="true"
                />

                <aside
                    aria-label="Menú lateral"
                    className={`spa-sidebar ${fijada ? 'fijado' : ''} ${movilAbierto ? 'abierto' : ''}`}
                >
                    <div className="flex min-h-16 items-center px-5 py-4">
                        <button
                            type="button"
                            onClick={() => setFijada((v) => !v)}
                            aria-expanded={fijada}
                            title="Expandir o colapsar el menú"
                            aria-label="Expandir o colapsar el menú"
                            className="sb-icono cursor-pointer border-0 bg-transparent text-[var(--color-texto-suave)] focus:outline-none"
                        >
                            <Icono nombre="sidebar" className="h-5 w-5" />
                        </button>
                        <button
                            type="button"
                            onClick={() => setMovilAbierto(false)}
                            aria-label="Cerrar menú"
                            className="ml-auto shrink-0 rounded-full border-0 bg-transparent p-2 text-[var(--color-texto-suave)] hover:bg-[var(--color-superficie-2)] md:hidden"
                        >
                            <Icono nombre="cerrar" className="h-5 w-5" />
                        </button>
                    </div>

                    <nav
                        className="flex-1 space-y-1.5 overflow-x-hidden overflow-y-auto px-3 pt-1 pb-10"
                        aria-label="Navegación principal"
                    >
                        {menu.map((entrada) =>
                            entrada.tipo === 'submenu' ? (
                                <Submenu
                                    key={entrada.etiqueta}
                                    entrada={entrada}
                                    expandida={expandida}
                                    alExpandir={() => setFijada(true)}
                                    alNavegar={() => setMovilAbierto(false)}
                                />
                            ) : (
                                <Enlace
                                    key={`${entrada.etiqueta}-${entrada.ruta}`}
                                    item={entrada}
                                    expandida={expandida}
                                    alNavegar={() => setMovilAbierto(false)}
                                />
                            ),
                        )}
                    </nav>

                    <div className="mt-auto px-5 py-4">
                        <p className="sb-etiqueta text-[11px] text-[var(--color-texto-suave)]">
                            {usuario?.rol_label}
                        </p>
                    </div>
                </aside>

                <div className="relative min-w-0 flex-1 overflow-hidden">
                    <main id="contenido" className="flex h-full w-full flex-col overflow-x-hidden overflow-y-auto">
                        <div key={pathname} className="animate-fade-in mx-auto w-full max-w-7xl min-w-0 flex-1 p-4 sm:p-6 lg:p-8">
                            <Outlet />
                        </div>
                        <PieDePagina />
                    </main>
                </div>
            </div>
        </div>
    );
}
