<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/db.php';

$db = getDB();
$message = '';
$error = false;
$token = $_GET['token'] ?? '';

// Validar token
if (!empty($token)) {
    $stmt = $db->prepare("SELECT id, email FROM admins WHERE reset_token = ? AND reset_expires > datetime('now')");
    $stmt->execute([$token]);
    $admin = $stmt->fetch();

    if (!$admin) {
        $error = true;
        $message = '❌ Token inválido o expirado. <a href="' . ADMIN_URL . '/forgot-password.php">Generar uno nuevo</a>';
    }
} else {
    $error = true;
    $message = '❌ Token no proporcionado. <a href="' . ADMIN_URL . '/forgot-password.php">Generar uno</a>';
}

// Procesar cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if (empty($password)) {
        $message = '❌ Ingresa una contraseña';
    } elseif (strlen($password) < 6) {
        $message = '❌ La contraseña debe tener al menos 6 caracteres';
    } elseif ($password !== $password2) {
        $message = '❌ Las contraseñas no coinciden';
    } else {
        // Cambiar contraseña
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare("UPDATE admins SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        $stmt->execute([$hashed, $admin['id']]);

        $error = false;
        $message = '✅ Contraseña cambiada correctamente. <a href="' . ADMIN_URL . '/login.php">Ir al login</a>';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cambiar Contraseña - Grupo Plata</title>
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
        .message a {
            color: inherit;
            text-decoration: underline;
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
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Cambiar Contraseña</h1>
        <p class="subtitle"><?= htmlspecialchars($admin['email'] ?? 'Usuario') ?></p>

        <?php if ($message): ?>
            <div class="message <?= $error ? 'error' : 'success' ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <?php if (!$error && $_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
            <form method="POST">
                <div class="form-group">
                    <label for="password">🔑 Nueva Contraseña</label>
                    <input type="password" id="password" name="password" required placeholder="Mínimo 6 caracteres">
                </div>
                <div class="form-group">
                    <label for="password2">🔁 Confirmar Contraseña</label>
                    <input type="password" id="password2" name="password2" required placeholder="Repetir contraseña">
                </div>
                <button type="submit">Cambiar Contraseña</button>
            </form>
        <?php endif; ?>

        <div class="back-link">
            <a href="<?= ADMIN_URL ?>/login.php">← Volver al Login</a>
        </div>
    </div>
</body>
</html>
