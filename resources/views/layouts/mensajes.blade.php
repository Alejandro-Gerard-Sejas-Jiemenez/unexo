@if (session('ok'))
    <div class="alerta ok" role="status">{{ session('ok') }}</div>
@endif
@if ($errors->any() && ! View::hasSection('errores_propios'))
    <div class="alerta error" role="alert">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
