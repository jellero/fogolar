<?php
// Prova del motore delle notifiche: cifratura (RFC 8291) e firma VAPID (RFC 8292). Non invia nulla e non tocca il database.
declare(strict_types=1);

function setting(string $k, string $d = ''): string
{
    return $GLOBALS['cfg'][$k] ?? $d;
}
function save_setting(string $k, string $v): void
{
    $GLOBALS['cfg'][$k] = $v;
}
$cfg = ['notify_email' => 'prova@example.com'];
require dirname(__DIR__) . '/app/push.php';

$fails = 0;
function ok(string $name, bool $cond): void
{
    global $fails;
    echo ($cond ? 'OK   ' : 'FAIL ') . $name . "\n";
    $fails += $cond ? 0 : 1;
}

/** Decifra come fa il telefono. */
function device_decrypt(string $body, $uaPriv, string $uaPub, string $auth): ?string
{
    $salt = substr($body, 0, 16);
    $idlen = ord($body[20]);
    $asPub = substr($body, 21, $idlen);
    $ct = substr($body, 21 + $idlen);
    $shared = openssl_pkey_derive(ec_public_from_raw($asPub), $uaPriv);
    $ikm = hash_hkdf('sha256', $shared, 32, "WebPush: info\0" . $uaPub . $asPub, $auth);
    $cek = hash_hkdf('sha256', $ikm, 16, "Content-Encoding: aes128gcm\0", $salt);
    $nonce = hash_hkdf('sha256', $ikm, 12, "Content-Encoding: nonce\0", $salt);
    $pt = openssl_decrypt(substr($ct, 0, -16), 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, substr($ct, -16));
    return $pt === false ? null : substr(rtrim($pt, "\0"), 0, -1);
}

function sig_to_der(string $sig): string
{
    $int = function (string $x): string {
        $x = ltrim($x, "\0");
        if ($x === '' || ord($x[0]) & 0x80) {
            $x = "\0" . $x;
        }
        return "\x02" . chr(strlen($x)) . $x;
    };
    $seq = $int(substr($sig, 0, 32)) . $int(substr($sig, 32));
    return "\x30" . chr(strlen($seq)) . $seq;
}

$ua = ec_new_key();
$uaPub = ec_raw_public($ua);
$auth = random_bytes(16);
$msg = json_encode(['title' => 'Nuova richiesta: Tavolo', 'body' => 'Mario Rossi è arrivato', 'url' => '/admin/richieste/1']);

$enc = push_encrypt($msg, b64u_enc($uaPub), b64u_enc($auth));
ok('il telefono decifra il messaggio', device_decrypt($enc, $ua, $uaPub, $auth) === $msg);
ok('una chiave sbagliata non decifra', device_decrypt($enc, ec_new_key(), $uaPub, $auth) === null);
ok('ogni invio ha un sale nuovo', substr($enc, 0, 16) !== substr(push_encrypt($msg, b64u_enc($uaPub), b64u_enc($auth)), 0, 16));

$v = vapid_keys();
ok('chiave pubblica VAPID di 65 byte', strlen(b64u_dec($v['public'])) === 65);
ok('chiavi VAPID stabili', vapid_keys()['public'] === $v['public']);
$h = vapid_header('https://fcm.googleapis.com/fcm/send/abc');
ok('intestazione vapid t=…, k=…', (bool) preg_match('/^vapid t=([\w-]+)\.([\w-]+)\.([\w-]+), k=([\w-]+)$/', $h, $m));
$claims = json_decode(b64u_dec($m[2]), true);
ok('audience uguale al servizio push', $claims['aud'] === 'https://fcm.googleapis.com');
ok('scadenza entro 24 ore', $claims['exp'] > time() && $claims['exp'] <= time() + 86400);
ok('firma valida', openssl_verify("$m[1].$m[2]", sig_to_der(b64u_dec($m[3])), ec_public_from_raw(b64u_dec($m[4])), OPENSSL_ALGO_SHA256) === 1);

exit($fails ? 1 : 0);
