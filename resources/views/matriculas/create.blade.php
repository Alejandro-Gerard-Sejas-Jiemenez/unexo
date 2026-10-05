@extends('layouts.app')
@section('titulo', 'Nueva matrícula')
@section('errores_propios', '1')
@section('contenido')
<div class="mb-4">
    <a href="{{ route('matriculas.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-indigo-600 transition-colors">
        <x-icono nombre="volver" class="w-4 h-4" /> Volver a matrículas
    </a>
</div>

<h1 class="text-3xl font-bold text-slate-800 tracking-tight mb-6">Nueva matrícula</h1>

<div class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden max-w-3xl">
    <form method="POST" action="{{ route('matriculas.store') }}" class="p-6 sm:p-8">
        @csrf

        <div class="mb-6 pb-6 border-b border-slate-100">
            <x-campo-select nombre="estudiante_id" :total="$estudiantesTotal" etiqueta="Estudiante" requerido
                :opciones="collect(['' => '— Selecciona un estudiante —'])->union($estudiantes->mapWithKeys(fn ($e) => [$e->id => $e->apellidos.', '.$e->nombres]))"
                :valor="old('estudiante_id')" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
            <div>
                <x-campo-select nombre="tipo" etiqueta="Tipo de matrícula" requerido
                    :opciones="['cohorte' => 'Programa completo (cohorte)', 'suelto' => 'Módulo suelto']"
                    :valor="old('tipo', 'cohorte')" />
            </div>

            <div data-tipo="cohorte">
                <x-campo-select nombre="cohorte_id" :total="$cohortesTotal" etiqueta="Cohorte"
                    :opciones="collect(['' => '— Selecciona una cohorte —'])->union($cohortes->mapWithKeys(fn ($c) => [$c->id => $c->programa->nombre.' · '.$c->nombre.' ('.$c->grupos_count.' grupos)']))"
                    :valor="old('cohorte_id')" />
                @if ($cohortes->isEmpty())
                    <p class="mt-2 text-sm text-amber-600 bg-amber-50 p-2.5 rounded border border-amber-100">
                        No hay cohortes creadas. <a href="{{ route('cohortes.create') }}" class="font-medium underline hover:text-amber-800">Crea una cohorte</a> primero.
                    </p>
                @endif
            </div>

            <div data-tipo="suelto" hidden>
                <x-campo-select nombre="grupo_id" :total="$gruposSueltosTotal" etiqueta="Grupo de módulo suelto"
                    :opciones="collect(['' => '— Selecciona un grupo —'])->union($gruposSueltos->mapWithKeys(fn ($g) => [$g->id => $g->modulo->nombre.' · '.$g->nombre]))"
                    :valor="old('grupo_id')" />
                @if ($gruposSueltos->isEmpty())
                    <p class="mt-2 text-sm text-amber-600 bg-amber-50 p-2.5 rounded border border-amber-100">
                        No hay grupos de módulo suelto. <a href="{{ route('grupos.create') }}" class="font-medium underline hover:text-amber-800">Crea un grupo</a> sin cohorte.
                    </p>
                @endif
            </div>
        </div>

        <div class="mt-6 mb-2">
            <div class="rounded-lg bg-indigo-50 border border-indigo-100 p-4 flex items-start gap-3">
                <svg class="w-5 h-5 text-indigo-500 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="text-sm text-indigo-800">La matrícula queda <strong>pendiente de pago</strong> hasta que apruebes el comprobante.</p>
            </div>
        </div>

        <x-form-acciones :cancelar="route('matriculas.index')" etiqueta="Registrar matrícula" />
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var tipo = document.getElementById('tipo');
    var bloques = document.querySelectorAll('[data-tipo]');
    function aplicar() {
        bloques.forEach(function (b) { b.hidden = b.getAttribute('data-tipo') !== tipo.value; });
    }
    if (tipo) { tipo.addEventListener('change', aplicar); aplicar(); }
})();
</script>
@endpush
