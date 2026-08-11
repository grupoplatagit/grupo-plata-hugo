<?php
function sanitize(string $value): string {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function flashSet(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flashGet(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function formatDate(string $date): string {
    return date('d/m/Y', strtotime($date));
}

function formatDateTime(string $date): string {
    return date('d/m/Y H:i', strtotime($date));
}

function getDolarRate(): array {
    $cacheFile = __DIR__ . '/../database/dolar_cache.json';
    // Usar caché por 30 minutos
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 1800) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if ($cached) return $cached;
    }

    $tipos = ['oficial', 'blue', 'bolsa'];
    $result = [];

    foreach ($tipos as $tipo) {
        $ctx = stream_context_create(['http' => [
            'timeout' => 4,
            'header'  => "User-Agent: JPMarket/1.0\r\n",
        ]]);
        $raw = @file_get_contents("https://dolarapi.com/v1/dolares/{$tipo}", false, $ctx);
        if ($raw) {
            $data = json_decode($raw, true);
            if ($data && isset($data['venta'])) {
                $result[$tipo] = [
                    'compra' => $data['compra'],
                    'venta'  => $data['venta'],
                    'nombre' => $data['nombre'],
                    'fecha'  => $data['fechaActualizacion'] ?? '',
                ];
            }
        }
    }

    if (!empty($result)) {
        file_put_contents($cacheFile, json_encode($result));
    }

    return $result ?: [];
}

function paginate(int $total, int $perPage, int $current): array {
    $pages = (int) ceil($total / $perPage);
    return [
        'total'   => $total,
        'pages'   => $pages,
        'current' => $current,
        'offset'  => ($current - 1) * $perPage,
        'perPage' => $perPage,
    ];
}

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfVerify(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        exit('Solicitud inválida.');
    }
}

function getSetting(PDO $db, string $key): string {
    static $cache = [];
    if (isset($cache[$key])) return $cache[$key];
    $r = $db->prepare("SELECT value FROM settings WHERE key = ?");
    $r->execute([$key]);
    $v = $r->fetchColumn();
    if (($v === false || $v === '') && in_array($key, ['wa_token','wa_phone_id','wa_waba_id']))
        return $cache[$key] = getLocalSetting($key);
    return $cache[$key] = ($v ?? '');
}

// Normaliza teléfonos al formato WhatsApp: solo dígitos, con 9 argentino
// +54 11 5693-5638 → 5491156935638
function normalizeWAPhone(string $phone): string {
    $p = preg_replace('/\D/', '', $phone);
    if (str_starts_with($p, '0')) $p = substr($p, 1);
    // Argentina: 54 + área sin 9 → agregar 9 (ej: 541156935638 → 5491156935638)
    if (preg_match('/^54(?!9)(\d{10})$/', $p, $m)) $p = '549' . $m[1];
    return $p;
}

function sendWATemplate(string $token, string $phoneId, string $to, string $templateName, string $languageCode, array $bodyParams = []): array {
    $to = preg_replace('/\D/', '', $to);
    if (str_starts_with($to, '0')) $to = substr($to, 1);
    $url = "https://graph.facebook.com/v18.0/{$phoneId}/messages";
    $template = ['name' => $templateName, 'language' => ['code' => $languageCode]];
    if (!empty($bodyParams)) {
        $template['components'] = [[
            'type'       => 'body',
            'parameters' => array_map(fn($v) => ['type' => 'text', 'text' => $v], $bodyParams),
        ]];
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode(['messaging_product' => 'whatsapp', 'to' => $to, 'type' => 'template', 'template' => $template]),
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token, 'Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 15,
    ]);
    $resp   = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $json = json_decode($resp, true);
    if ($status === 200 && isset($json['messages'][0]['id'])) {
        return ['ok' => true, 'msg' => 'Plantilla enviada', 'wa_msg_id' => $json['messages'][0]['id']];
    }
    return ['ok' => false, 'msg' => $json['error']['message'] ?? $resp];
}

function sendWAMessage(string $token, string $phoneId, string $to, string $message): array {
    $to = preg_replace('/\D/', '', $to);
    if (str_starts_with($to, '0')) $to = substr($to, 1);
    $url  = "https://graph.facebook.com/v18.0/{$phoneId}/messages";
    $body = json_encode([
        'messaging_product' => 'whatsapp',
        'to'                => $to,
        'type'              => 'text',
        'text'              => ['body' => $message],
    ]);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT => 15,
    ]);
    $resp   = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $json = json_decode($resp, true);
    if ($status === 200 && isset($json['messages'][0]['id'])) {
        return ['ok' => true, 'msg' => 'Mensaje enviado correctamente', 'wa_msg_id' => $json['messages'][0]['id']];
    }
    return ['ok' => false, 'msg' => $json['error']['message'] ?? $resp];
}

function downloadWAMedia(string $mediaId, string $token, PDO $db, string $logDir = ''): array {
    if (!$mediaId || !$token) {
        $msg = 'media_id o token vacío';
        if ($logDir) @file_put_contents("$logDir/whatsapp-media.log", date('Y-m-d H:i:s') . " [ERROR] $msg (mediaId=$mediaId, token=" . (strlen($token) > 0 ? 'OK' : 'VACÍO') . ")\n", FILE_APPEND);
        return ['ok' => false, 'msg' => $msg];
    }

    if ($logDir) @file_put_contents("$logDir/whatsapp-media.log", date('Y-m-d H:i:s') . " [DESCARGA] Iniciando: mediaId=$mediaId, uploadDir=" . (__DIR__ . '/../public_html/uploads/whatsapp') . "\n", FILE_APPEND);

    // PASO 1: Obtener URL de Media desde Meta
    $metaUrl = "https://graph.facebook.com/v18.0/{$mediaId}";
    $ch = curl_init($metaUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token],
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $metaResp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        if ($logDir) @file_put_contents("$logDir/whatsapp-media.log", date('Y-m-d H:i:s') . " [ERROR] Meta API retornó HTTP $httpCode\n", FILE_APPEND);
        return ['ok' => false, 'msg' => "Meta API error: HTTP $httpCode"];
    }

    $metaData = json_decode($metaResp, true);
    if (!$metaData || !isset($metaData['url'])) {
        if ($logDir) @file_put_contents("$logDir/whatsapp-media.log", date('Y-m-d H:i:s') . " [ERROR] Meta no devolvió URL\n", FILE_APPEND);
        return ['ok' => false, 'msg' => 'Meta no devolvió URL de media'];
    }

    $fileUrl = $metaData['url'];
    if ($logDir) @file_put_contents("$logDir/whatsapp-media.log", date('Y-m-d H:i:s') . " [DESCARGA] URL obtenida de Meta\n", FILE_APPEND);

    // PASO 2: Descargar archivo (URL temporal requiere Authorization)
    $ch = curl_init($fileUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token],
    ]);
    $fileContent = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || empty($fileContent)) {
        if ($logDir) @file_put_contents("$logDir/whatsapp-media.log", date('Y-m-d H:i:s') . " [ERROR] Descarga falló: HTTP $httpCode\n", FILE_APPEND);
        return ['ok' => false, 'msg' => "Descarga falló: HTTP $httpCode"];
    }

    // PASO 3: Guardar archivo localmente (DENTRO de public_html para acceso HTTP)
    $uploadDir = MEDIA_UPLOAD_DIR;
    @mkdir($uploadDir, 0755, true);

    $mimeType = $metaData['mime_type'] ?? 'application/octet-stream';
    $ext = extensionFromMime($mimeType);
    $filename = $mediaId . '.' . $ext;
    $filepath = "$uploadDir/$filename";

    if (!file_put_contents($filepath, $fileContent)) {
        $saveError = 'No se pudo guardar archivo en ' . $filepath;
        if ($logDir) @file_put_contents("$logDir/whatsapp-media.log", date('Y-m-d H:i:s') . " [ERROR] $saveError\n", FILE_APPEND);
        return ['ok' => false, 'msg' => $saveError];
    }

    $internalUrl = '/uploads/whatsapp/' . $filename;
    $logMsg = "[OK] Archivo guardado en $filepath (URL: $internalUrl)";
    if ($logDir) @file_put_contents("$logDir/whatsapp-media.log", date('Y-m-d H:i:s') . " " . $logMsg . "\n", FILE_APPEND);

    return ['ok' => true, 'url' => $internalUrl, 'mime_type' => $mimeType];
}

function extensionFromMime(string $mime): string {
    $map = [
        'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp',
        'audio/mpeg' => 'mp3', 'audio/ogg' => 'ogg', 'audio/wav' => 'wav',
        'video/mp4' => 'mp4', 'video/quicktime' => 'mov',
        'application/pdf' => 'pdf', 'application/zip' => 'zip',
    ];
    return $map[$mime] ?? 'bin';
}
