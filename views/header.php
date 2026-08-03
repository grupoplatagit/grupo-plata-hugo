<!DOCTYPE html>
<html lang="es" prefix="og: https://ogp.me/ns#">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Google Search Console -->
    <meta name="google-site-verification" content="PCol5Wu9fdiVBw8Q2GQLghdG38wQyHtKpKmOpgAQXXg">

    <!-- SEO primario -->
    <title><?= SITE_NAME ?> | <?= SITE_SLOGAN ?></title>
    <meta name="description" content="Agencia de marketing digital en La Paternal, Buenos Aires. Especialistas en ROI, gestión de redes sociales, email marketing y campañas digitales. Hacemos crecer tu negocio.">
    <meta name="keywords" content="agencia marketing digital Buenos Aires, marketing digital CABA, gestión redes sociales Argentina, email marketing Argentina, ROI marketing, campañas digitales Buenos Aires, agencia marketing La Paternal, publicidad digital Argentina">
    <meta name="author" content="GRUPO PLATA">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://jpmarketpro.com/">

    <!-- Geo -->
    <meta name="geo.region" content="AR-C">
    <meta name="geo.placename" content="La Paternal, Ciudad Autónoma de Buenos Aires, Argentina">
    <meta name="geo.position" content="-34.6083;-58.4711">
    <meta name="ICBM" content="-34.6083, -58.4711">

    <!-- Open Graph (WhatsApp, Facebook, LinkedIn) -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://jpmarketpro.com/">
    <meta property="og:title" content="GRUPO PLATA | Agencia de Marketing Digital en Buenos Aires">
    <meta property="og:description" content="Hacemos crecer tu negocio con marketing digital de resultados. ROI, redes sociales, email marketing y campañas. La Paternal, CABA.">
    <meta property="og:image" content="https://jpmarketpro.com/public/assets/img/android-chrome-512x512.png">
    <meta property="og:image:width" content="512">
    <meta property="og:image:height" content="512">
    <meta property="og:locale" content="es_AR">
    <meta property="og:site_name" content="GRUPO PLATA">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="GRUPO PLATA | Agencia de Marketing Digital en Buenos Aires">
    <meta name="twitter:description" content="Hacemos crecer tu negocio con marketing digital de resultados. ROI, redes sociales, email marketing. La Paternal, CABA.">
    <meta name="twitter:image" content="https://jpmarketpro.com/public/assets/img/android-chrome-512x512.png">

    <!-- Schema.org: Agencia local -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "MarketingAgency",
        "name": "GRUPO PLATA",
        "alternateName": "JP Market Pro",
        "url": "https://jpmarketpro.com",
        "logo": "https://jpmarketpro.com/public/assets/img/logo.png",
        "image": "https://jpmarketpro.com/public/assets/img/android-chrome-512x512.png",
        "description": "Agencia de marketing digital especializada en ROI, gestión de redes sociales, email marketing y campañas digitales en Buenos Aires, Argentina.",
        "slogan": "Empower your mind",
        "address": {
            "@type": "PostalAddress",
            "streetAddress": "La Paternal",
            "addressLocality": "Ciudad Autónoma de Buenos Aires",
            "addressRegion": "Buenos Aires",
            "addressCountry": "AR"
        },
        "geo": {
            "@type": "GeoCoordinates",
            "latitude": -34.6083,
            "longitude": -58.4711
        },
        "areaServed": {
            "@type": "Country",
            "name": "Argentina"
        },
        "sameAs": [
            "https://www.instagram.com/jpart.ar"
        ],
        "priceRange": "$$",
        "openingHours": "Mo-Fr 09:00-18:00",
        "contactPoint": {
            "@type": "ContactPoint",
            "contactType": "sales",
            "availableLanguage": "Spanish"
        },
        "hasOfferCatalog": {
            "@type": "OfferCatalog",
            "name": "Servicios de Marketing Digital",
            "itemListElement": [
                {"@type": "Offer", "itemOffered": {"@type": "Service", "name": "Marketing Digital"}},
                {"@type": "Offer", "itemOffered": {"@type": "Service", "name": "Gestión de Redes Sociales"}},
                {"@type": "Offer", "itemOffered": {"@type": "Service", "name": "Email Marketing"}},
                {"@type": "Offer", "itemOffered": {"@type": "Service", "name": "Automatización"}},
                {"@type": "Offer", "itemOffered": {"@type": "Service", "name": "Consultoría"}}
            ]
        }
    }
    </script>

    <!-- Favicons -->
    <link rel="apple-touch-icon" sizes="180x180" href="<?= BASE_URL ?>/public/assets/img/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= BASE_URL ?>/public/assets/img/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= BASE_URL ?>/public/assets/img/favicon-16x16.png">
    <link rel="shortcut icon" href="<?= BASE_URL ?>/public/assets/img/favicon.ico">
    <link rel="manifest" href="<?= BASE_URL ?>/public/assets/img/site.webmanifest">

    <!-- Schema.org: Servicios -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "ItemList",
        "name": "Servicios de GRUPO PLATA",
        "itemListElement": [
            {"@type":"ListItem","position":1,"item":{"@type":"Service","name":"Performance Marketing","description":"Campañas orientadas a resultados: leads, ventas y escalabilidad medible desde el primer mes.","provider":{"@type":"Organization","name":"GRUPO PLATA"}}},
            {"@type":"ListItem","position":2,"item":{"@type":"Service","name":"CRM y Automatización","description":"Automatizamos seguimiento, ventas y fidelización para aumentar conversión sin esfuerzo extra.","provider":{"@type":"Organization","name":"GRUPO PLATA"}}},
            {"@type":"ListItem","position":3,"item":{"@type":"Service","name":"Paid Ads","description":"Meta Ads, Google Ads y estrategias de adquisición optimizadas por ROI.","provider":{"@type":"Organization","name":"GRUPO PLATA"}}},
            {"@type":"ListItem","position":4,"item":{"@type":"Service","name":"Desarrollo Web","description":"Sitios modernos y rápidos con panel de administración propio.","provider":{"@type":"Organization","name":"GRUPO PLATA"}}},
            {"@type":"ListItem","position":5,"item":{"@type":"Service","name":"Growth Strategy","description":"Estrategias de crecimiento basadas en datos reales y escalabilidad con dirección.","provider":{"@type":"Organization","name":"GRUPO PLATA"}}},
            {"@type":"ListItem","position":6,"item":{"@type":"Service","name":"Analytics y Optimización","description":"Análisis de comportamiento para mejorar el embudo y bajar el costo por conversión.","provider":{"@type":"Organization","name":"GRUPO PLATA"}}}
        ]
    }
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/main.css">
</head>
<body>

<header class="site-header" id="siteHeader">
    <nav class="navbar">
        <div class="container">
            <a href="#inicio" class="brand" onclick="window.scrollTo({top:0,behavior:'smooth'});return false;">
                <img src="<?= BASE_URL ?>/public/assets/img/logo.png" alt="GRUPO PLATA" class="brand-logo">
            </a>
            <ul class="nav-links" id="navLinks">
                <li><a href="#inicio">Inicio</a></li>
                <li><a href="#nosotros">Nosotros</a></li>
                <li><a href="#fundador">Fundador</a></li>
                <li><a href="#servicios">Servicios</a></li>
                <li><a href="#portfolio">Portfolio</a></li>
                <li><a href="#testimonios">Testimonios</a></li>
                <li><a href="#empecemos" class="nav-cta">Empecemos</a></li>
                <li><a href="<?= ADMIN_URL ?>/login.php" class="nav-admin" title="Panel Admin">&#128274;</a></li>
            </ul>
            <button class="nav-toggle" id="navToggle" aria-label="Abrir menú">&#9776;</button>
        </div>
    </nav>
</header>
