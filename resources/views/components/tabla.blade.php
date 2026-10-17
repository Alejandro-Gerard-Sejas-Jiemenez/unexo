{{-- Tabla de listado con el aspecto de los demás índices. Cada columna: ['titulo' => ..., 'tipo' => texto|entero|porcentaje|fecha]; cada fila, sus valores en el mismo orden. --}}
@props(['titulo', 'columnas', 'filas' => [], 'vacio' => null])
@use('App\Support\Reporte')
@php $numerica = fn (array $columna) => in_array($columna['tipo'], ['entero', 'porcentaje']); @endphp
<section class="mb-8 bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <caption class="px-6 py-4 text-left text-base font-semibold text-slate-800 bg-white">{{ $titulo }}</caption>
            <thead class="bg-slate-50">
                <tr>
                    @foreach ($columnas as $columna)
                        <th scope="col" class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider {{ $numerica($columna) ? 'text-right' : 'text-left' }}">{{ $columna['titulo'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-100">
                @forelse ($filas as $fila)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        @foreach ($columnas as $i => $columna)
                            <td class="px-6 py-4 text-sm text-slate-700 {{ $numerica($columna) ? 'text-right tabular-nums' : '' }}">{{ Reporte::formatear($fila[$i], $columna['tipo']) }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columnas) }}" class="px-6 py-4 text-sm text-slate-500">{{ $vacio ?? Reporte::AVISO_SIN_DATOS }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
