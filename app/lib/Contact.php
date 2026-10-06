<?php
declare(strict_types=1);

namespace Muehle;

/**
 * Kontaktformular: Spam-Schutz ohne Drittanbieter (Honeypot, signiertes Zeit-Token, Drosselung).
 */
final class Contact
{
    public static function handle(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');
        header('X-Robots-Tag: noindex');

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($method === 'GET' && isset($_GET['token'])) {
            self::respond(true, '', ['token' => self::token()]);
        }
        if ($method !== 'POST') {
            self::respond(false, 'Methode nicht erlaubt.', [], 405);
        }

        $site = (array) Store::get('site', []);
        if (empty($site['contact_form']['enabled'])) {
            self::respond(false, 'Das Kontaktformular ist derzeit deaktiviert.', [], 403);
        }

        // Honeypot: Bots füllen versteckte Felder aus → still „erfolgreich“ antworten
        if (trim((string) ($_POST['website'] ?? '')) !== '') {
            app_log('contact', 'Honeypot ausgelöst', ['ip' => client_ip_hash()]);
            self::respond(true, (string) ($site['contact_form']['success'] ?? 'Vielen Dank!'));
        }

        if (!self::checkToken((string) ($_POST['token'] ?? ''))) {
            self::respond(false, 'Das Formular ist abgelaufen oder wurde zu schnell abgeschickt. Bitte versuchen Sie es noch einmal.', [], 400);
        }

        $name = trim(mb_substr(str_replace(["\r", "\n"], ' ', (string) ($_POST['name'] ?? '')), 0, 100));
        $email = trim((string) ($_POST['email'] ?? ''));
        $phone = trim(mb_substr(preg_replace('~[^0-9+ ()/-]~', '', (string) ($_POST['phone'] ?? '')) ?? '', 0, 40));
        $message = trim(mb_substr((string) ($_POST['message'] ?? ''), 0, 5000));

        if ($name === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($_POST['privacy'])) {
            self::respond(false, 'Bitte füllen Sie alle Pflichtfelder korrekt aus und bestätigen Sie die Datenschutzerklärung.', [], 422);
        }
        if (preg_match_all('~https?://~i', $message) > 3) {
            self::respond(false, 'Ihre Nachricht enthält zu viele Links.', [], 422);
        }
        if (!Auth::rateLimit('contact', (int) config('security.contact_max_per_hour', 5), 3600)) {
            self::respond(false, 'Sie haben bereits mehrere Nachrichten gesendet. Bitte versuchen Sie es später erneut oder rufen Sie uns an.', [], 429);
        }

        $to = (string) ($site['contact_form']['recipient'] ?? $site['email'] ?? '');
        $from = (string) config('mail.from');
        $fromName = (string) config('mail.from_name', 'Webseite');
        $subject = 'Neue Nachricht über die Webseite von ' . $name;
        $body = "Neue Nachricht über das Kontaktformular\n"
            . str_repeat('=', 42) . "\n\n"
            . "Name:    $name\nE-Mail:  $email\n" . ($phone !== '' ? "Telefon: $phone\n" : '')
            . "Datum:   " . date('d.m.Y H:i') . " Uhr\n\nNachricht:\n----------\n$message\n";

        $headers = [
            'From' => mb_encode_mimeheader($fromName, 'UTF-8', 'Q') . " <$from>",
            'Reply-To' => $email,
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Transfer-Encoding' => '8bit',
            'X-Mailer' => 'Muehle-CMS',
        ];
        $ok = $to !== '' && filter_var($to, FILTER_VALIDATE_EMAIL)
            && @mail($to, mb_encode_mimeheader($subject, 'UTF-8', 'Q'), $body, $headers, '-f' . $from);

        app_log('contact', $ok ? 'Nachricht versendet' : 'Versand fehlgeschlagen', ['ip' => client_ip_hash()]);
        if (!$ok) {
            self::respond(false, 'Die Nachricht konnte leider nicht versendet werden. Bitte schreiben Sie uns direkt an ' . ($site['email'] ?? '') . '.', [], 500);
        }
        self::respond(true, (string) ($site['contact_form']['success'] ?? 'Vielen Dank für Ihre Nachricht!'));
    }

    private static function secret(): string
    {
        $s = (string) config('app_secret', '');
        if (strlen($s) < 32) {
            // Fallback: dauerhaft gespeicherter Zufallswert
            $f = STORAGE_DIR . '/cache/.secret';
            if (!is_file($f)) {
                file_put_contents($f, bin2hex(random_bytes(32)));
                @chmod($f, 0600);
            }
            $s = (string) file_get_contents($f);
        }
        return $s;
    }

    private static function token(): string
    {
        $t = (string) time();
        return $t . '.' . hash_hmac('sha256', 'contact|' . $t, self::secret());
    }

    private static function checkToken(string $token): bool
    {
        [$t, $sig] = array_pad(explode('.', $token, 2), 2, '');
        if (!ctype_digit($t) || !hash_equals(hash_hmac('sha256', 'contact|' . $t, self::secret()), $sig)) {
            return false;
        }
        $age = time() - (int) $t;
        return $age >= 3 && $age <= 7200;
    }

    private static function respond(bool $ok, string $message, array $extra = [], int $code = 200): never
    {
        http_response_code($code);
        $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
        if (isset($_GET['token']) || str_contains($accept, 'application/json')) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => $ok, 'message' => $message] + $extra, JSON_UNESCAPED_UNICODE);
            exit;
        }
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html lang="de"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>Kontakt</title><link rel="stylesheet" href="' . e(url('/assets/css/site.css')) . '">'
            . '<main class="section"><div class="wrap wrap--narrow prose"><h1>' . ($ok ? 'Vielen Dank!' : 'Hinweis') . '</h1><p>' . e($message ?: 'Bitte aktivieren Sie JavaScript oder schreiben Sie uns eine E-Mail.') . '</p>'
            . '<p><a class="btn btn--primary" href="' . e(url('/kontakt/')) . '">Zurück</a></p></div></main></html>';
        exit;
    }
}
