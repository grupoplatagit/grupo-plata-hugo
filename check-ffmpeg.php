<?php
echo "TEST<br>";
$result = shell_exec('which ffmpeg 2>&1');
echo "Resultado: " . ($result ? $result : "VACIO") . "<br>";
echo "---<br>";
$result2 = shell_exec('ffmpeg -version 2>&1 | head -1');
echo "FFmpeg: " . ($result2 ? $result2 : "NO ENCONTRADO");
?>
