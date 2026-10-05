@extends('layouts.app')
@section('titulo', $usuario->exists ? 'Editar usuario' : 'Nuevo usuario')
@section('errores_propios', '1')
@section('contenido')
<div class="mb-4">
    <a href="{{ route('usuarios.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-indigo-600 transition-colors">
        <x-icono nombre="volver" class="w-4 h-4" /> Volver a usuarios
    </a>
</div>

<h1 class="text-3xl font-bold text-slate-800 tracking-tight mb-6">{{ $usuario->exists ? 'Editar usuario' : 'Nuevo usuario' }}</h1>

<div class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden max-w-4xl">
    <form method="POST" action="{{ $usuario->exists ? route('usuarios.update', $usuario) : route('usuarios.store') }}" class="p-6 sm:p-8">
        @csrf
        @if ($usuario->exists) @method('PUT') @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
            <div class="space-y-4">
                <h3 class="text-lg font-medium text-slate-900 border-b border-slate-200 pb-2 mb-4">Información de la cuenta</h3>
                <x-campo-select nombre="rol" etiqueta="Rol" requerido
                    :opciones="collect(\App\Enums\Rol::cases())->mapWithKeys(fn ($r) => [$r->value => $r->label()])"
                    :valor="$usuario->rol?->value" />
                
                <x-campo nombre="correo_institucional" limite="usuarios.correo_institucional" etiqueta="Correo institucional" tipo="email" :valor="$usuario->correo_institucional" autocomplete="off" />
                <x-campo nombre="email" limite="usuarios.email" etiqueta="Correo personal" tipo="email" requerido :valor="$usuario->email" autocomplete="off" />
                
                <x-campo nombre="password" limite="usuarios.password" etiqueta="Contraseña" tipo="password" autocomplete="new-password"
                    :requerido="! $usuario->exists"
                    :ayuda="$usuario->exists ? 'Déjala vacía para no cambiarla' : null" />
            </div>
            
            <div class="space-y-4 mt-8 md:mt-0">
                <h3 class="text-lg font-medium text-slate-900 border-b border-slate-200 pb-2 mb-4">Datos personales</h3>
                <x-campo nombre="documento_identidad" limite="usuarios.documento_identidad" etiqueta="Documento de identidad" :valor="$usuario->documento_identidad" />
                <x-campo nombre="nombres" limite="usuarios.nombres" etiqueta="Nombres" requerido :valor="$usuario->nombres" />
                <x-campo nombre="apellidos" limite="usuarios.apellidos" etiqueta="Apellidos" requerido :valor="$usuario->apellidos" />
                <x-campo nombre="telefono" limite="usuarios.telefono" etiqueta="Teléfono" :valor="$usuario->telefono" />
            </div>
        </div>

        <x-form-acciones :cancelar="route('usuarios.index')" />
    </form>
</div>
@endsection
