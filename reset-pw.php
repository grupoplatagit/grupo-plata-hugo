<?php
require_once __DIR__ . '/app/config.php';
require_once __DIR__ . '/app/db.php';
$db   = getDB();
$hash = password_hash('admin123', PASSWORD_DEFAULT);
$db->prepare('UPDATE admins SET password=? WHERE email=?')->execute([$hash, 'admin@jpmarket.com']);
unlink(__FILE__); // se borra solo
echo 'OK — contraseña reseteada a admin123. Este archivo fue eliminado.';
