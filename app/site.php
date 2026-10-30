<?php
declare(strict_types=1);

require_once __DIR__ . '/legal.php';
require_once __DIR__ . '/push.php';

function site_dispatch(?string $page, ?string $sub, int $depth): void
{
    if ($page === 'news' && $depth === 2) {
        page_news_detail((string) $sub);
        return;
    }
    if ($page === null || $depth > 1) {
        page_404();
        return;
    }
    ('page_' . $page)();
}

function base_url(): string
{
    $scheme = !empty($_SERVER['HTTPS']) ? 'https' : 'http';
    return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'www.albergoalfogolar.it');
}

function maps_url(): string
{
    return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode('Albergo Ristorante Al Fogolar, ' . setting('address') . ', Villa Santina');
}

function status_text(): array
{
    [$state, $key, $time, $note] = live_status();
    return [$state, sprintf(t($key), $time), $note];
}

function json_ld(): string
{
    $days = [1 => 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    $spec = [];
    foreach (hours() as $d => $slots) {
        foreach ($slots as [$from, $to]) {
            $spec[] = ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => $days[(int) $d], 'opens' => $from, 'closes' => $to];
        }
    }
    $base = base_url();
    $address = [
        '@type' => 'PostalAddress', 'streetAddress' => setting('address'), 'addressLocality' => 'Villa Santina',
        'postalCode' => '33029', 'addressRegion' => 'UD', 'addressCountry' => 'IT',
    ];
    $common = [
        'name' => 'Albergo Ristorante Al Fogolar', 'url' => $base . '/', 'telephone' => clean_phone(setting('phone')),
        'email' => setting('email'), 'address' => $address, 'image' => $base . '/assets/fire/og.jpg',
        'sameAs' => array_values(array_filter([setting('facebook'), setting('tripadvisor')])),
    ];
    $graph = [
        ['@type' => 'Hotel', '@id' => "$base/#hotel", 'numberOfRooms' => 8, 'petsAllowed' => true,
            'amenityFeature' => [
                ['@type' => 'LocationFeatureSpecification', 'name' => 'Wifi gratuito', 'value' => true],
                ['@type' => 'LocationFeatureSpecification', 'name' => 'Parcheggio', 'value' => true],
                ['@type' => 'LocationFeatureSpecification', 'name' => 'Garage moto', 'value' => true],
            ]] + $common,
        ['@type' => 'Restaurant', '@id' => "$base/#restaurant", 'servesCuisine' => ['Cucina carnica', 'Cucina friulana', 'Cucina italiana'],
            'openingHoursSpecification' => $spec, 'menu' => $base . url('ristorante', 'it')] + $common,
    ];
    return json_encode(['@context' => 'https://schema.org', '@graph' => $graph], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
}

function layout(string $page, string $title, string $desc, string $body, string $tail = '', bool $noindex = false, string $ogImage = 'assets/fire/og.jpg'): void
{
    $base = base_url();
    $canonical = $base . url($page, null, $tail);
    $phone = clean_phone(setting('phone'));
    $nav = ['ristorante' => 'nav_rest', 'albergo' => 'nav_hotel', 'appartamenti' => 'nav_apt', 'news' => 'nav_news', 'contatti' => 'nav_contact'];
    [$state, $statusLabel] = status_text();
    ?>
<!doctype html>
<html lang="<?= lang() ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e($desc) ?>">
<meta name="theme-color" content="#14100c">
<?php if ($noindex): ?><meta name="robots" content="noindex"><?php endif; ?>
<link rel="canonical" href="<?= e($canonical) ?>">
<?php if ($page !== 'news' || $tail === ''): foreach (LANGS as $l): ?>
<link rel="alternate" hreflang="<?= $l ?>" href="<?= e($base . url($page, $l, $tail)) ?>">
<?php endforeach; ?>
<link rel="alternate" hreflang="x-default" href="<?= e($base . url($page, 'it', $tail)) ?>">
<?php endif; ?>
<meta property="og:type" content="website">
<meta property="og:title" content="<?= e($title) ?>">
<meta property="og:description" content="<?= e($desc) ?>">
<meta property="og:url" content="<?= e($canonical) ?>">
<meta property="og:image" content="<?= e($base . '/' . $ogImage) ?>">
<meta property="og:locale" content="<?= ['it' => 'it_IT', 'en' => 'en_GB', 'de' => 'de_DE'][lang()] ?>">
<meta name="twitter:card" content="summary_large_image">
<link rel="icon" href="/assets/favicon.svg" type="image/svg+xml">
<link rel="preload" href="/assets/fonts/Fraunces.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="/assets/site.css?v=<?= filemtime(ROOT . '/public/assets/site.css') ?>">
<script type="application/ld+json"><?= json_ld() ?></script>
<script src="/assets/site.js?v=<?= filemtime(ROOT . '/public/assets/site.js') ?>" defer></script>
</head>
<body class="page-<?= e($page) ?>">
<a class="skip" href="#main"><?= e(t('skip')) ?></a>
<header class="top<?= $page === 'home' ? ' top--over' : '' ?>">
  <a class="top__logo" href="<?= e(url('home')) ?>"><img src="/assets/img/logo.webp" width="600" height="241" alt="Albergo Ristorante Bar Al Fogolar"></a>
  <nav class="top__nav" id="nav" aria-label="Menu">
    <?php foreach ($nav as $p => $k): ?>
      <a href="<?= e(url($p)) ?>"<?= $p === $page ? ' aria-current="page"' : '' ?>><?= e(t($k)) ?></a>
    <?php endforeach; ?>
    <div class="langs">
      <?php foreach (LANGS as $l): ?>
        <a href="<?= e(url($page === 'news' && $tail !== '' ? 'news' : $page, $l)) ?>" hreflang="<?= $l ?>" lang="<?= $l ?>"<?= $l === lang() ? ' aria-current="true"' : '' ?>><?= strtoupper($l) ?></a>
      <?php endforeach; ?>
    </div>
    <a class="btn btn--ember top__cta" href="<?= e(url('richiesta')) ?>"><?= e(t('cta_ask')) ?></a>
  </nav>
  <button class="burger" type="button" aria-expanded="false" aria-controls="nav" aria-label="<?= e(t('nav_menu')) ?>"><span></span><span></span></button>
</header>
<main id="main"><?= $body ?></main>
<footer class="foot">
  <div class="wrap foot__grid">
    <div>
      <img class="foot__logo" src="/assets/img/logo.webp" width="600" height="241" alt="" loading="lazy">
      <p><?= e(t('footer_family')) ?></p>
    </div>
    <address>
      <p><a href="<?= e(maps_url()) ?>" rel="noopener"><?= e(setting('address')) ?><br><?= e(setting('city')) ?></a></p>
      <p><a href="tel:<?= e($phone) ?>"><?= e(setting('phone')) ?></a><br><a href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a></p>
    </address>
    <div>
      <?= hours_table(true) ?>
    </div>
  </div>
  <div class="wrap foot__legal">
    <span><?= e(t('footer_vat')) ?> <?= e(setting('vat')) ?></span>
    <a href="<?= e(url('privacy')) ?>"><?= e(t('privacy')) ?></a>
    <a href="<?= e(url('cookie')) ?>"><?= e(t('cookie')) ?></a>
    <?php if (setting('facebook')): ?><a href="<?= e(setting('facebook')) ?>" rel="noopener"><?= e(t('follow')) ?></a><?php endif; ?>
    <span>© <?= date('Y') ?> Al Fogolar · JDev</span>
  </div>
</footer>
<div class="bar" role="navigation" aria-label="<?= e(t('cta_ask')) ?>">
  <a href="tel:<?= e($phone) ?>"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M6.6 3.5 9 3l1.8 4.3-2 1.6a12 12 0 0 0 6.300 6.300l1.600-2L21 15l-.5 2.400c-.3 1.300-1.500 2.200-2.800 2.100C10.200 19 5 13.800 4.500 6.300c-.1-1.300.8-2.500 2.100-2.800Z"/></svg><?= e(t('cta_call')) ?></a>
  <a href="<?= e(maps_url()) ?>" rel="noopener"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 21s7-6.300 7-11.500A7 7 0 0 0 5 9.500C5 14.700 12 21 12 21Z"/><circle cx="12" cy="9.500" r="2.400"/></svg><?= e(t('cta_map')) ?></a>
  <a class="bar__main" href="<?= e(url('richiesta')) ?>"><?= e(t('cta_ask_short')) ?></a>
</div>
</body>
</html>
    <?php
}

function hours_table(bool $compact = false): string
{
    $h = hours();
    $today = (int) date('N');
    $out = '<table class="hours' . ($compact ? ' hours--compact' : '') . '"><caption>' . e(t('hours_h')) . '</caption><tbody>';
    if ($compact) {
        // Raggruppa i giorni uguali: "Tutti i giorni tranne…" resta leggibile anche in tre lingue
        $groups = [];
        foreach (range(1, 7) as $d) {
            $key = json_encode($h[$d] ?? []);
            $last = array_key_last($groups);
            if ($last !== null && $groups[$last]['key'] === $key) {
                $groups[$last]['to'] = $d;
            } else {
                $groups[] = ['key' => $key, 'from' => $d, 'to' => $d, 'slots' => $h[$d] ?? []];
            }
        }
        foreach ($groups as $g) {
            $label = t('d' . $g['from']) . ($g['to'] !== $g['from'] ? '–' . t('d' . $g['to']) : '');
            $out .= '<tr><th scope="row">' . e($label) . '</th><td>' . slots_text($g['slots']) . '</td></tr>';
        }
    } else {
        foreach (range(1, 7) as $d) {
            $out .= '<tr' . ($d === $today ? ' class="is-today"' : '') . '><th scope="row">' . e(t("d$d")) . '</th><td>' . slots_text($h[$d] ?? []) . '</td></tr>';
        }
    }
    return $out . '</tbody></table>';
}

function slots_text(array $slots): string
{
    if (!$slots) {
        return '<span class="hours__closed">' . e(t('closed')) . '</span>';
    }
    return e(implode(' · ', array_map(fn($s) => $s[0] . '–' . $s[1], $slots)));
}

function status_pill(): string
{
    [$state, $label, $note] = status_text();
    return '<p class="pill pill--' . e($state) . '"><i></i>' . e($label) . ($note !== '' ? ' · ' . e($note) : '') . '</p>';
}

function gallery_strip(string $cat): string
{
    $rows = q('SELECT * FROM gallery WHERE cat = ? ORDER BY sort, id', [$cat])->fetchAll();
    if (!$rows) {
        return '';
    }
    $out = '<div class="strip" tabindex="0" role="group">';
    foreach ($rows as $r) {
        $out .= '<figure>' . pic($r['stem'], $r['alt'], '(min-width:900px) 34vw, 82vw') . '</figure>';
    }
    return $out . '</div>';
}

function subhead(string $h1, string $lead, string $photo, string $alt): string
{
    return '<section class="sub">' . pic("assets/img/$photo", $alt, '100vw', true, 'sub__img')
        . '<div class="sub__in wrap"><h1>' . e($h1) . '</h1><p>' . e($lead) . '</p></div></section>';
}

function news_card(array $n): string
{
    $date = $n['event_date'] ?: substr($n['created_at'], 0, 10);
    return '<article class="ncard">'
        . ($n['image'] ? pic($n['image'], '', '(min-width:900px) 30vw, 90vw') : '')
        . '<div><time datetime="' . e($date) . '">' . e(date('d/m/Y', strtotime($date))) . '</time>'
        . '<h3><a href="' . e(url('news', null, $n['slug'])) . '">' . e(loc($n, 'title')) . '</a></h3></div></article>';
}

function latest_news(int $limit): array
{
    return q('SELECT * FROM news WHERE published = 1 ORDER BY pinned DESC, COALESCE(NULLIF(event_date, \'\'), created_at) DESC LIMIT ' . $limit)->fetchAll();
}

// ---------- Pagine ----------

function page_home(): void
{
    $today = date('Y-m-d');
    $board = setting('board_until') >= $today ? setting('board_text') : '';
    $daily = setting('board_until') >= $today ? setting('daily_menu') : '';
    $reviews = q('SELECT * FROM reviews WHERE published = 1 ORDER BY sort, id')->fetchAll();
    $news = latest_news(3);
    $intents = [
        'tavolo' => ['intent_table', 'intent_table_s', '<path d="M7 3v8a2 2 0 0 0 2 2v8M5 3v5M9 3v5M17 3c-2 1-3 4-3 7 0 2 1 3 3 3v8"/>'],
        'camera' => ['intent_room', 'intent_room_s', '<path d="M3 19v-7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v7M3 16h18M6 10V7a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v3"/>'],
        'appartamento' => ['intent_apt', 'intent_apt_s', '<path d="M4 11 12 4l8 7M6 9.500V20h12V9.500M10 20v-6h4v6"/>'],
        'festa' => ['intent_party', 'intent_party_s', '<path d="M8 3h8l-1 7a3 3 0 0 1-6 0L8 3ZM12 13v7M8.500 20h7"/>'],
    ];
    $why = [
        'moto' => ['why_moto', 'why_moto_t', ['Sauris' => 26, 'Sappada' => 37, 'Zoncolan' => 19], 'esterno'],
        'bike' => ['why_bike', 'why_bike_t', ['Ovaro' => 10, 'Tolmezzo' => 8, 'Arta Terme' => 16], 'esterno-2'],
        'snow' => ['why_snow', 'why_snow_t', ['Zoncolan' => 19, 'Forni di Sopra' => 34, 'Sappada' => 37], 'fogolar-sala'],
        'walk' => ['why_walk', 'why_walk_t', ['Villa Santina' => 0, 'Sauris' => 26, 'Pesariis' => 22], 'esterno'],
        'family' => ['why_family', 'why_family_t', ['Tolmezzo' => 8, 'Zuglio' => 14, 'Arta Terme' => 16], 'sala'],
    ];
    ob_start();
    ?>
<section class="hero" id="hero">
  <picture class="hero__still">
    <source media="(max-width: 999px)" srcset="/assets/fire/poster-m.webp">
    <img src="/assets/fire/poster.webp" alt="" fetchpriority="high">
  </picture>
  <video class="hero__video" autoplay muted loop playsinline preload="auto" aria-hidden="true" tabindex="-1">
    <source media="(max-width: 999px)" src="/assets/fire/fogolar-m.mp4" type="video/mp4">
    <source src="/assets/fire/fogolar.mp4" type="video/mp4">
  </video>
  <div class="hero__shade"></div>
  <div class="hero__in wrap">
    <?= status_pill() ?>
    <p class="hero__kicker"><?= e(t('hero_kicker')) ?></p>
    <h1><?= e(t('hero_h1')) ?></h1>
    <p class="hero__sub"><?= e(t('hero_sub')) ?></p>
    <div class="hero__cta">
      <a class="btn btn--ember" href="<?= e(url('richiesta')) ?>"><?= e(t('cta_ask')) ?></a>
      <a class="btn btn--ghost" href="<?= e(url('ristorante')) ?>#menu"><?= e(t('rest_cta')) ?></a>
    </div>
  </div>
</section>

<section class="board wrap" aria-labelledby="board-h">
  <div class="board__card">
    <h2 id="board-h"><?= e(t('board_h')) ?> <small><?= e(t('d' . date('N'))) ?> <?= date('j/n') ?></small></h2>
    <div class="board__text"><?= paragraphs($board !== '' ? $board : t('board_default')) ?></div>
    <?php if ($daily !== ''): ?>
      <h3><?= e(t('daily_h')) ?></h3>
      <div class="board__text board__text--list"><?= paragraphs($daily) ?></div>
    <?php endif; ?>
  </div>
</section>

<section class="intent wrap" aria-labelledby="intent-h">
  <h2 id="intent-h"><?= e(t('intent_h')) ?></h2>
  <div class="intent__grid">
    <?php foreach ($intents as $kind => [$label, $sub, $icon]): ?>
      <a href="<?= e(url('richiesta')) ?>?tipo=<?= $kind ?>">
        <svg aria-hidden="true" viewBox="0 0 24 24"><?= $icon ?></svg>
        <strong><?= e(t($label)) ?></strong><span><?= e(t($sub)) ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<section class="story">
  <?= pic('assets/img/fogolar-sala', 'Il fogolar al centro della sala del ristorante', '(min-width:900px) 55vw, 100vw', false, 'story__img') ?>
  <div class="story__in">
    <h2><?= e(t('fire_h')) ?></h2>
    <p><?= t('fire_p') ?></p>
    <dl class="facts">
      <div><dt>8</dt><dd><?= e(t('fact_rooms')) ?></dd></div>
      <div><dt>120</dt><dd><?= e(t('fact_seats')) ?></dd></div>
      <?php if (setting('rating_rest')): ?>
      <div><dt><?= e(setting('rating_rest')) ?><small>/5</small></dt><dd><?= e(sprintf(t('fact_rating'), setting('rating_rest_count'), setting('rating_rest_source'))) ?></dd></div>
      <?php endif; ?>
    </dl>
  </div>
</section>

<section class="duo wrap">
  <article>
    <a class="duo__img" href="<?= e(url('ristorante')) ?>" tabindex="-1" aria-hidden="true"><?= pic('assets/img/sala', '', '(min-width:900px) 46vw, 92vw') ?></a>
    <h2><?= e(t('rest_h')) ?></h2>
    <p><?= e(t('rest_p')) ?></p>
    <a class="more" href="<?= e(url('ristorante')) ?>"><?= e(t('rest_cta')) ?></a>
  </article>
  <article>
    <a class="duo__img" href="<?= e(url('albergo')) ?>" tabindex="-1" aria-hidden="true"><?= pic('assets/img/camera-rossa', '', '(min-width:900px) 46vw, 92vw') ?></a>
    <h2><?= e(t('stay_h')) ?></h2>
    <p><?= e(t('stay_p')) ?></p>
    <a class="more" href="<?= e(url('albergo')) ?>"><?= e(t('stay_cta_h')) ?></a>
    <a class="more" href="<?= e(url('appartamenti')) ?>"><?= e(t('stay_cta_a')) ?></a>
  </article>
</section>

<section class="why" aria-labelledby="why-h">
  <div class="wrap">
    <h2 id="why-h"><?= e(t('why_h')) ?></h2>
    <p class="why__lead"><?= e(t('why_p')) ?></p>
    <div class="tabs" role="tablist">
      <?php $i = 0; foreach ($why as $k => [$label]): ?>
        <button type="button" role="tab" id="tab-<?= $k ?>" aria-controls="panel-<?= $k ?>" aria-selected="<?= $i++ ? 'false' : 'true' ?>"><?= e(t($label)) ?></button>
      <?php endforeach; ?>
    </div>
    <?php $i = 0; foreach ($why as $k => [, $text, $km, $img]): ?>
      <div class="panel" role="tabpanel" id="panel-<?= $k ?>" aria-labelledby="tab-<?= $k ?>"<?= $i++ ? ' data-later' : '' ?>>
        <p><?= e(t($text)) ?></p>
        <ul class="km">
          <?php foreach ($km as $place => $d): if ($d === 0) { continue; } ?>
            <li><b><?= e($place) ?></b> <span>~<?= $d ?> km</span></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endforeach; ?>
    <p class="note"><?= e(t('km_note')) ?></p>
  </div>
</section>

<?php if ($reviews): ?>
<section class="reviews" aria-labelledby="rev-h">
  <div class="wrap"><h2 id="rev-h"><?= e(t('reviews_h')) ?></h2></div>
  <div class="reviews__row" tabindex="0" role="group" aria-labelledby="rev-h">
    <?php foreach ($reviews as $r): ?>
      <blockquote><p><?= e($r['text']) ?></p><footer><?= e($r['author']) ?></footer></blockquote>
    <?php endforeach; ?>
  </div>
  <?php if (setting('tripadvisor')): ?>
  <div class="wrap"><a class="more" href="<?= e(setting('tripadvisor')) ?>" rel="noopener"><?= e(t('reviews_more')) ?></a></div>
  <?php endif; ?>
</section>
<?php endif; ?>

<?php if ($news): ?>
<section class="news wrap" id="news" aria-labelledby="news-h">
  <h2 id="news-h"><?= e(t('news_h')) ?></h2>
  <div class="news__grid"><?php foreach ($news as $n) { echo news_card($n); } ?></div>
  <a class="more" href="<?= e(url('news')) ?>"><?= e(t('news_all')) ?></a>
</section>
<?php endif; ?>
    <?php
    layout('home', t('home_title'), t('home_desc'), ob_get_clean());
}

function page_ristorante(): void
{
    $items = q('SELECT * FROM menu_items WHERE available = 1 ORDER BY sort, id')->fetchAll();
    $sections = [];
    foreach ($items as $it) {
        $sections[$it['section']][] = $it;
    }
    $order = array_intersect_key(array_flip(['antipasti', 'primi', 'secondi', 'taglieri', 'stagione', 'dolci', 'bevande']), $sections);
    $today = date('Y-m-d');
    $daily = setting('board_until') >= $today ? setting('daily_menu') : '';
    ob_start();
    echo subhead(t('rp_h1'), t('rp_lead'), 'sala', 'La sala da pranzo del ristorante');
    ?>
<section class="wrap block"><?= status_pill() ?></section>
<?php if ($daily !== ''): ?>
<section class="board wrap board--flat"><div class="board__card"><h2><?= e(t('daily_h')) ?> <small><?= e(t('d' . date('N'))) ?> <?= date('j/n') ?></small></h2><div class="board__text board__text--list"><?= paragraphs($daily) ?></div></div></section>
<?php endif; ?>
<section class="wrap block" id="menu">
  <h2><?= e(t('rp_menu_h')) ?></h2>
  <div class="menu">
    <?php foreach (array_keys($order) as $sec): ?>
      <section>
        <h3><?= e(t("sec_$sec")) ?></h3>
        <ul>
          <?php foreach ($sections[$sec] as $it): ?>
            <li><div><strong><?= e($it['name']) ?></strong><?php if ($d = loc($it, 'desc')): ?><span><?= e($d) ?></span><?php endif; ?></div><?php if ($it['price'] !== ''): ?><b class="price">€ <?= e($it['price']) ?></b><?php endif; ?></li>
          <?php endforeach; ?>
        </ul>
      </section>
    <?php endforeach; ?>
  </div>
  <p class="note"><?= e(t('rp_menu_note')) ?></p>
</section>
<?= gallery_strip('ristorante') ?>
<section class="split wrap">
  <div class="split__card split__card--dark">
    <h2><?= e(t('party_h')) ?></h2>
    <p><?= e(t('party_p')) ?></p>
    <a class="btn btn--ember" href="<?= e(url('richiesta')) ?>?tipo=festa"><?= e(t('party_cta')) ?></a>
  </div>
  <div class="split__card">
    <h2><?= e(t('art_h')) ?></h2>
    <p><?= e(t('art_p')) ?></p>
  </div>
</section>
    <?php
    layout('ristorante', t('rp_title'), t('rp_desc'), ob_get_clean());
}

function page_albergo(): void
{
    ob_start();
    echo subhead(t('hp_h1'), t('hp_lead'), 'esterno', 'La casa in stile montano del Fogolar con il cortile');
    ?>
<section class="wrap block">
  <h2><?= e(t('svc_h')) ?></h2>
  <ul class="chips">
    <?php foreach (['svc_wifi', 'svc_bath', 'svc_moto', 'svc_bike', 'svc_pets', 'svc_park', 'svc_rest'] as $k): ?><li><?= e(t($k)) ?></li><?php endforeach; ?>
  </ul>
</section>
<?= gallery_strip('albergo') ?>
<section class="wrap block">
  <h2><?= e(t('near_h')) ?></h2>
  <ul class="km km--list">
    <?php foreach (['Tolmezzo' => 8, 'Ovaro' => 10, 'Zuglio' => 14, 'Arta Terme' => 16, 'Zoncolan' => 19, 'Pesariis' => 22, 'Sauris' => 26, 'Forni di Sopra' => 34, 'Sappada' => 37] as $p => $d): ?>
      <li><b><?= e($p) ?></b> <span>~<?= $d ?> km</span></li>
    <?php endforeach; ?>
  </ul>
  <p class="note"><?= e(t('km_note')) ?></p>
  <a class="btn btn--ember" href="<?= e(url('richiesta')) ?>?tipo=camera"><?= e(t('cta_ask')) ?></a>
</section>
    <?php
    layout('albergo', t('hp_title'), t('hp_desc'), ob_get_clean());
}

function page_appartamenti(): void
{
    ob_start();
    echo subhead(t('ap_h1'), t('ap_lead'), 'app-1', 'Soggiorno con cucina di un miniappartamento');
    ?>
<section class="wrap block">
  <ul class="chips">
    <?php foreach (['apt_kitchen', 'apt_bath', 'apt_rooms', 'apt_park', 'apt_wifi', 'apt_extra'] as $k): ?><li><?= e(t($k)) ?></li><?php endforeach; ?>
  </ul>
</section>
<?= gallery_strip('appartamenti') ?>
<section class="wrap block">
  <p class="lead"><?= e(t('apt_price')) ?></p>
  <a class="btn btn--ember" href="<?= e(url('richiesta')) ?>?tipo=appartamento"><?= e(t('cta_ask')) ?></a>
</section>
    <?php
    layout('appartamenti', t('ap_title'), t('ap_desc'), ob_get_clean());
}

function page_contatti(): void
{
    $closures = q('SELECT * FROM closures WHERE date_to >= ? ORDER BY date_from LIMIT 5', [date('Y-m-d')])->fetchAll();
    $phone = clean_phone(setting('phone'));
    $wa = clean_phone(setting('whatsapp'));
    ob_start();
    ?>
<section class="plain wrap">
  <h1><?= e(t('cp_h1')) ?></h1>
  <?= status_pill() ?>
  <div class="contact">
    <div>
      <h2><?= e(t('where_h')) ?></h2>
      <p class="lead"><?= e(setting('address')) ?><br><?= e(setting('city')) ?></p>
      <div class="stack">
        <a class="btn btn--ember" href="tel:<?= e($phone) ?>"><?= e(t('cta_call')) ?> <?= e(setting('phone')) ?></a>
        <?php if ($wa !== ''): ?><a class="btn btn--line" href="https://wa.me/<?= e(ltrim($wa, '+')) ?>" rel="noopener">WhatsApp</a><?php endif; ?>
        <a class="btn btn--line" href="<?= e(maps_url()) ?>" rel="noopener"><?= e(t('cta_map')) ?></a>
        <a class="btn btn--line" href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a>
        <a class="btn btn--line" href="<?= e(url('richiesta')) ?>"><?= e(t('cta_ask')) ?></a>
      </div>
    </div>
    <div>
      <?= hours_table() ?>
      <?php if ($closures): ?>
        <h2 class="mt"><?= e(t('closures_h')) ?></h2>
        <ul class="plainlist">
        <?php foreach ($closures as $c): ?>
          <li><?= e(date('d/m', strtotime($c['date_from']))) ?><?= $c['date_to'] !== $c['date_from'] ? '–' . e(date('d/m', strtotime($c['date_to']))) : '' ?><?= $c['note'] !== '' ? ' · ' . e($c['note']) : '' ?></li>
        <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>
</section>
    <?php
    layout('contatti', t('cp_title'), t('cp_desc'), ob_get_clean());
}

function page_news(): void
{
    $news = latest_news(40);
    ob_start();
    ?>
<section class="plain wrap">
  <h1><?= e(t('news_h')) ?></h1>
  <?php if ($news): ?>
    <div class="news__grid"><?php foreach ($news as $n) { echo news_card($n); } ?></div>
  <?php else: ?>
    <p><?= e(t('news_none')) ?></p>
  <?php endif; ?>
</section>
    <?php
    layout('news', t('news_h') . ' | Al Fogolar', t('news_desc'), ob_get_clean());
}

function page_news_detail(string $slug): void
{
    $n = q('SELECT * FROM news WHERE slug = ? AND published = 1', [$slug])->fetch();
    if (!$n) {
        page_404();
        return;
    }
    $date = $n['event_date'] ?: substr($n['created_at'], 0, 10);
    ob_start();
    ?>
<article class="plain wrap article">
  <a class="back" href="<?= e(url('news')) ?>"><?= e(t('news_back')) ?></a>
  <time datetime="<?= e($date) ?>"><?= e(date('d/m/Y', strtotime($date))) ?></time>
  <h1><?= e(loc($n, 'title')) ?></h1>
  <?= $n['image'] ? pic($n['image'], '', '(min-width:800px) 720px, 100vw', true) : '' ?>
  <?= paragraphs(loc($n, 'body')) ?>
  <a class="btn btn--ember" href="<?= e(url('richiesta')) ?>"><?= e(t('cta_ask')) ?></a>
</article>
    <?php
    $og = $n['image'] ? $n['image'] . '-1600.webp' : 'assets/fire/og.jpg';
    layout('news', loc($n, 'title') . ' | Al Fogolar', mb_substr(strip_tags(loc($n, 'body')), 0, 155), ob_get_clean(), $n['slug'], false, $og);
}

function page_404(): void
{
    http_response_code(404);
    ob_start();
    ?>
<section class="plain wrap center">
  <h1><?= e(t('nf_h')) ?></h1>
  <p><?= e(t('nf_p')) ?></p>
  <a class="btn btn--ember" href="<?= e(url('home')) ?>"><?= e(t('ok_home')) ?></a>
</section>
    <?php
    layout('home', t('nf_h'), t('nf_p'), ob_get_clean(), '', true);
}

// ---------- Richiesta di disponibilità ----------

const KINDS = ['tavolo', 'camera', 'appartamento', 'festa'];

function page_richiesta(): void
{
    $errors = [];
    $v = ['kind' => in_array($_GET['tipo'] ?? '', KINDS, true) ? $_GET['tipo'] : 'tavolo', 'date_from' => '', 'date_to' => '', 'slot' => 'cena', 'people' => '2', 'name' => '', 'phone' => '', 'email' => '', 'note' => ''];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        foreach ($v as $k => $_) {
            $v[$k] = trim((string) ($_POST[$k] ?? ''));
        }
        $errors = handle_request($v);
        if (!$errors) {
            redirect(url('richiesta') . '?ok=1');
        }
    }
    if (isset($_GET['ok'])) {
        ob_start();
        ?>
<section class="plain wrap center thanks">
  <h1><?= e(t('ok_h')) ?></h1>
  <p class="lead"><?= e(t('ok_p')) ?></p>
  <div class="stack stack--center">
    <a class="btn btn--ember" href="tel:<?= e(clean_phone(setting('phone'))) ?>"><?= e(t('cta_call')) ?> <?= e(setting('phone')) ?></a>
    <a class="btn btn--line" href="<?= e(url('home')) ?>"><?= e(t('ok_home')) ?></a>
  </div>
</section>
        <?php
        layout('richiesta', t('ok_h'), t('q_desc'), ob_get_clean(), '', true);
        return;
    }
    $closedDates = [];
    foreach (q('SELECT date_from, date_to FROM closures WHERE date_to >= ?', [date('Y-m-d')]) as $c) {
        $closedDates[] = [$c['date_from'], $c['date_to']];
    }
    $closedDays = [];
    foreach (hours() as $d => $slots) {
        if (!$slots) {
            $closedDays[] = (int) $d % 7; // getDay() in JS: domenica = 0
        }
    }
    // Dopo un errore il tempo di compilazione resta quello iniziale: chi corregge in fretta non è un bot
    $ts = $errors ? (string) (int) ($_POST['ts'] ?? time()) : (string) time();
    $sig = hash_hmac('sha256', $ts, app_secret());
    $kinds = ['tavolo' => 'intent_table', 'camera' => 'intent_room', 'appartamento' => 'intent_apt', 'festa' => 'intent_party'];
    $today = date('Y-m-d');
    ob_start();
    ?>
<section class="plain wrap narrow">
  <h1><?= e(t('q_h1')) ?></h1>
  <p class="lead"><?= e(t('q_lead')) ?></p>
  <?php if ($errors): ?>
    <div class="alert" role="alert"><?= e(t('q_err')) ?><ul><?php foreach ($errors as $m) { echo '<li>' . e($m) . '</li>'; } ?></ul></div>
  <?php endif; ?>
  <form class="ask" method="post" action="<?= e(url('richiesta')) ?>" data-closed-days="<?= e(json_encode($closedDays)) ?>" data-closed-dates="<?= e(json_encode($closedDates)) ?>">
    <input type="hidden" name="ts" value="<?= $ts ?>"><input type="hidden" name="sig" value="<?= e($sig) ?>">
    <div class="hp" aria-hidden="true"><label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

    <fieldset class="step" data-step="1">
      <legend><?= e(t('q_s1')) ?></legend>
      <div class="kinds">
        <?php foreach ($kinds as $k => $label): ?>
          <label><input type="radio" name="kind" value="<?= $k ?>"<?= $v['kind'] === $k ? ' checked' : '' ?>><span><?= e(t($label)) ?></span></label>
        <?php endforeach; ?>
      </div>
    </fieldset>

    <fieldset class="step" data-step="2">
      <legend><?= e(t('q_s2')) ?></legend>
      <div class="row">
        <label><span data-label-from data-day="<?= e(t('q_date')) ?>" data-stay="<?= e(t('q_arrive')) ?>"><?= e(t('q_date')) ?></span>
          <input type="date" name="date_from" min="<?= $today ?>" value="<?= e($v['date_from']) ?>" required></label>
        <label data-only="stay"><span><?= e(t('q_leave')) ?></span>
          <input type="date" name="date_to" min="<?= $today ?>" value="<?= e($v['date_to']) ?>"></label>
        <div class="seg" data-only="table" role="group" aria-label="<?= e(t('q_slot')) ?>">
          <label><input type="radio" name="slot" value="pranzo"<?= $v['slot'] === 'pranzo' ? ' checked' : '' ?>><span><?= e(t('q_lunch')) ?></span></label>
          <label><input type="radio" name="slot" value="cena"<?= $v['slot'] !== 'pranzo' ? ' checked' : '' ?>><span><?= e(t('q_dinner')) ?></span></label>
        </div>
      </div>
      <p class="warn" data-warn hidden><?= e(t('q_warn_closed')) ?></p>
      <label class="people"><span><?= e(t('q_people')) ?></span>
        <span class="stepper"><button type="button" data-step-down aria-label="−">−</button><input type="number" name="people" min="1" max="300" inputmode="numeric" value="<?= e($v['people']) ?>" required><button type="button" data-step-up aria-label="+">+</button></span></label>
    </fieldset>

    <fieldset class="step" data-step="3">
      <legend><?= e(t('q_s3')) ?></legend>
      <label><span><?= e(t('q_name')) ?></span><input type="text" name="name" autocomplete="name" maxlength="120" value="<?= e($v['name']) ?>" required></label>
      <label><span><?= e(t('q_phone')) ?></span><input type="tel" name="phone" autocomplete="tel" inputmode="tel" maxlength="40" value="<?= e($v['phone']) ?>"></label>
      <label><span><?= e(t('q_email')) ?></span><input type="email" name="email" autocomplete="email" maxlength="160" value="<?= e($v['email']) ?>"></label>
      <p class="note"><?= e(t('q_contact_hint')) ?></p>
      <label><span><?= e(t('q_note')) ?></span><textarea name="note" rows="3" maxlength="1500" placeholder="<?= e(t('q_note_ph')) ?>"><?= e($v['note']) ?></textarea></label>
      <p class="note"><?= e(t('q_privacy')) ?> <a href="<?= e(url('privacy')) ?>"><?= e(t('privacy')) ?></a>.</p>
    </fieldset>

    <div class="ask__nav">
      <button class="btn btn--line" type="button" data-prev hidden><?= e(t('q_back')) ?></button>
      <button class="btn btn--ember" type="button" data-next hidden><?= e(t('q_next')) ?></button>
      <button class="btn btn--ember" type="submit" data-send><?= e(t('q_send')) ?></button>
    </div>
  </form>
</section>
    <?php
    layout('richiesta', t('q_title'), t('q_desc'), ob_get_clean());
}

function handle_request(array $v): array
{
    $errors = [];
    // Anti-spam silenzioso: honeypot e tempo minimo di compilazione
    $ts = (int) ($_POST['ts'] ?? 0);
    $sigOk = hash_equals(hash_hmac('sha256', (string) $ts, app_secret()), (string) ($_POST['sig'] ?? ''));
    if (($_POST['website'] ?? '') !== '' || !$sigOk || time() - $ts < 3) {
        redirect(url('richiesta') . '?ok=1');
    }
    $ip = ip_hash();
    $recent = (int) q("SELECT COUNT(*) FROM requests WHERE ip = ? AND created_at > datetime('now', 'localtime', '-1 hour')", [$ip])->fetchColumn();
    if ($recent >= 5) {
        return [t('q_err_limit')];
    }
    if (!in_array($v['kind'], KINDS, true)) {
        $v['kind'] = 'tavolo';
    }
    if (mb_strlen($v['name']) < 2) {
        $errors[] = t('q_err_name');
    }
    $validEmail = $v['email'] !== '' && filter_var($v['email'], FILTER_VALIDATE_EMAIL);
    if (strlen(preg_replace('/\D/', '', $v['phone'])) < 6 && !$validEmail) {
        $errors[] = t('q_err_contact');
    }
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $v['date_from']);
    if (!$d || $v['date_from'] < date('Y-m-d')) {
        $errors[] = t('q_err_date');
    }
    $stay = in_array($v['kind'], ['camera', 'appartamento'], true);
    if ($stay && $v['date_to'] !== '' && $v['date_to'] <= $v['date_from']) {
        $errors[] = t('q_err_date');
    }
    if ($errors) {
        return $errors;
    }
    q('INSERT INTO requests(kind, date_from, date_to, slot, people, name, phone, email, note, lang, ip, created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)', [
        $v['kind'], $v['date_from'], $stay ? $v['date_to'] : null, $v['kind'] === 'tavolo' ? $v['slot'] : null,
        max(1, min(300, (int) $v['people'])), $v['name'], $v['phone'], $validEmail ? $v['email'] : '', mb_substr($v['note'], 0, 1500), lang(), $ip, date('Y-m-d H:i:s'),
    ]);
    $id = (int) db()->lastInsertId();
    notify_owner($v, $stay, $id);
    push_new_request($v, $stay, $id);
    return [];
}

function notify_owner(array $v, bool $stay, int $id): void
{
    $to = setting('notify_email');
    if ($to === '') {
        return;
    }
    $strip = fn(string $s) => str_replace(["\r", "\n"], ' ', $s);
    $when = date('d/m/Y', strtotime($v['date_from'])) . ($stay && $v['date_to'] ? ' → ' . date('d/m/Y', strtotime($v['date_to'])) : '') . ($v['kind'] === 'tavolo' ? " ({$v['slot']})" : '');
    $body = "Nuova richiesta dal sito: {$v['kind']}\n\n$when · {$v['people']} persone\n{$v['name']}\nTel: {$v['phone']}\nEmail: {$v['email']}\n\n{$v['note']}\n\nGestisci: " . base_url() . "/admin/richieste/$id\n";
    $host = preg_replace('/^www\./', '', $_SERVER['HTTP_HOST'] ?? 'albergoalfogolar.it');
    $headers = "From: Sito Al Fogolar <noreply@$host>\r\nContent-Type: text/plain; charset=UTF-8";
    if (filter_var($v['email'], FILTER_VALIDATE_EMAIL)) {
        $headers .= "\r\nReply-To: " . $strip($v['email']);
    }
    @mail($to, '=?UTF-8?B?' . base64_encode($strip("Richiesta {$v['kind']}: {$v['name']}, $when")) . '?=', $body, $headers);
}

function site_sitemap(): void
{
    header('Content-Type: application/xml; charset=UTF-8');
    $base = base_url();
    echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">';
    $entry = function (string $page, string $tail = '', ?string $only = null) use ($base) {
        foreach ($only ? [$only] : LANGS as $l) {
            echo '<url><loc>' . e($base . url($page, $l, $tail)) . '</loc>';
            foreach (LANGS as $alt) {
                echo '<xhtml:link rel="alternate" hreflang="' . $alt . '" href="' . e($base . url($page, $alt, $tail)) . '"/>';
            }
            echo '</url>';
        }
    };
    foreach (['home', 'ristorante', 'albergo', 'appartamenti', 'contatti', 'news', 'richiesta', 'privacy', 'cookie'] as $p) {
        $entry($p);
    }
    foreach (q('SELECT slug FROM news WHERE published = 1') as $n) {
        $entry('news', $n['slug']);
    }
    echo '</urlset>';
}
