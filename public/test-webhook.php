<?php
// Test if webhook is accessible and working

echo "<h1>Webhook Test</h1>";

// Test 1: Direct access
echo "<h2>Test 1: Direct GET request</h2>";
$url = "https://tan-spider-523924.hostingersite.com/webhook?hub.mode=subscribe&hub.verify_token=test&hub.challenge=12345";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: <strong>$http_code</strong><br>";
echo "Response: <strong>$response</strong><br>";

if ($http_code === 200) {
    echo "✅ Webhook is accessible and working!";
} else {
    echo "❌ Webhook returned error code $http_code";
}

// Test 2: Check logs
echo "<h2>Test 2: Check webhook logs</h2>";
$log_file = __DIR__ . '/../logs/webhook.log';
if (file_exists($log_file)) {
    echo "✅ Log file exists<br>";
    $lines = file($log_file);
    $recent = array_slice($lines, -5);
    echo "<pre>";
    foreach ($recent as $line) {
        echo htmlspecialchars($line);
    }
    echo "</pre>";
} else {
    echo "❌ Log file not found";
}
?>
