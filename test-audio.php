<?php
echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Test Audio</title></head><body>";
echo "<h1>DIAGNÓSTICO DE AUDIO</h1>";
echo "<script>";
echo "const formats = ['audio/ogg;codecs=opus', 'audio/ogg', 'audio/webm;codecs=opus', 'audio/webm', 'audio/mp4', 'audio/aac'];";
echo "console.log('USER AGENT:', navigator.userAgent);";
echo "console.log('SOPORTE:');";
echo "formats.forEach(f => console.log('  ' + f + ':', MediaRecorder.isTypeSupported(f) ? 'SÍ' : 'NO'));";
echo "alert('Abre Console (F12) y copia todo lo que dice en console.log()');";
echo "</script>";
echo "<p>Abre F12 → Console y copia TODO lo que aparece arriba.</p>";
echo "</body></html>";
?>
