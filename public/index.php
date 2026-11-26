<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// El .htaccess de la raíz sirve public/ por reescritura, pero la URL no lleva
// "/public": sin esto Laravel no reconoce el prefijo /inf513/grupo06sa/proyecto2.
foreach (['SCRIPT_NAME', 'PHP_SELF'] as $clave) {
    if (isset($_SERVER[$clave])) {
        $_SERVER[$clave] = str_replace('/public/index.php', '/index.php', $_SERVER[$clave]);
    }
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
