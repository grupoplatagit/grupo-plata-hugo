<?php
// Router para el servidor de desarrollo (php -S)
// No usar en producción
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Si el archivo existe en el proyecto, servirlo directamente (CSS, JS, imágenes)
$file = __DIR__ . $path;
if ($path !== '/' && is_file($file)) {
    return false;
}

// Todo lo demás va a la landing page
require __DIR__ . '/public/index.php';
