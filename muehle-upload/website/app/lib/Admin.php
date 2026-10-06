<?php
declare(strict_types=1);

namespace Muehle;

use RuntimeException;

/**
 * Controller des Redaktionssystems (/admin/).
 */
final class Admin
{
    private const PUBLIC_ROUTES = ['login', 'totp', 'setup', 'hook'];
    private const ADMIN_ROUTES = ['settings', 'users', 'user-add', 'user-delete', 'backups', 'restore', 'backup-download', 'log'];

    public static function run(): void
    {
        self::headers();
        Auth::startSession();

        $route = preg_replace('~[^a-z-]~', '', (string) ($_GET['r'] ?? 'dashboard')) ?: 'dashboard';
        $post = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';

        try {
            if ($route === 'hook') {
                self::hook();
                return;
            }
            if (!Auth::users() && $route !== 'setup') {
                self::redirect('setup');
            }
            if (!in_array($route, self::PUBLIC_ROUTES, true) && !Auth::user()) {
                self::redirect('login');
            }
            if (in_array($route, self::ADMIN_ROUTES, true) && !Auth::isAdmin()) {
                throw new RuntimeException('Dafür benötigen Sie Administrator-Rechte.');
            }
            if ($post) {
                Auth::checkCsrf();
            }

            $method = 'r' . str_replace(' ', '', ucwords(str_replace('-', ' ', $route)));
            if (!method_exists(self::class, $method)) {
                http_response_code(404);
                self::view('message', ['title' => 'Nicht gefunden', 'message' => 'Diese Seite gibt es im Redaktionssystem nicht.']);
                return;
            }
            self::$method($post);
        } catch (RuntimeException $e) {
            if (self::wantsJson()) {
                self::json(['ok' => false, 'message' => $e->getMessage()], 400);
            }
            self::flash('error', $e->getMessage());
            if ($post && !in_array($route, ['login', 'totp', 'setup'], true)) {
                $back = $_SERVER['HTTP_REFERER'] ?? '';
                if ($back && parse_url($back, PHP_URL_HOST) === ($_SERVER['HTTP_HOST'] ?? '')) {
                    header('Location: ' . $back, true, 303);
                    exit;
                }
            }
            $view = match ($route) {
                'login' => 'login',
                'totp' => 'totp',
                'setup' => 'setup',
                default => 'message',
            };
            self::view($view, ['title' => 'Hinweis', 'message' => '']);
        }
    }

    // ================================================================ Infrastruktur

    private static function headers(): void
    {
        header("Content-Security-Policy: default-src 'self'; img-src 'self' data: blob:; style-src 'self'; script-src 'self'; font-src 'self'; connect-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'; object-src 'none'");
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: same-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(), interest-cohort=()');
        header('Cache-Control: no-store, max-age=0');
        header('X-Robots-Tag: noindex, nofollow');
        if (Auth::isHttps()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }

    public static function link(string $route, array $params = []): string
    {
        return '?' . http_build_query(['r' => $route] + $params);
    }

    private static function redirect(string $route, array $params = []): never
    {
        header('Location: ' . self::link($route, $params), true, 303);
        exit;
    }

    public static function flash(string $type, string $msg): void
    {
        $_SESSION['flash'][] = [$type, $msg];
    }

    private static function wantsJson(): bool
    {
        return str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
    }

    private static function json(array $data, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    private static function view(string $name, array $vars = []): void
    {
        $vars['user'] = Auth::user();
        $vars['flash'] = $_SESSION['flash'] ?? [];
        $_SESSION['flash'] = [];
        $vars['csrf'] = Auth::csrfToken();
        $vars['route'] = preg_replace('~[^a-z-]~', '', (string) ($_GET['r'] ?? 'dashboard'));
        $vars['content'] = Site::render('admin/' . $name, $vars);
        echo Site::render('admin/layout', $vars);
    }

    /** Seite neu erzeugen und Ergebnis melden. */
    private static function publish(string $what): void
    {
        Site::$target = 'server';
        Site::$basePath = rtrim((string) config('base_path', ''), '/');
        $files = Site::build(PUBLIC_DIR);
        $u = Auth::user()['username'] ?? '?';
        app_log('cms', "$what gespeichert und veröffentlicht", ['user' => $u, 'files' => count($files)]);
        self::flash('success', "$what gespeichert. Die Webseite wurde aktualisiert.");
    }

    private static function input(string $key): array
    {
        $v = $_POST[$key] ?? [];
        return is_array($v) ? $v : [];
    }

    // ================================================================ Anmeldung

    private static function rSetup(bool $post): void
    {
        if (Auth::users()) {
            self::redirect('login');
        }
        $key = (string) config('setup_key', '');
        if ($post) {
            if (strlen($key) < 16) {
                throw new RuntimeException('Bitte zuerst in app/config.php einen „setup_key“ mit mindestens 16 Zeichen eintragen.');
            }
            if (!hash_equals($key, (string) ($_POST['setup_key'] ?? ''))) {
                usleep(500_000);
                throw new RuntimeException('Einrichtungsschlüssel ist falsch.');
            }
            if (($_POST['password'] ?? '') !== ($_POST['password2'] ?? '')) {
                throw new RuntimeException('Die Passwörter stimmen nicht überein.');
            }
            Auth::createUser((string) ($_POST['username'] ?? ''), (string) ($_POST['password'] ?? ''), 'admin', (string) ($_POST['name'] ?? ''));
            app_log('auth', 'Erstes Admin-Konto angelegt', ['user' => $_POST['username'] ?? '']);
            self::flash('success', 'Konto angelegt. Bitte melden Sie sich jetzt an. Tipp: Aktivieren Sie danach die 2-Faktor-Anmeldung unter „Mein Konto“.');
            self::redirect('login');
        }
        self::view('setup', ['keyMissing' => strlen($key) < 16]);
    }

    private static function rLogin(bool $post): void
    {
        if (Auth::user()) {
            self::redirect('dashboard');
        }
        if ($post) {
            $res = Auth::attempt((string) ($_POST['username'] ?? ''), (string) ($_POST['password'] ?? ''));
            self::redirect($res === 'totp' ? 'totp' : 'dashboard');
        }
        self::view('login');
    }

    private static function rTotp(bool $post): void
    {
        if (empty($_SESSION['pending_user'])) {
            self::redirect('login');
        }
        if ($post) {
            Auth::attemptTotp((string) ($_POST['code'] ?? ''));
            self::redirect('dashboard');
        }
        self::view('totp');
    }

    private static function rLogout(bool $post): void
    {
        if (!$post) {
            self::redirect('dashboard');
        }
        app_log('auth', 'Logout', ['user' => Auth::user()['username'] ?? '']);
        Auth::logout();
        Auth::startSession();
        self::flash('success', 'Sie wurden abgemeldet.');
        self::redirect('login');
    }

    // ================================================================ Übersicht

    private static function rDashboard(bool $post): void
    {
        $news = (array) Store::get('news', []);
        $today = date('Y-m-d');
        $active = array_filter($news, static fn($n) => !empty($n['active']) && (empty($n['until']) || $n['until'] >= $today) && (empty($n['from']) || $n['from'] <= $today));
        $expired = array_filter($news, static fn($n) => !empty($n['until']) && $n['until'] < $today);
        self::view('dashboard', [
            'title' => 'Übersicht',
            'pages' => Site::pages(),
            'activeNews' => $active,
            'expiredNews' => count($expired),
            'media' => count(Media::all()),
            'hours' => (array) Store::get('hours', []),
            'lastBuild' => is_file(PUBLIC_DIR . '/index.html') ? filemtime(PUBLIC_DIR . '/index.html') : null,
        ]);
    }

    private static function rRebuild(bool $post): void
    {
        if ($post) {
            self::publish('Alle Seiten');
        }
        self::redirect('dashboard');
    }

    // ================================================================ Seiten

    private static function rPages(bool $post): void
    {
        self::view('pages', ['title' => 'Seiten', 'pages' => Site::pages()]);
    }

    private static function pageName(string $slug): string
    {
        if (!preg_match('~^[a-z0-9-]{1,60}$~', $slug)) {
            throw new RuntimeException('Ungültige Seite.');
        }
        return 'pages/' . $slug;
    }

    private static function rPage(bool $post): void
    {
        $slug = (string) ($_GET['id'] ?? '');
        $name = self::pageName($slug);
        if (!Store::exists($name)) {
            throw new RuntimeException('Seite nicht gefunden.');
        }
        $page = (array) Store::get($name);

        if ($post) {
            $in = self::input('page');
            $clean = ['slug' => $slug] + Schema::cleanFields(Schema::pageFields(), $in);
            if ($clean['title'] === '') {
                throw new RuntimeException('Bitte einen Seitentitel angeben.');
            }
            $clean['hero'] = Schema::cleanFields(Schema::heroFields(), (array) ($in['hero'] ?? []));
            $buttons = [];
            foreach ((array) ($in['hero']['buttons'] ?? []) as $b) {
                $label = Schema::cleanValue('text', $b['label'] ?? '');
                $link = Schema::cleanLink((string) ($b['link'] ?? ''));
                if ($label !== '' && $link !== '') {
                    $buttons[] = ['label' => $label, 'link' => $link];
                }
            }
            $clean['hero']['buttons'] = array_slice($buttons, 0, 2);
            $clean['blocks'] = [];
            foreach (self::input('blocks') as $b) {
                if (is_array($b) && ($cb = Schema::cleanBlock($b))) {
                    $clean['blocks'][] = $cb;
                }
            }
            if ($slug === 'start') {
                $clean['draft'] = false;
            }
            $clean['updated'] = date('c');
            Store::put($name, $clean);
            self::publish('Seite „' . $clean['title'] . '“');
            self::redirect('page', ['id' => $slug]);
        }

        self::view('page', [
            'title' => 'Seite bearbeiten: ' . ($page['title'] ?? $slug),
            'page' => $page,
            'hoursGroups' => (array) Store::get('hours', []),
        ]);
    }

    private static function rPageNew(bool $post): void
    {
        if (!$post) {
            self::redirect('pages');
        }
        $title = Schema::cleanValue('text', $_POST['title'] ?? '');
        $slug = slugify((string) ($_POST['slug'] ?? '') ?: $title);
        if ($title === '' || $slug === '') {
            throw new RuntimeException('Bitte einen Titel angeben.');
        }
        if (in_array($slug, Site::RESERVED_SLUGS, true) || $slug === 'start') {
            throw new RuntimeException('Diese Adresse ist reserviert. Bitte eine andere wählen.');
        }
        $name = self::pageName($slug);
        if (Store::exists($name)) {
            throw new RuntimeException('Eine Seite mit dieser Adresse existiert bereits.');
        }
        Store::put($name, [
            'slug' => $slug, 'title' => $title, 'nav_label' => $title, 'nav_order' => 50,
            'in_nav' => false, 'in_footer' => false, 'draft' => true, 'noindex' => false,
            'meta_title' => $title, 'meta_description' => '',
            'hero' => ['image' => '', 'image_alt' => '', 'eyebrow' => '', 'heading' => $title, 'text' => '', 'buttons' => []],
            'blocks' => [['type' => 'text', 'heading' => '', 'body' => 'Hier steht Ihr Text.', 'image' => '', 'image_alt' => '', 'image_position' => 'right', 'anchor' => '']],
            'updated' => date('c'),
        ]);
        app_log('cms', 'Seite angelegt', ['slug' => $slug, 'user' => Auth::user()['username'] ?? '']);
        self::flash('success', 'Seite angelegt. Sie ist noch als Entwurf markiert und daher nicht öffentlich.');
        self::redirect('page', ['id' => $slug]);
    }

    private static function rPageDelete(bool $post): void
    {
        if (!$post) {
            self::redirect('pages');
        }
        $slug = (string) ($_POST['id'] ?? '');
        if (in_array($slug, ['start', 'impressum', 'datenschutz'], true)) {
            throw new RuntimeException('Diese Pflichtseite kann nicht gelöscht werden.');
        }
        Store::delete(self::pageName($slug));
        self::publish('Seitenliste');
        self::flash('success', 'Seite gelöscht. Eine Sicherung liegt unter „Sicherungen“.');
        self::redirect('pages');
    }

    // ================================================================ Öffnungszeiten

    private static function rHours(bool $post): void
    {
        if ($post) {
            $groups = [];
            $seen = [];
            foreach (self::input('groups') as $g) {
                if (!is_array($g)) {
                    continue;
                }
                $title = Schema::cleanValue('text', $g['title'] ?? '');
                if ($title === '') {
                    continue;
                }
                $id = slugify((string) ($g['id'] ?? '')) ?: slugify($title);
                while (isset($seen[$id])) {
                    $id .= '-2';
                }
                $seen[$id] = true;
                $rows = [];
                foreach ((array) ($g['rows'] ?? []) as $r) {
                    $d = Schema::cleanValue('text', $r['days'] ?? '');
                    $t = Schema::cleanValue('text', $r['time'] ?? '');
                    if ($d !== '' || $t !== '') {
                        $rows[] = ['days' => $d, 'time' => $t];
                    }
                }
                $notes = array_values(array_filter(array_map(
                    static fn($l) => Schema::cleanValue('text', $l),
                    explode("\n", (string) ($g['notes'] ?? ''))
                ), 'strlen'));
                $groups[] = ['id' => $id, 'title' => $title, 'rows' => $rows, 'notes' => $notes];
            }
            Store::put('hours', $groups);
            self::publish('Öffnungszeiten');
            self::redirect('hours');
        }
        self::view('hours', ['title' => 'Öffnungszeiten', 'groups' => (array) Store::get('hours', [])]);
    }

    // ================================================================ Aktuelles

    private static function rNews(bool $post): void
    {
        $pages = Site::pages();
        if ($post) {
            $items = [];
            foreach (self::input('news') as $n) {
                if (!is_array($n)) {
                    continue;
                }
                $c = Schema::cleanFields(Schema::newsFields(), $n);
                if ($c['title'] === '' && $c['text'] === '') {
                    continue;
                }
                $c['id'] = slugify((string) ($n['id'] ?? '')) ?: (slugify($c['title']) . '-' . substr(bin2hex(random_bytes(3)), 0, 6));
                $c['pages'] = array_values(array_intersect(array_keys($pages), (array) ($n['pages'] ?? [])));
                if ($c['from'] && $c['until'] && $c['until'] < $c['from']) {
                    throw new RuntimeException('„' . $c['title'] . '“: Das Enddatum liegt vor dem Startdatum.');
                }
                $items[] = $c;
            }
            Store::put('news', $items);
            self::publish('Aktuelles');
            self::redirect('news');
        }
        self::view('news', ['title' => 'Aktuelles & Hinweise', 'news' => (array) Store::get('news', []), 'pages' => $pages]);
    }

    // ================================================================ Medien

    private static function rMedia(bool $post): void
    {
        if ($post) {
            $files = $_FILES['files'] ?? null;
            if (!$files || !is_array($files['name'])) {
                throw new RuntimeException('Bitte Dateien auswählen.');
            }
            $done = [];
            $errors = [];
            foreach ($files['name'] as $i => $n) {
                try {
                    $done[] = Media::upload([
                        'name' => (string) $n,
                        'tmp_name' => (string) $files['tmp_name'][$i],
                        'error' => (int) $files['error'][$i],
                        'size' => (int) $files['size'][$i],
                    ], Schema::cleanValue('text', $_POST['alt'] ?? ''));
                } catch (RuntimeException $e) {
                    $errors[] = $n . ': ' . $e->getMessage();
                }
            }
            app_log('cms', 'Upload', ['user' => Auth::user()['username'] ?? '', 'files' => array_column($done, 'key')]);
            if (self::wantsJson()) {
                self::json(['ok' => !$errors, 'files' => $done, 'message' => implode(' ', $errors)], $errors && !$done ? 400 : 200);
            }
            if ($done) {
                self::flash('success', count($done) . ' Datei(en) hochgeladen.');
            }
            foreach ($errors as $err) {
                self::flash('error', $err);
            }
            self::redirect('media');
        }
        $filter = in_array($_GET['type'] ?? '', ['image', 'doc'], true) ? $_GET['type'] : '';
        $all = Media::all();
        if ($filter) {
            $all = array_filter($all, static fn($m) => ($m['type'] ?? '') === $filter);
        }
        uasort($all, static fn($a, $b) => strcmp((string) ($b['uploaded'] ?? ''), (string) ($a['uploaded'] ?? '')));
        self::view('media', ['title' => 'Bilder & Dokumente', 'media' => $all, 'usage' => self::mediaUsage(), 'filter' => $filter]);
    }

    private static function rMediaJson(bool $post): void
    {
        $type = ($_GET['type'] ?? 'image') === 'doc' ? 'doc' : 'image';
        $out = [];
        foreach (Media::all() as $key => $m) {
            if (($m['type'] ?? '') !== $type) {
                continue;
            }
            $v = $type === 'image' ? Media::variants('/uploads/' . $key) : [];
            $out[] = [
                'path' => '/uploads/' . $key,
                'thumb' => url($v[min(array_keys($v) ?: [0])] ?? '/uploads/' . $key),
                'alt' => $m['alt'] ?? '',
                'title' => $m['title'] ?? basename($key),
                'uploaded' => $m['uploaded'] ?? '',
            ];
        }
        usort($out, static fn($a, $b) => strcmp($b['uploaded'], $a['uploaded']));
        self::json(['ok' => true, 'items' => $out]);
    }

    private static function rMediaUpdate(bool $post): void
    {
        if ($post) {
            Media::update((string) ($_POST['key'] ?? ''), ['alt' => $_POST['alt'] ?? '', 'title' => $_POST['title'] ?? '']);
            self::publish('Bildbeschreibung');
        }
        self::redirect('media');
    }

    private static function rMediaDelete(bool $post): void
    {
        if ($post) {
            $key = (string) ($_POST['key'] ?? '');
            $usage = self::mediaUsage();
            if (!empty($usage['/uploads/' . $key])) {
                throw new RuntimeException('Die Datei wird noch verwendet auf: ' . implode(', ', $usage['/uploads/' . $key]) . '. Bitte dort zuerst entfernen.');
            }
            Media::delete($key);
            app_log('cms', 'Datei gelöscht', ['user' => Auth::user()['username'] ?? '', 'key' => $key]);
            self::flash('success', 'Datei gelöscht.');
        }
        self::redirect('media');
    }

    /** @return array<string,list<string>> Pfad → Liste von Seiten, die ihn verwenden */
    private static function mediaUsage(): array
    {
        $usage = [];
        $scan = static function (string $label, mixed $data) use (&$usage, &$scan): void {
            if (is_array($data)) {
                foreach ($data as $v) {
                    $scan($label, $v);
                }
            } elseif (is_string($data) && preg_match_all('~/uploads/(?:images|docs)/[a-z0-9._-]+~', $data, $m)) {
                foreach ($m[0] as $p) {
                    $usage[$p][$label] = $label;
                }
            }
        };
        foreach (Site::pages() as $p) {
            $scan((string) ($p['title'] ?? $p['slug']), $p);
        }
        $scan('Einstellungen', Store::get('site', []));
        $scan('Aktuelles', Store::get('news', []));
        return array_map('array_values', $usage);
    }

    // ================================================================ Einstellungen

    private static function rSettings(bool $post): void
    {
        $site = (array) Store::get('site', []);
        if ($post) {
            $in = self::input('site');
            $clean = Schema::cleanFields(Schema::siteFields(), $in);
            if ($clean['email'] !== '' && !filter_var($clean['email'], FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Die E-Mail-Adresse ist ungültig.');
            }
            $cf = (array) ($in['contact_form'] ?? []);
            $recipient = Schema::cleanValue('text', $cf['recipient'] ?? '');
            if ($recipient !== '' && !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Die Empfänger-Adresse des Kontaktformulars ist ungültig.');
            }
            $clean['contact_form'] = [
                'enabled' => Schema::cleanValue('checkbox', $cf['enabled'] ?? ''),
                'recipient' => $recipient,
                'success' => Schema::cleanValue('textarea', $cf['success'] ?? ''),
            ];
            Store::put('site', array_merge($site, $clean));
            self::publish('Einstellungen');
            self::redirect('settings');
        }
        self::view('settings', ['title' => 'Einstellungen', 'site' => $site]);
    }

    // ================================================================ Konto & Benutzer

    private static function rAccount(bool $post): void
    {
        $user = Auth::user();
        $users = Auth::users();
        $name = $user['username'];

        if ($post) {
            $action = (string) ($_POST['action'] ?? '');
            if (!password_verify((string) ($_POST['current'] ?? ''), (string) $users[$name]['hash'])) {
                throw new RuntimeException('Das aktuelle Passwort ist falsch.');
            }
            if ($action === 'password') {
                $new = (string) ($_POST['new'] ?? '');
                if ($new !== (string) ($_POST['new2'] ?? '')) {
                    throw new RuntimeException('Die neuen Passwörter stimmen nicht überein.');
                }
                if ($err = Auth::validatePassword($new, $name)) {
                    throw new RuntimeException($err);
                }
                $users[$name]['hash'] = Auth::hash($new);
                Auth::saveUsers($users);
                session_regenerate_id(true);
                app_log('auth', 'Passwort geändert', ['user' => $name]);
                self::flash('success', 'Passwort geändert.');
            } elseif ($action === 'totp-enable') {
                $secret = (string) ($_SESSION['totp_setup'] ?? '');
                $step = $secret ? Totp::verify($secret, (string) ($_POST['code'] ?? '')) : null;
                if ($step === null) {
                    throw new RuntimeException('Der Code aus der App ist ungültig. Bitte prüfen Sie auch die Uhrzeit Ihres Telefons.');
                }
                $users[$name]['totp_secret'] = $secret;
                $users[$name]['totp_enabled'] = true;
                $users[$name]['totp_last'] = $step;
                Auth::saveUsers($users);
                unset($_SESSION['totp_setup']);
                app_log('auth', '2FA aktiviert', ['user' => $name]);
                self::flash('success', '2-Faktor-Anmeldung ist aktiv. Ab jetzt brauchen Sie bei der Anmeldung zusätzlich den Code aus der App.');
            } elseif ($action === 'totp-disable') {
                $users[$name]['totp_secret'] = '';
                $users[$name]['totp_enabled'] = false;
                Auth::saveUsers($users);
                app_log('auth', '2FA deaktiviert', ['user' => $name]);
                self::flash('warn', '2-Faktor-Anmeldung wurde ausgeschaltet.');
            }
            self::redirect('account');
        }

        if (empty($user['totp_enabled']) && empty($_SESSION['totp_setup'])) {
            $_SESSION['totp_setup'] = Totp::secret();
        }
        $secret = (string) ($_SESSION['totp_setup'] ?? '');
        self::view('account', [
            'title' => 'Mein Konto',
            'totpSecret' => $secret,
            'totpUri' => $secret ? Totp::uri($secret, $name, (string) (Store::get('site')['name'] ?? 'Mühle Prautitz') . ' CMS') : '',
        ]);
    }

    private static function rUsers(bool $post): void
    {
        self::view('users', ['title' => 'Benutzer', 'users' => Auth::users()]);
    }

    private static function rUserAdd(bool $post): void
    {
        if ($post) {
            Auth::createUser((string) ($_POST['username'] ?? ''), (string) ($_POST['password'] ?? ''), (string) ($_POST['role'] ?? 'editor'), (string) ($_POST['name'] ?? ''));
            app_log('auth', 'Benutzer angelegt', ['by' => Auth::user()['username'] ?? '', 'user' => $_POST['username'] ?? '']);
            self::flash('success', 'Benutzer angelegt. Bitte das Passwort sicher (nicht per E-Mail) weitergeben.');
        }
        self::redirect('users');
    }

    private static function rUserDelete(bool $post): void
    {
        if ($post) {
            $name = (string) ($_POST['username'] ?? '');
            if ($name === (Auth::user()['username'] ?? '')) {
                throw new RuntimeException('Sie können sich nicht selbst löschen.');
            }
            $users = Auth::users();
            unset($users[$name]);
            if (!array_filter($users, static fn($u) => ($u['role'] ?? '') === 'admin')) {
                throw new RuntimeException('Es muss mindestens ein Administrator bleiben.');
            }
            Auth::saveUsers($users);
            app_log('auth', 'Benutzer gelöscht', ['by' => Auth::user()['username'] ?? '', 'user' => $name]);
            self::flash('success', 'Benutzer gelöscht.');
        }
        self::redirect('users');
    }

    // ================================================================ Sicherungen & Protokoll

    private static function rBackups(bool $post): void
    {
        self::view('backups', ['title' => 'Sicherungen', 'backups' => array_slice(Store::backups(), 0, 200)]);
    }

    private static function rRestore(bool $post): void
    {
        if ($post) {
            $name = Store::restore((string) ($_POST['file'] ?? ''));
            self::publish('Wiederhergestellter Stand von „' . $name . '“');
        }
        self::redirect('backups');
    }

    private static function rBackupDownload(bool $post): void
    {
        if (!$post) {
            self::redirect('backups');
        }
        if (!class_exists(\ZipArchive::class)) {
            throw new RuntimeException('ZIP-Erweiterung ist auf dem Server nicht verfügbar.');
        }
        $tmp = STORAGE_DIR . '/cache/backup-' . bin2hex(random_bytes(6)) . '.zip';
        $zip = new \ZipArchive();
        $zip->open($tmp, \ZipArchive::CREATE);
        foreach ([CONTENT_DIR => 'content', UPLOAD_DIR => 'public/uploads'] as $dir => $prefix) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $f) {
                if ($f->isFile()) {
                    $zip->addFile($f->getPathname(), $prefix . '/' . substr($f->getPathname(), strlen($dir) + 1));
                }
            }
        }
        $zip->close();
        app_log('cms', 'Komplettsicherung heruntergeladen', ['user' => Auth::user()['username'] ?? '']);
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="muehle-sicherung-' . date('Y-m-d') . '.zip"');
        header('Content-Length: ' . filesize($tmp));
        readfile($tmp);
        unlink($tmp);
        exit;
    }

    private static function rLog(bool $post): void
    {
        $lines = [];
        $files = glob(STORAGE_DIR . '/logs/*.log') ?: [];
        rsort($files);
        foreach (array_slice($files, 0, 4) as $f) {
            $lines = array_merge($lines, array_reverse(file($f, FILE_IGNORE_NEW_LINES) ?: []));
        }
        usort($lines, static fn($a, $b) => strcmp(substr($b, 1, 19), substr($a, 1, 19)));
        self::view('log', ['title' => 'Protokoll', 'lines' => array_slice($lines, 0, 300)]);
    }

    // ================================================================ Deploy-Hook

    private static function hook(): void
    {
        $token = (string) config('deploy_token', '');
        $sent = (string) ($_SERVER['HTTP_X_DEPLOY_TOKEN'] ?? '');
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || strlen($token) < 32 || !hash_equals($token, $sent)) {
            usleep(300_000);
            self::json(['ok' => false], 403);
        }
        Site::$target = 'server';
        Site::$basePath = rtrim((string) config('base_path', ''), '/');
        $files = Site::build(PUBLIC_DIR);
        app_log('cms', 'Deploy-Hook: Seiten neu erzeugt', ['files' => count($files)]);
        self::json(['ok' => true, 'files' => count($files)]);
    }
}
