@echo off
title JP MARKET - Servidor Local
color 0B
echo.
echo  ==========================================
echo   JP MARKET - Servidor de desarrollo
echo   http://localhost:8082
echo   http://localhost:8082/admin/login.php
echo  ==========================================
echo.
echo  Usuario: admin@jpmarket.com
echo  Contrasena: ver logs/install.log (generada al crear la DB)
echo.
echo  Presiona Ctrl+C para detener el servidor.
echo.
"C:\xampp\php\php.exe" -S localhost:8082 "%~dp0dev-router.php" -t "%~dp0"
pause
