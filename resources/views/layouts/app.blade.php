<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Panel') - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body
    class="bg-[#f0f2f5] text-slate-800 font-sans antialiased h-screen flex flex-col overflow-hidden selection:bg-indigo-500 selection:text-white">
    @auth
        @php
            $usuario = auth()->user();

            $menu = [];

            if ($usuario->rol === \App\Enums\Rol::Administrador) {
                $menu = [
                    [
                        'tipo' => 'enlace',
                        'etiqueta' => 'Inicio',
                        'url' => route('panel'),
                        'patron' => 'panel',
                        'icono' => 'inicio',
                    ],
                    [
                        'tipo' => 'submenu',
                        'etiqueta' => 'Programas',
                        'icono' => 'programa',
                        'items' => [
                            ['Catálogo', route('catalogo'), 'catalogo', 'catalogo'],
                            ['Módulos', route('modulos.index'), 'modulos.*', 'modulos'],
                            ['Programas', route('programas.index'), 'programas.*', 'programa'],
                            ['Ediciones', route('cohortes.index'), 'cohortes.*', 'calendario'],
                            ['Grupos', route('grupos.index'), 'grupos.*', 'usuarios'],
                            ['Matrículas', route('matriculas.index'), 'matriculas.*', 'matricula'],
                        ],
                    ],
                    [
                        'tipo' => 'enlace',
                        'etiqueta' => 'Reportes',
                        'url' => route('reportes.matriculas'),
                        'patron' => 'reportes.*',
                        'icono' => 'reportes',
                    ],
                    [
                        'tipo' => 'submenu',
                        'etiqueta' => 'Configuración y Seguridad',
                        'icono' => 'candado',
                        'items' => [
                            ['Usuarios', route('usuarios.index'), 'usuarios.*', 'usuarios'],
                            ['Acceso API', route('clientes-api.index'), 'clientes-api.*', 'candado'],
                        ],
                    ],
                ];
            } elseif ($usuario->rol === \App\Enums\Rol::Docente) {
                $menu = [
                    [
                        'tipo' => 'enlace',
                        'etiqueta' => 'Inicio',
                        'url' => route('panel'),
                        'patron' => 'panel',
                        'icono' => 'inicio',
                    ],
                    [
                        'tipo' => 'enlace',
                        'etiqueta' => 'Catálogo',
                        'url' => route('catalogo'),
                        'patron' => 'catalogo',
                        'icono' => 'catalogo',
                    ],
                    [
                        'tipo' => 'submenu',
                        'etiqueta' => 'Docencia',
                        'icono' => 'usuarios',
                        'items' => [
                            ['Mis grupos', route('mis-grupos.index'), 'mis-grupos.*', 'usuarios'],
                            ['Calendario', route('calendario'), 'calendario', 'calendario'],
                        ],
                    ],
                ];
            } elseif ($usuario->rol === \App\Enums\Rol::Estudiante) {
                $menu = [
                    [
                        'tipo' => 'enlace',
                        'etiqueta' => 'Inicio',
                        'url' => route('panel'),
                        'patron' => 'panel',
                        'icono' => 'inicio',
                    ],
                    [
                        'tipo' => 'enlace',
                        'etiqueta' => 'Catálogo',
                        'url' => route('catalogo'),
                        'patron' => 'catalogo',
                        'icono' => 'catalogo',
                    ],
                    [
                        'tipo' => 'submenu',
                        'etiqueta' => 'Mis Estudios',
                        'icono' => 'video',
                        'items' => [
                            ['Mis cursos', route('mis-cursos'), 'mis-cursos', 'video'],
                            ['Calendario', route('calendario'), 'calendario', 'calendario'],
                        ],
                    ],
                ];
            }

            $iniciales = mb_strtoupper(mb_substr($usuario->nombres, 0, 1) . mb_substr($usuario->apellidos, 0, 1));
        @endphp

        <a class="sr-only focus:not-sr-only focus:absolute focus:p-4 focus:bg-white focus:text-indigo-600 focus:z-60"
            href="#contenido">Saltar al contenido</a>

        <!-- ===== Barra superior ===== -->
        <header class="shrink-0 h-16 bg-white border-b border-slate-200 flex items-center gap-3 px-4 sm:px-6 z-40">
            <button id="abrir-sidebar" type="button" aria-controls="sidebar-menu" aria-expanded="false"
                aria-label="Abrir menú"
                class="md:hidden p-2 text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                <x-icono nombre="menu" class="w-6 h-6" />
            </button>

            <a href="{{ route('panel') }}" class="flex items-center gap-2.5 shrink-0">
                <img src="{{ asset('images/logo_v2.png') }}" alt="Logo" class="w-9 h-9 object-contain">
                <span
                    class="font-bold text-slate-900 text-lg tracking-tight hidden sm:inline">{{ config('app.name') }}</span>
            </a>

            <span class="hidden md:block w-px h-6 bg-slate-200" aria-hidden="true"></span>
            <h1 class="hidden md:block text-sm font-semibold text-slate-500 truncate">@yield('titulo', 'Panel')</h1>

            <!-- Menú de usuario -->
            <details class="relative ml-auto shrink-0 group/menu">
                <summary
                    class="list-none flex items-center gap-2.5 p-1.5 pr-2.5 rounded-full hover:bg-slate-100 cursor-pointer transition-colors [&::-webkit-details-marker]:hidden">
                    <span
                        class="flex items-center justify-center w-9 h-9 rounded-full bg-linear-to-tr from-indigo-500 to-violet-500 text-white font-bold text-sm shadow-sm"
                        aria-hidden="true">{{ $iniciales }}</span>
                    <span class="hidden sm:flex flex-col items-start leading-tight">
                        <span class="text-sm font-semibold text-slate-800">{{ explode(' ', $usuario->nombres)[0] }}
                            {{ explode(' ', $usuario->apellidos)[0] }}</span>
                        <span class="text-xs text-slate-500">{{ $usuario->rol->label() }}</span>
                    </span>
                    <x-icono nombre="bajar" class="w-4 h-4 text-slate-400 hidden sm:block" />
                </summary>
                <div class="absolute right-0 mt-2 w-60 bg-white border border-slate-200 rounded-2xl shadow-xl p-2 z-50">
                    <div class="px-3 py-2 border-b border-slate-100 mb-1">
                        <p class="text-sm font-semibold text-slate-900 truncate">{{ $usuario->nombre_completo }}</p>
                        <p class="text-xs text-slate-500 truncate">{{ $usuario->correo_institucional ?? $usuario->email }}
                        </p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                            class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm font-medium text-slate-600 hover:bg-red-50 hover:text-red-600 transition-colors">
                            <x-icono nombre="salir" class="w-4 h-4" /> Cerrar sesión
                        </button>
                    </form>
                </div>
            </details>
        </header>

        <!-- ===== Zona inferior: sidebar colapsable + contenido ===== -->
        <div class="flex flex-1 overflow-hidden">
            <div id="sidebar-fondo" class="sidebar-fondo" aria-hidden="true"></div>

            <!-- Sidebar: escritorio colapsa a iconos (6rem) y se expande al fijar (18rem); móvil es un cajón. -->
            <aside id="sidebar-menu" class="sidebar" aria-label="Menú lateral">
                {{-- Antes del primer pintado: restaura el estado fijado sin parpadeo ni animación. --}}
                <script>
                    (function () {
                        var s = document.getElementById('sidebar-menu');
                        s.classList.add('sin-animacion');
                        try { if (localStorage.getItem('sidebar-pinned') === 'true') s.classList.add('is-pinned'); } catch (e) { }
                    })();
                </script>

                <!-- Botón para colapsar/expandir (escritorio) y título del panel -->
                <div class="flex items-center px-5 py-4 min-h-16">
                    <button id="toggle-sidebar" type="button" class="sb-icono cursor-pointer focus:outline-none"
                        title="Expandir o colapsar el menú" aria-label="Expandir o colapsar el menú">
                        <x-icono nombre="sidebar" class="w-5 h-5 text-slate-500" />
                    </button>
                    <button id="cerrar-sidebar" type="button"
                        class="md:hidden ml-auto p-2 text-slate-500 hover:bg-slate-100 rounded-full shrink-0"
                        aria-label="Cerrar menú">
                        <x-icono nombre="cerrar" class="w-5 h-5" />
                    </button>
                </div>
                <nav class="flex-1 px-3 pt-1 pb-10 space-y-1.5 overflow-y-auto overflow-x-hidden [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar:none]"
                    aria-label="Navegación principal">
                    @foreach ($menu as $elemento)
                        @if ($elemento['tipo'] === 'enlace')
                            @php
                                $activo = request()->routeIs($elemento['patron']);
                            @endphp
                            <a href="{{ $elemento['url'] }}" title="{{ $elemento['etiqueta'] }}"
                                class="sb-fila {{ $activo ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"
                                @if ($activo) aria-current="page" @endif>
                                <span class="sb-icono">
                                    <x-icono :nombre="$elemento['icono']"
                                        class="w-5 h-5 {{ $activo ? 'text-white' : 'text-slate-400' }}" />
                                </span>
                                <span class="sb-etiqueta">{{ $elemento['etiqueta'] }}</span>
                            </a>
                        @elseif ($elemento['tipo'] === 'submenu')
                            @php
                                $subActivo = collect($elemento['items'])->contains(fn($it) => request()->routeIs($it[2]));
                            @endphp
                            <details class="group/sub sb-grupo" {{ $subActivo ? 'open' : '' }}>
                                <summary title="{{ $elemento['etiqueta'] }}"
                                    class="sb-fila cursor-pointer list-none select-none transition-colors [&::-webkit-details-marker]:hidden {{ $subActivo ? 'text-indigo-700 bg-indigo-50/70 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                    <span class="sb-icono">
                                        <x-icono :nombre="$elemento['icono']"
                                            class="w-5 h-5 {{ $subActivo ? 'text-indigo-600' : 'text-slate-400' }}" />
                                    </span>
                                    <span class="sb-etiqueta flex items-center justify-between flex-1 pr-2">
                                        <span class="truncate">{{ $elemento['etiqueta'] }}</span>
                                        <x-icono nombre="chevron"
                                            class="w-4 h-4 text-slate-400 transition-transform duration-200 group-open/sub:rotate-180 shrink-0" />
                                    </span>
                                </summary>

                                <div class="sb-sub-lista">
                                    @foreach ($elemento['items'] as [$subEtiqueta, $subUrl, $subPatron, $subIcono])
                                        @php
                                            $itemActivo = request()->routeIs($subPatron);
                                        @endphp
                                        <a href="{{ $subUrl }}" title="{{ $subEtiqueta }}"
                                            class="sb-sub-fila {{ $itemActivo ? 'activo' : '' }}"
                                            @if ($itemActivo) aria-current="page" @endif>
                                            <span class="sb-sub-icono">
                                                <x-icono :nombre="$subIcono" class="w-4 h-4" />
                                            </span>
                                            <span class="truncate">{{ $subEtiqueta }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </details>
                        @endif
                    @endforeach
                </nav>

                <div class="px-5 py-4 mt-auto">
                    <p class="sb-etiqueta text-[11px] text-slate-400">{{ $usuario->rol->label() }}</p>
                </div>
            </aside>

            <!-- Contenido principal. La pantalla de carga vive aquí dentro: solo cubre esta zona, nunca la cabecera ni el menú. -->
            <div class="relative flex-1 min-w-0 overflow-hidden">
                <div id="cargando-barra" class="cargando-barra" aria-hidden="true"></div>
                <div id="cargando" class="cargando" role="status" aria-live="polite" aria-hidden="true">
                    <div class="flex flex-col items-center">
                        <div class="cargando-marca">
                            <span class="cargando-anillo"></span>
                            <img src="{{ asset('images/logo_v2.png') }}" alt="" class="cargando-logo">
                        </div>
                        <p class="cargando-texto">Cargando</p>
                    </div>
                </div>
                <main class="h-full overflow-y-auto w-full p-4 sm:p-6 lg:p-8 animate-fade-in" id="contenido">
                    <div class="max-w-7xl mx-auto">
                        @include('layouts.mensajes')
                        @yield('contenido')
                    </div>
                </main>
            </div>
        </div>

        <!-- Lógica del sidebar: fijar en escritorio, cajón en móvil -->
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const sidebar = document.getElementById('sidebar-menu');
                const fondo = document.getElementById('sidebar-fondo');
                const abrir = document.getElementById('abrir-sidebar');
                const cerrar = document.getElementById('cerrar-sidebar');
                const toggle = document.getElementById('toggle-sidebar');
                const escritorio = window.matchMedia('(min-width: 48rem)');

                // Activa las transiciones solo después del primer pintado.
                requestAnimationFrame(() => requestAnimationFrame(() => sidebar.classList.remove('sin-animacion')));

                // Escritorio: fijar / liberar y recordar el estado.
                const fijar = (e) => {
                    if (e) e.preventDefault();
                    sidebar.classList.toggle('is-pinned');
                    try { localStorage.setItem('sidebar-pinned', sidebar.classList.contains('is-pinned')); } catch (err) { }
                };
                toggle?.addEventListener('click', (e) => { if (escritorio.matches) fijar(e); });

                // Escritorio colapsado: al hacer clic en un submenú, expandir primero a 18rem
                sidebar.querySelectorAll('details.sb-grupo summary').forEach((sum) => {
                    sum.addEventListener('click', () => {
                        if (escritorio.matches && !sidebar.classList.contains('is-pinned')) {
                            sidebar.classList.add('is-pinned');
                            try { localStorage.setItem('sidebar-pinned', 'true'); } catch (err) {}
                        }
                    });
                });

                // Móvil: cajón con fondo.
                const mostrar = (v) => {
                    sidebar.classList.toggle('abierto', v);
                    fondo.classList.toggle('abierto', v);
                    abrir?.setAttribute('aria-expanded', v);
                    if (v) cerrar?.focus(); else if (!escritorio.matches) abrir?.focus();
                };
                abrir?.addEventListener('click', () => mostrar(true));
                cerrar?.addEventListener('click', () => mostrar(false));
                fondo.addEventListener('click', () => mostrar(false));
                document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && sidebar.classList.contains('abierto')) mostrar(false); });
                sidebar.querySelectorAll('nav a').forEach((a) => a.addEventListener('click', () => sidebar.classList.remove('abierto')));
                escritorio.addEventListener('change', () => { sidebar.classList.remove('abierto'); fondo.classList.remove('abierto'); });
            });
        </script>
    @else
        <main class="@yield('clase_main', 'flex min-h-screen flex-col justify-center items-center p-4 bg-slate-50')">
            @include('layouts.mensajes')
            @yield('contenido')
        </main>
    @endauth

    <!-- Lógica de la pantalla de carga (el velo vive dentro del área de contenido) -->
    <script>
        (function () {
            const velo = document.getElementById('cargando');
            const barra = document.getElementById('cargando-barra');
            if (!velo || !barra) return;
            let esperaBarra, esperaVelo, seguridad;

            // Feedback progresivo:
            // 1. La barra superior arranca rápido (60ms) dando respuesta inmediata al clic sin tapar la pantalla.
            // 2. El velo modal translúcido con spinner solo se muestra si la página tarda más de 450ms,
            //    eliminando por completo el molesto parpadeo en transiciones normales.
            const mostrar = () => {
                clearTimeout(esperaBarra);
                clearTimeout(esperaVelo);
                clearTimeout(seguridad);

                esperaBarra = setTimeout(() => {
                    barra.classList.add('activa');
                }, 60);

                esperaVelo = setTimeout(() => {
                    velo.classList.add('activo');
                    velo.setAttribute('aria-hidden', 'false');
                }, 450);

                seguridad = setTimeout(ocultar, 10000); // por si la navegación no ocurre (descargas, errores)
            };
            const ocultar = () => {
                clearTimeout(esperaBarra);
                clearTimeout(esperaVelo);
                clearTimeout(seguridad);
                velo.classList.remove('activo');
                velo.setAttribute('aria-hidden', 'true');
                barra.classList.remove('activa');
            };

            document.addEventListener('click', (e) => {
                const a = e.target.closest('a[href]');
                if (!a || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
                if (a.target === '_blank' || a.hasAttribute('download')) return;
                const url = new URL(a.href, location.href);
                if (url.origin !== location.origin) return;
                if (url.pathname === location.pathname && url.search === location.search) return; // mismo lugar o ancla
                mostrar();
            });
            document.addEventListener('submit', (e) => {
                if (e.defaultPrevented || e.target.target === '_blank') return;
                mostrar();
            });
            window.addEventListener('pageshow', ocultar); // al volver con "atrás" desde la caché del navegador
        })();
    </script>

    @stack('scripts')
</body>

</html>