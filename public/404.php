<?php
http_response_code(404);
require_once __DIR__ . '/../app/config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Página no encontrada | JP MARKET</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= BASE_URL ?>/public/assets/img/favicon-32x32.png">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --bg: #0a0a0a; --surface: #161616; --border: #2a2a2a;
            --accent: #06b6d4; --text: #d4d4d4; --muted: #6b6b6b;
            --font: 'Inter', sans-serif;
        }
        html, body {
            height: 100%; font-family: var(--font);
            background: var(--bg); color: var(--text);
            overflow: hidden;
        }
        body {
            display: flex; align-items: center; justify-content: center;
            text-align: center; padding: 24px;
            background-image: radial-gradient(rgba(6,182,212,.04) 1px, transparent 1px);
            background-size: 28px 28px;
        }

        /* Orbs */
        .orb {
            position: fixed; border-radius: 50%;
            filter: blur(80px); pointer-events: none; z-index: 0;
        }
        .orb-1 {
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(6,182,212,.18) 0%, transparent 70%);
            top: -120px; left: -100px;
            animation: drift1 14s ease-in-out infinite alternate;
        }
        .orb-2 {
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(6,182,212,.1) 0%, transparent 70%);
            bottom: -100px; right: -80px;
            animation: drift2 11s ease-in-out infinite alternate;
        }
        @keyframes drift1 { from { transform: translate(0,0); } to { transform: translate(40px,-30px); } }
        @keyframes drift2 { from { transform: translate(0,0); } to { transform: translate(-30px,25px); } }

        .content { position: relative; z-index: 1; max-width: 560px; }

        /* 404 big number */
        .num-404 {
            font-size: clamp(7rem, 20vw, 11rem);
            font-weight: 900; line-height: 1;
            letter-spacing: -6px;
            background: linear-gradient(135deg, #00e5ff 0%, #06b6d4 35%, #2563eb 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
            filter: drop-shadow(0 0 40px rgba(6,182,212,.3));
            animation: float 4s ease-in-out infinite;
        }
        @keyframes float {
            0%,100% { transform: translateY(0); }
            50%      { transform: translateY(-12px); }
        }

        .badge {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(6,182,212,.07); border: 1px solid rgba(6,182,212,.2);
            color: #22d3ee; padding: 6px 18px; border-radius: 50px;
            font-size: .75rem; font-weight: 700; letter-spacing: 2px;
            text-transform: uppercase; margin-bottom: 24px;
        }
        .badge::before { content: '●'; font-size: .45rem; animation: blink 2s infinite; }
        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:.3} }

        h1 {
            font-size: clamp(1.4rem, 4vw, 2rem);
            font-weight: 800; color: #fff; margin-bottom: 16px; line-height: 1.2;
        }
        p {
            font-size: 1rem; color: var(--muted); line-height: 1.7;
            margin-bottom: 40px; max-width: 420px; margin-left: auto; margin-right: auto;
        }

        .actions { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }

        .btn-primary {
            display: inline-flex; align-items: center; gap: 8px;
            background: var(--accent); color: #000;
            padding: 13px 28px; border-radius: 50px;
            font-size: .9rem; font-weight: 700; text-decoration: none;
            transition: transform .2s, box-shadow .2s;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(6,182,212,.4);
        }
        .btn-ghost {
            display: inline-flex; align-items: center; gap: 8px;
            border: 1px solid var(--border); color: var(--text);
            padding: 13px 28px; border-radius: 50px;
            font-size: .9rem; font-weight: 600; text-decoration: none;
            transition: border-color .2s, color .2s;
        }
        .btn-ghost:hover { border-color: var(--accent); color: var(--accent); }

        .logo {
            display: block; margin: 0 auto 40px;
            opacity: .6; transition: opacity .2s;
        }
        .logo:hover { opacity: 1; }
        .logo img { height: 40px; }
    </style>
</head>
<body>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>

    <div class="content">
        <a href="<?= BASE_URL ?>" class="logo">
            <img src="<?= BASE_URL ?>/public/assets/img/logo.png" alt="JP MARKET">
        </a>

        <div class="num-404">404</div>

        <div class="badge">Página no encontrada</div>

        <h1>Esta URL no convierte.<br>Pero tu negocio sí puede.</h1>
        <p>La página que buscás no existe o fue movida. Volvé al inicio y descubrí cómo JP MARKET puede hacer crecer tu negocio.</p>

        <div class="actions">
            <a href="<?= BASE_URL ?>" class="btn-primary">&#8592; Volver al inicio</a>
            <a href="<?= BASE_URL ?>/#contacto" class="btn-ghost">Contactarnos</a>
        </div>
    </div>
</body>
</html>
