<?php
declare(strict_types=1);

namespace Muehle;

use RuntimeException;

/**
 * Benutzer, Anmeldung, Sitzungen, CSRF-Schutz und Login-Drosselung.
 * Benutzerdaten liegen in storage/users.json (außerhalb des Webroots).
 */
final class Auth
{
    private const USERS_FILE = 'users.json';
    private const THROTTLE_FILE = 'cache/throttle.json';
    public const ROLES = ['admin' => 'Administrator', 'editor' => 'Redakteur'];

    // ---------------------------------------------------------------- Benutzer

    public static function users(): array
    {
        $f = STORAGE_DIR . '/' . self::USERS_FILE;
        if (!is_file($f)) {
            return [];
        }
        return (array) json_decode((string) file_get_contents($f), true);
    }

    public static function saveUsers(array $users): void
    {
        write_file_atomic(STORAGE_DIR . '/' . self::USERS_FILE, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}');
        @chmod(STORAGE_DIR . '/' . self::USERS_FILE, 0600);
    }

    public static function hash(string $password): string
    {
        return defined('PASSWORD_ARGON2ID')
            ? password_hash($password, PASSWORD_ARGON2ID)
            : password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /** Passwort-Richtlinie (orientiert an BSI/NIST: Länge vor Komplexität). */
    public static function validatePassword(string $pw, string $username = ''): ?string
    {
        if (mb_strlen($pw) < 12) {
            return 'Das Passwort muss mindestens 12 Zeichen lang sein.';
        }
        if (mb_strlen($pw) > 200) {
            return 'Das Passwort ist zu lang.';
        }
        if ($username !== '' && stripos($pw, $username) !== false) {
            return 'Das Passwort darf den Benutzernamen nicht enthalten.';
        }
        if (count(array_unique(mb_str_split($pw))) < 6) {
            return 'Das Passwort ist zu einfach (zu wenige verschiedene Zeichen).';
        }
        $common = ['passwort', 'password', '123456', 'qwertz', 'qwerty', 'muehle', 'prautitz', 'forelle', 'angelteich'];
        foreach ($common as $c) {
            if (stripos($pw, $c) !== false && mb_strlen($pw) < 20) {
                return 'Das Passwort enthält ein leicht zu erratendes Wort. Bitte länger oder anders wählen.';
            }
        }
        return null;
    }

    public static function createUser(string $username, string $password, string $role = 'editor', string $name = ''): void
    {
        $username = strtolower(trim($username));
        if (!preg_match('~^[a-z0-9._-]{3,40}$~', $username)) {
            throw new RuntimeException('Benutzername: 3–40 Zeichen, nur a–z, 0–9, Punkt, Bindestrich, Unterstrich.');
        }
        if ($err = self::validatePassword($password, $username)) {
            throw new RuntimeException($err);
        }
        $users = self::users();
        if (isset($users[$username])) {
            throw new RuntimeException('Benutzer existiert bereits.');
        }
        $users[$username] = [
            'name' => trim($name) ?: $username,
            'role' => isset(self::ROLES[$role]) ? $role : 'editor',
            'hash' => self::hash($password),
            'totp_secret' => '',
            'totp_enabled' => false,
            'totp_last' => null,
            'created' => date('c'),
            'last_login' => null,
        ];
        self::saveUsers($users);
    }

    // ---------------------------------------------------------------- Sitzung

    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $https = self::isHttps();
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.sid_length', '48');
        ini_set('session.sid_bits_per_character', '6');
        ini_set('session.gc_maxlifetime', (string) (config('security.session_max_hours', 8) * 3600));
        session_save_path(STORAGE_DIR . '/sessions');
        session_name($https ? '__Host-muehle_cms' : 'muehle_cms');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $https,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();

        $now = time();
        if (!empty($_SESSION['user'])) {
            $idle = (int) config('security.session_idle_minutes', 30) * 60;
            $max = (int) config('security.session_max_hours', 8) * 3600;
            $fp = self::fingerprint();
            if (($now - (int) ($_SESSION['last'] ?? 0)) > $idle
                || ($now - (int) ($_SESSION['started'] ?? 0)) > $max
                || !hash_equals((string) ($_SESSION['fp'] ?? ''), $fp)
                || !isset(self::users()[$_SESSION['user']])) {
                self::logout();
                session_start();
                $_SESSION['flash'][] = ['warn', 'Ihre Sitzung ist abgelaufen. Bitte melden Sie sich erneut an.'];
                return;
            }
            $_SESSION['last'] = $now;
        }
    }

    public static function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
            || (($_SERVER['SERVER_PORT'] ?? '') === '443');
    }

    private static function fingerprint(): string
    {
        return hash('sha256', (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
    }

    public static function user(): ?array
    {
        $u = $_SESSION['user'] ?? null;
        if (!$u) {
            return null;
        }
        $users = self::users();
        return isset($users[$u]) ? ['username' => $u] + $users[$u] : null;
    }

    public static function isAdmin(): bool
    {
        return (self::user()['role'] ?? '') === 'admin';
    }

    /**
     * Schritt 1: Benutzername + Passwort prüfen.
     * @return 'ok'|'totp'
     */
    public static function attempt(string $username, string $password): string
    {
        $username = strtolower(trim($username));
        self::guardThrottle($username);

        $users = self::users();
        $user = $users[$username] ?? null;
        if ($user === null) {
            // Unbekannter Benutzer: gleiche Rechenzeit wie eine echte Prüfung (kein Benutzer-Raten über Zeitmessung)
            self::hash($password);
            $ok = false;
        } else {
            $ok = password_verify($password, (string) $user['hash']);
        }

        if (!$ok) {
            self::registerFailure($username);
            app_log('auth', 'Login fehlgeschlagen', ['user' => $username, 'ip' => client_ip_hash()]);
            usleep(random_int(200_000, 600_000));
            throw new RuntimeException('Benutzername oder Passwort ist falsch.');
        }

        if (password_needs_rehash($user['hash'], defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT)) {
            $users[$username]['hash'] = self::hash($password);
            self::saveUsers($users);
        }

        session_regenerate_id(true);
        if (!empty($user['totp_enabled'])) {
            $_SESSION['pending_user'] = $username;
            $_SESSION['pending_since'] = time();
            return 'totp';
        }
        self::complete($username);
        return 'ok';
    }

    /** Schritt 2 (optional): Einmal-Code prüfen. */
    public static function attemptTotp(string $code): void
    {
        $username = (string) ($_SESSION['pending_user'] ?? '');
        if ($username === '' || time() - (int) ($_SESSION['pending_since'] ?? 0) > 300) {
            unset($_SESSION['pending_user'], $_SESSION['pending_since']);
            throw new RuntimeException('Zeit abgelaufen. Bitte erneut anmelden.');
        }
        self::guardThrottle($username);
        $users = self::users();
        $u = $users[$username] ?? null;
        $step = $u ? Totp::verify((string) $u['totp_secret'], $code, $u['totp_last'] ?? null) : null;
        if ($step === null) {
            self::registerFailure($username);
            app_log('auth', '2FA-Code falsch', ['user' => $username, 'ip' => client_ip_hash()]);
            throw new RuntimeException('Der Code ist ungültig.');
        }
        $users[$username]['totp_last'] = $step;
        self::saveUsers($users);
        unset($_SESSION['pending_user'], $_SESSION['pending_since']);
        self::complete($username);
    }

    private static function complete(string $username): void
    {
        session_regenerate_id(true);
        $_SESSION['user'] = $username;
        $_SESSION['started'] = time();
        $_SESSION['last'] = time();
        $_SESSION['fp'] = self::fingerprint();
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        self::clearThrottle($username);
        $users = self::users();
        $users[$username]['last_login'] = date('c');
        self::saveUsers($users);
        app_log('auth', 'Login erfolgreich', ['user' => $username, 'ip' => client_ip_hash()]);
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', ['expires' => time() - 42000, 'path' => $p['path'], 'secure' => $p['secure'], 'httponly' => true, 'samesite' => 'Strict']);
        }
        session_destroy();
    }

    // ---------------------------------------------------------------- CSRF

    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    public static function checkCsrf(): void
    {
        $sent = (string) ($_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if ($sent === '' || !hash_equals(self::csrfToken(), $sent)) {
            throw new RuntimeException('Sicherheitsprüfung fehlgeschlagen. Bitte Seite neu laden und erneut versuchen.');
        }
        // Zusätzlich Herkunft prüfen, falls der Browser sie mitsendet
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if ($origin !== '' && parse_url($origin, PHP_URL_HOST) !== parse_url('//' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST)) {
            throw new RuntimeException('Ungültige Herkunft der Anfrage.');
        }
    }

    // ---------------------------------------------------------------- Drosselung

    private static function throttleKeys(string $username): array
    {
        return ['ip:' . client_ip_hash(), 'user:' . hash('sha256', $username)];
    }

    private static function throttle(callable $fn): mixed
    {
        $file = STORAGE_DIR . '/' . self::THROTTLE_FILE;
        $fh = fopen($file, 'c+');
        if (!$fh) {
            return $fn([])[1] ?? null;
        }
        flock($fh, LOCK_EX);
        $data = json_decode((string) stream_get_contents($fh), true) ?: [];
        // Alte Einträge entfernen
        $data = array_filter($data, static fn($e) => ($e['last'] ?? 0) > time() - 86400);
        [$data, $result] = $fn($data);
        ftruncate($fh, 0);
        rewind($fh);
        fwrite($fh, json_encode($data) ?: '{}');
        flock($fh, LOCK_UN);
        fclose($fh);
        return $result;
    }

    private static function guardThrottle(string $username): void
    {
        $until = self::throttle(function (array $d) use ($username) {
            $max = 0;
            foreach (self::throttleKeys($username) as $k) {
                $max = max($max, (int) ($d[$k]['until'] ?? 0));
            }
            return [$d, $max];
        });
        if ($until > time()) {
            $min = (int) ceil(($until - time()) / 60);
            throw new RuntimeException("Zu viele Fehlversuche. Bitte warten Sie $min Minute(n).");
        }
    }

    private static function registerFailure(string $username): void
    {
        self::throttle(function (array $d) use ($username) {
            $maxAttempts = (int) config('security.login_max_attempts', 5);
            $lock = (int) config('security.login_lock_minutes', 15) * 60;
            foreach (self::throttleKeys($username) as $k) {
                $e = $d[$k] ?? ['fails' => 0, 'until' => 0, 'locks' => 0];
                $e['fails']++;
                $e['last'] = time();
                if ($e['fails'] >= $maxAttempts) {
                    $e['locks'] = ($e['locks'] ?? 0) + 1;
                    $e['until'] = time() + $lock * min(8, 2 ** ($e['locks'] - 1));
                    $e['fails'] = 0;
                }
                $d[$k] = $e;
            }
            return [$d, null];
        });
    }

    private static function clearThrottle(string $username): void
    {
        self::throttle(function (array $d) use ($username) {
            foreach (self::throttleKeys($username) as $k) {
                unset($d[$k]);
            }
            return [$d, null];
        });
    }

    /** Allgemeine Drosselung (z. B. Kontaktformular): true wenn erlaubt. */
    public static function rateLimit(string $bucket, int $max, int $windowSeconds): bool
    {
        $key = 'rl:' . $bucket . ':' . client_ip_hash();
        return (bool) self::throttle(function (array $d) use ($key, $max, $windowSeconds) {
            $e = $d[$key] ?? ['hits' => [], 'last' => time()];
            $e['hits'] = array_values(array_filter((array) $e['hits'], static fn($t) => $t > time() - $windowSeconds));
            if (count($e['hits']) >= $max) {
                return [$d, false];
            }
            $e['hits'][] = time();
            $e['last'] = time();
            $d[$key] = $e;
            return [$d, true];
        });
    }
}
