<?php
declare(strict_types=1);

// Notifiche push sul telefono e sul computer (Web Push con chiavi VAPID), senza librerie esterne.
// RFC 8291 (cifratura aes128gcm) e RFC 8292 (VAPID). Stesso motore di jdevproadmin.

function b64u_enc(string $s): string
{
    return rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
}

function b64u_dec(string $s): string
{
    return (string) base64_decode(strtr($s, '-_', '+/') . str_repeat('=', (4 - strlen($s) % 4) % 4));
}

/** Chiave pubblica P-256 grezza (65 byte, 0x04||X||Y) → oggetto chiave OpenSSL. */
function ec_public_from_raw(string $raw)
{
    $der = hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200') . $raw;
    $pem = "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END PUBLIC KEY-----\n";
    $k = openssl_pkey_get_public($pem);
    if ($k === false) {
        throw new RuntimeException('chiave pubblica del dispositivo non valida');
    }
    return $k;
}

function ec_raw_public($key): string
{
    $d = openssl_pkey_get_details($key);
    return "\x04" . str_pad($d['ec']['x'], 32, "\0", STR_PAD_LEFT) . str_pad($d['ec']['y'], 32, "\0", STR_PAD_LEFT);
}

function ec_new_key()
{
    $k = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
    if ($k === false) {
        throw new RuntimeException('OpenSSL non riesce a creare chiavi EC');
    }
    return $k;
}

/** Chiavi VAPID del backoffice: create una volta e salvate nel database, mai nel repository. */
function vapid_keys(): array
{
    $pem = setting('vapid_private');
    if ($pem === '') {
        $k = ec_new_key();
        openssl_pkey_export($k, $pem);
        save_setting('vapid_private', $pem);
        save_setting('vapid_public', b64u_enc(ec_raw_public($k)));
    }
    return ['private' => openssl_pkey_get_private(setting('vapid_private')), 'public' => setting('vapid_public')];
}

/** Firma ES256: OpenSSL restituisce DER, il JWT vuole r||s da 64 byte. */
function es256_sign(string $data, $priv): string
{
    if (!openssl_sign($data, $der, $priv, OPENSSL_ALGO_SHA256)) {
        throw new RuntimeException('firma VAPID non riuscita');
    }
    $o = 2;
    if (ord($der[1]) & 0x80) {
        $o += ord($der[1]) & 0x7f;
    }
    $rl = ord($der[$o + 1]);
    $r = substr($der, $o + 2, $rl);
    $o += 2 + $rl;
    $sl = ord($der[$o + 1]);
    $s = substr($der, $o + 2, $sl);
    return str_pad(ltrim($r, "\0"), 32, "\0", STR_PAD_LEFT) . str_pad(ltrim($s, "\0"), 32, "\0", STR_PAD_LEFT);
}

function vapid_header(string $endpoint): string
{
    $v = vapid_keys();
    $p = parse_url($endpoint);
    $aud = $p['scheme'] . '://' . $p['host'] . (isset($p['port']) ? ':' . $p['port'] : '');
    $sub = 'mailto:' . (setting('notify_email') ?: setting('email'));
    $jwt = b64u_enc(json_encode(['typ' => 'JWT', 'alg' => 'ES256'])) . '.' . b64u_enc(json_encode(['aud' => $aud, 'exp' => time() + 12 * 3600, 'sub' => $sub]));
    $jwt .= '.' . b64u_enc(es256_sign($jwt, $v['private']));
    return 'vapid t=' . $jwt . ', k=' . $v['public'];
}

/** Cifra il messaggio per un dispositivo (aes128gcm). */
function push_encrypt(string $payload, string $uaPublicB64, string $authB64): string
{
    $uaPub = b64u_dec($uaPublicB64);
    $auth = b64u_dec($authB64);
    if (strlen($uaPub) !== 65 || strlen($auth) !== 16) {
        throw new RuntimeException('chiavi del dispositivo non valide');
    }
    $as = ec_new_key();
    $asPub = ec_raw_public($as);
    $shared = openssl_pkey_derive(ec_public_from_raw($uaPub), $as);
    if ($shared === false) {
        throw new RuntimeException('accordo di chiavi non riuscito');
    }
    $salt = random_bytes(16);
    $ikm = hash_hkdf('sha256', $shared, 32, "WebPush: info\0" . $uaPub . $asPub, $auth);
    $cek = hash_hkdf('sha256', $ikm, 16, "Content-Encoding: aes128gcm\0", $salt);
    $nonce = hash_hkdf('sha256', $ikm, 12, "Content-Encoding: nonce\0", $salt);
    $tag = '';
    $ct = openssl_encrypt($payload . "\x02", 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag);
    return $salt . pack('N', 4096) . chr(65) . $asPub . $ct . $tag;
}

/** Invia a un dispositivo. Restituisce il codice HTTP (201 = consegnata al servizio push). */
function push_send(array $sub, array $message): int
{
    $body = push_encrypt(json_encode($message, JSON_UNESCAPED_UNICODE), $sub['p256dh'], $sub['auth']);
    $headers = ['Content-Type: application/octet-stream', 'Content-Encoding: aes128gcm', 'TTL: 86400', 'Urgency: high',
        'Authorization: ' . vapid_header($sub['endpoint']), 'Content-Length: ' . strlen($body)];
    if (function_exists('curl_init')) {
        $c = curl_init($sub['endpoint']);
        curl_setopt_array($c, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body, CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10, CURLOPT_CONNECTTIMEOUT => 5]);
        curl_exec($c);
        return (int) curl_getinfo($c, CURLINFO_RESPONSE_CODE);
    }
    $ctx = stream_context_create(['http' => ['method' => 'POST', 'header' => implode("\r\n", $headers) . "\r\n", 'content' => $body, 'timeout' => 10, 'ignore_errors' => true]]);
    @file_get_contents($sub['endpoint'], false, $ctx);
    $head = function_exists('http_get_last_response_headers') ? (http_get_last_response_headers() ?? []) : ($http_response_header ?? []);
    return isset($head[0]) && preg_match('/\s(\d{3})\s/', $head[0], $m) ? (int) $m[1] : 0;
}

/** Manda la notifica a tutti i dispositivi iscritti; elimina gli abbonamenti scaduti. */
function push_to_all(string $title, string $body, string $url, string $tag = ''): int
{
    $n = 0;
    foreach (q('SELECT * FROM push_subscriptions')->fetchAll() as $s) {
        try {
            $code = push_send($s, ['title' => $title, 'body' => $body, 'url' => $url, 'tag' => $tag]);
        } catch (Throwable $e) {
            error_log('push: ' . $e->getMessage());
            continue;
        }
        if ($code === 404 || $code === 410) {
            q('DELETE FROM push_subscriptions WHERE id = ?', [$s['id']]); // il dispositivo ha disattivato le notifiche
        } elseif ($code >= 200 && $code < 300) {
            q('UPDATE push_subscriptions SET last_ok_at = ? WHERE id = ?', [date('Y-m-d H:i:s'), $s['id']]);
            $n++;
        } else {
            error_log('push: risposta ' . $code . ' da ' . parse_url($s['endpoint'], PHP_URL_HOST));
        }
    }
    return $n;
}

/** Avviso di nuova richiesta: parte dopo aver risposto all'ospite, che così non aspetta. */
function push_new_request(array $v, bool $stay, int $id): void
{
    if (!extension_loaded('openssl')) {
        return;
    }
    $labels = ['tavolo' => 'Tavolo', 'camera' => 'Camera', 'appartamento' => 'Appartamento', 'festa' => 'Festa'];
    $when = date('d/m', strtotime($v['date_from'])) . ($stay && $v['date_to'] ? ' → ' . date('d/m', strtotime($v['date_to'])) : '') . ($v['kind'] === 'tavolo' ? ', ' . $v['slot'] : '');
    $title = 'Nuova richiesta: ' . ($labels[$v['kind']] ?? $v['kind']);
    $body = $v['name'] . ' · ' . $when . ' · ' . max(1, (int) $v['people']) . ' pers.';
    register_shutdown_function(function () use ($title, $body, $id) {
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        try {
            push_to_all($title, $body, "/admin/richieste/$id", "richiesta-$id");
        } catch (Throwable $e) {
            error_log('push: ' . $e->getMessage());
        }
    });
}
