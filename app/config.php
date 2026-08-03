<?php
// En producción (Hostinger) la DB vive fuera de public_html para sobrevivir deploys.
// En local (cli-server) usa la carpeta database/ del proyecto.
if (php_sapi_name() === 'cli-server') {
    define('DB_PATH', __DIR__ . '/../database/jpmarket.sqlite');
} else {
    // Sube 4 niveles: app/ → public_html/ → jpmarketpro.com/ → domains/ → /home/user/
    $_dbHome = dirname(dirname(dirname(dirname(__DIR__))));
    $_dbDir  = $_dbHome . '/jpmarket_db';
    if (!is_dir($_dbDir)) @mkdir($_dbDir, 0755, true);
    define('DB_PATH', $_dbDir . '/jpmarket.sqlite');
    unset($_dbHome, $_dbDir);
}

define('SITE_NAME', 'JP MARKET');
define('SITE_SLOGAN', 'Empower your mind');
// Auto-detecta la URL según el entorno
if (php_sapi_name() === 'cli-server') {
    define('BASE_URL', 'http://localhost:8082');
} else {
    define('BASE_URL', 'https://jpmarketpro.com');
}
define('ADMIN_URL', BASE_URL . '/admin');

define('SESSION_NAME', 'jpmarket_admin');
define('SESSION_LIFETIME', 3600);

ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

date_default_timezone_set('America/Argentina/Buenos_Aires');

session_name(SESSION_NAME);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
