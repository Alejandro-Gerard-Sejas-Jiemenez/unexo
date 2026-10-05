@extends('layouts.app')
@section('titulo', 'Iniciar sesión')
@section('clase_main', 'flex-1 w-full overflow-y-auto')
@section('errores_propios', '1')
@section('contenido')
    <div class="flex min-h-screen w-full flex-col md:flex-row bg-white">
        <!-- Lado Izquierdo: Presentación (Oculto en móvil, visible en md) -->
        <section
            class="hidden md:flex flex-col justify-center w-full md:w-1/2 lg:w-5/12 bg-indigo-600 text-white p-12 lg:p-20 relative overflow-hidden"
            aria-label="Presentación">
            <!-- Decoración de fondo -->
            <div class="absolute inset-0 bg-gradient-to-br from-indigo-500 to-indigo-800 opacity-90"></div>
            <div class="absolute -top-24 -left-24 w-96 h-96 bg-white opacity-10 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 right-0 w-80 h-80 bg-indigo-400 opacity-20 rounded-full blur-2xl"></div>

            <div class="relative z-10 flex flex-col h-full justify-between">
                <div class="mt-8 flex justify-center w-full">
                    <span class="inline-flex items-center justify-center bg-white p-6 rounded-3xl shadow-lg mb-6">
                        <img src="{{ asset('images/logo_v2.png') }}" alt="{{ config('app.name') }}" class="h-20 w-auto object-contain">
                    </span>
                </div>

                <div class="mt-5 mb-auto text-center">
                    <h2 class="text-4xl lg:text-5xl font-extrabold tracking-tight mb-6 leading-tight">Sistema
                        de<br>Administración<br>Académica</h2>
                    <p class="text-indigo-100 text-lg lg:text-xl font-medium mb-10 max-w-md">La unidad de cursos y
                        consultorías en un solo lugar.</p>
                </div>

                <div class="text-indigo-200 text-sm font-medium text-center">
                    &copy; {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.
                </div>
            </div>
        </section>

        <!-- Lado Derecho: Formulario -->
        <section
            class="flex-1 flex flex-col justify-center px-4 sm:px-6 lg:flex-none lg:w-7/12 lg:px-20 xl:px-24 bg-white py-12 md:py-0 relative">
            <div class="mx-auto w-full max-w-sm lg:w-96 relative z-10">
                <!-- Header móvil (solo visible en sm y md, oculto en lg) -->
                <div class="md:hidden mb-10 text-center">
                    <span
                        class="inline-flex items-center justify-center bg-white p-4 rounded-2xl shadow-sm border border-slate-100 mx-auto">
                        <img src="{{ asset('images/logo_v2.png') }}" alt="{{ config('app.name') }}" class="h-12 w-auto object-contain">
                    </span>
                </div>

                <div class="text-center">
                    <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Te damos la bienvenida</h1>
                    <p class="mt-3 text-sm text-slate-500 font-medium">Ingresa con tu correo institucional o personal.</p>
                </div>

                <div class="mt-8">
                    @if ($errors->any())
                        <div class="rounded-lg bg-red-50 border border-red-200 p-4 mb-6 shadow-sm" role="alert"
                            id="error-acceso">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor"
                                        aria-hidden="true">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-red-800">Hubo un problema con tu acceso</h3>
                                    <div class="mt-2 text-sm text-red-700">
                                        <ul role="list" class="list-disc space-y-1 pl-5 font-medium">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" class="space-y-6">
                        @csrf
                        <div>
                            <label for="usuario" class="block text-sm font-semibold text-slate-700 mb-1.5">Correo
                                electrónico</label>
                            <div class="relative rounded-md shadow-sm">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <svg class="h-5 w-5 text-slate-400" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" aria-hidden="true">
                                        <rect width="20" height="16" x="2" y="4" rx="2" />
                                        <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
                                    </svg>
                                </div>
                                <input type="email" id="usuario" name="usuario" value="{{ old('usuario') }}" required
                                    autofocus autocomplete="username" placeholder="nombre@correo.com"
                                    class="block w-full rounded-lg border-0 py-2.5 pl-10 text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 transition-shadow"
                                    @if ($errors->any()) aria-invalid="true" aria-describedby="error-acceso" @endif>
                            </div>
                        </div>

                        <div>
                            <label for="password"
                                class="block text-sm font-semibold text-slate-700 mb-1.5">Contraseña</label>
                            <div class="relative rounded-md shadow-sm group">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <svg class="h-5 w-5 text-slate-400" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" aria-hidden="true">
                                        <rect width="18" height="11" x="3" y="11" rx="2" ry="2" />
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                    </svg>
                                </div>
                                <input type="password" id="password" name="password" required
                                    autocomplete="current-password" placeholder="Escribe tu contraseña"
                                    class="block w-full rounded-lg border-0 py-2.5 pl-10 pr-10 text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 transition-shadow"
                                    @if ($errors->any()) aria-describedby="error-acceso" @endif>

                                <button type="button"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-indigo-600 focus:outline-none transition-colors"
                                    data-ver-clave="password" aria-label="Mostrar contraseña" aria-pressed="false" hidden>
                                    <span data-icono-mostrar class="p-1"><x-icono nombre="ojo" class="w-4 h-4" /></span>
                                    <span data-icono-ocultar class="p-1" hidden><x-icono nombre="ojo-tachado"
                                            class="w-4 h-4" /></span>
                                    <span data-texto class="sr-only">Mostrar</span>
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <input type="checkbox" id="recordar" name="recordar" value="1"
                                    class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-600 cursor-pointer transition-colors">
                                <label for="recordar"
                                    class="ml-2 block text-sm font-medium text-slate-700 cursor-pointer">Mantener la sesión
                                    iniciada</label>
                            </div>
                        </div>

                        <div>
                            <button type="submit"
                                class="flex w-full justify-center items-center gap-2 rounded-lg bg-indigo-600 px-3 py-3 text-sm font-semibold leading-6 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition-all active:scale-[0.98]">
                                <x-icono nombre="entrar" class="w-5 h-5" /> Acceder
                            </button>
                        </div>
                    </form>

                    <div class="mt-10 border-t border-slate-200 pt-6">
                        <p
                            class="text-sm text-slate-500 font-medium text-center bg-slate-50 p-4 rounded-lg border border-slate-100">
                            ¿No puedes ingresar?<br>Comunícate con la administración de {{ config('app.name') }} para
                            recuperar tu acceso.
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-ver-clave]').forEach(function (boton) {
            var campo = document.getElementById(boton.getAttribute('data-ver-clave'));
            boton.hidden = false;
            boton.addEventListener('click', function () {
                var visible = campo.type === 'text';
                campo.type = visible ? 'password' : 'text';
                boton.querySelector('[data-texto]').textContent = visible ? 'Mostrar' : 'Ocultar';
                boton.querySelector('[data-icono-mostrar]').hidden = !visible;
                boton.querySelector('[data-icono-ocultar]').hidden = visible;
                boton.setAttribute('aria-pressed', visible ? 'false' : 'true');
                boton.setAttribute('aria-label', visible ? 'Mostrar contraseña' : 'Ocultar contraseña');
            });
        });
    </script>
@endpush
