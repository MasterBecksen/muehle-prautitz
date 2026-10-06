<?php
declare(strict_types=1);

/**
 * Benutzer über die Kommandozeile anlegen (Alternative zur Web-Einrichtung).
 *   php tools/create-user.php <benutzername> <rolle: admin|editor> ["Anzeigename"]
 * Das Passwort wird verdeckt abgefragt.
 */
if (PHP_SAPI !== 'cli') {
    exit(1);
}
require dirname(__DIR__) . '/app/bootstrap.php';

[$_, $user, $role, $name] = array_pad($argv, 4, '');
if ($user === '') {
    fwrite(STDERR, "Aufruf: php tools/create-user.php <benutzername> <admin|editor> [\"Name\"]\n");
    exit(1);
}
echo 'Passwort (mind. 12 Zeichen): ';
system('stty -echo 2>/dev/null');
$pw = trim((string) fgets(STDIN));
system('stty echo 2>/dev/null');
echo "\n";
try {
    \Muehle\Auth::createUser($user, $pw, $role ?: 'editor', $name);
    echo "✔ Benutzer $user angelegt.\n";
} catch (RuntimeException $e) {
    fwrite(STDERR, '✘ ' . $e->getMessage() . "\n");
    exit(1);
}
