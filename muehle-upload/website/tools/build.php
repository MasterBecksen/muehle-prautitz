<?php
declare(strict_types=1);

/**
 * Statische Seiten erzeugen.
 *
 *   php tools/build.php                         → schreibt nach public/ (Strato / lokaler Server)
 *   php tools/build.php --target=github --out=_site --base=/repo-name
 *                                               → komplette, eigenständige Vorschau für GitHub Pages
 */

if (PHP_SAPI !== 'cli') {
    exit(1);
}
require dirname(__DIR__) . '/app/bootstrap.php';

use Muehle\Site;

$opts = getopt('', ['target::', 'out::', 'base::']);
$target = (string) ($opts['target'] ?? 'server');
$out = (string) ($opts['out'] ?? ($target === 'github' ? ROOT_DIR . '/_site' : PUBLIC_DIR));
if (!str_starts_with($out, '/')) {
    $out = getcwd() . '/' . $out;
}
Site::$target = $target === 'github' ? 'github' : 'server';
Site::$basePath = rtrim((string) ($opts['base'] ?? config('base_path', '')), '/');

$start = microtime(true);
$files = Site::build($out, $target === 'github');

if ($target === 'github') {
    // GitHub Pages: Jekyll-Verarbeitung abschalten
    file_put_contents($out . '/.nojekyll', '');
}

printf("✔ %d Dateien erzeugt in %s (%.0f ms)\n", count($files), $out, (microtime(true) - $start) * 1000);
foreach ($files as $f) {
    echo "  · $f\n";
}
