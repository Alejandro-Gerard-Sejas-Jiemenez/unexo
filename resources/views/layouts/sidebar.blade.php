@inject('menuService', 'App\Services\MenuService')

@php
    $usuario = auth()->user();
    $menu = $menuService->paraUsuario($usuario);
@endphp

<div id="sidebar-fondo" class="sidebar-fondo" aria-hidden="true"></div>

<!-- Sidebar: escritorio colapsa a iconos (6rem) y se expande al fijar (18rem); móvil es un cajón. -->
<aside id="sidebar-menu" class="sidebar" aria-label="Menú lateral">
    <!-- Botón para colapsar/expandir (escritorio) y cerrar en móvil -->
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

    <!-- Navegación con enlaces y submenús delegados a componentes -->
    <nav class="flex-1 px-3 pt-1 pb-10 space-y-1.5 overflow-y-auto overflow-x-hidden [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar:none]"
        aria-label="Navegación principal">
        @foreach ($menu as $elemento)
            @if ($elemento['tipo'] === 'enlace')
                <x-sidebar-enlace :elemento="$elemento" />
            @elseif ($elemento['tipo'] === 'submenu')
                <x-sidebar-grupo :elemento="$elemento" />
            @endif
        @endforeach
    </nav>

    <!-- Pie del sidebar -->
    <div class="px-5 py-4 mt-auto">
        <p class="sb-etiqueta text-[11px] text-slate-400">{{ $usuario->rol->label() }}</p>
    </div>
</aside>
