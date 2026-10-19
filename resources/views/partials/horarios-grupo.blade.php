@if ($horarios->isEmpty())
    <p class="text-base text-slate-500">Todavía no se registraron horarios.</p>
@else
    <table class="w-full max-w-md text-left border-collapse">
        <caption class="sr-only">Horarios del grupo, de lunes a domingo</caption>
        <thead>
            <tr class="text-sm font-semibold text-slate-600 border-b border-slate-200">
                <th scope="col" class="py-2 pr-4">Día</th>
                <th scope="col" class="py-2">Horario</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-base text-slate-900">
            @foreach ($horarios as $horario)
                <tr>
                    <th scope="row" class="py-2 pr-4 font-medium">{{ $horario->dia_semana->label() }}</th>
                    <td class="py-2">{{ $horario->rango }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
