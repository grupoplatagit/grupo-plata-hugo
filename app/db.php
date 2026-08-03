<?php
require_once __DIR__ . '/config.php';

// Returns value from local.php (never wiped by deploys) or '' if not set
// Looks first in home dir (~/) then in app/ as fallback
function getLocalSetting(string $key): string {
    static $local = null;
    if ($local === null) {
        $homeFile = dirname(dirname(__DIR__)) . '/local.php'; // ~/local.php
        $appFile  = __DIR__ . '/local.php';                   // app/local.php
        // Try ~/local.php (3 levels up from app/: domains/site/public_html/app)
        $homeFile3 = dirname(dirname(dirname(dirname(__DIR__)))) . '/local.php';
        if (file_exists($homeFile3))        $local = require $homeFile3;
        elseif (file_exists($homeFile))     $local = require $homeFile;
        elseif (file_exists($appFile))      $local = require $appFile;
        else                                $local = [];
    }
    return (string)($local[$key] ?? '');
}

function getDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $isNew = !file_exists(DB_PATH);

    $pdo = new PDO('sqlite:' . DB_PATH, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA journal_mode=WAL; PRAGMA foreign_keys=ON;');

    if ($isNew) {
        _initDB($pdo);
    } else {
        _migrateDB($pdo);
    }

    return $pdo;
}

function _migrateDB(PDO $pdo): void {
    // Ensure core tables exist in case DB file exists but is incomplete
    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
        id         INTEGER PRIMARY KEY AUTOINCREMENT,
        nombre     TEXT NOT NULL,
        email      TEXT NOT NULL UNIQUE,
        password   TEXT NOT NULL,
        activo     INTEGER DEFAULT 1,
        created_at TEXT DEFAULT (datetime('now','localtime'))
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS clientes (
        id         INTEGER PRIMARY KEY AUTOINCREMENT,
        nombre     TEXT NOT NULL,
        empresa    TEXT,
        email      TEXT,
        telefono   TEXT,
        ciudad     TEXT,
        servicio   TEXT,
        monto      REAL DEFAULT 0,
        campana    INTEGER DEFAULT 1,
        notas      TEXT,
        activo     INTEGER DEFAULT 1,
        created_at TEXT DEFAULT (datetime('now','localtime')),
        updated_at TEXT DEFAULT (datetime('now','localtime'))
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS leads (
        id           INTEGER PRIMARY KEY AUTOINCREMENT,
        nombre       TEXT NOT NULL,
        email        TEXT NOT NULL,
        whatsapp     TEXT,
        nicho        TEXT,
        ciudad       TEXT,
        pais         TEXT,
        presupuesto  TEXT,
        objetivo     TEXT,
        leido        INTEGER DEFAULT 0,
        wa_status    TEXT DEFAULT 'pending',
        wa_sent_at   TEXT,
        created_at   TEXT DEFAULT (datetime('now','localtime'))
    )");
    // Ensure default admin exists if table was just created
    if ($pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn() == 0) {
        $initPassword = bin2hex(random_bytes(8));
        $hash = password_hash($initPassword, PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO admins (nombre, email, password) VALUES (?, ?, ?)")
            ->execute(['Juan Pablo', 'admin@jpmarket.com', $hash]);
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) mkdir($logDir, 0755, true);
        file_put_contents($logDir . '/install.log',
            date('Y-m-d H:i:s') . " Admin creado: admin@jpmarket.com / $initPassword\n", FILE_APPEND);
    }

    $cols = array_column($pdo->query("PRAGMA table_info(clientes)")->fetchAll(), 'name');
    if (!in_array('campana', $cols)) {
        $pdo->exec("ALTER TABLE clientes ADD COLUMN campana INTEGER DEFAULT 1");
        $pdo->exec("UPDATE clientes SET campana = 0 WHERE nombre LIKE 'C.E.R.Y.D.E%'");
    }

    $leadCols = array_column($pdo->query("PRAGMA table_info(leads)")->fetchAll(), 'name');
    if (!in_array('wa_status', $leadCols)) {
        $pdo->exec("ALTER TABLE leads ADD COLUMN wa_status TEXT DEFAULT 'pending'");
        $pdo->exec("ALTER TABLE leads ADD COLUMN wa_sent_at TEXT");
    }

    // Bot sessions
    $pdo->exec("CREATE TABLE IF NOT EXISTS bot_sessions (
        id          INTEGER PRIMARY KEY AUTOINCREMENT,
        phone       TEXT NOT NULL UNIQUE,
        estado      TEXT DEFAULT 'inicio',
        servicio    TEXT,
        presencia   TEXT,
        presupuesto TEXT,
        nombre      TEXT,
        email       TEXT,
        calificacion TEXT,
        created_at  TEXT DEFAULT (datetime('now','localtime')),
        updated_at  TEXT DEFAULT (datetime('now','localtime'))
    )");

    // Bot setting
    $tables2 = array_column($pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(), 'name');
    if (in_array('settings', $tables2)) {
        $keys2 = array_column($pdo->query("SELECT key FROM settings")->fetchAll(), 'key');
        if (!in_array('wa_bot_enabled', $keys2))
            $pdo->exec("INSERT INTO settings (key,value) VALUES ('wa_bot_enabled','0')");
    }

    // CRM tables
    $pdo->exec("CREATE TABLE IF NOT EXISTS oportunidades (
        id             INTEGER PRIMARY KEY AUTOINCREMENT,
        titulo         TEXT NOT NULL,
        cliente        TEXT,
        lead_id        INTEGER,
        etapa          TEXT DEFAULT 'nuevo',
        valor          REAL DEFAULT 0,
        moneda         TEXT DEFAULT 'USD',
        probabilidad   INTEGER DEFAULT 0,
        notas          TEXT,
        motivo_perdida TEXT,
        created_at     TEXT DEFAULT (datetime('now','localtime')),
        updated_at     TEXT DEFAULT (datetime('now','localtime'))
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS actividades (
        id               INTEGER PRIMARY KEY AUTOINCREMENT,
        oportunidad_id   INTEGER NOT NULL,
        tipo             TEXT DEFAULT 'nota',
        descripcion      TEXT NOT NULL,
        fecha            TEXT,
        created_at       TEXT DEFAULT (datetime('now','localtime'))
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS tareas (
        id               INTEGER PRIMARY KEY AUTOINCREMENT,
        oportunidad_id   INTEGER,
        titulo           TEXT NOT NULL,
        fecha_limite     TEXT,
        completada       INTEGER DEFAULT 0,
        created_at       TEXT DEFAULT (datetime('now','localtime'))
    )");

    $tables = array_column($pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(), 'name');
    if (!in_array('settings', $tables)) {
        $pdo->exec("CREATE TABLE settings (key TEXT PRIMARY KEY, value TEXT)");
        $pdo->exec("INSERT INTO settings (key,value) VALUES
            ('wa_token',''),
            ('wa_phone_id',''),
            ('wa_auto_enabled','0'),
            ('wa_auto_minutes','10'),
            ('wa_auto_message','Hola {nombre} 👋 Vi que completaste el formulario en nuestra web y me interesó tu proyecto. Soy Juan Pablo de JP MARKET — ¿me contás un poco más sobre tu negocio? Me gustaría ver cómo podemos ayudarte a crecer 🚀'),
            ('wa_webhook_token','jpmarket_webhook_2024')
        ");
    } else {
        // Ensure new settings keys exist in older DBs
        $existingKeys = array_column($pdo->query("SELECT key FROM settings")->fetchAll(), 'key');
        $defaults = [
            'wa_webhook_token' => 'jpmarket_webhook_2024',
            'wa_waba_id'       => '',
            'wa_app_secret'    => '',
            'wa_cron_token'    => bin2hex(random_bytes(16)),
        ];
        foreach ($defaults as $k => $v) {
            if (!in_array($k, $existingKeys)) {
                $pdo->prepare("INSERT INTO settings (key,value) VALUES (?,?)")->execute([$k, $v]);
            }
        }
        // Restore credentials from local.php if they are empty in DB
        $local = file_exists(__DIR__ . '/local.php') ? (require __DIR__ . '/local.php') : [];
        if (!empty($local)) {
            $getStmt = $pdo->prepare("SELECT value FROM settings WHERE key=?");
            $updStmt = $pdo->prepare("UPDATE settings SET value=? WHERE key=?");
            foreach (['wa_token','wa_phone_id','wa_waba_id'] as $k) {
                if (!empty($local[$k])) {
                    $getStmt->execute([$k]);
                    $cur = $getStmt->fetchColumn();
                    if (empty($cur)) $updStmt->execute([$local[$k], $k]);
                }
            }
        }
    }
    if (!in_array('wa_contacts', $tables)) {
        $pdo->exec("CREATE TABLE wa_contacts (
            phone      TEXT PRIMARY KEY,
            lead_id    INTEGER,
            wa_name    TEXT,
            label      TEXT DEFAULT 'nuevo',
            notes      TEXT,
            updated_at TEXT DEFAULT (datetime('now','localtime'))
        )");
    } else {
        $waCols = array_column($pdo->query("PRAGMA table_info(wa_contacts)")->fetchAll(), 'name');
        if (!in_array('wa_name', $waCols)) {
            $pdo->exec("ALTER TABLE wa_contacts ADD COLUMN wa_name TEXT");
        }
    }
    if (!in_array('wa_messages', $tables)) {
        $pdo->exec("CREATE TABLE wa_messages (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            lead_id    INTEGER,
            from_phone TEXT,
            wa_msg_id  TEXT UNIQUE,
            direction  TEXT CHECK(direction IN ('in','out')),
            body       TEXT NOT NULL,
            leido      INTEGER DEFAULT 0,
            wa_status  TEXT,
            created_at TEXT DEFAULT (datetime('now','localtime'))
        )");
    } else {
        $waMsgCols = array_column($pdo->query("PRAGMA table_info(wa_messages)")->fetchAll(), 'name');
        if (!in_array('wa_status', $waMsgCols)) {
            $pdo->exec("ALTER TABLE wa_messages ADD COLUMN wa_status TEXT");
        }
    }
    $pdo->exec("CREATE TABLE IF NOT EXISTS login_attempts (
        ip            TEXT PRIMARY KEY,
        attempts      INTEGER DEFAULT 0,
        blocked_until TEXT
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS prospectos (
        id              INTEGER PRIMARY KEY AUTOINCREMENT,
        nombre          TEXT NOT NULL,
        zona            TEXT,
        telefono        TEXT,
        web             TEXT,
        instagram       TEXT,
        direccion       TEXT,
        categoria       TEXT DEFAULT 'SIN WEB ★★★',
        estado          TEXT DEFAULT 'nuevo',
        notas           TEXT,
        secuencia_dia   INTEGER DEFAULT 0,
        ultimo_contacto TEXT,
        created_at      TEXT DEFAULT (datetime('now','localtime')),
        updated_at      TEXT DEFAULT (datetime('now','localtime'))
    )");
    // Migracion: agregar columnas si no existen
    $prCols = array_column($pdo->query("PRAGMA table_info(prospectos)")->fetchAll(), 'name');
    if (!in_array('instagram', $prCols)) $pdo->exec("ALTER TABLE prospectos ADD COLUMN instagram TEXT");
    if (!in_array('direccion', $prCols)) $pdo->exec("ALTER TABLE prospectos ADD COLUMN direccion TEXT");
    $pdo->exec("CREATE TABLE IF NOT EXISTS propuestas (
        id              INTEGER PRIMARY KEY AUTOINCREMENT,
        codigo          TEXT NOT NULL UNIQUE,
        clave           TEXT NOT NULL,
        cliente_nombre  TEXT NOT NULL,
        cliente_empresa TEXT NOT NULL,
        lead_id         INTEGER,
        estado          TEXT DEFAULT 'borrador',
        viewed_at       TEXT,
        notas           TEXT,
        created_at      TEXT DEFAULT (datetime('now','localtime')),
        updated_at      TEXT DEFAULT (datetime('now','localtime'))
    )");
}

function _initDB(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE admins (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre     TEXT NOT NULL,
            email      TEXT NOT NULL UNIQUE,
            password   TEXT NOT NULL,
            activo     INTEGER DEFAULT 1,
            created_at TEXT DEFAULT (datetime('now','localtime'))
        );

        CREATE TABLE clientes (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre     TEXT NOT NULL,
            empresa    TEXT,
            email      TEXT,
            telefono   TEXT,
            ciudad     TEXT,
            servicio   TEXT,
            monto      REAL DEFAULT 0,
            campana    INTEGER DEFAULT 1,
            notas      TEXT,
            activo     INTEGER DEFAULT 1,
            created_at TEXT DEFAULT (datetime('now','localtime')),
            updated_at TEXT DEFAULT (datetime('now','localtime'))
        );

        CREATE TABLE leads (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre       TEXT NOT NULL,
            email        TEXT NOT NULL,
            whatsapp     TEXT,
            nicho        TEXT,
            ciudad       TEXT,
            pais         TEXT,
            presupuesto  TEXT,
            objetivo     TEXT,
            leido        INTEGER DEFAULT 0,
            wa_status    TEXT DEFAULT 'pending',
            wa_sent_at   TEXT,
            created_at   TEXT DEFAULT (datetime('now','localtime'))
        );

        CREATE TABLE settings (
            key   TEXT PRIMARY KEY,
            value TEXT
        );

        CREATE TABLE wa_contacts (
            phone      TEXT PRIMARY KEY,
            lead_id    INTEGER,
            wa_name    TEXT,
            label      TEXT DEFAULT 'nuevo',
            notes      TEXT,
            updated_at TEXT DEFAULT (datetime('now','localtime'))
        );

        CREATE TABLE wa_messages (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            lead_id    INTEGER,
            from_phone TEXT,
            wa_msg_id  TEXT UNIQUE,
            direction  TEXT CHECK(direction IN ('in','out')),
            body       TEXT NOT NULL,
            leido      INTEGER DEFAULT 0,
            created_at TEXT DEFAULT (datetime('now','localtime'))
        );
    ");

    // Load local overrides (file not tracked by git, persists across deploys)
    $local = file_exists(__DIR__ . '/local.php') ? (require __DIR__ . '/local.php') : [];

    $defaults = [
        'wa_token'        => $local['wa_token']         ?? '',
        'wa_phone_id'     => $local['wa_phone_id']      ?? '',
        'wa_waba_id'      => $local['wa_waba_id']       ?? '',
        'wa_auto_enabled' => $local['wa_auto_enabled']  ?? '0',
        'wa_auto_minutes' => $local['wa_auto_minutes']  ?? '10',
        'wa_auto_message' => $local['wa_auto_message']  ?? 'Hola {nombre} 👋 Vi que completaste el formulario en nuestra web y me interesó tu proyecto. Soy Juan Pablo de JP MARKET — ¿me contás un poco más sobre tu negocio? Me gustaría ver cómo podemos ayudarte a crecer 🚀',
        'wa_webhook_token'=> $local['wa_webhook_token'] ?? 'jpmarket_webhook_2024',
        'wa_app_secret'   => $local['wa_app_secret']    ?? '',
        'wa_cron_token'   => $local['wa_cron_token']    ?? bin2hex(random_bytes(16)),
    ];
    $ins = $pdo->prepare("INSERT INTO settings (key,value) VALUES (?,?)");
    foreach ($defaults as $k => $v) $ins->execute([$k, $v]);

    $pdo->exec("CREATE TABLE IF NOT EXISTS login_attempts (
        ip            TEXT PRIMARY KEY,
        attempts      INTEGER DEFAULT 0,
        blocked_until TEXT
    )");

    $initPassword = bin2hex(random_bytes(8));
    $hash = password_hash($initPassword, PASSWORD_DEFAULT);
    $pdo->prepare("INSERT INTO admins (nombre, email, password) VALUES (?, ?, ?)")
        ->execute(['Juan Pablo', 'admin@jpmarket.com', $hash]);
    $initLogDir = __DIR__ . '/../logs';
    if (!is_dir($initLogDir)) mkdir($initLogDir, 0755, true);
    file_put_contents($initLogDir . '/install.log',
        date('Y-m-d H:i:s') . " Admin creado: admin@jpmarket.com / $initPassword\n", FILE_APPEND);

    // nombre, empresa, email, telefono, ciudad, servicio, monto, campana, notas
    $clientes = [
        ['Hugo',   'Grupo Plata',      'hugo@grupoplata.com',   '+54 342 411-0001', 'Santa Fe',            'Marketing Digital',         400000, 1, 'Cliente activo'],
        ['Roxana', 'Grupo Plata',      'roxana@grupoplata.com', '+54 342 411-0002', 'Santa Fe',            'Marketing Digital',         400000, 1, 'Cliente activo'],
        ['Elias',  'Soy Bonanza',      'elias@soybonanza.com',  '+54 342 422-0010', 'Santa Fe',            'Marketing Digital',         380000, 1, 'Cliente activo'],
        ['Daniel', 'Grupo Azul Plata', 'daniel@azulplata.com',  '+54 342 433-0020', 'Santa Fe',            'Marketing Digital',         400000, 1, 'Cliente activo'],
        ['Juanjo', 'C.E.R.Y.D.E',     'info@cerydeclub.com',   '+54 11 4201-3300', 'Avellaneda, Bs. As.', 'Gestión de Redes Sociales',      0, 0, 'Sin inversión en campaña'],
    ];
    $ins = $pdo->prepare("INSERT INTO clientes (nombre, empresa, email, telefono, ciudad, servicio, monto, campana, notas) VALUES (?,?,?,?,?,?,?,?,?)");
    foreach ($clientes as $c) $ins->execute($c);
}
