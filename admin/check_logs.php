<?php
echo "<h1>Verificación de Logs</h1>";

echo "<h2>Configuración de error_log:</h2>";
echo "error_log: " . ini_get('error_log') . "<br>";
echo "log_errors: " . (ini_get('log_errors') ? 'On' : 'Off') . "<br>";
echo "error_reporting: " . error_reporting() . "<br>";
echo "display_errors: " . (ini_get('display_errors') ? 'On' : 'Off') . "<br>";

$log_file = ini_get('error_log');
if ($log_file && file_exists($log_file)) {
    echo "<h2>Últimas líneas del log:</h2>";
    $lines = file($log_file);
    $last_lines = array_slice($lines, -20);
    echo "<pre>";
    foreach ($last_lines as $line) {
        echo htmlspecialchars($line);
    }
    echo "</pre>";
} else {
    echo "<h2>No se encontró archivo de log o logging está deshabilitado</h2>";
}

// Intentar escribir en el log
echo "<h2>Prueba de escritura en log:</h2>";
if (error_log("Test log entry: " . date('Y-m-d H:i:s'))) {
    echo "<div style='color: green;'>✅ Se pudo escribir en el log</div>";
} else {
    echo "<div style='color: red;'>❌ No se pudo escribir en el log</div>";
}
?>
