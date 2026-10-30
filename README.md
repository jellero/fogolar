# Al Fogolar

Sito dell'Albergo Ristorante Al Fogolar (Villa Santina) con backoffice per i contenuti dinamici.
PHP 8.1+ e SQLite, senza Composer né build: si pubblica copiando i file.

## Struttura

- `public/` document root: `index.php`, risorse, foto caricate in `uploads/`
- `app/` codice: `site.php` (pagine), `admin.php` (backoffice), `i18n.php` (testi IT/EN/DE), `schema.php` (database e contenuti iniziali)
- `data/` database e sessioni, creati al primo avvio, mai versionati
- `tools/assets.py` rigenera foto e texture del fuoco dagli originali

## Provarlo in locale

```bash
php -S localhost:8123 -t public tools/dev-router.php
```

Servono le estensioni `pdo_sqlite`, `gd` (con WebP), `mbstring`, `fileinfo`.

## Pubblicazione su Plesk

1. Fare il backup del sito Drupal attuale.
2. Copiare il repository sul dominio e impostare come document root la cartella `public`.
3. Rendere scrivibili da PHP le cartelle `data/` e `public/uploads/`.
4. Aprire `/admin`: chiede il codice che si trova in `data/setup-token.txt`, poi la password. Il file si cancella da solo.
5. Fare il backup periodico di `data/fogolar.sqlite` e `public/uploads/`.

I vecchi indirizzi (`/ristorante`, `/albergo`, `/appartamenti`, `/contatti`) restano validi, `/it/...` fa redirect 301.

## Il fuoco

Non è un video: `public/assets/fire.js` anima in WebGL la foto reale. Quattro fotogrammi della vecchia GIF vengono deformati e
fusi solo dove la maschera indica movimento, più scintille al tocco. Con "riduci movimento", "risparmio dati", senza WebGL o
su dispositivi troppo lenti resta la foto fissa.
