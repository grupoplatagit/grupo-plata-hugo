<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/functions.php';

if (isLoggedIn()) {
    redirect(ADMIN_URL . '/index.php');
}

$error   = '';
$success = '';

// ── Olvidé mi contraseña ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forgot'])) {
    $db      = getDB();
    $newPass = bin2hex(random_bytes(5)); // 10 chars
    $hash    = password_hash($newPass, PASSWORD_DEFAULT);
    $db->prepare("UPDATE admins SET password=? WHERE activo=1")->execute([$hash]);

    $to      = 'info@jpmarketpro.com';
    $subject = 'JP MARKET - Nueva contraseña temporal';
    $body    = "Tu nueva contraseña temporal es:\n\n   $newPass\n\nIngresá en: " . ADMIN_URL . "/login.php\n\nCambiala desde el panel una vez que ingreses.";
    $headers = "From: no-reply@jpmarketpro.com\r\nContent-Type: text/plain; charset=UTF-8";
    mail($to, $subject, $body, $headers);

    $success = 'Se envió la nueva contraseña a info@jpmarketpro.com';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['forgot'])) {
    $csrfInput = $_POST['csrf_token'] ?? '';
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfInput)) {
        $error = 'Solicitud inválida. Recargá la página.';
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $ip       = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $db       = getDB();

        $stmt = $db->prepare("SELECT attempts, blocked_until FROM login_attempts WHERE ip = ?");
        $stmt->execute([$ip]);
        $row = $stmt->fetch();

        if ($row && $row['blocked_until'] && strtotime($row['blocked_until']) > time()) {
            $error = 'Demasiados intentos fallidos. Esperá 15 minutos.';
        } elseif (empty($email) || empty($password)) {
            $error = 'Completá todos los campos.';
        } elseif (!login($email, $password)) {
            $db->prepare("INSERT INTO login_attempts (ip, attempts) VALUES (?, 1)
                          ON CONFLICT(ip) DO UPDATE SET
                            attempts = attempts + 1,
                            blocked_until = CASE WHEN attempts + 1 >= 5
                                                 THEN datetime('now','localtime','+15 minutes')
                                                 ELSE NULL END")
               ->execute([$ip]);
            $error = 'Credenciales incorrectas.';
        } else {
            $db->prepare("DELETE FROM login_attempts WHERE ip = ?")->execute([$ip]);
            redirect(ADMIN_URL . '/index.php');
        }
    }
} // end POST login
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | JP MARKET Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root { --bg:#0a0a0a; --surface:#161616; --accent:#06b6d4; --text:#d4d4d4; --muted:#6b6b6b; --border:#2a2a2a; --danger:#ef4444; }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--bg); color: var(--text);
            min-height: 100vh; display: grid; place-items: center;
            background-image: radial-gradient(ellipse at 50% 0%, rgba(6,182,212,0.07) 0%, transparent 60%);
        }
        .login-wrap { width: 100%; max-width: 420px; padding: 20px; }
        .login-logo { display: flex; justify-content: center; margin-bottom: 28px; }
        .login-logo img { height: 60px; width: auto; object-fit: contain; }
        .login-card {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 18px; padding: 40px 36px;
            box-shadow: 0 24px 80px rgba(0,0,0,0.4);
        }
        .login-title { font-size: 1.3rem; font-weight: 800; text-align: center; margin-bottom: 4px; }
        .subtitle { text-align: center; color: var(--muted); font-size: .85rem; margin-bottom: 32px; }
        .form-group { margin-bottom: 16px; }
        label { display: block; font-size: .75rem; font-weight: 600; color: var(--muted);
            text-transform: uppercase; letter-spacing: 1px; margin-bottom: 7px; }
        input { width: 100%; background: var(--bg); border: 1px solid var(--border);
            border-radius: 9px; padding: 12px 14px; color: var(--text);
            font-size: .92rem; font-family: inherit; transition: border-color .2s, box-shadow .2s; }
        input:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(6,182,212,0.1); }
        .btn { width: 100%; background: var(--accent); color: #000; border: none;
            border-radius: 9px; padding: 13px; font-weight: 700; font-size: .95rem;
            font-family: inherit; cursor: pointer; transition: all .2s; margin-top: 8px; }
        .btn:hover { background: #22d3ee; box-shadow: 0 0 24px rgba(6,182,212,0.3); }
        .alert { background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.25);
            color: var(--danger); padding: 11px 16px; border-radius: 9px;
            font-size: .875rem; margin-bottom: 20px; }
        .back-link { display: block; text-align: center; margin-top: 20px;
            font-size: .8rem; color: var(--muted); text-decoration: none; transition: color .2s; }
        .back-link:hover { color: var(--text); }
    </style>
</head>
<body>
    <div class="login-wrap">
        <div class="login-logo">
            <img src="<?= BASE_URL ?>/public/assets/img/logo.png" alt="JP MARKET">
        </div>
        <div class="login-card">
            <div class="login-title">Bienvenido de vuelta</div>
            <p class="subtitle">Panel de administración · JP MARKET</p>
            <?php if ($error): ?>
                <div class="alert"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert" style="background:rgba(34,197,94,.1);border-color:rgba(34,197,94,.25);color:#4ade80"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        placeholder="admin@jpmarket.com">
                </div>
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" required placeholder="••••••••">
                </div>
                <button type="submit" class="btn">Ingresar al panel &#8594;</button>
            </form>
        </div>
        <form method="POST" action="" style="text-align:center;margin-top:12px">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <button type="submit" name="forgot" value="1" style="background:none;border:none;color:var(--muted);font-size:.8rem;cursor:pointer;font-family:inherit;transition:color .2s" onmouseover="this.style.color='var(--text)'" onmouseout="this.style.color='var(--muted)'">
                ¿Olvidaste tu contraseña?
            </button>
        </form>
        <a href="<?= BASE_URL ?>" class="back-link">&#8592; Volver al sitio</a>
    </div>
</body>
</html>
