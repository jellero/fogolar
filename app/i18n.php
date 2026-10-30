<?php
declare(strict_types=1);

const ROUTES = [
    'home' => ['it' => '', 'en' => '', 'de' => ''],
    'ristorante' => ['it' => 'ristorante', 'en' => 'restaurant', 'de' => 'restaurant'],
    'albergo' => ['it' => 'albergo', 'en' => 'hotel', 'de' => 'hotel'],
    'appartamenti' => ['it' => 'appartamenti', 'en' => 'apartments', 'de' => 'ferienwohnungen'],
    'contatti' => ['it' => 'contatti', 'en' => 'contact', 'de' => 'kontakt'],
    'news' => ['it' => 'news', 'en' => 'news', 'de' => 'neuigkeiten'],
    'richiesta' => ['it' => 'richiesta', 'en' => 'enquiry', 'de' => 'anfrage'],
    'privacy' => ['it' => 'privacy', 'en' => 'privacy', 'de' => 'datenschutz'],
    'cookie' => ['it' => 'cookie', 'en' => 'cookies', 'de' => 'cookies'],
];

function lang(?string $set = null): string
{
    static $l = 'it';
    if ($set) {
        $l = $set;
    }
    return $l;
}

function url(string $page, ?string $l = null, string $tail = ''): string
{
    $l ??= lang();
    $p = ROUTES[$page][$l] ?? '';
    $path = ($l === 'it' ? '' : "/$l") . ($p !== '' ? "/$p" : '') . ($tail !== '' ? "/$tail" : '');
    return $path === '' ? '/' : $path;
}

function t(string $key): string
{
    static $d = null;
    $d ??= dictionary();
    return $d[lang()][$key] ?? $d['it'][$key] ?? $key;
}

function loc(array $row, string $field): string
{
    $v = $row[$field . '_' . lang()] ?? '';
    return $v !== '' ? $v : $row[$field . '_it'];
}

function dictionary(): array
{
    return [
    'it' => [
        'nav_rest' => 'Ristorante', 'nav_hotel' => 'Albergo', 'nav_apt' => 'Appartamenti', 'nav_news' => 'News', 'nav_contact' => 'Contatti', 'nav_menu' => 'Menu',
        'cta_ask' => 'Chiedi disponibilità', 'cta_call' => 'Chiama', 'cta_map' => 'Indicazioni', 'cta_ask_short' => 'Richiesta', 'skip' => 'Vai al contenuto', 'close' => 'Chiudi',
        'st_open' => 'Aperto ora · fino alle %s', 'st_closing' => 'Chiude alle %s', 'st_opens' => 'Apre alle %s', 'st_closed_now' => 'Ora chiuso', 'st_closed_today' => 'Oggi chiuso', 'st_full' => 'Oggi al completo',
        'home_title' => 'Albergo Ristorante Al Fogolar · Villa Santina, Carnia', 'home_desc' => 'Cucina carnica, 8 camere e miniappartamenti a Villa Santina, nel cuore della Carnia. Garage moto, deposito bici, feste fino a 120 coperti. Famiglia Mainardis.',
        'hero_kicker' => 'Albergo · Ristorante · Bar a Villa Santina', 'hero_h1' => 'Nel cuore della Carnia, il calore dell’accoglienza.', 'hero_sub' => 'Da noi si mangia, si dorme e ci si scalda attorno al fogolar. Della famiglia Mainardis.', 'hero_hint' => 'Tocca il fuoco',
        'board_h' => 'Oggi al Fogolar', 'board_default' => 'Cjarsons, frico, polenta e quello che la stagione porta in tavola. Chiedi a noi cosa c’è oggi.', 'daily_h' => 'Menu del giorno',
        'intent_h' => 'Cosa ti serve?', 'intent_table' => 'Un tavolo', 'intent_table_s' => 'Pranzo o cena', 'intent_room' => 'Una camera', 'intent_room_s' => '8 camere con bagno', 'intent_apt' => 'Un appartamento', 'intent_apt_s' => 'Brevi o lunghe permanenze', 'intent_party' => 'Una festa', 'intent_party_s' => 'Fino a circa 120 coperti',
        'fire_h' => 'Il fogolar è il cuore di casa', 'fire_p' => 'In Friuli il <em>fogolâr</em> è il focolare attorno a cui si sta insieme. Il nostro è ancora lì, al centro della sala, tra i quadri del pittore Marino Romano che raccontano i luoghi di questa terra.',
        'fact_rooms' => 'camere con bagno privato', 'fact_seats' => 'coperti per feste e ricevimenti', 'fact_rating' => 'su %s voti, %s',
        'rest_h' => 'La tavola carnica', 'rest_p' => 'Cjarsons, gnocchi, frico, salsiccia in tocj di braide, polenta. E poi erbe spontanee, funghi e le verdure del nostro orto, quando è il loro momento. Accanto ai classici della cucina italiana e a un buon bicchiere di vino.', 'rest_cta' => 'Ristorante e menu',
        'stay_h' => 'Dormire in Carnia', 'stay_p' => 'Otto camere sobrie e funzionali con wifi gratuito, oppure un miniappartamento con cucina e parcheggio privato. Piccoli animali benvenuti.', 'stay_cta_h' => 'Le camere', 'stay_cta_a' => 'Gli appartamenti',
        'why_h' => 'Perché vieni in Carnia?', 'why_p' => 'Siamo nel centro della Carnia: da qui si raggiunge tutto. Scegli il tuo motivo.',
        'why_moto' => 'In moto', 'why_moto_t' => 'Garage dedicato alle moto, al sicuro dalle intemperie, e ampio parcheggio. Parti al mattino verso Sauris, Sappada e lo Zoncolan, la sera ti aspetta la cena.',
        'why_bike' => 'In bici', 'why_bike_t' => 'Custodia delle biciclette al coperto. Siamo a un passo da Ovaro, dove comincia la salita più temuta del Giro d’Italia: lo Zoncolan.',
        'why_snow' => 'Sulla neve', 'why_snow_t' => 'In inverno raggiungi facilmente i centri sciistici dello Zoncolan, di Forni di Sopra e di Sappada. Al ritorno, il fuoco è acceso.',
        'why_walk' => 'A piedi', 'why_walk_t' => 'Siamo nel Parco Intercomunale delle Colline Carniche: itinerari di trekking e falesie d’arrampicata proprio nel Comune di Villa Santina.',
        'why_family' => 'In famiglia', 'why_family_t' => 'Terme di Arta, Museo Carnico di Tolmezzo, orologeria di Pesariis, Zuglio archeologica, il maneggio di Villa Santina e i borghi di Sauris e Sappada.',
        'km_note' => 'Distanze stradali indicative da Villa Santina.',
        'reviews_h' => 'Dicono di noi', 'reviews_more' => 'Tutte le recensioni su Tripadvisor',
        'news_h' => 'Novità ed eventi', 'news_all' => 'Tutte le news', 'news_none' => 'Nessuna novità in questo momento.', 'news_desc' => 'Eventi, serate e novità dell’Albergo Ristorante Al Fogolar di Villa Santina.', 'news_back' => 'Tutte le news',
        'rp_title' => 'Ristorante a Villa Santina · Cucina carnica | Al Fogolar', 'rp_desc' => 'Cjarsons, frico, polenta e piatti di stagione con funghi ed erbe spontanee. Feste fino a 120 coperti con parcheggio anche per pullman. Chiuso il mercoledì.', 'rp_h1' => 'Ristorante', 'rp_lead' => 'Le specialità della cucina carnica e i classici italiani, in una sala che racconta la nostra terra.',
        'rp_menu_h' => 'Dal nostro menu', 'rp_menu_note' => 'Il menu cambia con le stagioni: chiedici i piatti del giorno.', 'sec_primi' => 'Primi', 'sec_secondi' => 'Secondi e piatti tipici', 'sec_taglieri' => 'A ogni ora', 'sec_stagione' => 'Di stagione', 'sec_dolci' => 'Dolci', 'sec_antipasti' => 'Antipasti', 'sec_bevande' => 'Bevande',
        'party_h' => 'Feste, gruppi e ricevimenti', 'party_p' => 'Fino a circa 120 coperti, anche con musica dal vivo. Ampio parcheggio, anche per autocorriere. Raccontaci l’occasione e ti proponiamo un menu.', 'party_cta' => 'Chiedi un preventivo',
        'art_h' => 'Una mostra permanente', 'art_p' => 'Nel ristorante esponiamo le opere del pittore Marino Romano: i luoghi della nostra terra, sapientemente dipinti, ad arricchire l’atmosfera.',
        'hp_title' => 'Albergo a Villa Santina, in Carnia · Camere con wifi | Al Fogolar', 'hp_desc' => '8 camere con bagno privato e wifi gratuito nel centro della Carnia. Garage moto, deposito bici, animali piccoli benvenuti. Vicino a Zoncolan, Sauris e Arta Terme.', 'hp_h1' => 'Albergo', 'hp_lead' => 'Otto camere con bagno privato e wifi gratuito, arredate in maniera sobria e funzionale, nel centro della Carnia.',
        'svc_h' => 'Cosa trovi', 'svc_wifi' => 'Wifi gratuito', 'svc_bath' => 'Bagno privato in ogni camera', 'svc_pets' => 'Piccoli animali benvenuti', 'svc_bike' => 'Custodia biciclette al coperto', 'svc_moto' => 'Garage dedicato alle moto', 'svc_park' => 'Ampio parcheggio', 'svc_rest' => 'Ristorante e bar in casa',
        'near_h' => 'Vicino a noi',
        'ap_title' => 'Miniappartamenti a Villa Santina, Carnia | Al Fogolar', 'ap_desc' => 'Miniappartamenti arredati con cucina, bagno, 1 o 2 camere, parcheggio privato e wifi, per brevi o lunghe permanenze senza spese extra.', 'ap_h1' => 'Appartamenti', 'ap_lead' => 'Miniappartamenti arredati e completi di tutto, per brevi o lunghe permanenze, senza spese extra.',
        'apt_kitchen' => 'Cucina attrezzata', 'apt_bath' => 'Bagno privato', 'apt_rooms' => '1 o 2 camere', 'apt_park' => 'Parcheggio privato', 'apt_wifi' => 'Wifi', 'apt_extra' => 'Nessuna spesa extra', 'apt_price' => 'I prezzi variano in base al periodo e alla tipologia di appartamento: scrivici le date e ti rispondiamo con la proposta giusta.',
        'cp_title' => 'Contatti e orari | Albergo Ristorante Al Fogolar, Villa Santina', 'cp_desc' => 'Via C. Battisti 60, Villa Santina (UD). Telefono +39 0433 750400. Orari, chiusure e come raggiungerci.', 'cp_h1' => 'Contatti', 'hours_h' => 'Orari', 'closed' => 'Chiuso', 'closures_h' => 'Prossime chiusure', 'where_h' => 'Dove siamo', 'write_h' => 'Scrivici',
        'd1' => 'Lunedì', 'd2' => 'Martedì', 'd3' => 'Mercoledì', 'd4' => 'Giovedì', 'd5' => 'Venerdì', 'd6' => 'Sabato', 'd7' => 'Domenica',
        'q_title' => 'Richiesta di disponibilità | Al Fogolar', 'q_desc' => 'Chiedi un tavolo, una camera, un appartamento o un preventivo per la tua festa. Ti rispondiamo noi.', 'q_h1' => 'Chiedi disponibilità', 'q_lead' => 'Trenta secondi. Non è ancora una prenotazione: ti rispondiamo noi, di persona.',
        'q_s1' => 'Cosa ti serve?', 'q_s2' => 'Quando e in quanti?', 'q_s3' => 'Come ti ricontattiamo?', 'q_next' => 'Avanti', 'q_back' => 'Indietro', 'q_send' => 'Invia la richiesta', 'q_date' => 'Giorno', 'q_arrive' => 'Arrivo', 'q_leave' => 'Partenza', 'q_slot' => 'Momento', 'q_lunch' => 'Pranzo', 'q_dinner' => 'Cena', 'q_people' => 'Persone', 'q_name' => 'Nome e cognome', 'q_phone' => 'Telefono', 'q_email' => 'Email', 'q_note' => 'Vuoi aggiungere qualcosa?', 'q_note_ph' => 'Allergie, moto o bici da ricoverare, animali, occasione…', 'q_contact_hint' => 'Basta il telefono oppure l’email.',
        'q_privacy' => 'Inviando la richiesta i tuoi dati vengono usati solo per risponderti, come spiegato nella', 'privacy' => 'Privacy Policy', 'cookie' => 'Cookie Policy',
        'q_warn_closed' => 'Attenzione: quel giorno il ristorante è chiuso. Prova con un’altra data.', 'q_err' => 'Controlla i campi evidenziati.', 'q_err_contact' => 'Lasciaci un telefono o un’email.', 'q_err_date' => 'Scegli una data valida.', 'q_err_name' => 'Scrivici il tuo nome.', 'q_err_limit' => 'Troppe richieste ravvicinate. Riprova più tardi o chiamaci.',
        'ok_h' => 'Grazie, ricevuto!', 'ok_p' => 'Ti ricontattiamo appena possibile. Se è per oggi, faresti prima a chiamarci.', 'ok_home' => 'Torna alla home',
        'nf_h' => 'Qui il fuoco è spento', 'nf_p' => 'La pagina che cerchi non c’è più. Torna alla home.',
        'footer_vat' => 'P.IVA', 'footer_family' => 'Della famiglia Mainardis',
        'follow' => 'Seguici su Facebook', 'legal_updated' => 'Ultimo aggiornamento:',
    ],
    'en' => [
        'nav_rest' => 'Restaurant', 'nav_hotel' => 'Hotel', 'nav_apt' => 'Apartments', 'nav_news' => 'News', 'nav_contact' => 'Contact', 'nav_menu' => 'Menu',
        'cta_ask' => 'Check availability', 'cta_call' => 'Call', 'cta_map' => 'Directions', 'cta_ask_short' => 'Enquire', 'skip' => 'Skip to content', 'close' => 'Close',
        'st_open' => 'Open now · until %s', 'st_closing' => 'Closes at %s', 'st_opens' => 'Opens at %s', 'st_closed_now' => 'Closed now', 'st_closed_today' => 'Closed today', 'st_full' => 'Fully booked today',
        'home_title' => 'Al Fogolar Hotel & Restaurant · Villa Santina, Carnia', 'home_desc' => 'Carnic cooking, 8 rooms and small apartments in Villa Santina, in the heart of Carnia, Italy. Motorcycle garage, bike storage, parties for up to 120. The Mainardis family.',
        'hero_kicker' => 'Hotel · Restaurant · Bar in Villa Santina', 'hero_h1' => 'In the heart of Carnia, the warmth of a real welcome.', 'hero_sub' => 'Eat, sleep and warm up around the fogolar. Run by the Mainardis family.', 'hero_hint' => 'Touch the fire',
        'board_h' => 'Today at Al Fogolar', 'board_default' => 'Cjarsons, frico, polenta and whatever the season brings. Ask us what’s on today.', 'daily_h' => 'Today’s menu',
        'intent_h' => 'What do you need?', 'intent_table' => 'A table', 'intent_table_s' => 'Lunch or dinner', 'intent_room' => 'A room', 'intent_room_s' => '8 en-suite rooms', 'intent_apt' => 'An apartment', 'intent_apt_s' => 'Short or long stays', 'intent_party' => 'A party', 'intent_party_s' => 'Up to about 120 guests',
        'fire_h' => 'The fogolar is the heart of the house', 'fire_p' => 'In Friuli the <em>fogolâr</em> is the open hearth people gather around. Ours is still there, in the middle of the room, among the paintings by Marino Romano that portray this land.',
        'fact_rooms' => 'en-suite rooms', 'fact_seats' => 'guests for parties and receptions', 'fact_rating' => 'from %s ratings, %s',
        'rest_h' => 'The Carnic table', 'rest_p' => 'Cjarsons, gnocchi, frico, sausage in tocj di braide, polenta. Wild herbs, mushrooms and vegetables from our garden when their moment comes. Alongside Italian classics and a good glass of wine.', 'rest_cta' => 'Restaurant & menu',
        'stay_h' => 'Stay in Carnia', 'stay_p' => 'Eight simple, practical rooms with free wifi, or a small apartment with kitchen and private parking. Small pets welcome.', 'stay_cta_h' => 'The rooms', 'stay_cta_a' => 'The apartments',
        'why_h' => 'What brings you to Carnia?', 'why_p' => 'We’re right in the centre of Carnia, with everything within reach. Pick your reason.',
        'why_moto' => 'By motorbike', 'why_moto_t' => 'A dedicated, weatherproof motorcycle garage and plenty of parking. Ride out to Sauris, Sappada and Zoncolan in the morning; dinner is waiting in the evening.',
        'why_bike' => 'By bicycle', 'why_bike_t' => 'Indoor bicycle storage. We’re close to Ovaro, where the most feared climb of the Giro d’Italia begins: Monte Zoncolan.',
        'why_snow' => 'On snow', 'why_snow_t' => 'In winter the ski areas of Zoncolan, Forni di Sopra and Sappada are easy to reach. When you get back, the fire is lit.',
        'why_walk' => 'On foot', 'why_walk_t' => 'We’re inside the Colline Carniche park: trekking routes and climbing crags right in the municipality of Villa Santina.',
        'why_family' => 'With family', 'why_family_t' => 'Arta Terme spa, the Carnic Museum in Tolmezzo, the Pesariis clock museum, archaeological Zuglio, Villa Santina’s riding stables and the villages of Sauris and Sappada.',
        'km_note' => 'Approximate road distances from Villa Santina.',
        'reviews_h' => 'What guests say', 'reviews_more' => 'All reviews on Tripadvisor',
        'news_h' => 'News and events', 'news_all' => 'All news', 'news_none' => 'Nothing new right now.', 'news_desc' => 'Events, evenings and news from Al Fogolar in Villa Santina.', 'news_back' => 'All news',
        'rp_title' => 'Restaurant in Villa Santina · Carnic cooking | Al Fogolar', 'rp_desc' => 'Cjarsons, frico, polenta and seasonal dishes with mushrooms and wild herbs. Parties for up to 120 with coach parking. Closed on Wednesdays.', 'rp_h1' => 'Restaurant', 'rp_lead' => 'Carnic specialities and Italian classics, in a dining room that tells the story of this land.',
        'rp_menu_h' => 'From our menu', 'rp_menu_note' => 'The menu follows the seasons: ask us for today’s dishes.', 'sec_primi' => 'First courses', 'sec_secondi' => 'Mains and local dishes', 'sec_taglieri' => 'Any time', 'sec_stagione' => 'In season', 'sec_dolci' => 'Desserts', 'sec_antipasti' => 'Starters', 'sec_bevande' => 'Drinks',
        'party_h' => 'Parties, groups and receptions', 'party_p' => 'Up to about 120 guests, live music possible. Plenty of parking, coaches included. Tell us the occasion and we’ll suggest a menu.', 'party_cta' => 'Ask for a quote',
        'art_h' => 'A permanent exhibition', 'art_p' => 'Our restaurant displays works by the painter Marino Romano: the places of our land, skilfully painted.',
        'hp_title' => 'Hotel in Villa Santina, Carnia · Rooms with wifi | Al Fogolar', 'hp_desc' => '8 en-suite rooms with free wifi in central Carnia. Motorcycle garage, bike storage, small pets welcome. Near Zoncolan, Sauris and Arta Terme.', 'hp_h1' => 'Hotel', 'hp_lead' => 'Eight en-suite rooms with free wifi, simply and practically furnished, in central Carnia.',
        'svc_h' => 'What you’ll find', 'svc_wifi' => 'Free wifi', 'svc_bath' => 'Private bathroom in every room', 'svc_pets' => 'Small pets welcome', 'svc_bike' => 'Indoor bicycle storage', 'svc_moto' => 'Dedicated motorcycle garage', 'svc_park' => 'Plenty of parking', 'svc_rest' => 'Restaurant and bar in house',
        'near_h' => 'Nearby',
        'ap_title' => 'Small apartments in Villa Santina, Carnia | Al Fogolar', 'ap_desc' => 'Furnished small apartments with kitchen, bathroom, 1 or 2 bedrooms, private parking and wifi, for short or long stays with no extra charges.', 'ap_h1' => 'Apartments', 'ap_lead' => 'Fully furnished small apartments for short or long stays, with no extra charges.',
        'apt_kitchen' => 'Equipped kitchen', 'apt_bath' => 'Private bathroom', 'apt_rooms' => '1 or 2 bedrooms', 'apt_park' => 'Private parking', 'apt_wifi' => 'Wifi', 'apt_extra' => 'No extra charges', 'apt_price' => 'Prices vary by season and apartment type: send us your dates and we’ll reply with the right offer.',
        'cp_title' => 'Contact and opening hours | Al Fogolar, Villa Santina', 'cp_desc' => 'Via C. Battisti 60, Villa Santina (UD), Italy. Phone +39 0433 750400. Opening hours, closures and how to reach us.', 'cp_h1' => 'Contact', 'hours_h' => 'Opening hours', 'closed' => 'Closed', 'closures_h' => 'Upcoming closures', 'where_h' => 'Where we are', 'write_h' => 'Write to us',
        'd1' => 'Monday', 'd2' => 'Tuesday', 'd3' => 'Wednesday', 'd4' => 'Thursday', 'd5' => 'Friday', 'd6' => 'Saturday', 'd7' => 'Sunday',
        'q_title' => 'Availability enquiry | Al Fogolar', 'q_desc' => 'Ask for a table, room, apartment or party quote. We’ll reply personally.', 'q_h1' => 'Check availability', 'q_lead' => 'Thirty seconds. This isn’t a booking yet: we’ll reply to you personally.',
        'q_s1' => 'What do you need?', 'q_s2' => 'When, and how many?', 'q_s3' => 'How can we reach you?', 'q_next' => 'Next', 'q_back' => 'Back', 'q_send' => 'Send enquiry', 'q_date' => 'Date', 'q_arrive' => 'Arrival', 'q_leave' => 'Departure', 'q_slot' => 'Meal', 'q_lunch' => 'Lunch', 'q_dinner' => 'Dinner', 'q_people' => 'People', 'q_name' => 'Full name', 'q_phone' => 'Phone', 'q_email' => 'Email', 'q_note' => 'Anything to add?', 'q_note_ph' => 'Allergies, motorbike or bicycle to store, pets, occasion…', 'q_contact_hint' => 'Phone or email is enough.',
        'q_privacy' => 'Your details are used only to reply to you, as explained in the', 'privacy' => 'Privacy Policy', 'cookie' => 'Cookie Policy',
        'q_warn_closed' => 'Please note: the restaurant is closed that day. Try another date.', 'q_err' => 'Please check the highlighted fields.', 'q_err_contact' => 'Leave us a phone number or an email.', 'q_err_date' => 'Choose a valid date.', 'q_err_name' => 'Please tell us your name.', 'q_err_limit' => 'Too many requests. Try later or call us.',
        'ok_h' => 'Thank you, got it!', 'ok_p' => 'We’ll get back to you as soon as we can. If it’s for today, it’s quicker to call.', 'ok_home' => 'Back to home',
        'nf_h' => 'The fire’s out here', 'nf_p' => 'The page you’re looking for has gone. Head back home.',
        'footer_vat' => 'VAT', 'footer_family' => 'Run by the Mainardis family',
        'follow' => 'Follow us on Facebook', 'legal_updated' => 'Last updated:',
    ],
    'de' => [
        'nav_rest' => 'Restaurant', 'nav_hotel' => 'Hotel', 'nav_apt' => 'Ferienwohnungen', 'nav_news' => 'Neuigkeiten', 'nav_contact' => 'Kontakt', 'nav_menu' => 'Menü',
        'cta_ask' => 'Verfügbarkeit anfragen', 'cta_call' => 'Anrufen', 'cta_map' => 'Anfahrt', 'cta_ask_short' => 'Anfrage', 'skip' => 'Zum Inhalt', 'close' => 'Schließen',
        'st_open' => 'Jetzt geöffnet · bis %s', 'st_closing' => 'Schließt um %s', 'st_opens' => 'Öffnet um %s', 'st_closed_now' => 'Jetzt geschlossen', 'st_closed_today' => 'Heute geschlossen', 'st_full' => 'Heute ausgebucht',
        'home_title' => 'Hotel Restaurant Al Fogolar · Villa Santina, Karnien', 'home_desc' => 'Karnische Küche, 8 Zimmer und kleine Ferienwohnungen in Villa Santina, im Herzen Karniens. Motorradgarage, Fahrradraum, Feiern bis 120 Personen. Familie Mainardis.',
        'hero_kicker' => 'Hotel · Restaurant · Bar in Villa Santina', 'hero_h1' => 'Im Herzen Karniens, die Wärme einer echten Gastfreundschaft.', 'hero_sub' => 'Bei uns isst, schläft und wärmt man sich am Fogolar. Familie Mainardis.', 'hero_hint' => 'Berühre das Feuer',
        'board_h' => 'Heute im Fogolar', 'board_default' => 'Cjarsons, Frico, Polenta und was die Saison bringt. Fragen Sie uns, was es heute gibt.', 'daily_h' => 'Tagesmenü',
        'intent_h' => 'Was brauchen Sie?', 'intent_table' => 'Einen Tisch', 'intent_table_s' => 'Mittag- oder Abendessen', 'intent_room' => 'Ein Zimmer', 'intent_room_s' => '8 Zimmer mit Bad', 'intent_apt' => 'Eine Wohnung', 'intent_apt_s' => 'Kurz oder lang', 'intent_party' => 'Eine Feier', 'intent_party_s' => 'Bis ca. 120 Personen',
        'fire_h' => 'Der Fogolar ist das Herz des Hauses', 'fire_p' => 'In Friaul ist der <em>Fogolâr</em> die offene Feuerstelle, um die man zusammenkommt. Unsere steht noch immer mitten im Raum, zwischen den Bildern des Malers Marino Romano, die diese Landschaft zeigen.',
        'fact_rooms' => 'Zimmer mit eigenem Bad', 'fact_seats' => 'Plätze für Feiern und Empfänge', 'fact_rating' => 'aus %s Bewertungen, %s',
        'rest_h' => 'Der karnische Tisch', 'rest_p' => 'Cjarsons, Gnocchi, Frico, Wurst in Tocj di Braide, Polenta. Dazu Wildkräuter, Pilze und Gemüse aus dem eigenen Garten, wenn ihre Zeit gekommen ist. Neben italienischen Klassikern und einem guten Glas Wein.', 'rest_cta' => 'Restaurant & Speisekarte',
        'stay_h' => 'Übernachten in Karnien', 'stay_p' => 'Acht schlichte, praktische Zimmer mit kostenlosem WLAN oder eine kleine Wohnung mit Küche und eigenem Parkplatz. Kleine Haustiere willkommen.', 'stay_cta_h' => 'Die Zimmer', 'stay_cta_a' => 'Die Wohnungen',
        'why_h' => 'Was führt Sie nach Karnien?', 'why_p' => 'Wir liegen mitten in Karnien, alles ist gut erreichbar. Wählen Sie Ihren Grund.',
        'why_moto' => 'Mit dem Motorrad', 'why_moto_t' => 'Eigene wettergeschützte Motorradgarage und viele Parkplätze. Morgens nach Sauris, Sappada und auf den Zoncolan, abends wartet das Essen.',
        'why_bike' => 'Mit dem Rad', 'why_bike_t' => 'Überdachter Fahrradraum. Wir sind ganz nah bei Ovaro, wo der gefürchtetste Anstieg des Giro d’Italia beginnt: der Monte Zoncolan.',
        'why_snow' => 'Im Schnee', 'why_snow_t' => 'Im Winter erreichen Sie bequem die Skigebiete Zoncolan, Forni di Sopra und Sappada. Wenn Sie zurückkommen, brennt das Feuer.',
        'why_walk' => 'Zu Fuß', 'why_walk_t' => 'Wir liegen im Park der Colline Carniche: Wanderwege und Klettergärten direkt in der Gemeinde Villa Santina.',
        'why_family' => 'Mit der Familie', 'why_family_t' => 'Therme Arta, Karnisches Museum in Tolmezzo, Uhrenmuseum Pesariis, das archäologische Zuglio, der Reitstall von Villa Santina und die Dörfer Sauris und Sappada.',
        'km_note' => 'Ungefähre Straßenentfernungen ab Villa Santina.',
        'reviews_h' => 'Das sagen unsere Gäste', 'reviews_more' => 'Alle Bewertungen auf Tripadvisor',
        'news_h' => 'Neuigkeiten und Events', 'news_all' => 'Alle Neuigkeiten', 'news_none' => 'Im Moment gibt es nichts Neues.', 'news_desc' => 'Events, Abende und Neuigkeiten aus dem Al Fogolar in Villa Santina.', 'news_back' => 'Alle Neuigkeiten',
        'rp_title' => 'Restaurant in Villa Santina · Karnische Küche | Al Fogolar', 'rp_desc' => 'Cjarsons, Frico, Polenta und saisonale Gerichte mit Pilzen und Wildkräutern. Feiern bis 120 Personen, Busparkplatz. Mittwochs geschlossen.', 'rp_h1' => 'Restaurant', 'rp_lead' => 'Karnische Spezialitäten und italienische Klassiker, in einem Saal, der von dieser Landschaft erzählt.',
        'rp_menu_h' => 'Aus unserer Karte', 'rp_menu_note' => 'Die Karte folgt den Jahreszeiten: Fragen Sie nach den Tagesgerichten.', 'sec_primi' => 'Erste Gänge', 'sec_secondi' => 'Hauptgerichte und Regionales', 'sec_taglieri' => 'Zu jeder Zeit', 'sec_stagione' => 'Saisonal', 'sec_dolci' => 'Desserts', 'sec_antipasti' => 'Vorspeisen', 'sec_bevande' => 'Getränke',
        'party_h' => 'Feiern, Gruppen und Empfänge', 'party_p' => 'Bis ca. 120 Personen, auch mit Live-Musik. Viele Parkplätze, auch für Busse. Erzählen Sie uns vom Anlass, wir schlagen ein Menü vor.', 'party_cta' => 'Angebot anfragen',
        'art_h' => 'Eine Dauerausstellung', 'art_p' => 'Im Restaurant zeigen wir Werke des Malers Marino Romano: die Orte unserer Heimat, meisterhaft gemalt.',
        'hp_title' => 'Hotel in Villa Santina, Karnien · Zimmer mit WLAN | Al Fogolar', 'hp_desc' => '8 Zimmer mit Bad und kostenlosem WLAN im Zentrum Karniens. Motorradgarage, Fahrradraum, kleine Haustiere willkommen. Nahe Zoncolan, Sauris und Arta Terme.', 'hp_h1' => 'Hotel', 'hp_lead' => 'Acht Zimmer mit eigenem Bad und kostenlosem WLAN, schlicht und praktisch eingerichtet, im Zentrum Karniens.',
        'svc_h' => 'Das finden Sie bei uns', 'svc_wifi' => 'Kostenloses WLAN', 'svc_bath' => 'Eigenes Bad in jedem Zimmer', 'svc_pets' => 'Kleine Haustiere willkommen', 'svc_bike' => 'Überdachter Fahrradraum', 'svc_moto' => 'Eigene Motorradgarage', 'svc_park' => 'Viele Parkplätze', 'svc_rest' => 'Restaurant und Bar im Haus',
        'near_h' => 'In der Nähe',
        'ap_title' => 'Kleine Ferienwohnungen in Villa Santina, Karnien | Al Fogolar', 'ap_desc' => 'Möblierte kleine Ferienwohnungen mit Küche, Bad, 1 oder 2 Schlafzimmern, eigenem Parkplatz und WLAN, für kurze oder lange Aufenthalte ohne Zusatzkosten.', 'ap_h1' => 'Ferienwohnungen', 'ap_lead' => 'Komplett möblierte kleine Wohnungen für kurze oder lange Aufenthalte, ohne Zusatzkosten.',
        'apt_kitchen' => 'Ausgestattete Küche', 'apt_bath' => 'Eigenes Bad', 'apt_rooms' => '1 oder 2 Schlafzimmer', 'apt_park' => 'Eigener Parkplatz', 'apt_wifi' => 'WLAN', 'apt_extra' => 'Keine Zusatzkosten', 'apt_price' => 'Die Preise richten sich nach Saison und Wohnungstyp: Schreiben Sie uns Ihre Daten, wir antworten mit dem passenden Angebot.',
        'cp_title' => 'Kontakt und Öffnungszeiten | Al Fogolar, Villa Santina', 'cp_desc' => 'Via C. Battisti 60, Villa Santina (UD), Italien. Telefon +39 0433 750400. Öffnungszeiten, Ruhetage und Anfahrt.', 'cp_h1' => 'Kontakt', 'hours_h' => 'Öffnungszeiten', 'closed' => 'Geschlossen', 'closures_h' => 'Nächste Schließtage', 'where_h' => 'So finden Sie uns', 'write_h' => 'Schreiben Sie uns',
        'd1' => 'Montag', 'd2' => 'Dienstag', 'd3' => 'Mittwoch', 'd4' => 'Donnerstag', 'd5' => 'Freitag', 'd6' => 'Samstag', 'd7' => 'Sonntag',
        'q_title' => 'Verfügbarkeitsanfrage | Al Fogolar', 'q_desc' => 'Fragen Sie nach einem Tisch, Zimmer, einer Wohnung oder einem Angebot für Ihre Feier. Wir antworten persönlich.', 'q_h1' => 'Verfügbarkeit anfragen', 'q_lead' => 'Dreißig Sekunden. Noch keine Buchung: Wir antworten Ihnen persönlich.',
        'q_s1' => 'Was brauchen Sie?', 'q_s2' => 'Wann und wie viele?', 'q_s3' => 'Wie erreichen wir Sie?', 'q_next' => 'Weiter', 'q_back' => 'Zurück', 'q_send' => 'Anfrage senden', 'q_date' => 'Datum', 'q_arrive' => 'Anreise', 'q_leave' => 'Abreise', 'q_slot' => 'Mahlzeit', 'q_lunch' => 'Mittagessen', 'q_dinner' => 'Abendessen', 'q_people' => 'Personen', 'q_name' => 'Vor- und Nachname', 'q_phone' => 'Telefon', 'q_email' => 'E-Mail', 'q_note' => 'Möchten Sie etwas ergänzen?', 'q_note_ph' => 'Allergien, Motorrad oder Fahrrad, Haustiere, Anlass…', 'q_contact_hint' => 'Telefon oder E-Mail genügt.',
        'q_privacy' => 'Ihre Daten werden nur zur Beantwortung verwendet, wie erläutert in der', 'privacy' => 'Datenschutzerklärung', 'cookie' => 'Cookie-Richtlinie',
        'q_warn_closed' => 'Achtung: An diesem Tag ist das Restaurant geschlossen. Bitte wählen Sie ein anderes Datum.', 'q_err' => 'Bitte prüfen Sie die markierten Felder.', 'q_err_contact' => 'Bitte Telefon oder E-Mail angeben.', 'q_err_date' => 'Bitte ein gültiges Datum wählen.', 'q_err_name' => 'Bitte geben Sie Ihren Namen an.', 'q_err_limit' => 'Zu viele Anfragen. Bitte später erneut versuchen oder anrufen.',
        'ok_h' => 'Danke, angekommen!', 'ok_p' => 'Wir melden uns so bald wie möglich. Wenn es um heute geht, rufen Sie am besten an.', 'ok_home' => 'Zur Startseite',
        'nf_h' => 'Hier ist das Feuer aus', 'nf_p' => 'Diese Seite gibt es nicht mehr. Zurück zur Startseite.',
        'footer_vat' => 'USt-IdNr.', 'footer_family' => 'Familie Mainardis',
        'follow' => 'Folgen Sie uns auf Facebook', 'legal_updated' => 'Letzte Aktualisierung:',
    ],
    ];
}
