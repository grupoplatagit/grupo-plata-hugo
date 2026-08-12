<?php
/**
 * WhatsApp Lead Qualification Bot
 * State machine: inicio → servicio → presencia → presupuesto → nombre → email → completado
 */

function botSendMessage(string $token, string $phoneId, string $to, string $body): void {
    $to   = preg_replace('/\D/', '', $to);
    if (str_starts_with($to, '0')) $to = substr($to, 1);
    $url  = "https://graph.facebook.com/v18.0/{$phoneId}/messages";
    $ch   = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode([
            'messaging_product' => 'whatsapp',
            'to'   => $to,
            'type' => 'text',
            'text' => ['body' => $body],
        ]),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT => 10,
    ]);
    curl_exec($ch);
    curl_close($ch);
}

function getOrCreateBotSession(PDO $db, string $phone): array {
    $stmt = $db->prepare("SELECT * FROM bot_sessions WHERE phone = ?");
    $stmt->execute([$phone]);
    $session = $stmt->fetch();
    if (!$session) {
        $db->prepare("INSERT INTO bot_sessions (phone) VALUES (?)")->execute([$phone]);
        $stmt->execute([$phone]);
        $session = $stmt->fetch();
    }
    return $session;
}

function updateBotSession(PDO $db, string $phone, array $data): void {
    $data['updated_at'] = date('Y-m-d H:i:s');
    $sets   = implode(',', array_map(fn($k) => "$k=?", array_keys($data)));
    $params = array_values($data);
    $params[] = $phone;
    $db->prepare("UPDATE bot_sessions SET $sets WHERE phone=?")->execute($params);
}

function normalizarRespuesta(string $msg): string {
    return mb_strtolower(trim(preg_replace('/\s+/', ' ', $msg)));
}

function matchOpcion(string $msg, array $opciones): ?string {
    $m = normalizarRespuesta($msg);
    foreach ($opciones as $key => $variants) {
        foreach ($variants as $v) {
            if ($m === $v || str_contains($m, $v)) return $key;
        }
    }
    return null;
}

/**
 * Main bot handler. Returns true if bot handled the message.
 */
function handleBotMessage(PDO $db, string $phone, string $message, string $token, string $phoneId): bool {
    // Bot enabled?
    $enabled = $db->query("SELECT value FROM settings WHERE key='wa_bot_enabled'")->fetchColumn();
    if ($enabled !== '1') return false;

    // Don't bot if there's already a real lead with sent/failed WA (already in pipeline)
    $existingLead = $db->prepare("SELECT id FROM leads WHERE whatsapp LIKE ? AND wa_status IN ('sent','failed') LIMIT 1");
    $existingLead->execute(['%' . preg_replace('/\D/', '', $phone) . '%']);
    if ($existingLead->fetch()) return false;

    $session = getOrCreateBotSession($db, $phone);
    $estado  = $session['estado'];

    if ($estado === 'completado') return false;

    $msg = normalizarRespuesta($message);

    // ── INICIO: primera interacción ──────────────────────────────────────────
    if ($estado === 'inicio') {
        updateBotSession($db, $phone, ['estado' => 'servicio']);
        botSendMessage($token, $phoneId, $phone,
            "¡Hola! 👋 Soy el asistente virtual de *JP MARKET*.\n\n" .
            "Estoy acá para ayudarte a encontrar la solución digital perfecta para tu negocio. 🚀\n\n" .
            "¿En qué área querés crecer?\n\n" .
            "1️⃣ Marketing Digital\n" .
            "2️⃣ Desarrollo Web\n" .
            "3️⃣ CRM & Automatización\n" .
            "4️⃣ Otro"
        );
        return true;
    }

    // ── SERVICIO ─────────────────────────────────────────────────────────────
    if ($estado === 'servicio') {
        $opciones = [
            'Marketing Digital'    => ['1','marketing','publicidad','ads','meta','google','redes'],
            'Desarrollo Web'       => ['2','web','sitio','página','desarrollo','wordpress'],
            'CRM & Automatización' => ['3','crm','automatización','automatizar','whatsapp bot','sistema'],
            'Otro'                 => ['4','otro','consulta','información'],
        ];
        $servicio = matchOpcion($msg, $opciones);

        if (!$servicio) {
            botSendMessage($token, $phoneId, $phone,
                "No entendí bien tu respuesta 😅\nElegí una opción:\n\n1️⃣ Marketing Digital\n2️⃣ Desarrollo Web\n3️⃣ CRM & Automatización\n4️⃣ Otro"
            );
            return true;
        }

        updateBotSession($db, $phone, ['estado' => 'presencia', 'servicio' => $servicio]);
        botSendMessage($token, $phoneId, $phone,
            "¡Excelente elección! 🎯\n\n¿Tu negocio tiene presencia digital actualmente?\n(sitio web activo o redes sociales con actividad)\n\n" .
            "1️⃣ Sí, tengo web y/o redes\n" .
            "2️⃣ No todavía"
        );
        return true;
    }

    // ── PRESENCIA ─────────────────────────────────────────────────────────────
    if ($estado === 'presencia') {
        $opciones = [
            'si' => ['1','si','sí','yes','tengo','cuenta','web','redes','instagram','facebook'],
            'no' => ['2','no','not','ninguna','todavía','nada'],
        ];
        $presencia = matchOpcion($msg, $opciones);

        if (!$presencia) {
            botSendMessage($token, $phoneId, $phone,
                "Respondé con *1* (Sí) o *2* (No) 😊"
            );
            return true;
        }

        updateBotSession($db, $phone, ['estado' => 'presupuesto', 'presencia' => $presencia]);
        botSendMessage($token, $phoneId, $phone,
            "Perfecto! 💡 ¿Cuál es tu inversión mensual aproximada en marketing?\n\n" .
            "🅰️ Menos de \$500 USD\n" .
            "🅱️ Entre \$500 y \$2,000 USD\n" .
            "🅲 Más de \$2,000 USD"
        );
        return true;
    }

    // ── PRESUPUESTO ───────────────────────────────────────────────────────────
    if ($estado === 'presupuesto') {
        $opciones = [
            'Menos de $500 USD'         => ['a','1','menos','bajo','poco','500','básico'],
            '$500 - $2,000 USD'         => ['b','2','medio','500','1000','1500','2000'],
            'Más de $2,000 USD'         => ['c','3','más','alto','grande','2000','3000','5000'],
        ];
        $presupuesto = matchOpcion($msg, $opciones);

        if (!$presupuesto) {
            botSendMessage($token, $phoneId, $phone,
                "Elegí una opción:\n\n🅰️ Menos de \$500 USD\n🅱️ \$500 – \$2,000 USD\n🅲 Más de \$2,000 USD"
            );
            return true;
        }

        updateBotSession($db, $phone, ['estado' => 'nombre', 'presupuesto' => $presupuesto]);
        botSendMessage($token, $phoneId, $phone,
            "¡Casi terminamos! 😊\n\n¿Cuál es tu nombre?"
        );
        return true;
    }

    // ── NOMBRE ────────────────────────────────────────────────────────────────
    if ($estado === 'nombre') {
        if (strlen($message) < 2 || strlen($message) > 60) {
            botSendMessage($token, $phoneId, $phone, "¿Cuál es tu nombre? 😊");
            return true;
        }

        $nombre = ucwords(mb_strtolower(trim($message)));
        updateBotSession($db, $phone, ['estado' => 'email', 'nombre' => $nombre]);

        // Auto-agenda el contacto
        try {
            $db->prepare("INSERT INTO wa_contacts (phone, wa_name, updated_at) VALUES (?, ?, datetime('now','localtime'))
                          ON CONFLICT(phone) DO UPDATE SET wa_name=excluded.wa_name, updated_at=excluded.updated_at")
               ->execute([$phone, $nombre]);
        } catch (Exception $e) {
            // Silently fail if contact save fails
        }

        botSendMessage($token, $phoneId, $phone,
            "Un gusto, *{$nombre}*! 🤝\n\n¿Tenés un email de contacto?\n_(Escribí \"no\" para omitir)_"
        );
        return true;
    }

    // ── EMAIL ─────────────────────────────────────────────────────────────────
    if ($estado === 'email') {
        $email = null;
        if ($msg !== 'no' && $msg !== 'nop' && $msg !== 'n' && filter_var(trim($message), FILTER_VALIDATE_EMAIL)) {
            $email = trim($message);
        }

        $session = getOrCreateBotSession($db, $phone);
        updateBotSession($db, $phone, ['estado' => 'completado', 'email' => $email]);

        // Qualify lead
        $presupuesto = $session['presupuesto'];
        $presencia   = $session['presencia'];
        $calificacion = 'frio';
        if ($presupuesto === 'Más de $2,000 USD') {
            $calificacion = 'caliente';
        } elseif ($presupuesto === '$500 - $2,000 USD' || $presencia === 'si') {
            $calificacion = 'tibio';
        }
        updateBotSession($db, $phone, ['calificacion' => $calificacion]);

        // Create lead in DB
        $nombre   = $session['nombre'];
        $servicio = $session['servicio'];
        $stmt = $db->prepare("INSERT INTO leads (nombre, email, whatsapp, nicho, presupuesto, objetivo, leido, wa_status)
                              VALUES (?, ?, ?, ?, ?, ?, 0, 'bot')");
        $stmt->execute([
            $nombre,
            $email ?? '',
            $phone,
            $servicio,
            $presupuesto,
            'Calificado por bot: ' . $calificacion,
        ]);

        // Response based on qualification
        if ($calificacion === 'caliente') {
            botSendMessage($token, $phoneId, $phone,
                "¡Gracias, *{$nombre}*! 🚀\n\n" .
                "Tu perfil es exactamente lo que trabajamos en JP MARKET.\n\n" .
                "Un especialista va a contactarte en las próximas *24 horas* para prepararte una propuesta personalizada. 💪\n\n" .
                "¡Hasta pronto!"
            );
        } elseif ($calificacion === 'tibio') {
            botSendMessage($token, $phoneId, $phone,
                "¡Gracias, *{$nombre}*! 😊\n\n" .
                "Registramos tu consulta sobre *{$servicio}*.\n\n" .
                "Un asesor de JP MARKET se va a comunicar con vos próximamente para ver cómo podemos ayudarte. 🎯"
            );
        } else {
            botSendMessage($token, $phoneId, $phone,
                "¡Gracias por tu interés, *{$nombre}*! 👋\n\n" .
                "Te vamos a enviar información sobre nuestros servicios para que puedas evaluar las opciones cuando sea el momento indicado. 📚\n\n" .
                "¡Cualquier consulta, estamos acá!"
            );
        }

        return true;
    }

    return false;
}
