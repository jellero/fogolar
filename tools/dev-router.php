<?php
// Solo per sviluppo: php -S localhost:8123 -t public tools/dev-router.php
$p = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($p !== '/' && is_file(__DIR__ . '/../public' . $p)) {
    return false;
}
require __DIR__ . '/../public/index.php';
