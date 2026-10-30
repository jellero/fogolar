<?php
declare(strict_types=1);

const KIND_LABEL = ['tavolo' => 'Tavolo', 'camera' => 'Camera', 'appartamento' => 'Appartamento', 'festa' => 'Festa'];
const STATUS_LABEL = ['nuova' => 'Da gestire', 'risposta' => 'Risposto', 'confermata' => 'Confermata', 'rifiutata' => 'Non disponibile'];
const SECTION_LABEL = ['antipasti' => 'Antipasti', 'primi' => 'Primi', 'secondi' => 'Secondi e piatti tipici', 'taglieri' => 'A ogni ora', 'stagione' => 'Di stagione', 'dolci' => 'Dolci', 'bevande' => 'Bevande'];
const CAT_LABEL = ['ristorante' => 'Ristorante', 'albergo' => 'Albergo', 'appartamenti' => 'Appartamenti'];

function admin_dispatch(string $path): void
{
    if (!is_dir(ROOT . '/data/sessions')) {
        mkdir(ROOT . '/data/sessions', 0700, true);
    }
    start_admin_session();
    header('X-Robots-Tag: noindex, nofollow');
    header('Cache-Control: no-store');

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !hash_equals(csrf(), (string) ($_POST['_csrf'] ?? ''))) {
        http_response_code(419);
        admin_message('Sessione scaduta', 'Torna indietro, ricarica la pagina e riprova.');
        return;
    }

    $hasUser = (int) q('SELECT COUNT(*) FROM users')->fetchColumn() > 0;
    if (!$hasUser) {
        admin_setup();
        return;
    }
    if ($path === '/admin/login') {
        admin_login();
        return;
    }
    if (empty($_SESSION['uid'])) {
        redirect('/admin/login');
    }
    if ($path === '/admin/logout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $_SESSION = [];
        session_destroy();
        redirect('/admin/login');
    }

    // I dati delle richieste non restano oltre 12 mesi (vedi privacy)
    q("DELETE FROM requests WHERE created_at < datetime('now', 'localtime', '-12 months')");

    $sub = substr($path, strlen('/admin'));
    match (true) {
        $sub === '' => admin_today(),
        $sub === '/richieste' => admin_requests(),
        (bool) preg_match('#^/richieste/(\d+)$#', $sub, $m) => admin_request((int) $m[1]),
        $sub === '/news' => admin_news(),
        $sub === '/news/modifica' => admin_news_edit(),
        $sub === '/menu' => admin_menu(),
        $sub === '/foto' => admin_photos(),
        $sub === '/recensioni' => admin_reviews(),
        $sub === '/impostazioni' => admin_settings(),
        default => admin_message('Pagina non trovata', 'Questa pagina non esiste.'),
    };
}

function flash(?string $msg = null): ?string
{
    if ($msg !== null) {
        $_SESSION['flash'] = $msg;
        return null;
    }
    $m = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $m;
}

function pending_count(): int
{
    return (int) q("SELECT COUNT(*) FROM requests WHERE status = 'nuova'")->fetchColumn();
}

function admin_layout(string $title, string $body, string $active = '', bool $bare = false): void
{
    $flash = $bare ? null : flash();
    $pending = $bare ? 0 : pending_count();
    $tabs = [
        '' => ['Oggi', '<path d="M12 3s5 4.500 5 10a5 5 0 0 1-10 0c0-2 1-3.500 2-4.500.2 1.500 1 2.500 2 2.500 0-3 0-5.500 1-8Z"/>'],
        'richieste' => ['Richieste', '<path d="M4 5h16v11H9l-5 4V5Z"/>'],
        'news' => ['News', '<path d="M5 4h14v16H5zM8 8h8M8 12h8M8 16h5"/>'],
        'menu' => ['Menu', '<path d="M7 3v8a2 2 0 0 0 2 2v8M5 3v5M9 3v5M17 3c-2 1-3 4-3 7 0 2 1 3 3 3v8"/>'],
        'altro' => ['Altro', '<circle cx="5" cy="12" r="1.500"/><circle cx="12" cy="12" r="1.500"/><circle cx="19" cy="12" r="1.500"/>'],
    ];
    ?>
<!doctype html>
<html lang="it">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($title) ?> · Al Fogolar</title>
<meta name="robots" content="noindex">
<meta name="theme-color" content="#14100c">
<link rel="manifest" href="/admin.webmanifest">
<link rel="icon" href="/assets/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/assets/app-icon-192.png">
<link rel="stylesheet" href="/assets/admin.css?v=<?= filemtime(ROOT . '/public/assets/admin.css') ?>">
<script src="/assets/admin.js?v=<?= filemtime(ROOT . '/public/assets/admin.js') ?>" defer></script>
</head>
<body<?= $bare ? ' class="bare"' : '' ?>>
<?php if (!$bare): ?>
<header class="ahead"><strong>Al Fogolar</strong><a href="/" target="_blank" rel="noopener">Vedi il sito ↗</a></header>
<?php endif; ?>
<main class="amain">
<?php if ($flash): ?><p class="flash" role="status"><?= e($flash) ?></p><?php endif; ?>
<?= $body ?>
</main>
<?php if (!$bare): ?>
<nav class="anav" aria-label="Sezioni">
  <?php foreach ($tabs as $slug => [$label, $icon]): ?>
    <a href="/admin<?= $slug === 'altro' ? '/impostazioni' : ($slug ? "/$slug" : '') ?>"<?= $active === $slug ? ' aria-current="page"' : '' ?>>
      <svg viewBox="0 0 24 24" aria-hidden="true"><?= $icon ?></svg><?= e($label) ?>
      <?php if ($slug === 'richieste' && $pending): ?><b class="badge"><?= $pending ?></b><?php endif; ?>
    </a>
  <?php endforeach; ?>
</nav>
<?php endif; ?>
</body>
</html>
    <?php
}

function admin_message(string $title, string $text): void
{
    admin_layout($title, '<h1>' . e($title) . '</h1><p>' . e($text) . '</p><p><a class="abtn" href="/admin">Torna a Oggi</a></p>', '', empty($_SESSION['uid']));
}

// ---------- Accesso ----------

function admin_setup(): void
{
    $file = ROOT . '/data/setup-token.txt';
    if (!is_file($file)) {
        file_put_contents($file, bin2hex(random_bytes(12)) . "\n");
        chmod($file, 0600);
    }
    $token = trim((string) file_get_contents($file));
    $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $pass = (string) ($_POST['password'] ?? '');
        if (!hash_equals($token, trim((string) ($_POST['token'] ?? '')))) {
            $error = 'Codice di installazione errato.';
        } elseif (strlen($pass) < 10) {
            $error = 'La password deve avere almeno 10 caratteri.';
        } else {
            q('INSERT INTO users(name, hash) VALUES(?, ?)', [trim((string) ($_POST['name'] ?? 'Fogolar')) ?: 'Fogolar', password_hash($pass, PASSWORD_DEFAULT)]);
            unlink($file);
            session_regenerate_id(true);
            $_SESSION['uid'] = (int) db()->lastInsertId();
            redirect('/admin');
        }
    }
    ob_start();
    ?>
<h1>Prima attivazione</h1>
<p>Il codice si trova sul server nel file <code>data/setup-token.txt</code>. Viene cancellato appena crei l'accesso.</p>
<?php if ($error): ?><p class="flash flash--bad" role="alert"><?= e($error) ?></p><?php endif; ?>
<form method="post" class="aform"><?= csrf_field() ?>
  <label>Codice di installazione<input name="token" required autocomplete="off"></label>
  <label>Il tuo nome<input name="name" required autocomplete="name"></label>
  <label>Scegli una password (almeno 10 caratteri)<input type="password" name="password" required minlength="10" autocomplete="new-password"></label>
  <button class="abtn abtn--main">Crea l'accesso</button>
</form>
    <?php
    admin_layout('Prima attivazione', ob_get_clean(), '', true);
}

function admin_login(): void
{
    if (!empty($_SESSION['uid'])) {
        redirect('/admin');
    }
    $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $ip = ip_hash();
        q('DELETE FROM login_attempts WHERE at < ?', [time() - 900]);
        $tries = (int) q('SELECT COUNT(*) FROM login_attempts WHERE ip = ?', [$ip])->fetchColumn();
        if ($tries >= 8) {
            $error = 'Troppi tentativi. Riprova tra 15 minuti.';
        } else {
            $ok = false;
            foreach (q('SELECT * FROM users') as $u) {
                if (password_verify((string) ($_POST['password'] ?? ''), $u['hash'])) {
                    session_regenerate_id(true);
                    $_SESSION['uid'] = (int) $u['id'];
                    $ok = true;
                    break;
                }
            }
            if ($ok) {
                q('DELETE FROM login_attempts WHERE ip = ?', [$ip]);
                redirect('/admin');
            }
            q('INSERT INTO login_attempts(ip, at) VALUES(?, ?)', [$ip, time()]);
            $error = 'Password non corretta.';
        }
    }
    ob_start();
    ?>
<img class="alogo" src="/assets/img/logo.webp" alt="Al Fogolar" width="600" height="241">
<?php if ($error): ?><p class="flash flash--bad" role="alert"><?= e($error) ?></p><?php endif; ?>
<form method="post" class="aform"><?= csrf_field() ?>
  <label>Password<input type="password" name="password" required autofocus autocomplete="current-password"></label>
  <button class="abtn abtn--main">Entra</button>
</form>
    <?php
    admin_layout('Accesso', ob_get_clean(), '', true);
}

// ---------- Oggi ----------

function admin_today(): void
{
    $today = date('Y-m-d');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        switch ($_POST['do'] ?? '') {
            case 'board':
                $days = max(0, min(14, (int) ($_POST['days'] ?? 0)));
                save_setting('board_text', trim((string) ($_POST['board_text'] ?? '')));
                save_setting('daily_menu', trim((string) ($_POST['daily_menu'] ?? '')));
                save_setting('board_until', date('Y-m-d', strtotime("+$days days")));
                flash('Lavagna pubblicata sul sito.');
                break;
            case 'board_clear':
                save_setting('board_text', '');
                save_setting('daily_menu', '');
                save_setting('board_until', '');
                flash('Lavagna cancellata.');
                break;
            case 'status':
                $mode = in_array($_POST['mode'] ?? '', ['normal', 'full', 'closed'], true) ? $_POST['mode'] : 'normal';
                save_setting('status_mode', $mode);
                save_setting('status_until', $mode === 'normal' ? '' : $today);
                save_setting('status_note', $mode === 'normal' ? '' : trim((string) ($_POST['note'] ?? '')));
                flash($mode === 'normal' ? 'Stato normale: il sito segue gli orari.' : 'Stato aggiornato per oggi. Domani torna normale da solo.');
                break;
            case 'closure_add':
                $from = (string) ($_POST['date_from'] ?? '');
                $to = (string) ($_POST['date_to'] ?? '') ?: $from;
                if (preg_match('/^\d{4}-\d\d-\d\d$/', $from) && preg_match('/^\d{4}-\d\d-\d\d$/', $to) && $to >= $from) {
                    q('INSERT INTO closures(date_from, date_to, note) VALUES(?,?,?)', [$from, $to, trim((string) ($_POST['note'] ?? ''))]);
                    flash('Chiusura aggiunta.');
                } else {
                    flash('Controlla le date della chiusura.');
                }
                break;
            case 'closure_del':
                q('DELETE FROM closures WHERE id = ?', [(int) ($_POST['id'] ?? 0)]);
                flash('Chiusura tolta.');
                break;
        }
        redirect('/admin');
    }
    $boardLive = setting('board_until') >= $today;
    $mode = setting('status_until') >= $today ? setting('status_mode', 'normal') : 'normal';
    $new = q("SELECT * FROM requests WHERE status = 'nuova' ORDER BY created_at DESC LIMIT 5")->fetchAll();
    $closures = q('SELECT * FROM closures WHERE date_to >= ? ORDER BY date_from', [$today])->fetchAll();
    [$state, $key, $time, $note] = live_status();
    require_once __DIR__ . '/i18n.php';
    ob_start();
    ?>
<h1>Buongiorno</h1>
<p class="asub">Sul sito ora si legge: <b class="dot dot--<?= e($state) ?>"><?= e(sprintf(t($key), $time)) ?><?= $note ? ' · ' . e($note) : '' ?></b></p>

<?php if ($new): ?>
<section class="acard acard--hot">
  <h2><?= count($new) ?> <?= count($new) === 1 ? 'richiesta da gestire' : 'richieste da gestire' ?></h2>
  <?php foreach ($new as $r) { echo request_row($r); } ?>
</section>
<?php endif; ?>

<section class="acard">
  <h2>La lavagna di oggi</h2>
  <p class="ahelp">Quello che scrivi appare subito in cima al sito.</p>
  <form method="post" class="aform"><?= csrf_field() ?>
    <input type="hidden" name="do" value="board">
    <label>Cosa vuoi dire?<textarea name="board_text" rows="3" maxlength="400" placeholder="Stasera cjarsons alle erbe. Fuoco acceso dalle 18."><?= $boardLive ? e(setting('board_text')) : '' ?></textarea></label>
    <label>Menu del giorno (facoltativo)<textarea name="daily_menu" rows="4" maxlength="800" placeholder="Un piatto per riga"><?= $boardLive ? e(setting('daily_menu')) : '' ?></textarea></label>
    <label>Resta visibile
      <select name="days">
        <option value="0">Solo oggi</option><option value="1">Oggi e domani</option><option value="3">3 giorni</option><option value="7">Una settimana</option>
      </select>
    </label>
    <div class="arow"><button class="abtn abtn--main">Pubblica</button>
    <?php if ($boardLive && (setting('board_text') !== '' || setting('daily_menu') !== '')): ?>
      <button class="abtn" name="do" value="board_clear">Cancella</button>
    <?php endif; ?></div>
  </form>
</section>

<section class="acard">
  <h2>Com'è oggi?</h2>
  <form method="post" class="aform"><?= csrf_field() ?>
    <input type="hidden" name="do" value="status">
    <label>Nota (facoltativa)<input name="note" maxlength="100" value="<?= $mode !== 'normal' ? e(setting('status_note')) : '' ?>" placeholder="Es. matrimonio, riapriamo domani"></label>
    <div class="choices">
      <button name="mode" value="normal"<?= $mode === 'normal' ? ' aria-pressed="true"' : '' ?>>Normale</button>
      <button name="mode" value="full"<?= $mode === 'full' ? ' aria-pressed="true"' : '' ?>>Al completo</button>
      <button name="mode" value="closed"<?= $mode === 'closed' ? ' aria-pressed="true"' : '' ?>>Chiuso</button>
    </div>
  </form>
</section>

<section class="acard">
  <h2>Ferie e chiusure</h2>
  <p class="ahelp">Il sito avvisa chi sceglie queste date per un tavolo.</p>
  <?php foreach ($closures as $c): ?>
    <form method="post" class="aitem"><?= csrf_field() ?>
      <input type="hidden" name="do" value="closure_del"><input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
      <span><?= e(date('d/m/Y', strtotime($c['date_from']))) ?><?= $c['date_to'] !== $c['date_from'] ? ' → ' . e(date('d/m/Y', strtotime($c['date_to']))) : '' ?><?= $c['note'] ? '<small>' . e($c['note']) . '</small>' : '' ?></span>
      <button class="abtn abtn--sm" data-confirm="Togliere questa chiusura?">Togli</button>
    </form>
  <?php endforeach; ?>
  <form method="post" class="aform"><?= csrf_field() ?>
    <input type="hidden" name="do" value="closure_add">
    <div class="arow arow--2"><label>Dal<input type="date" name="date_from" required min="<?= $today ?>"></label><label>Al<input type="date" name="date_to" min="<?= $today ?>"></label></div>
    <label>Motivo (facoltativo)<input name="note" maxlength="100" placeholder="Ferie"></label>
    <button class="abtn">Aggiungi chiusura</button>
  </form>
</section>
    <?php
    admin_layout('Oggi', ob_get_clean(), '');
}

// ---------- Richieste ----------

function request_when(array $r): string
{
    $s = date('D j/n', strtotime($r['date_from']));
    $s = strtr($s, ['Mon' => 'Lun', 'Tue' => 'Mar', 'Wed' => 'Mer', 'Thu' => 'Gio', 'Fri' => 'Ven', 'Sat' => 'Sab', 'Sun' => 'Dom']);
    if ($r['date_to']) {
        $s .= ' → ' . date('j/n', strtotime($r['date_to']));
    }
    if ($r['slot']) {
        $s .= ', ' . $r['slot'];
    }
    return $s;
}

function request_row(array $r): string
{
    return '<a class="rrow" href="/admin/richieste/' . (int) $r['id'] . '"><span class="tag tag--' . e($r['kind']) . '">' . e(KIND_LABEL[$r['kind']] ?? $r['kind']) . '</span>'
        . '<span><strong>' . e($r['name']) . '</strong><small>' . e(request_when($r)) . ' · ' . (int) $r['people'] . ' pers.</small></span>'
        . '<em class="st st--' . e($r['status']) . '">' . e(STATUS_LABEL[$r['status']] ?? '') . '</em></a>';
}

function admin_requests(): void
{
    $filter = $_GET['stato'] ?? 'aperte';
    $rows = $filter === 'tutte'
        ? q('SELECT * FROM requests ORDER BY created_at DESC LIMIT 200')->fetchAll()
        : q("SELECT * FROM requests WHERE status IN ('nuova', 'risposta') ORDER BY status = 'risposta', date_from LIMIT 200")->fetchAll();
    ob_start();
    ?>
<h1>Richieste</h1>
<div class="choices choices--links">
  <a href="/admin/richieste"<?= $filter !== 'tutte' ? ' aria-current="true"' : '' ?>>Aperte</a>
  <a href="/admin/richieste?stato=tutte"<?= $filter === 'tutte' ? ' aria-current="true"' : '' ?>>Tutte</a>
</div>
<section class="acard">
  <?php foreach ($rows as $r) { echo request_row($r); } ?>
  <?php if (!$rows): ?><p class="ahelp">Nessuna richiesta aperta.</p><?php endif; ?>
</section>
    <?php
    admin_layout('Richieste', ob_get_clean(), 'richieste');
}

function reply_text(array $r, string $outcome): string
{
    $lang = $r['lang'];
    $date = date('d/m/Y', strtotime($r['date_from']));
    $range = $date . ($r['date_to'] ? ' - ' . date('d/m/Y', strtotime($r['date_to'])) : '');
    $n = (int) $r['people'];
    $yes = $outcome === 'si';
    return match ($lang) {
        'en' => "Hello {$r['name']}, this is Al Fogolar in Villa Santina. About your enquiry for $range ($n people): " . ($yes ? 'we have availability and we\'ll be happy to welcome you! Please let us know if this suits you.' : 'unfortunately we are not available on those dates. Would other dates work for you?'),
        'de' => "Guten Tag {$r['name']}, hier ist das Al Fogolar in Villa Santina. Zu Ihrer Anfrage für $range ($n Personen): " . ($yes ? 'Wir haben Platz und freuen uns auf Sie! Bitte geben Sie uns kurz Bescheid.' : 'Leider sind wir an diesen Tagen nicht verfügbar. Würden andere Termine passen?'),
        default => "Buongiorno {$r['name']}, siamo Al Fogolar di Villa Santina. Per la sua richiesta del $range ($n persone): " . ($yes ? 'abbiamo disponibilità, la aspettiamo volentieri! Ci conferma?' : 'purtroppo in quelle date non abbiamo disponibilità. Possono andare bene altre date?'),
    };
}

function admin_request(int $id): void
{
    $r = q('SELECT * FROM requests WHERE id = ?', [$id])->fetch();
    if (!$r) {
        admin_message('Richiesta non trovata', 'Potrebbe essere già stata cancellata.');
        return;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (($_POST['do'] ?? '') === 'delete') {
            q('DELETE FROM requests WHERE id = ?', [$id]);
            flash('Richiesta cancellata.');
            redirect('/admin/richieste');
        }
        $st = (string) ($_POST['status'] ?? '');
        if (isset(STATUS_LABEL[$st])) {
            q('UPDATE requests SET status = ? WHERE id = ?', [$st, $id]);
            flash('Segnata come: ' . STATUS_LABEL[$st]);
        }
        redirect("/admin/richieste/$id");
    }
    $phone = clean_phone((string) $r['phone']);
    ob_start();
    ?>
<a class="aback" href="/admin/richieste">← Richieste</a>
<h1><?= e($r['name']) ?></h1>
<p class="asub"><span class="tag tag--<?= e($r['kind']) ?>"><?= e(KIND_LABEL[$r['kind']]) ?></span> <em class="st st--<?= e($r['status']) ?>"><?= e(STATUS_LABEL[$r['status']]) ?></em></p>
<section class="acard">
  <dl class="adl">
    <div><dt>Quando</dt><dd><?= e(request_when($r)) ?></dd></div>
    <div><dt>Persone</dt><dd><?= (int) $r['people'] ?></dd></div>
    <?php if ($r['phone']): ?><div><dt>Telefono</dt><dd><?= e($r['phone']) ?></dd></div><?php endif; ?>
    <?php if ($r['email']): ?><div><dt>Email</dt><dd><?= e($r['email']) ?></dd></div><?php endif; ?>
    <div><dt>Lingua</dt><dd><?= e(strtoupper($r['lang'])) ?></dd></div>
    <div><dt>Arrivata il</dt><dd><?= e(date('d/m/Y H:i', strtotime($r['created_at']))) ?></dd></div>
  </dl>
  <?php if ($r['note']): ?><blockquote class="anote"><?= nl2br(e($r['note']), false) ?></blockquote><?php endif; ?>
</section>

<section class="acard">
  <h2>Rispondi</h2>
  <p class="ahelp">Il messaggio è già scritto, nella lingua del cliente: puoi correggerlo prima di inviarlo.</p>
  <?php if ($phone !== ''): ?><a class="abtn abtn--main abtn--wide" href="tel:<?= e($phone) ?>">Chiama</a><?php endif; ?>
  <?php foreach (['si' => 'Sì, c\'è posto', 'no' => 'No, non c\'è posto'] as $o => $label): $txt = reply_text($r, $o); ?>
    <h3><?= e($label) ?></h3>
    <div class="arow">
      <?php if ($phone !== ''): ?><a class="abtn" href="https://wa.me/<?= e(ltrim($phone, '+')) ?>?text=<?= e(rawurlencode($txt)) ?>" rel="noopener">WhatsApp</a><?php endif; ?>
      <?php if ($r['email']): ?><a class="abtn" href="mailto:<?= e($r['email']) ?>?subject=<?= e(rawurlencode('Al Fogolar')) ?>&amp;body=<?= e(rawurlencode($txt)) ?>">Email</a><?php endif; ?>
    </div>
  <?php endforeach; ?>
</section>

<section class="acard">
  <h2>Com'è andata?</h2>
  <form method="post" class="choices"><?= csrf_field() ?>
    <?php foreach (STATUS_LABEL as $k => $l): ?>
      <button name="status" value="<?= $k ?>"<?= $r['status'] === $k ? ' aria-pressed="true"' : '' ?>><?= e($l) ?></button>
    <?php endforeach; ?>
  </form>
  <form method="post" class="adanger"><?= csrf_field() ?>
    <button class="alink" name="do" value="delete" data-confirm="Cancellare definitivamente questa richiesta?">Cancella la richiesta</button>
  </form>
</section>
    <?php
    admin_layout($r['name'], ob_get_clean(), 'richieste');
}

// ---------- News ----------

function admin_news(): void
{
    $rows = q("SELECT * FROM news ORDER BY pinned DESC, COALESCE(NULLIF(event_date, ''), created_at) DESC")->fetchAll();
    ob_start();
    ?>
<h1>News ed eventi</h1>
<a class="abtn abtn--main abtn--wide" href="/admin/news/modifica">Scrivi una news</a>
<section class="acard mt">
  <?php foreach ($rows as $n): ?>
    <a class="rrow" href="/admin/news/modifica?id=<?= (int) $n['id'] ?>">
      <span><strong><?= e($n['title_it']) ?></strong><small><?= e(date('d/m/Y', strtotime($n['event_date'] ?: $n['created_at']))) ?><?= $n['pinned'] ? ' · in evidenza' : '' ?></small></span>
      <em class="st st--<?= $n['published'] ? 'confermata' : 'nuova' ?>"><?= $n['published'] ? 'Online' : 'Bozza' ?></em>
    </a>
  <?php endforeach; ?>
  <?php if (!$rows): ?><p class="ahelp">Ancora nessuna news.</p><?php endif; ?>
</section>
    <?php
    admin_layout('News', ob_get_clean(), 'news');
}

function admin_news_edit(): void
{
    $id = (int) ($_GET['id'] ?? 0);
    $n = $id ? q('SELECT * FROM news WHERE id = ?', [$id])->fetch() : null;
    if ($id && !$n) {
        admin_message('News non trovata', 'Potrebbe essere già stata cancellata.');
        return;
    }
    $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (($_POST['do'] ?? '') === 'delete' && $n) {
            delete_upload($n['image']);
            q('DELETE FROM news WHERE id = ?', [$id]);
            flash('News cancellata.');
            redirect('/admin/news');
        }
        $f = [];
        foreach (['title_it', 'title_en', 'title_de', 'body_it', 'body_en', 'body_de'] as $k) {
            $f[$k] = trim((string) ($_POST[$k] ?? ''));
        }
        $date = (string) ($_POST['event_date'] ?? '');
        $date = preg_match('/^\d{4}-\d\d-\d\d$/', $date) ? $date : '';
        $image = $n['image'] ?? null;
        if (!empty($_POST['remove_image'])) {
            delete_upload($image);
            $image = null;
        }
        if (!empty($_FILES['image']['name'])) {
            $new = save_upload($_FILES['image']);
            if ($new) {
                delete_upload($image);
                $image = $new;
            } else {
                $error = 'Foto non valida: usa JPG, PNG o WebP fino a 12 MB.';
            }
        }
        if ($f['title_it'] === '' || $f['body_it'] === '') {
            $error = 'Titolo e testo in italiano sono obbligatori.';
        }
        if ($error === '') {
            $pub = !empty($_POST['published']) ? 1 : 0;
            $pin = !empty($_POST['pinned']) ? 1 : 0;
            if ($n) {
                q('UPDATE news SET title_it=?, title_en=?, title_de=?, body_it=?, body_en=?, body_de=?, image=?, event_date=?, published=?, pinned=? WHERE id=?',
                    [...array_values($f), $image, $date, $pub, $pin, $id]);
            } else {
                $slug = $base = slugify($f['title_it']);
                for ($i = 2; q('SELECT 1 FROM news WHERE slug = ?', [$slug])->fetchColumn(); $i++) {
                    $slug = "$base-$i";
                }
                q('INSERT INTO news(slug, title_it, title_en, title_de, body_it, body_en, body_de, image, event_date, published, pinned, created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)',
                    [$slug, ...array_values($f), $image, $date, $pub, $pin, date('Y-m-d H:i:s')]);
            }
            flash($pub ? 'News pubblicata sul sito.' : 'Bozza salvata.');
            redirect('/admin/news');
        }
        $n = array_merge($n ?? [], $f, ['image' => $image, 'event_date' => $date, 'published' => $_POST['published'] ?? 0, 'pinned' => $_POST['pinned'] ?? 0]);
    }
    ob_start();
    ?>
<a class="aback" href="/admin/news">← News</a>
<h1><?= $id ? 'Modifica news' : 'Nuova news' ?></h1>
<?php if ($error): ?><p class="flash flash--bad" role="alert"><?= e($error) ?></p><?php endif; ?>
<form method="post" enctype="multipart/form-data" class="aform acard"><?= csrf_field() ?>
  <label>Titolo<input name="title_it" required maxlength="140" value="<?= e($n['title_it'] ?? '') ?>"></label>
  <label>Testo<textarea name="body_it" rows="7" required><?= e($n['body_it'] ?? '') ?></textarea></label>
  <label>Data dell'evento (facoltativa)<input type="date" name="event_date" value="<?= e($n['event_date'] ?? '') ?>"></label>
  <?php if (!empty($n['image'])): ?>
    <div class="athumb"><?= pic($n['image'], '', '400px') ?><label class="check"><input type="checkbox" name="remove_image" value="1"> Togli la foto</label></div>
  <?php endif; ?>
  <label>Foto (facoltativa)<input type="file" name="image" accept="image/jpeg,image/png,image/webp"></label>
  <label class="check"><input type="checkbox" name="published" value="1"<?= ($n['published'] ?? 1) ? ' checked' : '' ?>> Visibile sul sito</label>
  <label class="check"><input type="checkbox" name="pinned" value="1"<?= !empty($n['pinned']) ? ' checked' : '' ?>> In evidenza, sempre per prima</label>
  <details>
    <summary>Traduzioni in inglese e tedesco (facoltative)</summary>
    <p class="ahelp">Se le lasci vuote, gli stranieri leggono il testo in italiano.</p>
    <label>Titolo inglese<input name="title_en" maxlength="140" value="<?= e($n['title_en'] ?? '') ?>"></label>
    <label>Testo inglese<textarea name="body_en" rows="5"><?= e($n['body_en'] ?? '') ?></textarea></label>
    <label>Titolo tedesco<input name="title_de" maxlength="140" value="<?= e($n['title_de'] ?? '') ?>"></label>
    <label>Testo tedesco<textarea name="body_de" rows="5"><?= e($n['body_de'] ?? '') ?></textarea></label>
  </details>
  <button class="abtn abtn--main">Salva</button>
</form>
<?php if ($id): ?>
<form method="post" class="adanger"><?= csrf_field() ?><button class="alink" name="do" value="delete" data-confirm="Cancellare questa news?">Cancella la news</button></form>
<?php endif; ?>
    <?php
    admin_layout('News', ob_get_clean(), 'news');
}

// ---------- Menu ----------

function admin_menu(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = (int) ($_POST['id'] ?? 0);
        switch ($_POST['do'] ?? '') {
            case 'toggle':
                q('UPDATE menu_items SET available = 1 - available WHERE id = ?', [$id]);
                break;
            case 'delete':
                q('DELETE FROM menu_items WHERE id = ?', [$id]);
                flash('Piatto cancellato.');
                break;
            case 'save':
                $sec = isset(SECTION_LABEL[$_POST['section'] ?? '']) ? $_POST['section'] : 'primi';
                $name = trim((string) ($_POST['name'] ?? ''));
                $args = [$sec, $name, trim((string) ($_POST['desc_it'] ?? '')), trim((string) ($_POST['desc_en'] ?? '')), trim((string) ($_POST['desc_de'] ?? '')), trim((string) ($_POST['price'] ?? ''))];
                if ($name === '') {
                    flash('Scrivi il nome del piatto.');
                } elseif ($id) {
                    q('UPDATE menu_items SET section=?, name=?, desc_it=?, desc_en=?, desc_de=?, price=? WHERE id=?', [...$args, $id]);
                    flash('Piatto aggiornato.');
                } else {
                    q('INSERT INTO menu_items(section, name, desc_it, desc_en, desc_de, price, sort) VALUES(?,?,?,?,?,?,(SELECT COALESCE(MAX(sort),0)+1 FROM menu_items))', $args);
                    flash('Piatto aggiunto.');
                }
                break;
        }
        redirect('/admin/menu');
    }
    $edit = isset($_GET['id']) ? q('SELECT * FROM menu_items WHERE id = ?', [(int) $_GET['id']])->fetch() : null;
    $bySec = [];
    foreach (q('SELECT * FROM menu_items ORDER BY sort, id') as $it) {
        $bySec[$it['section']][] = $it;
    }
    ob_start();
    ?>
<h1>Menu</h1>
<p class="asub">Finito un piatto? Toccalo per nasconderlo: sparisce subito dal sito.</p>
<?php foreach (SECTION_LABEL as $sec => $label): if (empty($bySec[$sec])) { continue; } ?>
<section class="acard">
  <h2><?= e($label) ?></h2>
  <?php foreach ($bySec[$sec] as $it): ?>
    <div class="aitem<?= $it['available'] ? '' : ' is-off' ?>">
      <a href="/admin/menu?id=<?= (int) $it['id'] ?>#piatto"><?= e($it['name']) ?><?= $it['price'] !== '' ? '<small>€ ' . e($it['price']) . '</small>' : '<small>Senza prezzo</small>' ?></a>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="toggle"><input type="hidden" name="id" value="<?= (int) $it['id'] ?>">
        <button class="switch" role="switch" aria-checked="<?= $it['available'] ? 'true' : 'false' ?>" aria-label="Disponibile: <?= e($it['name']) ?>"></button></form>
    </div>
  <?php endforeach; ?>
</section>
<?php endforeach; ?>

<section class="acard" id="piatto">
  <h2><?= $edit ? 'Modifica piatto' : 'Aggiungi un piatto' ?></h2>
  <form method="post" class="aform"><?= csrf_field() ?>
    <input type="hidden" name="do" value="save"><input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
    <label>Nome<input name="name" required maxlength="120" value="<?= e($edit['name'] ?? '') ?>"></label>
    <div class="arow arow--2">
      <label>Sezione<select name="section"><?php foreach (SECTION_LABEL as $k => $l): ?><option value="<?= $k ?>"<?= ($edit['section'] ?? '') === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
      <label>Prezzo €<input name="price" inputmode="decimal" maxlength="10" value="<?= e($edit['price'] ?? '') ?>" placeholder="12,00"></label>
    </div>
    <label>Descrizione<input name="desc_it" maxlength="200" value="<?= e($edit['desc_it'] ?? '') ?>"></label>
    <details><summary>Traduzioni (facoltative)</summary>
      <label>Inglese<input name="desc_en" maxlength="200" value="<?= e($edit['desc_en'] ?? '') ?>"></label>
      <label>Tedesco<input name="desc_de" maxlength="200" value="<?= e($edit['desc_de'] ?? '') ?>"></label>
    </details>
    <div class="arow"><button class="abtn abtn--main">Salva</button>
    <?php if ($edit): ?><a class="abtn" href="/admin/menu">Annulla</a><?php endif; ?></div>
  </form>
  <?php if ($edit): ?>
  <form method="post" class="adanger"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= (int) $edit['id'] ?>"><button class="alink" data-confirm="Cancellare questo piatto?">Cancella il piatto</button></form>
  <?php endif; ?>
</section>
    <?php
    admin_layout('Menu', ob_get_clean(), 'menu');
}

// ---------- Altro: foto, recensioni, impostazioni ----------

function other_tabs(string $active): string
{
    $tabs = ['foto' => 'Foto', 'recensioni' => 'Recensioni', 'impostazioni' => 'Orari e dati'];
    $out = '<div class="choices choices--links">';
    foreach ($tabs as $k => $l) {
        $out .= '<a href="/admin/' . $k . '"' . ($active === $k ? ' aria-current="true"' : '') . '>' . e($l) . '</a>';
    }
    return $out . '</div>';
}

function admin_photos(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (($_POST['do'] ?? '') === 'delete') {
            $g = q('SELECT * FROM gallery WHERE id = ?', [(int) ($_POST['id'] ?? 0)])->fetch();
            if ($g) {
                delete_upload($g['stem']);
                q('DELETE FROM gallery WHERE id = ?', [$g['id']]);
                flash('Foto tolta.');
            }
        } else {
            $cat = isset(CAT_LABEL[$_POST['cat'] ?? '']) ? $_POST['cat'] : 'ristorante';
            $stem = save_upload($_FILES['photo'] ?? []);
            if ($stem) {
                q('INSERT INTO gallery(stem, cat, alt, sort) VALUES(?,?,?,(SELECT COALESCE(MIN(sort),0)-1 FROM gallery))', [$stem, $cat, trim((string) ($_POST['alt'] ?? ''))]);
                flash('Foto aggiunta, è la prima della sezione.');
            } else {
                flash('Foto non valida: usa JPG, PNG o WebP fino a 12 MB.');
            }
        }
        redirect('/admin/foto');
    }
    ob_start();
    ?>
<h1>Altro</h1>
<?= other_tabs('foto') ?>
<section class="acard">
  <h2>Aggiungi una foto</h2>
  <form method="post" enctype="multipart/form-data" class="aform"><?= csrf_field() ?>
    <input type="hidden" name="do" value="add">
    <label>Foto<input type="file" name="photo" accept="image/jpeg,image/png,image/webp" required></label>
    <label>Dove va?<select name="cat"><?php foreach (CAT_LABEL as $k => $l): ?><option value="<?= $k ?>"><?= e($l) ?></option><?php endforeach; ?></select></label>
    <label>Cosa si vede?<input name="alt" maxlength="140" placeholder="Es. tavola apparecchiata per la festa" required></label>
    <button class="abtn abtn--main">Carica</button>
  </form>
</section>
<?php foreach (CAT_LABEL as $cat => $label): ?>
<section class="acard">
  <h2><?= e($label) ?></h2>
  <div class="agrid">
    <?php foreach (q('SELECT * FROM gallery WHERE cat = ? ORDER BY sort, id', [$cat]) as $g): ?>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= (int) $g['id'] ?>">
        <?= pic($g['stem'], $g['alt'], '160px') ?>
        <button class="alink" data-confirm="Togliere questa foto dal sito?">Togli</button>
      </form>
    <?php endforeach; ?>
  </div>
</section>
<?php endforeach; ?>
    <?php
    admin_layout('Foto', ob_get_clean(), 'altro');
}

function admin_reviews(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = (int) ($_POST['id'] ?? 0);
        if (($_POST['do'] ?? '') === 'delete') {
            q('DELETE FROM reviews WHERE id = ?', [$id]);
            flash('Recensione tolta.');
        } else {
            $author = trim((string) ($_POST['author'] ?? ''));
            $text = trim((string) ($_POST['text'] ?? ''));
            if ($author !== '' && $text !== '') {
                q('INSERT INTO reviews(author, text, sort) VALUES(?,?,(SELECT COALESCE(MIN(sort),0)-1 FROM reviews))', [$author, $text]);
                flash('Recensione aggiunta.');
            }
        }
        redirect('/admin/recensioni');
    }
    ob_start();
    ?>
<h1>Altro</h1>
<?= other_tabs('recensioni') ?>
<section class="acard">
  <h2>Aggiungi una recensione</h2>
  <p class="ahelp">Solo parole vere di clienti veri, con il loro permesso o prese da una recensione pubblica.</p>
  <form method="post" class="aform"><?= csrf_field() ?>
    <input type="hidden" name="do" value="add">
    <label>Chi l'ha scritta<input name="author" required maxlength="80" placeholder="Maria R."></label>
    <label>Testo<textarea name="text" rows="4" required maxlength="600"></textarea></label>
    <button class="abtn abtn--main">Aggiungi</button>
  </form>
</section>
<section class="acard">
  <?php foreach (q('SELECT * FROM reviews ORDER BY sort, id') as $r): ?>
    <form method="post" class="aitem aitem--top"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
      <span><b><?= e($r['author']) ?></b><small><?= e($r['text']) ?></small></span>
      <button class="abtn abtn--sm" data-confirm="Togliere questa recensione?">Togli</button>
    </form>
  <?php endforeach; ?>
</section>
    <?php
    admin_layout('Recensioni', ob_get_clean(), 'altro');
}

function admin_settings(): void
{
    $days = [1 => 'Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato', 'Domenica'];
    $fields = [
        'phone' => ['Telefono', 'tel'], 'whatsapp' => ['WhatsApp (se c\'è, appare sul sito)', 'tel'], 'email' => ['Email pubblica', 'email'],
        'notify_email' => ['Email dove ricevere le richieste', 'email'], 'address' => ['Indirizzo', 'text'], 'city' => ['Località', 'text'],
        'facebook' => ['Pagina Facebook', 'url'], 'tripadvisor' => ['Pagina Tripadvisor', 'url'],
    ];
    $ratings = ['rating_rest' => 'Voto ristorante (su 5)', 'rating_rest_count' => 'Numero di voti', 'rating_rest_source' => 'Fonte del voto'];
    $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        switch ($_POST['do'] ?? '') {
            case 'data':
                foreach (array_keys($fields) + [] as $k) {
                    save_setting($k, trim((string) ($_POST[$k] ?? '')));
                }
                foreach (array_keys($ratings) as $k) {
                    save_setting($k, trim((string) ($_POST[$k] ?? '')));
                }
                flash('Dati salvati.');
                break;
            case 'hours':
                $hours = [];
                $re = '/^([01]\d|2[0-3]):[0-5]\d$/';
                foreach ($days as $d => $_) {
                    $hours[$d] = [];
                    if (!empty($_POST["open$d"])) {
                        foreach ([1, 2] as $s) {
                            $from = (string) ($_POST["from{$d}_$s"] ?? '');
                            $to = (string) ($_POST["to{$d}_$s"] ?? '');
                            if (preg_match($re, $from) && preg_match($re, $to) && $from < $to) {
                                $hours[$d][] = [$from, $to];
                            }
                        }
                    }
                }
                save_setting('hours', json_encode($hours));
                flash('Orari salvati: sito e stato "aperto ora" sono aggiornati.');
                break;
            case 'password':
                $u = q('SELECT * FROM users WHERE id = ?', [$_SESSION['uid']])->fetch();
                $new = (string) ($_POST['new'] ?? '');
                if (!password_verify((string) ($_POST['old'] ?? ''), $u['hash'])) {
                    flash('La password attuale non è corretta.');
                } elseif (strlen($new) < 10) {
                    flash('La nuova password deve avere almeno 10 caratteri.');
                } else {
                    q('UPDATE users SET hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $u['id']]);
                    flash('Password cambiata.');
                }
                break;
        }
        redirect('/admin/impostazioni');
    }
    $hours = hours();
    ob_start();
    ?>
<h1>Altro</h1>
<?= other_tabs('impostazioni') ?>
<section class="acard">
  <h2>Orari settimanali</h2>
  <form method="post" class="aform"><?= csrf_field() ?>
    <input type="hidden" name="do" value="hours">
    <?php foreach ($days as $d => $label): $sl = $hours[$d] ?? []; ?>
      <fieldset class="aday">
        <label class="check"><input type="checkbox" name="open<?= $d ?>" value="1"<?= $sl ? ' checked' : '' ?>> <b><?= $label ?></b></label>
        <div class="aslots">
          <?php foreach ([1, 2] as $s): ?>
            <span><input type="time" name="from<?= $d ?>_<?= $s ?>" aria-label="<?= $label ?> apertura <?= $s ?>" value="<?= e($sl[$s - 1][0] ?? ($s === 1 ? '08:00' : '17:30')) ?>"> – <input type="time" name="to<?= $d ?>_<?= $s ?>" aria-label="<?= $label ?> chiusura <?= $s ?>" value="<?= e($sl[$s - 1][1] ?? ($s === 1 ? '14:00' : '22:00')) ?>"></span>
          <?php endforeach; ?>
        </div>
      </fieldset>
    <?php endforeach; ?>
    <button class="abtn abtn--main">Salva gli orari</button>
  </form>
</section>
<section class="acard">
  <h2>Contatti e profili</h2>
  <form method="post" class="aform"><?= csrf_field() ?>
    <input type="hidden" name="do" value="data">
    <?php foreach ($fields as $k => [$label, $type]): ?>
      <label><?= e($label) ?><input type="<?= $type ?>" name="<?= $k ?>" value="<?= e(setting($k)) ?>"></label>
    <?php endforeach; ?>
    <h3>Il voto mostrato in home</h3>
    <p class="ahelp">Aggiornalo ogni tanto, deve corrispondere a quello pubblico.</p>
    <?php foreach ($ratings as $k => $label): ?>
      <label><?= e($label) ?><input name="<?= $k ?>" value="<?= e(setting($k)) ?>"></label>
    <?php endforeach; ?>
    <button class="abtn abtn--main">Salva</button>
  </form>
</section>
<section class="acard">
  <h2>Cambia password</h2>
  <form method="post" class="aform"><?= csrf_field() ?>
    <input type="hidden" name="do" value="password">
    <label>Password attuale<input type="password" name="old" required autocomplete="current-password"></label>
    <label>Nuova password (almeno 10 caratteri)<input type="password" name="new" required minlength="10" autocomplete="new-password"></label>
    <button class="abtn">Cambia</button>
  </form>
</section>
<form method="post" action="/admin/logout" class="adanger"><?= csrf_field() ?><button class="alink">Esci dal backoffice</button></form>
    <?php
    admin_layout('Orari e dati', ob_get_clean(), 'altro');
}
