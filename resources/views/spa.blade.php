<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="base-path" content="{{ rtrim(parse_url(url('/'), PHP_URL_PATH) ?: '', '/') }}">
    <title>{{ config('app.name') }}</title>
    <link rel="icon" href="{{ url('/images/logo.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700;800&family=Nunito:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @viteReactRefresh
    @vite(['resources/css/spa.css', 'resources/js/spa/main.jsx'])
</head>
<body>
    <div id="app"></div>
</body>
</html>
