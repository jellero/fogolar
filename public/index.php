<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/i18n.php';

security_headers();

$path = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/') ?: '/';

if ($path === '/admin' || str_starts_with($path, '/admin/')) {
    require __DIR__ . '/../app/admin.php';
    admin_dispatch($path);
    exit;
}

require __DIR__ . '/../app/site.php';

// Vecchi indirizzi del sito Drupal
if (preg_match('#^/it(/.*)?$#', $path, $m)) {
    $old = trim($m[1] ?? '', '/');
    redirect('/' . ($old === 'home' ? '' : $old), 301);
}

if ($path === '/sitemap.xml') {
    site_sitemap();
    exit;
}

$parts = array_values(array_filter(explode('/', $path), 'strlen'));
if (isset($parts[0]) && in_array($parts[0], ['en', 'de'], true)) {
    lang(array_shift($parts));
}

$slug = $parts[0] ?? '';
$page = null;
foreach (ROUTES as $name => $map) {
    if ($map[lang()] === $slug) {
        $page = $name;
        break;
    }
}

site_dispatch($page, $parts[1] ?? null, count($parts));
