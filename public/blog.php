<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/functions.php';

$pageTitle = 'Blog — Tendencias de Marketing Digital';

// ── RSS sources (todas en español) ───────────────────────────────────────────
$sources = [
    ['name' => 'HubSpot ES',       'color' => '#ff7a59', 'url' => 'https://blog.hubspot.com/es/marketing/rss.xml'],
    ['name' => 'M4eCommerce',      'color' => '#22c55e', 'url' => 'https://marketing4ecommerce.net/feed/'],
    ['name' => 'Puro Marketing',   'color' => '#06b6d4', 'url' => 'https://www.puromarketing.com/rss.xml'],
    ['name' => 'Mkt Directo',      'color' => '#7c3aed', 'url' => 'https://www.marketingdirecto.com/feed'],
    ['name' => 'Rock Content ES',  'color' => '#f59e0b', 'url' => 'https://rockcontent.com/es/blog/feed/'],
];

// ── Fetch with file cache ─────────────────────────────────────────────────────
$cacheFile = __DIR__ . '/../database/rss_cache.json';
$cacheTtl  = 4 * 3600;

function fetchRSSFeeds(array $sources, string $cacheFile, int $ttl): array {
    if (file_exists($cacheFile)) {
        $c = json_decode(file_get_contents($cacheFile), true);
        if ($c && isset($c['ts']) && (time() - $c['ts']) < $ttl && !empty($c['items'])) {
            return $c['items'];
        }
        $fallback = $c['items'] ?? [];
    } else {
        $fallback = [];
    }

    $items = [];
    foreach ($sources as $src) {
        try {
            $ctx = stream_context_create(['http' => [
                'timeout'    => 6,
                'user_agent' => 'Mozilla/5.0 (compatible; JPMarket/1.0)',
            ]]);
            $raw = @file_get_contents($src['url'], false, $ctx);
            if (!$raw) continue;
            $xml = @simplexml_load_string($raw);
            if (!$xml) continue;

            $channel = $xml->channel ?? $xml;
            $count   = 0;

            foreach ($channel->item as $item) {
                if ($count >= 5) break;

                // Extract image
                $image = '';
                $ns = $item->getNameSpaces(true);
                if (isset($ns['media'])) {
                    $m = $item->children($ns['media']);
                    if (!empty($m->content)) {
                        $a = $m->content->attributes();
                        $image = (string)($a['url'] ?? '');
                    }
                }
                if (!$image && !empty($item->enclosure)) {
                    $a = $item->enclosure->attributes();
                    $image = (string)($a['url'] ?? '');
                }
                // Try to grab first img from description
                if (!$image) {
                    preg_match('/<img[^>]+src=["\']([^"\']+)["\']/', (string)$item->description, $m2);
                    $image = $m2[1] ?? '';
                }

                $desc = strip_tags(html_entity_decode((string)$item->description, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                $desc = preg_replace('/\s+/', ' ', trim($desc));
                if (mb_strlen($desc) > 160) $desc = mb_substr($desc, 0, 157) . '…';

                $items[] = [
                    'title'  => html_entity_decode(strip_tags((string)$item->title), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                    'link'   => (string)$item->link,
                    'date'   => (string)$item->pubDate,
                    'ts'     => strtotime((string)$item->pubDate) ?: 0,
                    'desc'   => $desc,
                    'image'  => $image,
                    'source' => $src['name'],
                    'color'  => $src['color'],
                ];
                $count++;
            }
        } catch (Throwable $e) { /* skip */ }
    }

    if (empty($items)) return $fallback;

    usort($items, fn($a, $b) => $b['ts'] - $a['ts']);
    @file_put_contents($cacheFile, json_encode(['ts' => time(), 'items' => $items]));
    return $items;
}

$articles = fetchRSSFeeds($sources, $cacheFile, $cacheTtl);

function timeAgo(string $dateStr): string {
    $ts   = strtotime($dateStr);
    if (!$ts) return '';
    $diff = time() - $ts;
    if ($diff < 3600)   return 'Hace ' . round($diff/60) . ' min';
    if ($diff < 86400)  return 'Hace ' . round($diff/3600) . ' h';
    if ($diff < 604800) return 'Hace ' . round($diff/86400) . ' días';
    return date('d/m/Y', $ts);
}

include __DIR__ . '/../views/header.php';
?>

<style>
.blog-hero {
    padding: 140px 20px 80px;
    text-align: center;
    position: relative;
    overflow: clip;
    background-image: radial-gradient(rgba(6,182,212,.04) 1px, transparent 1px);
    background-size: 28px 28px;
}
.blog-hero::before {
    content: '';
    position: absolute; top: -100px; left: 50%; transform: translateX(-50%);
    width: 600px; height: 600px;
    background: radial-gradient(circle, rgba(6,182,212,.12) 0%, transparent 65%);
    pointer-events: none;
}
.blog-hero-inner { position: relative; z-index: 1; max-width: 640px; margin: 0 auto; }

.blog-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
    padding: 0 0 80px;
}
@media(max-width:1024px) { .blog-grid { grid-template-columns: repeat(2,1fr); } }
@media(max-width:640px)  { .blog-grid { grid-template-columns: 1fr; } }

/* Featured first article */
.blog-grid .article-card:first-child {
    grid-column: span 2;
}
@media(max-width:1024px) { .blog-grid .article-card:first-child { grid-column: span 2; } }
@media(max-width:640px)  { .blog-grid .article-card:first-child { grid-column: span 1; } }

.article-card {
    background: #111;
    border: 1px solid rgba(255,255,255,.07);
    border-radius: 16px; overflow: hidden;
    display: flex; flex-direction: column;
    transition: transform .3s, box-shadow .3s, border-color .3s;
    text-decoration: none; color: inherit;
}
.article-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 20px 50px rgba(0,0,0,.4);
    border-color: rgba(6,182,212,.25);
}

.article-img {
    width: 100%; height: 200px;
    object-fit: cover; display: block;
    background: #1a1a1a;
    transition: transform .5s;
}
.article-card:first-child .article-img { height: 260px; }
.article-card:hover .article-img { transform: scale(1.04); }

.article-img-wrap { overflow: hidden; flex-shrink: 0; }

.article-img-placeholder {
    width: 100%; height: 200px;
    background: linear-gradient(135deg, #111 0%, #1a1a1a 100%);
    display: flex; align-items: center; justify-content: center;
    font-size: 2.5rem; flex-shrink: 0;
}
.article-card:first-child .article-img-placeholder { height: 260px; }

.article-body { padding: 20px 22px 24px; display: flex; flex-direction: column; flex: 1; }

.article-meta {
    display: flex; align-items: center; gap: 10px;
    margin-bottom: 12px; flex-wrap: wrap;
}
.article-source {
    font-size: .68rem; font-weight: 800; letter-spacing: .08em;
    text-transform: uppercase; padding: 3px 10px;
    border-radius: 50px; border: 1px solid;
    opacity: .9;
}
.article-time {
    font-size: .72rem; color: #555;
}

.article-title {
    font-size: .98rem; font-weight: 700; color: #fff;
    line-height: 1.4; margin-bottom: 10px;
}
.article-card:first-child .article-title { font-size: 1.2rem; }

.article-desc {
    font-size: .82rem; color: #666;
    line-height: 1.6; flex: 1; margin-bottom: 16px;
}

.article-cta {
    font-size: .8rem; font-weight: 700;
    color: var(--accent); display: inline-flex; align-items: center; gap: 4px;
    margin-top: auto;
}
.article-card:hover .article-cta { gap: 8px; transition: gap .2s; }

.blog-sources {
    text-align: center; padding: 0 0 60px;
    font-size: .78rem; color: #444;
}
.blog-sources strong { color: #666; }

.no-articles {
    text-align: center; padding: 80px 20px; color: #555;
}
.no-articles .icon { font-size: 3rem; margin-bottom: 16px; }
</style>

<!-- Hero -->
<section class="blog-hero">
    <div class="blog-hero-inner">
        <span class="section-tag">Recursos & Tendencias</span>
        <h1 class="section-title" style="margin-bottom:16px">
            Blog de <span>Marketing Digital</span>
        </h1>
        <p style="color:#6b6b6b;font-size:1rem;line-height:1.7;max-width:480px;margin:0 auto">
            Las últimas noticias, estrategias y tendencias del mundo del marketing digital, curadas de las mejores fuentes globales.
        </p>
    </div>
</section>

<!-- Articles -->
<div class="container">
    <?php if (empty($articles)): ?>
    <div class="no-articles">
        <div class="icon">📡</div>
        <p>Cargando artículos... Volvé en unos minutos.</p>
    </div>
    <?php else: ?>
    <div class="blog-grid">
        <?php foreach ($articles as $a): ?>
        <a href="<?= htmlspecialchars($a['link']) ?>" target="_blank" rel="noopener noreferrer" class="article-card">

            <?php if (!empty($a['image'])): ?>
            <div class="article-img-wrap">
                <img src="<?= htmlspecialchars($a['image']) ?>" alt="" class="article-img" loading="lazy"
                     onerror="this.parentElement.innerHTML='<div class=\'article-img-placeholder\'>📰</div>'">
            </div>
            <?php else: ?>
            <div class="article-img-placeholder">📰</div>
            <?php endif; ?>

            <div class="article-body">
                <div class="article-meta">
                    <span class="article-source"
                          style="color:<?= htmlspecialchars($a['color']) ?>;border-color:<?= htmlspecialchars($a['color']) ?>40;background:<?= htmlspecialchars($a['color']) ?>12">
                        <?= htmlspecialchars($a['source']) ?>
                    </span>
                    <?php if (!empty($a['date'])): ?>
                    <span class="article-time"><?= timeAgo($a['date']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="article-title"><?= htmlspecialchars($a['title']) ?></div>

                <?php if (!empty($a['desc'])): ?>
                <div class="article-desc"><?= htmlspecialchars($a['desc']) ?></div>
                <?php endif; ?>

                <span class="article-cta">Leer artículo <span>&#8594;</span></span>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <div class="blog-sources">
        Contenido curado de <strong>HubSpot ES · Marketing4eCommerce · Puro Marketing · Marketing Directo · Rock Content</strong>
        · Actualizado cada 4 horas
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../views/footer.php'; ?>
