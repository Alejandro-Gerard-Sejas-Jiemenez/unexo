<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/spa.css', 'resources/js/spa/main.jsx'])
</head>
<body>
    <div id="app"></div>
</body>
</html>
