@extends('layouts.app')
@section('titulo', 'Acceso de la API')
@section('contenido')
<div class="mb-8">
    <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Acceso de la API</h1>
    <p class="mt-2 text-lg text-slate-500">Tokens que usan los sistemas externos (el CRM) para consumir el SIS.</p>
</div>

@if (session('token'))
    <div class="mb-8 p-5 bg-amber-50 border border-amber-200 rounded-xl" role="alert">
        <p class="font-semibold text-amber-900 mb-2">Copia el token ahora: no se volverá a mostrar.</p>
        <code class="block break-all p-3 bg-white border border-amber-200 rounded-lg text-sm select-all">{{ session('token') }}</code>
        <p class="mt-2 text-sm text-amber-800">Se envía en el encabezado <code>Authorization: Bearer &lt;token&gt;</code>.</p>
    </div>
@endif

<form method="POST" action="{{ route('clientes-api.store') }}" class="mb-8 flex flex-col sm:flex-row gap-3 sm:items-end max-w-xl">
    @csrf
    <div class="flex-1">
        <x-campo nombre="nombre" etiqueta="Nombre del cliente" :requerido="true" icono="candado" />
    </div>
    <button type="submit" class="px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 shadow-sm">Generar token</button>
</form>

<div class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden">
    <table class="w-full text-left text-sm">
        <thead class="bg-slate-50 border-b border-slate-200 font-semibold text-slate-600">
            <tr><th class="px-6 py-4">Cliente</th><th class="px-6 py-4">Tokens activos</th><th class="px-6 py-4">Creado</th><th class="px-6 py-4"></th></tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
        @forelse ($clientes as $cliente)
            <tr>
                <td class="px-6 py-4 font-medium text-slate-900">{{ $cliente->nombre }}</td>
                <td class="px-6 py-4 text-slate-500">{{ $cliente->tokens_count }}</td>
                <td class="px-6 py-4 text-slate-500">{{ $cliente->created_at->format('d/m/Y') }}</td>
                <td class="px-6 py-4 text-right">
                    <form method="POST" action="{{ route('clientes-api.destroy', $cliente) }}" onsubmit="return confirm('¿Revocar el acceso de {{ $cliente->nombre }}?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-rose-700 font-medium hover:underline">Revocar</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="px-6 py-10 text-center text-slate-500">Todavía no hay clientes de la API.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
