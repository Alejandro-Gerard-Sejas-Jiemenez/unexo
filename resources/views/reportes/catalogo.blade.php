@extends('layouts.app')
@section('titulo', 'Reporte de catálogo y oferta')
@section('contenido')
<div class="mb-6">
    <h1 class="text-3xl font-bold text-slate-800 tracking-tight">Reportes</h1>
    <p class="text-sm text-slate-500 mt-1">Estado del catálogo y oferta que ve el CRM.</p>
</div>

@include('reportes._navegacion')

@if ($reporte)
    <div class="mb-6">
        @include('reportes._descargas', ['rutaPdf' => 'reportes.catalogo.pdf', 'rutaExcel' => 'reportes.catalogo.excel', 'filtros' => $filtros])
    </div>

    @include('reportes._cuerpo', ['reporte' => $reporte])
@endif
@endsection
