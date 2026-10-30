<?php
declare(strict_types=1);

const LEGAL_UPDATED = '2026-10-30';

/** Testi legali: descrivono quello che il sito fa davvero. Se cambia il sito, vanno aggiornati. */
function legal_text(string $doc): array
{
    $owner = e(setting('legal_name', 'Albergo Ristorante Al Fogolar di Mainardis Alex'));
    $addr = e(setting('address') . ', ' . setting('city'));
    $vat = e(setting('vat'));
    $mail = e(setting('email'));
    $phone = e(setting('phone'));
    $mailto = '<a href="mailto:' . $mail . '">' . $mail . '</a>';
    $cookieUrl = e(url('cookie'));
    $privacyUrl = e(url('privacy'));
    $garante = '<a href="https://www.garanteprivacy.it" rel="noopener">www.garanteprivacy.it</a>';

    $texts = [
    'privacy' => [
    'it' => ['Privacy Policy', 'Come trattiamo i tuoi dati quando usi questo sito.', [
        ['Chi è il titolare', "<p>Il titolare del trattamento è <strong>$owner</strong>, $addr, P.IVA $vat. Per qualsiasi domanda sui tuoi dati: $mailto, telefono $phone.</p>"],
        ['Quali dati raccogliamo', "<p><strong>Richieste di disponibilità.</strong> Se compili il modulo raccogliamo nome, telefono e/o email, date, numero di persone, tipo di richiesta, lingua del sito e il testo che scrivi. Non scrivere nelle note dati che non servono, in particolare sulla salute, salvo le allergie che vuoi segnalarci.</p><p><strong>Protezione dagli abusi.</strong> Insieme alla richiesta salviamo una versione cifrata e non leggibile del tuo indirizzo IP, per limitare invii ripetuti.</p><p><strong>Dati tecnici.</strong> Il server può registrare nei suoi log indirizzo IP, pagina richiesta, data e ora, per sicurezza e funzionamento.</p><p>Il sito non usa strumenti di statistica, profilazione o pubblicità.</p>"],
        ['Perché e su quale base', "<p>Usiamo i dati della richiesta solo per risponderti e, se lo decidi, organizzare il tuo soggiorno o la tua prenotazione: è un'attività precontrattuale richiesta da te (art. 6.1.b GDPR). I dati tecnici e la protezione dagli abusi si basano sul nostro legittimo interesse alla sicurezza del sito (art. 6.1.f GDPR). Senza un telefono o un'email non possiamo risponderti.</p>"],
        ['Per quanto tempo', "<p>Le richieste vengono cancellate automaticamente dopo 12 mesi, o prima se lo chiedi. Restano valide le conservazioni più lunghe imposte dalla legge se la richiesta diventa un soggiorno, ad esempio per i documenti fiscali.</p>"],
        ['Chi può vedere i dati', "<p>Le persone della nostra struttura incaricate di rispondere. Il fornitore di hosting del sito e i fornitori dei servizi che usiamo per risponderti trattano i dati per nostro conto o come titolari autonomi: la notifica della richiesta arriva alla nostra casella Gmail (Google), le notifiche sui nostri dispositivi passano cifrate dai servizi push di Google, Apple o Mozilla, e se ti rispondiamo su WhatsApp si applicano le condizioni di WhatsApp. Questi fornitori possono trattare dati anche fuori dall'Unione europea, con le garanzie previste dal GDPR. Non vendiamo né cediamo i dati a nessuno.</p>"],
        ['I tuoi diritti', "<p>Puoi chiederci in ogni momento accesso, rettifica, cancellazione, limitazione, portabilità dei dati e opporti al trattamento basato sul legittimo interesse (artt. 15-22 GDPR), scrivendo a $mailto. Puoi anche presentare reclamo al Garante per la protezione dei dati personali: $garante.</p>"],
        ['Link esterni e cookie', "<p>I link a Google Maps, Facebook e Tripadvisor portano a siti di terzi, con le loro regole: fino a quando non li apri non ricevono nulla da questo sito. Per i cookie leggi la <a href=\"$cookieUrl\">Cookie Policy</a>.</p>"],
    ]],
    'en' => ['Privacy Policy', 'How we handle your data when you use this website.', [
        ['Data controller', "<p>The data controller is <strong>$owner</strong>, $addr, Italy, VAT $vat. For any question about your data: $mailto, phone $phone.</p>"],
        ['What we collect', "<p><strong>Availability enquiries.</strong> If you fill in the form we collect your name, phone and/or email, dates, number of people, type of request, site language and the text you write. Please do not include data we do not need in the notes, especially about health, other than allergies you wish to tell us about.</p><p><strong>Abuse protection.</strong> With each enquiry we store an encrypted, unreadable version of your IP address to limit repeated submissions.</p><p><strong>Technical data.</strong> The server may log IP address, requested page, date and time for security and operation.</p><p>This site uses no analytics, profiling or advertising tools.</p>"],
        ['Why, and on what legal basis', "<p>We use enquiry data only to reply and, if you decide to go ahead, to arrange your stay or booking: pre-contractual steps taken at your request (Art. 6(1)(b) GDPR). Technical data and abuse protection rely on our legitimate interest in site security (Art. 6(1)(f) GDPR). Without a phone number or email we cannot reply.</p>"],
        ['How long we keep it', "<p>Enquiries are deleted automatically after 12 months, or sooner if you ask. Longer legal retention applies if an enquiry becomes a stay, for example for tax documents.</p>"],
        ['Who can see it', "<p>Our staff responsible for replying. The site's hosting provider and the providers of the services we use to reply process data on our behalf or as independent controllers: enquiry notifications reach our Gmail mailbox (Google), notifications to our devices pass encrypted through the push services of Google, Apple or Mozilla, and WhatsApp's terms apply if we reply there. These providers may process data outside the European Union, with the safeguards required by the GDPR. We never sell or hand over your data.</p>"],
        ['Your rights', "<p>You may at any time request access, rectification, erasure, restriction and portability, and object to processing based on legitimate interest (Arts. 15-22 GDPR), by writing to $mailto. You may also lodge a complaint with the Italian Data Protection Authority: $garante.</p>"],
        ['External links and cookies', "<p>Links to Google Maps, Facebook and Tripadvisor lead to third-party sites with their own rules: they receive nothing from this site until you open them. For cookies, see the <a href=\"$cookieUrl\">Cookie Policy</a>.</p>"],
    ]],
    'de' => ['Datenschutzerklärung', 'Wie wir Ihre Daten bei der Nutzung dieser Website behandeln.', [
        ['Verantwortlicher', "<p>Verantwortlich ist <strong>$owner</strong>, $addr, Italien, USt-IdNr. $vat. Bei Fragen zu Ihren Daten: $mailto, Telefon $phone.</p>"],
        ['Welche Daten wir erheben', "<p><strong>Verfügbarkeitsanfragen.</strong> Wenn Sie das Formular ausfüllen, erheben wir Name, Telefon und/oder E-Mail, Daten, Personenzahl, Art der Anfrage, Sprache der Website und Ihren Text. Bitte geben Sie in den Notizen keine unnötigen Daten an, insbesondere zur Gesundheit, außer Allergien, die Sie uns mitteilen möchten.</p><p><strong>Schutz vor Missbrauch.</strong> Mit jeder Anfrage speichern wir eine verschlüsselte, nicht lesbare Version Ihrer IP-Adresse, um wiederholte Eingaben zu begrenzen.</p><p><strong>Technische Daten.</strong> Der Server kann IP-Adresse, aufgerufene Seite, Datum und Uhrzeit aus Sicherheits- und Betriebsgründen protokollieren.</p><p>Diese Website verwendet keine Analyse-, Profiling- oder Werbewerkzeuge.</p>"],
        ['Zweck und Rechtsgrundlage', "<p>Anfragedaten verwenden wir nur, um Ihnen zu antworten und, falls Sie sich entscheiden, Ihren Aufenthalt oder Ihre Reservierung zu organisieren: vorvertragliche Maßnahmen auf Ihre Anfrage (Art. 6 Abs. 1 lit. b DSGVO). Technische Daten und Missbrauchsschutz beruhen auf unserem berechtigten Interesse an der Sicherheit der Website (Art. 6 Abs. 1 lit. f DSGVO). Ohne Telefonnummer oder E-Mail können wir nicht antworten.</p>"],
        ['Speicherdauer', "<p>Anfragen werden nach 12 Monaten automatisch gelöscht, auf Wunsch früher. Längere gesetzliche Aufbewahrungsfristen gelten, wenn aus der Anfrage ein Aufenthalt wird, etwa für Steuerunterlagen.</p>"],
        ['Wer die Daten sehen kann', "<p>Unsere für die Antwort zuständigen Mitarbeitenden. Der Hosting-Anbieter der Website und die Anbieter der Dienste, mit denen wir antworten, verarbeiten Daten in unserem Auftrag oder als eigenständige Verantwortliche: Benachrichtigungen gehen an unser Gmail-Postfach (Google), Benachrichtigungen an unsere Geräte laufen verschlüsselt über die Push-Dienste von Google, Apple oder Mozilla, und wenn wir über WhatsApp antworten, gelten dessen Bedingungen. Diese Anbieter können Daten auch außerhalb der Europäischen Union verarbeiten, mit den von der DSGVO vorgesehenen Garantien. Wir verkaufen oder übergeben Ihre Daten niemals.</p>"],
        ['Ihre Rechte', "<p>Sie können jederzeit Auskunft, Berichtigung, Löschung, Einschränkung und Datenübertragbarkeit verlangen und der auf berechtigtem Interesse beruhenden Verarbeitung widersprechen (Art. 15-22 DSGVO), per E-Mail an $mailto. Außerdem können Sie bei der italienischen Datenschutzbehörde Beschwerde einlegen: $garante.</p>"],
        ['Externe Links und Cookies', "<p>Links zu Google Maps, Facebook und Tripadvisor führen zu Websites Dritter mit eigenen Regeln: Sie erhalten von dieser Website nichts, bevor Sie sie öffnen. Zu Cookies lesen Sie die <a href=\"$cookieUrl\">Cookie-Richtlinie</a>.</p>"],
    ]],
    ],
    'cookie' => [
    'it' => ['Cookie Policy', 'Questo sito non usa cookie di profilazione, statistica o pubblicità.', [
        ['In breve', "<p>Navigando nelle pagine pubbliche del sito non viene salvato nessun cookie sul tuo dispositivo. Per questo non ti chiediamo alcun consenso e non trovi un banner. Font e immagini sono ospitati su questo stesso sito, senza collegamenti a servizi di terzi durante la navigazione.</p>"],
        ['L\'unico cookie', "<p>Esiste un solo cookie tecnico, usato soltanto da chi gestisce il sito dopo l'accesso all'area riservata:</p><table class=\"legal__table\"><thead><tr><th>Nome</th><th>A cosa serve</th><th>Durata</th></tr></thead><tbody><tr><td><code>fogolar_admin</code></td><td>Mantiene l'accesso all'area di gestione</td><td>30 giorni</td></tr></tbody></table><p>I visitatori non lo ricevono. I cookie tecnici di questo tipo non richiedono consenso.</p>"],
        ['Siti esterni', "<p>Se apri i link a Google Maps, Facebook o Tripadvisor esci da questo sito: lì valgono le cookie policy di quei servizi.</p>"],
        ['Come gestire i cookie', "<p>Puoi cancellare o bloccare i cookie dalle impostazioni del tuo browser. Per il resto dei dati leggi la <a href=\"$privacyUrl\">Privacy Policy</a>; per domande: $mailto.</p>"],
    ]],
    'en' => ['Cookie Policy', 'This site uses no profiling, analytics or advertising cookies.', [
        ['In short', "<p>Browsing the public pages stores no cookies on your device. That is why we ask for no consent and show no banner. Fonts and images are hosted on this same site, with no connections to third-party services while you browse.</p>"],
        ['The only cookie', "<p>There is one technical cookie, used only by the people who manage the site after logging into the private area:</p><table class=\"legal__table\"><thead><tr><th>Name</th><th>Purpose</th><th>Duration</th></tr></thead><tbody><tr><td><code>fogolar_admin</code></td><td>Keeps the management area logged in</td><td>30 days</td></tr></tbody></table><p>Visitors do not receive it. Technical cookies of this kind need no consent.</p>"],
        ['External sites', "<p>If you open links to Google Maps, Facebook or Tripadvisor you leave this site, and those services' cookie policies apply.</p>"],
        ['Managing cookies', "<p>You can delete or block cookies in your browser settings. For other data see the <a href=\"$privacyUrl\">Privacy Policy</a>; for questions: $mailto.</p>"],
    ]],
    'de' => ['Cookie-Richtlinie', 'Diese Website verwendet keine Profiling-, Analyse- oder Werbe-Cookies.', [
        ['Kurz gesagt', "<p>Beim Besuch der öffentlichen Seiten werden keine Cookies auf Ihrem Gerät gespeichert. Deshalb bitten wir nicht um Einwilligung und zeigen kein Banner. Schriften und Bilder liegen auf dieser Website, ohne Verbindung zu Diensten Dritter beim Surfen.</p>"],
        ['Das einzige Cookie', "<p>Es gibt ein einziges technisches Cookie, das nur die Betreiber der Website nach der Anmeldung im geschützten Bereich erhalten:</p><table class=\"legal__table\"><thead><tr><th>Name</th><th>Zweck</th><th>Dauer</th></tr></thead><tbody><tr><td><code>fogolar_admin</code></td><td>Hält die Anmeldung im Verwaltungsbereich</td><td>30 Tage</td></tr></tbody></table><p>Besucher erhalten es nicht. Technische Cookies dieser Art benötigen keine Einwilligung.</p>"],
        ['Externe Websites', "<p>Wenn Sie Links zu Google Maps, Facebook oder Tripadvisor öffnen, verlassen Sie diese Website; dort gelten deren Cookie-Richtlinien.</p>"],
        ['Cookies verwalten', "<p>Sie können Cookies in den Browsereinstellungen löschen oder blockieren. Zu anderen Daten lesen Sie die <a href=\"$privacyUrl\">Datenschutzerklärung</a>; bei Fragen: $mailto.</p>"],
    ]],
    ],
    ];
    return $texts[$doc][lang()];
}

function legal_page(string $doc): void
{
    [$title, $lead, $sections] = legal_text($doc);
    ob_start();
    ?>
<article class="plain wrap legal">
  <h1><?= e($title) ?></h1>
  <p class="lead"><?= e($lead) ?></p>
  <?php foreach ($sections as [$h, $html]): ?>
    <section><h2><?= e($h) ?></h2><?= $html ?></section>
  <?php endforeach; ?>
  <p class="note"><?= e(t('legal_updated')) ?> <time datetime="<?= LEGAL_UPDATED ?>"><?= date('d/m/Y', strtotime(LEGAL_UPDATED)) ?></time></p>
</article>
    <?php
    layout($doc, $title . ' | Al Fogolar', $lead, ob_get_clean());
}

function page_privacy(): void
{
    legal_page('privacy');
}

function page_cookie(): void
{
    legal_page('cookie');
}
