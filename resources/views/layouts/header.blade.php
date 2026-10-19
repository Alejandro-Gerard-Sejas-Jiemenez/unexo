@php
    $usuario = auth()->user();
    $iniciales = mb_strtoupper(mb_substr($usuario->nombres, 0, 1) . mb_substr($usuario->apellidos, 0, 1));
@endphp

<header class="shrink-0 h-16 bg-white border-b border-slate-200 flex items-center gap-3 px-4 sm:px-6 z-40">
    <button id="abrir-sidebar" type="button" aria-controls="sidebar-menu" aria-expanded="false"
        aria-label="Abrir menú"
        class="md:hidden p-2 text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
        <x-icono nombre="menu" class="w-6 h-6" />
    </button>

    <a href="{{ route('panel') }}" class="flex items-center gap-2.5 shrink-0">
        <img src="{{ asset('images/logo_v2.png') }}" alt="Logo" class="w-9 h-9 object-contain">
        <span class="font-bold text-slate-900 text-lg tracking-tight hidden sm:inline">{{ config('app.name') }}</span>
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
                <p class="text-xs text-slate-500 truncate">{{ $usuario->correo_institucional ?? $usuario->email }}</p>
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
