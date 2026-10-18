@extends('layouts.app')
@section('titulo', 'Acceso API & Integraciones CRM')
@section('contenido')
    <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                    Módulo de Seguridad
                </span>
                <span class="text-xs text-slate-400">REST API v1</span>
            </div>
            <h1 class="text-3xl font-bold text-slate-800 tracking-tight mt-1">Acceso API & Integración CRM</h1>
            <p class="text-sm text-slate-500 mt-1">
                Genera credenciales seguras (tokens Bearer) y consulta la documentación de endpoints para que el CRM consuma
                el catálogo de cursos y sincronice matrículas.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('catalogo') }}"
                class="inline-flex items-center gap-1.5 px-4 py-2 bg-white border border-slate-300 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50 shadow-sm transition-colors">
                <x-icono nombre="catalogo" class="w-4 h-4 text-indigo-600" /> Ver catálogo visual
            </a>
        </div>
    </div>

    {{-- Banner de Token Recién Generado --}}
    @if (session('token'))
        <div class="mb-8 p-5 bg-linear-to-r from-amber-50 to-orange-50 border border-amber-300 rounded-xl shadow-sm"
            role="alert">
            <div class="flex items-start gap-3">
                <div class="w-9 h-9 rounded-lg bg-amber-200 text-amber-800 flex items-center justify-center shrink-0 mt-0.5">
                    <x-icono nombre="candado" class="w-5 h-5" />
                </div>
                <div class="flex-1">
                    <h3 class="font-bold text-amber-900 text-base">Token de acceso generado con éxito</h3>
                    <p class="text-xs text-amber-800 mt-0.5 mb-3">
                        Copia este token ahora mismo. Por motivos de seguridad criptográfica, <strong>no se volverá a
                            mostrar</strong> una vez que salgas de esta pantalla.
                    </p>
                    <div class="flex items-center gap-2 max-w-2xl">
                        <input id="token_generado" type="text" readonly value="{{ session('token') }}"
                            class="w-full font-mono text-xs p-2.5 bg-white border border-amber-300 rounded-lg select-all text-slate-800 shadow-inner">
                        <button type="button" onclick="copiarToken()"
                            class="px-4 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-medium text-xs rounded-lg shadow-sm transition-colors shrink-0 flex items-center gap-1.5">
                            <span id="btn_copiar_texto">Copiar</span>
                        </button>
                    </div>
                    <p class="mt-2 text-xs text-amber-700">
                        Uso en peticiones HTTP: <code
                            class="bg-amber-100/80 px-1.5 py-0.5 rounded font-mono text-[11px]">Authorization: Bearer {{ session('token') }}</code>
                    </p>
                </div>
            </div>
        </div>

        <script>
            function copiarToken() {
                const input = document.getElementById('token_generado');
                input.select();
                navigator.clipboard.writeText(input.value).then(() => {
                    const btn = document.getElementById('btn_copiar_texto');
                    btn.textContent = '¡Copiado!';
                    setTimeout(() => { btn.textContent = 'Copiar'; }, 2500);
                });
            }
        </script>
    @endif

    {{-- Sección de Gestión: Formulario de Creación y Tabla de Clientes --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-12">
        {{-- Formulario para nuevo cliente --}}
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6 lg:col-span-1 flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <div
                        class="w-7 h-7 rounded-md bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600">
                        <x-icono nombre="candado" class="w-4 h-4" />
                    </div>
                    <h2 class="text-base font-bold text-slate-900">Crear cliente API</h2>
                </div>
                <p class="text-xs text-slate-500 mb-5 leading-relaxed">
                    Registra un nuevo sistema cliente (ej. <em>CRM Comercial</em>, <em>Landing Page</em>, <em>Portal
                        Web</em>) para otorgarle acceso a los endpoints de la API.
                </p>

                <form method="POST" action="{{ route('clientes-api.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <x-campo nombre="nombre" etiqueta="Nombre del cliente o sistema" :requerido="true"
                            placeholder="Ej. CRM Comercial HubSpot" />
                    </div>
                    <button type="submit"
                        class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm rounded-lg shadow-sm transition-all flex items-center justify-center gap-2">
                        <x-icono nombre="mas" class="w-4 h-4" /> Generar nuevo token
                    </button>
                </form>
            </div>

            <div class="mt-6 pt-4 border-t border-slate-100 text-xs text-slate-400">
                Los tokens utilizan la especificación <strong>Laravel Sanctum Bearer</strong> con cifrado SHA-256.
            </div>
        </div>

        {{-- Tabla de clientes activos --}}
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden lg:col-span-2">
            <div class="p-5 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Sistemas y Tokens Activos</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Sistemas autorizados para realizar consultas e inserciones vía
                        API.</p>
                </div>
                <span
                    class="px-2.5 py-1 bg-white border border-slate-200 rounded-full text-xs font-semibold text-slate-700">
                    {{ $clientes->count() }} {{ $clientes->count() === 1 ? 'cliente' : 'clientes' }}
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm divide-y divide-slate-200">
                    <thead class="bg-slate-50 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-3.5">Sistema / Cliente</th>
                            <th class="px-6 py-3.5">Estado</th>
                            <th class="px-6 py-3.5">Tokens</th>
                            <th class="px-6 py-3.5">Fecha alta</th>
                            <th class="px-6 py-3.5 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($clientes as $cliente)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-4 font-bold text-slate-800">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                        {{ $cliente->nombre }}
                                    </div>
                                    <span class="text-[11px] font-mono text-slate-400 ml-4">ID:
                                        {{ substr($cliente->id, 0, 8) }}…</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                                        Activo
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-xs font-medium text-slate-600">
                                    {{ $cliente->tokens_count }} {{ $cliente->tokens_count === 1 ? 'token' : 'tokens' }}
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-500">
                                    {{ $cliente->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <form method="POST" action="{{ route('clientes-api.destroy', $cliente) }}"
                                        onsubmit="return confirm('¿Revocar todo el acceso al cliente «{{ $cliente->nombre }}»? Sus tokens dejarán de funcionar de inmediato.');">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                            class="text-xs font-semibold text-rose-600 hover:text-rose-800 hover:underline">
                                            Revocar acceso
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                                    <x-icono nombre="candado" class="w-8 h-8 text-slate-300 mx-auto mb-2" />
                                    <p class="font-medium text-slate-700">Aún no hay clientes API registrados</p>
                                    <p class="text-xs text-slate-400 mt-1">Crea uno en el formulario lateral para habilitar al
                                        CRM.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- DOCUMENTACIÓN INTERACTIVA DE ENDPOINTS PARA EL CRM --}}
    <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6 sm:p-8">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 pb-6 border-b border-slate-200">
            <div>
                <div
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-bold uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-100 mb-2">
                    Guía de Integración CRM
                </div>
                <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Documentación de la API para el CRM</h2>
                <p class="text-sm text-slate-500 mt-1">Endpoints listos para consumir los programas, módulos ofertables,
                    ediciones y convertir prospectos.</p>
            </div>
            <div class="bg-slate-50 p-3 rounded-lg border border-slate-200 text-xs font-mono text-slate-600">
                <span class="text-slate-400">Base URL:</span> <strong>{{ url('/api/v1') }}</strong>
            </div>
        </div>

        {{-- Lista de Endpoints --}}
        <div class="space-y-4">
            {{-- Endpoint 1: Catálogo Completo CRM --}}
            <div class="border border-slate-200 rounded-xl overflow-hidden">
                <div
                    class="p-4 bg-slate-50 border-b border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="px-2.5 py-1 bg-emerald-600 text-white rounded font-mono font-bold text-xs">GET</span>
                        <code class="font-mono text-sm font-semibold text-slate-800">/api/v1/crm/catalogo</code>
                    </div>
                    <span class="text-xs text-slate-500 font-medium">Recomendado para el CRM (un solo fetch)</span>
                </div>
                <div class="p-5 text-sm text-slate-600">
                    <p class="mb-3 text-xs leading-relaxed">
                        Devuelve la <strong>oferta comercial completa</strong>: programas abiertos con su lista ordenada de
                        módulos, sus ediciones planificadas (Edición 1, Edición 2...), grupos operativos con modalidad
                        (Virtual, Presencial, Mixta), y los módulos independientes con fechas de inicio.
                    </p>
                    <details class="group">
                        <summary
                            class="text-xs font-semibold text-indigo-600 cursor-pointer hover:underline list-none flex items-center gap-1">
                            <x-icono nombre="chevron" class="w-3.5 h-3.5 transition-transform group-open:rotate-180" />
                            Ver ejemplo de respuesta JSON
                        </summary>
                        <div
                            class="mt-3 p-4 bg-slate-900 rounded-lg text-emerald-400 font-mono text-xs overflow-x-auto leading-relaxed shadow-inner">
                            <pre>{
      "exito": true,
      "fecha_consulta": "{{ now()->toIso8601String() }}",
      "resumen": {
        "total_programas_abiertos": 3,
        "total_modulos_libres": 8
      },
      "programas": [
        {
          "id": "uuid-programa-1",
          "codigo": "DIP-AI",
          "nombre": "Diplomado en Inteligencia Artificial",
          "version": "1.0",
          "descripcion": "Especialización avanzada en modelos de aprendizaje...",
          "nivel_academico": "Postgrado",
          "precio": 4500.00,
          "moneda": "BOB",
          "total_modulos": 5,
          "duracion_total_horas": 160,
          "total_creditos": 16,
          "modulos": [
            { "id": "uuid-mod-1", "codigo": "MOD-1", "nombre": "Fundamentos de IA", "orden": 1, "duracion_horas": 30, "creditos": 3 }
          ],
          "ediciones_disponibles": [
            {
              "id": "uuid-edicion-1",
              "nombre": "Edición 1",
              "fecha_inicio": "2026-09-01",
              "fecha_fin": "2026-12-15",
              "estado": "programado",
              "grupos": [
                { "id": "uuid-grupo-1", "nombre": "Grupo A", "modalidad": "mixta", "fecha_inicio": "2026-09-01", "aula": "Aula Magna" }
              ]
            }
          ]
        }
      ],
      "modulos_libres": [
        {
          "id": "uuid-mod-indep",
          "codigo": "CUR-PY",
          "nombre": "Python para Análisis de Datos",
          "duracion_horas": 40,
          "creditos": 4,
          "grupos_disponibles": [
            { "id": "uuid-g-suelto", "nombre": "Grupo A", "modalidad": "virtual", "fecha_inicio": "2026-09-10" }
          ]
        }
      ]
    }</pre>
                        </div>
                    </details>
                </div>
            </div>

            {{-- Endpoint 2: Programas Específicos --}}
            <div class="border border-slate-200 rounded-xl overflow-hidden">
                <div
                    class="p-4 bg-slate-50 border-b border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="px-2.5 py-1 bg-emerald-600 text-white rounded font-mono font-bold text-xs">GET</span>
                        <code
                            class="font-mono text-sm font-semibold text-slate-800">/api/v1/crm/programas?q={busqueda}&nivel={nivel}</code>
                    </div>
                    <span class="text-xs text-slate-500 font-medium">Búsqueda y filtrado de programas</span>
                </div>
                <div class="p-4 text-xs text-slate-600">
                    Permite al CRM filtrar programas por texto (nombre, código o descripción) y nivel académico. Devuelve la
                    lista en formato <code>CrmProgramaResource</code>.
                </div>
            </div>

            {{-- Endpoint 3: Ficha Detallada de Programa --}}
            <div class="border border-slate-200 rounded-xl overflow-hidden">
                <div
                    class="p-4 bg-slate-50 border-b border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="px-2.5 py-1 bg-emerald-600 text-white rounded font-mono font-bold text-xs">GET</span>
                        <code class="font-mono text-sm font-semibold text-slate-800">/api/v1/crm/programas/{id}</code>
                    </div>
                    <span class="text-xs text-slate-500 font-medium">Ficha técnica por ID</span>
                </div>
                <div class="p-4 text-xs text-slate-600">
                    Obtiene toda la información curricular, perfil de egreso, requisitos de ingreso, certificación, precio y
                    ediciones de un único programa.
                </div>
            </div>

            {{-- Endpoint 4: Módulos Libres --}}
            <div class="border border-slate-200 rounded-xl overflow-hidden">
                <div
                    class="p-4 bg-slate-50 border-b border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="px-2.5 py-1 bg-emerald-600 text-white rounded font-mono font-bold text-xs">GET</span>
                        <code class="font-mono text-sm font-semibold text-slate-800">/api/v1/crm/modulos?q={busqueda}</code>
                    </div>
                    <span class="text-xs text-slate-500 font-medium">Cursos y módulos sueltos</span>
                </div>
                <div class="p-4 text-xs text-slate-600">
                    Lista los módulos abiertos que los prospectos pueden tomar individualmente, con enlaces a temario en PDF
                    y grupos disponibles.
                </div>
            </div>

            {{-- Endpoint 5: Convertir Prospecto desde CRM --}}
            <div class="border border-slate-200 rounded-xl overflow-hidden">
                <div
                    class="p-4 bg-slate-50 border-b border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="px-2.5 py-1 bg-blue-600 text-white rounded font-mono font-bold text-xs">POST</span>
                        <code class="font-mono text-sm font-semibold text-slate-800">/api/v1/estudiantes-convertidos</code>
                    </div>
                    <span class="text-xs text-slate-500 font-medium">Conversión de Lead a Alumno</span>
                </div>
                <div class="p-5 text-sm text-slate-600">
                    <p class="mb-3 text-xs leading-relaxed">
                        Cuando un lead se cierra exitosamente en el CRM, envía sus datos para matricularlo automáticamente
                        en una edición de programa o en un módulo suelto.
                    </p>
                    <details class="group">
                        <summary
                            class="text-xs font-semibold text-indigo-600 cursor-pointer hover:underline list-none flex items-center gap-1">
                            <x-icono nombre="chevron" class="w-3.5 h-3.5 transition-transform group-open:rotate-180" />
                            Ver ejemplo de solicitud cURL
                        </summary>
                        <div
                            class="mt-3 p-4 bg-slate-900 rounded-lg text-slate-200 font-mono text-xs overflow-x-auto leading-relaxed shadow-inner">
                            <pre>curl -X POST "{{ url('/api/v1/estudiantes-convertidos') }}" \
      -H "Authorization: Bearer &lt;TU_TOKEN&gt;" \
      -H "Content-Type: application/json" \
      -H "Accept: application/json" \
      -d '{
        "id_externo_crm": "LEAD-9824",
        "nombres": "Juan Carlos",
        "apellidos": "Pérez Gómez",
        "email": "juan.perez@example.com",
        "documento_identidad": "8472910 LP",
        "telefono": "+591 71234567",
        "cohorte_id": "uuid-de-la-edicion"
      }'</pre>
                        </div>
                    </details>
                </div>
            </div>
        </div>
    </div>
@endsection