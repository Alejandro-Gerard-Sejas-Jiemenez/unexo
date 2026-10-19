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
        <a class="sr-only focus:not-sr-only focus:absolute focus:p-4 focus:bg-white focus:text-indigo-600 focus:z-60"
            href="#contenido">Saltar al contenido</a>

        <!-- Barra superior -->
        @include('layouts.header')

        <!-- Zona inferior: sidebar colapsable + contenido principal -->
        <div class="flex flex-1 overflow-hidden">
            @include('layouts.sidebar')

            <div class="relative flex-1 min-w-0 overflow-hidden">
                @include('layouts.loader')

                <main class="h-full overflow-y-auto w-full p-4 sm:p-6 lg:p-8 animate-fade-in" id="contenido">
                    <div class="max-w-7xl mx-auto">
                        @include('layouts.mensajes')
                        @yield('contenido')
                    </div>
                </main>
            </div>
        </div>
    @else
        <main class="@yield('clase_main', 'flex min-h-screen flex-col justify-center items-center p-4 bg-slate-50')">
            @include('layouts.mensajes')
            @yield('contenido')
        </main>
    @endauth

    @stack('scripts')
</body>

</html>