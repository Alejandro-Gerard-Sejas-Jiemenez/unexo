<?php

// Hook PostToolUse: corre la suite de tests cuando se edita código PHP de la aplicación.
// Lee el JSON del hook por stdin. Si los tests fallan, sale con código 2 para que el agente vea el error.

$entrada = json_decode(stream_get_contents(STDIN), true);
$archivo = str_replace('\\', '/', $entrada['tool_input']['file_path'] ?? '');

$esCodigo = preg_match('#/(app|routes|database|config|tests|resources/views)/#', $archivo)
    && preg_match('#\.(php)$#', $archivo);

if (! $esCodigo) {
    exit(0);
}

$raiz = dirname(__DIR__, 2);
chdir($raiz);

exec('php artisan test --stop-on-failure 2>&1', $salida, $codigo);

if ($codigo !== 0) {
    fwrite(STDERR, "Los tests fallaron tras editar {$archivo}:\n".implode("\n", array_slice($salida, -40))."\n");
    exit(2);
}

exit(0);
