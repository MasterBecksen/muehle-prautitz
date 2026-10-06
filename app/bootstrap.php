<?php
declare(strict_types=1);

/**
 * Gemeinsamer Einstiegspunkt für CMS, Build-Skripte und Kontaktformular.
 * Liegt bewusst außerhalb des Webroots (public/).
 */

define('ROOT_DIR', dirname(__DIR__));
define('APP_DIR', ROOT_DIR . '/app');
define('CONTENT_DIR', ROOT_DIR . '/content');
define('STORAGE_DIR', ROOT_DIR . '/storage');
define('PUBLIC_DIR', ROOT_DIR . '/public');
define('UPLOAD_DIR', PUBLIC_DIR . '/uploads');

mb_internal_encoding('UTF-8');
date_default_timezone_set('Europe/Berlin');

spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'Muehle\\')) {
        return;
    }
    $file = APP_DIR . '/lib/' . str_replace('\\', '/', substr($class, 7)) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require APP_DIR . '/lib/helpers.php';

/** Konfiguration laden (config.php ist nicht im Git, siehe config.sample.php). */
function config(?string $key = null, mixed $default = null): mixed
{
    static $cfg = null;
    if ($cfg === null) {
        $defaults = require APP_DIR . '/config.sample.php';
        $local = is_file(APP_DIR . '/config.php') ? require APP_DIR . '/config.php' : [];
        $cfg = array_replace_recursive($defaults, is_array($local) ? $local : []);
    }
    if ($key === null) {
        return $cfg;
    }
    $value = $cfg;
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

foreach ([STORAGE_DIR . '/backups', STORAGE_DIR . '/logs', STORAGE_DIR . '/sessions', STORAGE_DIR . '/cache'] as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0750, true);
    }
}
