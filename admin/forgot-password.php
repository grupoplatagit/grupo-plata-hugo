<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/db.php';

$db = getDB();
$message = '';
$token = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);

    if (empty($email)) {
        $message = '❌ Ingresa tu email';
    } else {
        $stmt = $db->prepare("SELECT id FROM admins WHERE email = ?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if (!$admin) {
            $message = '❌ Email no encontrado en el sistema';
        } else {
            // Generar token único
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600); // Válido por 1 hora

            $stmt = $db->prepare("UPDATE admins SET reset_token = ?, reset_expires = ? WHERE id = ?");
            $stmt->execute([$token, $expires, $admin['id']]);

            // Enviar email
            $resetLink = BASE_URL . '/admin/reset-password-token.php?token=' . urlencode($token);
            $to = 'juancolasurdo24@gmail.com';
            $subject = 'Reset de Contraseña - Grupo Plata';
            $body = "Se ha solicitado un reset de contraseña para: $email\n\n";
            $body .= "Enlace de reset (válido por 1 hora):\n";
            $body .= "$resetLink\n\n";
            $body .= "Si no solicitaste esto, ignora este email.\n";
            $headers = "From: no-reply@grupoplatasf.com\r\nContent-Type: text/plain; charset=UTF-8";

            mail($to, $subject, $body, $headers);

            $message = '✅ Email enviado a juancolasurdo24@gmail.com';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recuperar Contraseña - Grupo Plata</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 400px;
            width: 100%;
            padding: 40px;
        }
        h1 {
            font-size: 24px;
            margin-bottom: 8px;
            color: #333;
        }
        .subtitle {
            color: #666;
            font-size: 14px;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        input:focus {
            outline: none;
            border-color: #667eea;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        button:hover {
            background: #5568d3;
        }
        .message {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 13px;
        }
        .message.error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        .message.success {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }
        .token-box {
            background: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 12px;
            margin-top: 20px;
            font-size: 12px;
            word-break: break-all;
            color: #333;
            font-family: monospace;
        }
        .reset-link {
            display: block;
            word-break: break-all;
            margin-top: 10px;
            padding: 10px;
            background: #f0f0f0;
            border-radius: 4px;
            color: #333;
            text-decoration: none;
            font-size: 12px;
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Recuperar Contraseña</h1>
        <p class="subtitle">Ingresa tu email para obtener un enlace de reset</p>

        <?php if ($message): ?>
            <div class="message <?= strpos($message, '✅') !== false ? 'success' : 'error' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if (!$message): ?>
            <form method="POST">
                <div class="form-group">
                    <label for="email">📧 Email del usuario</label>
                    <input type="email" id="email" name="email" required placeholder="daniel@grupoplata.com">
                </div>
                <button type="submit">Enviar Link de Reset</button>
            </form>
        <?php else: ?>
            <form method="POST" style="margin-top: 30px;">
                <div class="form-group">
                    <label for="email">📧 Otro email</label>
                    <input type="email" id="email" name="email" placeholder="otro@email.com">
                </div>
                <button type="submit">Enviar Otro Reset</button>
            </form>
        <?php endif; ?>

        <div class="back-link">
            <a href="<?= ADMIN_URL ?>/login.php">← Volver al Login</a>
        </div>
    </div>
</body>
</html>
