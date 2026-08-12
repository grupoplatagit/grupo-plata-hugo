<?php
echo "<h2>CHECK: FFmpeg</h2>";
echo "<hr>";

// Test 1: exec
echo "<strong>1. Probando exec:</strong><br>";
@exec('ffmpeg -version 2>&1 | head -1', $output1, $return1);
echo "Resultado: " . (empty($output1) ? "VACIO" : $output1[0]) . "<br>";
echo "Return code: $return1<br><br>";

// Test 2: shell_exec
echo "<strong>2. Probando shell_exec:</strong><br>";
$output2 = @shell_exec('ffmpeg -version 2>&1 | head -1');
echo "Resultado: " . (empty($output2) ? "VACIO" : $output2) . "<br><br>";

// Test 3: Disabled functions
echo "<strong>3. Funciones deshabilitadas:</strong><br>";
echo "disable_functions: " . ini_get('disable_functions') . "<br><br>";

// Test 4: Conclusión
if (!empty($output1[0]) || !empty($output2)) {
    echo "<h3>✅ FFmpeg ESTÁ DISPONIBLE</h3>";
} else {
    echo "<h3>❌ FFmpeg NO ESTÁ DISPONIBLE</h3>";
}
?>

