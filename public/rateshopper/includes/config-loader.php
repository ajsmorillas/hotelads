<?php

/**
 * Carga config.php desde fuera del árbol que gestiona Astro (public/ -> dist/),
 * en un directorio hermano de dist/: dirname($_SERVER['DOCUMENT_ROOT']) . '/rateshopper-config/config.php'.
 * Así sobrevive a cada rebuild (`npm run build` limpia dist/ en cada deploy,
 * pero nunca toca esta carpeta porque está fuera del árbol que gestiona Astro).
 */
function rateshopper_config(): array
{
    static $config = null;

    if ($config !== null) {
        return $config;
    }

    $path = dirname($_SERVER['DOCUMENT_ROOT']) . '/rateshopper-config/config.php';

    if (!is_file($path)) {
        http_response_code(500);
        exit(
            'Rate Shopper: no se encuentra el archivo de configuración en "' . $path . '". ' .
            'Debe crearse manualmente en el servidor (fuera de dist/, sobrevive a los rebuilds) ' .
            'a partir de rateshopper-config/config.example.php. Consulta las instrucciones de despliegue.'
        );
    }

    $config = require $path;

    return $config;
}
