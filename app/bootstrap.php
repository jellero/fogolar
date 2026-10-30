<?php
declare(strict_types=1);

const ROOT = __DIR__ . '/..';
const LANGS = ['it', 'en', 'de'];
const IMG_WIDTHS = [480, 800, 1600];

date_default_timezone_set('Europe/Rome');
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

function db(): PDO
{
    static $pdo = null;
    if ($pdo) {
        return $pdo;
    }
    $file = ROOT . '/data/fogolar.sqlite';
    $fresh = !is_file($file);
    $pdo = new PDO('sqlite:' . $file, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA journal_mode=WAL; PRAGMA foreign_keys=ON;');
    if ($fresh) {
        require __DIR__ . '/schema.php';
        install_schema($pdo);
    }
    $pdo->exec('CREATE TABLE IF NOT EXISTS push_subscriptions (
            id INTEGER PRIMARY KEY, endpoint TEXT UNIQUE NOT NULL, p256dh TEXT NOT NULL, auth TEXT NOT NULL,
            device TEXT DEFAULT \'\', last_ok_at TEXT, created_at TEXT NOT NULL
        )');
    return $pdo;
}

function q(string $sql, array $args = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($args);
    return $st;
}

function setting(string $key, string $default = ''): string
{
    static $all = null;
    if ($all === null || $key === '__reload') {
        $all = [];
        foreach (q('SELECT key, value FROM settings') as $row) {
            $all[$row['key']] = $row['value'];
        }
    }
    return $all[$key] ?? $default;
}

function save_setting(string $key, string $value): void
{
    q('INSERT INTO settings(key, value) VALUES(?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value', [$key, $value]);
    setting('__reload');
}

function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Testo semplice del cliente: paragrafi e a capo, sempre escapato. */
function paragraphs(string $s): string
{
    $out = '';
    foreach (preg_split('/\R{2,}/', trim($s)) as $p) {
        $out .= '<p>' . nl2br(e($p), false) . '</p>';
    }
    return $out;
}

function slugify(string $s): string
{
    $s = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s);
    return trim(preg_replace('/[^a-z0-9]+/', '-', $s), '-') ?: 'news';
}

/** <img> responsive da uno "stem", es. assets/img/sala oppure uploads/abc. */
function pic(string $stem, string $alt, string $sizes = '100vw', bool $eager = false, string $class = ''): string
{
    $set = [];
    $src = '';
    foreach (IMG_WIDTHS as $w) {
        if (is_file(ROOT . "/public/$stem-$w.webp")) {
            $set[] = "/$stem-$w.webp {$w}w";
            if ($w <= 800 || $src === '') {
                $src = "/$stem-$w.webp";
            }
        }
    }
    return '<img src="' . e($src) . '" srcset="' . e(implode(', ', $set)) . '" sizes="' . e($sizes) . '" alt="' . e($alt) . '"'
        . ($class ? ' class="' . e($class) . '"' : '')
        . ($eager ? ' fetchpriority="high"' : ' loading="lazy"') . ' decoding="async">';
}

/** Salva una foto caricata in 3 larghezze WebP e ritorna lo stem. */
function save_upload(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || $file['size'] > 12_000_000) {
        return null;
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $img = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($file['tmp_name']),
        'image/png' => @imagecreatefrompng($file['tmp_name']),
        'image/webp' => @imagecreatefromwebp($file['tmp_name']),
        default => false,
    };
    if (!$img) {
        return null;
    }
    if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
        $o = @exif_read_data($file['tmp_name'])['Orientation'] ?? 1;
        $rot = [3 => 180, 6 => -90, 8 => 90][$o] ?? 0;
        if ($rot) {
            $img = imagerotate($img, $rot, 0);
        }
    }
    $name = bin2hex(random_bytes(8));
    foreach (IMG_WIDTHS as $w) {
        $copy = imagesx($img) > $w ? imagescale($img, $w) : $img;
        imagewebp($copy, ROOT . "/public/uploads/$name-$w.webp", 76);
    }
    return "uploads/$name";
}

function delete_upload(?string $stem): void
{
    if ($stem && str_starts_with($stem, 'uploads/')) {
        foreach (IMG_WIDTHS as $w) {
            @unlink(ROOT . "/public/$stem-$w.webp");
        }
    }
}

// ---------- Orari e stato ----------

function hours(): array
{
    return json_decode(setting('hours', '{}'), true) ?: [];
}

function closure_on(string $date): ?array
{
    return q('SELECT * FROM closures WHERE date_from <= ? AND date_to >= ? LIMIT 1', [$date, $date])->fetch() ?: null;
}

/** Stato "adesso": ritorna [state, key, time]. state: open|soon|closed|full */
function live_status(?DateTimeImmutable $now = null): array
{
    $now ??= new DateTimeImmutable();
    $today = $now->format('Y-m-d');
    $hm = $now->format('H:i');
    $note = setting('status_note');
    $active = setting('status_until') >= $today;
    if ($active && setting('status_mode') === 'closed') {
        return ['closed', 'st_closed_today', '', $note];
    }
    if (closure_on($today)) {
        return ['closed', 'st_closed_today', '', closure_on($today)['note']];
    }
    $slots = hours()[$now->format('N')] ?? [];
    $full = $active && setting('status_mode') === 'full';
    foreach ($slots as [$from, $to]) {
        if ($hm >= $from && $hm < $to) {
            if ($full) {
                return ['full', 'st_full', '', $note];
            }
            $mins = ((int) substr($to, 0, 2) * 60 + (int) substr($to, 3)) - ((int) $now->format('G') * 60 + (int) $now->format('i'));
            return [$mins <= 45 ? 'soon' : 'open', $mins <= 45 ? 'st_closing' : 'st_open', $to, ''];
        }
        if ($hm < $from) {
            return [$full ? 'full' : 'closed', $full ? 'st_full' : 'st_opens', $from, $note];
        }
    }
    return ['closed', $slots ? 'st_closed_now' : 'st_closed_today', '', $note];
}

// ---------- Sessione e sicurezza (solo backoffice) ----------

function start_admin_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name('fogolar_admin');
    session_set_cookie_params([
        'lifetime' => 60 * 60 * 24 * 30,
        'path' => '/admin',
        'secure' => !empty($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.gc_maxlifetime', (string) (60 * 60 * 24 * 30));
    ini_set('session.use_strict_mode', '1');
    session_save_path(ROOT . '/data/sessions');
    session_start();
}

function csrf(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(24));
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf()) . '">';
}

function ip_hash(): string
{
    return hash_hmac('sha256', $_SERVER['REMOTE_ADDR'] ?? '', app_secret());
}

function app_secret(): string
{
    $s = setting('app_secret');
    if ($s === '') {
        $s = bin2hex(random_bytes(32));
        save_setting('app_secret', $s);
    }
    return $s;
}

function redirect(string $to, int $code = 303): never
{
    header('Location: ' . $to, true, $code);
    exit;
}

function security_headers(): void
{
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Frame-Options: DENY');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; font-src 'self'; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
    if (!empty($_SERVER['HTTPS'])) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

function clean_phone(string $p): string
{
    $n = preg_replace('/[^\d+]/', '', $p);
    if (str_starts_with($n, '00')) {
        $n = '+' . substr($n, 2);
    }
    if ($n !== '' && $n[0] !== '+') {
        $n = '+39' . $n;
    }
    return $n;
}
