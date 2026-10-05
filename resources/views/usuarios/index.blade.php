@extends('layouts.app')
@section('titulo', 'Usuarios')
@section('contenido')
<div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <h1 class="text-3xl font-bold text-slate-800 tracking-tight">Usuarios</h1>
    <a class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 shadow-sm transition-all focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('usuarios.create') }}">
        <x-icono nombre="mas" class="w-5 h-5" /> Nuevo usuario
    </a>
</div>

<div class="mb-6 flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white p-4 rounded-xl shadow-sm border border-slate-200">
    <!-- Filtros -->
    <div class="flex flex-wrap gap-2">
        @php
            $claseActivo = "px-4 py-1.5 rounded-full text-sm font-medium bg-indigo-100 text-indigo-700 ring-1 ring-inset ring-indigo-200 transition-colors";
            $claseInactivo = "px-4 py-1.5 rounded-full text-sm font-medium bg-slate-100 text-slate-600 hover:bg-slate-200 transition-colors";
        @endphp
        
        <a href="{{ route('usuarios.index', ['q' => $busqueda ?: null]) }}" class="{{ $rolFiltro || $soloInactivos ? $claseInactivo : $claseActivo }}">Todos</a>
        
        @foreach (\App\Enums\Rol::cases() as $rol)
            <a href="{{ route('usuarios.index', ['rol' => $rol->value, 'q' => $busqueda ?: null]) }}" class="{{ $rolFiltro === $rol->value ? $claseActivo : $claseInactivo }}">{{ $rol->label() }}</a>
        @endforeach
        <a href="{{ route('usuarios.index', ['estado' => 'inactivo', 'q' => $busqueda ?: null]) }}" class="{{ $soloInactivos ? $claseActivo : $claseInactivo }}">Inactivos</a>
    </div>

    <!-- Buscador -->
    <div class="w-full lg:w-auto lg:min-w-[300px]">
        @include('partials.buscador', ['ruta' => route('usuarios.index'), 'busqueda' => $busqueda, 'placeholder' => 'Buscar por nombre o correo…'])
    </div>
</div>

<div class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Nombre</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Rol</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Correo institucional</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Correo personal</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Estado</th>
                    <th scope="col" class="relative px-6 py-4"><span class="sr-only">Acciones</span></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-100">
            @forelse ($usuarios as $u)
                <tr class="hover:bg-slate-50/50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-800 flex items-center gap-2">
                        <span class="w-7 h-7 rounded-full bg-indigo-100 text-xs font-bold text-indigo-700 flex items-center justify-center uppercase">{{ mb_substr($u->nombres,0,1).mb_substr($u->apellidos,0,1) }}</span>
                        {{ $u->apellidos }}, {{ $u->nombres }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex px-2.5 py-1 rounded-md text-xs font-medium bg-slate-100 text-slate-700 ring-1 ring-inset ring-slate-500/20">
                            {{ $u->rol->label() }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $u->correo_institucional ?? '—' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $u->email }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex px-2.5 py-1 rounded-md text-xs font-medium {{ $u->estaActivo() ? 'bg-green-100 text-green-700 ring-1 ring-inset ring-green-600/20' : 'bg-red-100 text-red-700 ring-1 ring-inset ring-red-600/20' }}">
                            {{ ucfirst($u->estado) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('usuarios.edit', $u) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-600 bg-white border border-slate-300 rounded-md hover:bg-slate-50 hover:text-indigo-600 transition-colors shadow-sm">
                                <x-icono nombre="editar" class="w-4 h-4 text-slate-400" /> Editar
                            </a>
                            @if (! $u->is(auth()->user()))
                            <form method="POST" action="{{ route('usuarios.estado', $u) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium {{ $u->estaActivo() ? 'text-red-600 bg-white border border-red-200 hover:bg-red-50' : 'text-green-600 bg-white border border-green-200 hover:bg-green-50' }} rounded-md transition-colors shadow-sm">
                                    <x-icono :nombre="$u->estaActivo() ? 'usuario-desactivar' : 'usuario-activar'" class="w-4 h-4 {{ $u->estaActivo() ? 'text-red-400' : 'text-green-400' }}" /> 
                                    {{ $u->estaActivo() ? 'Desactivar' : 'Activar' }}
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-slate-900">No se encontraron usuarios</h3>
                        <p class="mt-1 text-sm text-slate-500">Intenta buscar con otros términos.</p>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
{{ $usuarios->links('partials.paginacion') }}
@endsection
