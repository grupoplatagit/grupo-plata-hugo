-- JP MARKET - Base de datos
-- Importar en phpMyAdmin o con: mysql -u root -p jpmarket_db < database.sql

CREATE DATABASE IF NOT EXISTS jpmarket_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE jpmarket_db;

-- Tabla de administradores
CREATE TABLE IF NOT EXISTS admins (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    nombre     VARCHAR(100) NOT NULL,
    email      VARCHAR(150) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    activo     TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admin por defecto: admin@jpmarket.com / admin123
-- Cambiá la contraseña en producción
INSERT INTO admins (nombre, email, password) VALUES
('Administrador', 'admin@jpmarket.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Tabla de clientes
CREATE TABLE IF NOT EXISTS clientes (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    nombre     VARCHAR(100) NOT NULL,
    email      VARCHAR(150) NOT NULL UNIQUE,
    telefono   VARCHAR(30),
    notas      TEXT,
    activo     TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de leads (potenciales clientes)
CREATE TABLE IF NOT EXISTS leads (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    nombre       VARCHAR(100) NOT NULL,
    email        VARCHAR(150) NOT NULL,
    whatsapp     VARCHAR(30),
    nicho        VARCHAR(100),
    ciudad       VARCHAR(100),
    pais         VARCHAR(100),
    presupuesto  VARCHAR(50),
    objetivo     VARCHAR(100),
    leido        TINYINT(1) DEFAULT 0,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Datos de ejemplo
INSERT INTO clientes (nombre, email, telefono, notas) VALUES
('Juan Pérez',    'juan@example.com',  '+54 9 11 1234-5678', 'Cliente desde el inicio'),
('María García',  'maria@example.com', '+54 9 11 8765-4321', NULL),
('Carlos López',  'carlos@example.com', NULL,                'Interesado en servicios premium');
