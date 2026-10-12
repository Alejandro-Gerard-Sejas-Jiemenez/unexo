<?php

namespace App\Http\Controllers;

use App\Enums\EstadoComercial;
use App\Models\Modulo;
use App\Models\Programa;
use App\Services\OrdenModulosService;
use App\Support\Opciones;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProgramaController extends Controller
{
    public function index(Request $request): View
    {
        $estado = $request->query('estado');
        $sinModulosAbiertos = $request->query('atencion') === 'sin-modulos-abiertos';
        $busqueda = trim((string) $request->query('q', ''));

        $programas = Programa::withCount('modulos')
            ->when(EstadoComercial::tryFrom((string) $estado), fn ($q, $e) => $q->where('estado_comercial', $e->value))
            ->when($sinModulosAbiertos, fn ($q) => $q->abiertoSinModulosAbiertos())
            ->when($busqueda !== '', fn ($q) => $q->where(fn ($b) => $b
                ->where('codigo', 'like', "%{$busqueda}%")
                ->orWhere('nombre', 'like', "%{$busqueda}%")))
            ->orderBy('nombre')
            ->paginate(config('limites.por_pagina'))->withQueryString();

        return view('programas.index', [
            'programas' => $programas,
            'estadoFiltro' => $estado,
            'sinModulosAbiertos' => $sinModulosAbiertos,
            'busqueda' => $busqueda,
        ]);
    }

    public function create(): View
    {
        return view('programas.form', ['programa' => new Programa(['version' => '1.0', 'estado_comercial' => EstadoComercial::Borrador])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->reglas(null));

        if ($request->hasFile('imagen_portada_url')) {
            $path = $request->file('imagen_portada_url')->store('programas', 'public');
            $data['imagen_portada_url'] = '/storage/' . $path;
        }

        $programa = Programa::create($data);

        return redirect()->route('programas.show', $programa)->with('ok', 'Programa creado. Ahora agrega sus módulos.');
    }

    public function show(Programa $programa): View
    {
        $programa->load('modulos');

        [$disponibles, $disponiblesTotal] = Opciones::limitar(
            Modulo::whereNotIn('id', $programa->modulos->pluck('id'))->orderBy('nombre')
        );

        return view('programas.show', compact('programa', 'disponibles', 'disponiblesTotal'));
    }

    public function edit(Programa $programa): View
    {
        return view('programas.form', compact('programa'));
    }

    public function update(Request $request, Programa $programa): RedirectResponse
    {
        $data = $request->validate($this->reglas($programa));

        if ($request->hasFile('imagen_portada_url')) {
            $path = $request->file('imagen_portada_url')->store('programas', 'public');
            $data['imagen_portada_url'] = '/storage/' . $path;
        } else {
            unset($data['imagen_portada_url']);
        }

        $programa->update($data);

        return redirect()->route('programas.show', $programa)->with('ok', 'Programa actualizado.');
    }

    public function agregarModulo(Request $request, Programa $programa, OrdenModulosService $orden): RedirectResponse
    {
        $datos = $request->validate(['modulo_id' => ['required', 'uuid', 'exists:modulos,id']]);

        if (! $orden->agregar($programa, $datos['modulo_id'])) {
            return back()->withErrors(['modulo_id' => 'Ese módulo ya está en el programa.']);
        }

        return back()->with('ok', 'Módulo agregado al programa.');
    }

    public function quitarModulo(Programa $programa, Modulo $modulo, OrdenModulosService $orden): RedirectResponse
    {
        $orden->quitar($programa, $modulo);

        return back()->with('ok', 'Módulo quitado del programa.');
    }

    public function moverModulo(Request $request, Programa $programa, Modulo $modulo, OrdenModulosService $orden): RedirectResponse
    {
        $datos = $request->validate(['direccion' => ['required', Rule::in(['subir', 'bajar'])]]);

        $orden->mover($programa, $modulo, $datos['direccion']);

        return back();
    }

    private function reglas(?Programa $programa): array
    {
        return [
            'codigo' => ['required', 'string', 'max:'.config('limites.programas.codigo'), Rule::unique('programas', 'codigo')->ignore($programa)],
            'nombre' => [
                'required', 'string', 'max:'.config('limites.programas.nombre'),
                Rule::unique('programas', 'nombre')
                    ->where(fn ($q) => $q->where('version', request('version')))
                    ->ignore($programa),
            ],
            'version' => ['required', 'string', 'max:'.config('limites.programas.version')],
            'descripcion' => ['nullable', 'string', 'max:'.config('limites.programas.descripcion')],
            'estado_comercial' => ['required', Rule::enum(EstadoComercial::class)],
            'precio' => ['nullable', 'numeric', 'min:0'],
            'nivel_academico' => ['nullable', 'string', 'max:100'],
            'imagen_portada_url' => ['nullable', 'image', 'max:2048'],
            'requisitos_ingreso' => ['nullable', 'string'],
            'perfil_egreso' => ['nullable', 'string'],
            'certificado_otorga' => ['nullable', 'string', 'max:255'],
        ];
    }
}
