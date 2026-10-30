<?php
declare(strict_types=1);

/** Crea il database al primo avvio con i contenuti del sito attuale. */
function install_schema(PDO $pdo): void
{
    $pdo->exec(<<<'SQL'
    CREATE TABLE settings (key TEXT PRIMARY KEY, value TEXT NOT NULL);
    CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT NOT NULL, hash TEXT NOT NULL);
    CREATE TABLE login_attempts (ip TEXT NOT NULL, at INTEGER NOT NULL);
    CREATE TABLE news (
        id INTEGER PRIMARY KEY, slug TEXT UNIQUE NOT NULL,
        title_it TEXT NOT NULL, title_en TEXT DEFAULT '', title_de TEXT DEFAULT '',
        body_it TEXT NOT NULL, body_en TEXT DEFAULT '', body_de TEXT DEFAULT '',
        image TEXT, event_date TEXT, published INTEGER DEFAULT 1, pinned INTEGER DEFAULT 0, created_at TEXT NOT NULL
    );
    CREATE TABLE menu_items (
        id INTEGER PRIMARY KEY, section TEXT NOT NULL, name TEXT NOT NULL,
        desc_it TEXT DEFAULT '', desc_en TEXT DEFAULT '', desc_de TEXT DEFAULT '',
        price TEXT DEFAULT '', seasonal INTEGER DEFAULT 0, available INTEGER DEFAULT 1, sort INTEGER DEFAULT 0
    );
    CREATE TABLE reviews (id INTEGER PRIMARY KEY, author TEXT NOT NULL, text TEXT NOT NULL, published INTEGER DEFAULT 1, sort INTEGER DEFAULT 0);
    CREATE TABLE gallery (id INTEGER PRIMARY KEY, stem TEXT NOT NULL, cat TEXT NOT NULL, alt TEXT DEFAULT '', sort INTEGER DEFAULT 0);
    CREATE TABLE closures (id INTEGER PRIMARY KEY, date_from TEXT NOT NULL, date_to TEXT NOT NULL, note TEXT DEFAULT '');
    CREATE TABLE requests (
        id INTEGER PRIMARY KEY, kind TEXT NOT NULL, date_from TEXT, date_to TEXT, slot TEXT, people INTEGER,
        name TEXT NOT NULL, phone TEXT, email TEXT, note TEXT, lang TEXT DEFAULT 'it',
        status TEXT DEFAULT 'nuova', ip TEXT, created_at TEXT NOT NULL
    );
    CREATE INDEX idx_requests_status ON requests(status, created_at);
    SQL);

    $slots = [['08:00', '14:00'], ['17:30', '22:00']];
    $hours = [];
    foreach (range(1, 7) as $d) {
        $hours[$d] = $d === 3 ? [] : $slots;
    }
    $settings = [
        'phone' => '+39 0433 750400',
        'whatsapp' => '',
        'email' => 'fogolar.albergo@gmail.com',
        'address' => 'Via C. Battisti, 60',
        'city' => '33029 Villa Santina (UD)',
        'vat' => '02159030309',
        'facebook' => 'https://www.facebook.com/alfogolar/',
        'tripadvisor' => 'https://www.tripadvisor.it/Hotel_Review-g2533452-d5857071-Reviews-or5-Hotel_Al_Fogolar-Villa_Santina_Province_of_Udine_Friuli_Venezia_Giulia.html',
        'privacy_url' => 'https://www.iubenda.com/privacy-policy/85806322',
        'cookie_url' => 'https://www.iubenda.com/privacy-policy/85806322/cookie-policy',
        'notify_email' => 'fogolar.albergo@gmail.com',
        'hours' => json_encode($hours),
        'board_text' => '',
        'board_until' => '',
        'status_mode' => 'normal',
        'status_until' => '',
        'status_note' => '',
        'daily_menu' => '',
        'rating_rest' => '4,8',
        'rating_rest_count' => '368',
        'rating_rest_source' => 'Restaurant Guru',
        'rating_hotel' => '8,6',
        'rating_hotel_count' => '73',
        'rating_hotel_source' => 'Kayak',
    ];
    $st = $pdo->prepare('INSERT INTO settings(key, value) VALUES(?, ?)');
    foreach ($settings as $k => $v) {
        $st->execute([$k, $v]);
    }

    $st = $pdo->prepare('INSERT INTO menu_items(section, name, desc_it, desc_en, desc_de, seasonal, sort) VALUES(?,?,?,?,?,?,?)');
    $menu = [
        ['primi', 'Cjarsons', 'Il piatto simbolo della Carnia', 'The signature filled pasta of Carnia', 'Die typischen gefüllten Teigtaschen Karniens', 0],
        ['primi', 'Gnocchi', 'Fatti secondo la tradizione', 'Made the traditional way', 'Nach traditioneller Art', 0],
        ['secondi', 'Frico', 'Formaggio e patate, come in Friuli', 'Friulian cheese and potato', 'Friaulischer Käse mit Kartoffeln', 0],
        ['secondi', 'Salsiccia in tocj di braide', 'Con polenta morbida', 'With soft polenta', 'Mit weicher Polenta', 0],
        ['secondi', 'Polenta', 'Il contorno di casa', 'The classic side', 'Die klassische Beilage', 0],
        ['taglieri', 'Tagliere di salumi nostrani e formaggio', 'A qualunque ora del giorno', 'At any time of day', 'Zu jeder Tageszeit', 0],
        ['stagione', 'Funghi di bosco', 'Quando il bosco li regala', 'When the woods offer them', 'Wenn der Wald sie schenkt', 1],
        ['stagione', 'Erbe spontanee', 'Nei piatti di primavera', 'In our spring dishes', 'In unseren Frühlingsgerichten', 1],
        ['stagione', 'Verdure del nostro orto', 'Secondo stagione', 'As the season allows', 'Je nach Saison', 1],
    ];
    foreach ($menu as $i => $m) {
        $st->execute([...$m, $i]);
    }

    $st = $pdo->prepare('INSERT INTO reviews(author, text, sort) VALUES(?,?,?)');
    $reviews = [
        ['Sandro A.', 'In occasione del tradizionale incontro dei Vinais (gruppo numeroso) abbiamo cenato al Fogolar. Ci è stato proposto un menu ricco e completo, dall\'aperitivo al sorbetto, apprezzato da tutti i presenti. Ambiente rustico, tradizionale, con un gentilissimo servizio.'],
        ['Luca B.', 'Siamo arrivati tardi, all\'ora di chiusura, ma siamo stati accolti con sorriso e gentilezza. Abbiamo potuto mangiare piatti tipici friulani gustosi e vari. Il servizio è stato eccellente e rapido: un posto che ci ha rallegrato la giornata.'],
        ['Nereo', 'Posto magnifico, tradizionale, accogliente. Pietanze saporite, molto buone e curate dallo chef Alex. Posto da ritornare con amici o per banchetti di lavoro. Bravi e grazie!'],
    ];
    foreach ($reviews as $i => $r) {
        $st->execute([...$r, $i]);
    }

    $st = $pdo->prepare('INSERT INTO gallery(stem, cat, alt, sort) VALUES(?,?,?,?)');
    $photos = [
        ['fogolar-sala', 'ristorante', 'Il fogolar al centro della sala'],
        ['sala', 'ristorante', 'La sala da pranzo apparecchiata, con la carrozza d\'epoca'],
        ['bar', 'ristorante', 'Il bar con la stufa in maiolica'],
        ['sala-2', 'ristorante', 'I quadri alle pareti della sala'],
        ['tavoli', 'ristorante', 'Tavoli apparecchiati con fiori freschi'],
        ['camera-rossa', 'albergo', 'Camera matrimoniale con copriletto rosso'],
        ['camera-1', 'albergo', 'Camera matrimoniale con parete in legno'],
        ['camera-2', 'albergo', 'Camera a due letti'],
        ['camera-3', 'albergo', 'Camera matrimoniale luminosa'],
        ['camera-6', 'albergo', 'Camera con letti singoli e vista sul verde'],
        ['bagno', 'albergo', 'Bagno privato di una camera'],
        ['app-1', 'appartamenti', 'Soggiorno con cucina di un miniappartamento'],
        ['app-2', 'appartamenti', 'Camera matrimoniale di un miniappartamento'],
        ['app-3', 'appartamenti', 'Cucina attrezzata e finestra'],
        ['app-4', 'appartamenti', 'Zona pranzo con balcone'],
        ['app-5', 'appartamenti', 'Piano cottura e dispense'],
        ['app-6', 'appartamenti', 'Camera con pareti in legno'],
        ['app-7', 'appartamenti', 'Bagno con doccia e lavatrice'],
    ];
    foreach ($photos as $i => [$stem, $cat, $alt]) {
        $st->execute(["assets/img/$stem", $cat, $alt, $i]);
    }

    $pdo->prepare('INSERT INTO news(slug, title_it, title_en, title_de, body_it, body_en, body_de, created_at, pinned) VALUES(?,?,?,?,?,?,?,?,0)')
        ->execute([
            'ti-aspettiamo-al-fogolar',
            'Ti aspettiamo al Fogolar!',
            'We look forward to seeing you!',
            'Wir freuen uns auf Sie!',
            'Vieni a trovarci a Villa Santina: tavola carnica, camere e appartamenti nel cuore della Carnia.',
            'Come and see us in Villa Santina: Carnic cooking, rooms and apartments in the heart of Carnia.',
            'Besuchen Sie uns in Villa Santina: karnische Küche, Zimmer und Ferienwohnungen im Herzen Karniens.',
            date('Y-m-d H:i:s'),
        ]);
}
