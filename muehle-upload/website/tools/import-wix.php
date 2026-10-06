<?php
declare(strict_types=1);

/**
 * Übernimmt Bilder und PDFs der alten Wix-Seite (einmalig).
 * Läuft lokal oder in GitHub Actions:  php tools/import-wix.php
 * Bereits vorhandene Dateien werden übersprungen.
 */

if (PHP_SAPI !== 'cli') {
    exit(1);
}
require dirname(__DIR__) . '/app/bootstrap.php';

use Muehle\Media;
use Muehle\Store;

$manifest = json_decode((string) file_get_contents(__DIR__ . '/wix-import.json'), true);
$tmpDir = STORAGE_DIR . '/cache/import';
@mkdir($tmpDir, 0750, true);
@mkdir(UPLOAD_DIR . '/images', 0755, true);
@mkdir(UPLOAD_DIR . '/docs', 0755, true);

function fetch_url(string $url): ?string
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Muehle-Prautitz Import)',
        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
    ]);
    $data = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    return ($data !== false && $code === 200 && strlen((string) $data) > 1000) ? (string) $data : null;
}

$ok = $skip = $fail = 0;

foreach ($manifest['images'] as $name => $info) {
    if (is_file(UPLOAD_DIR . '/images/' . $name) && Media::find('/uploads/images/' . $name)) {
        $skip++;
        continue;
    }
    // Wix liefert über diesen Pfad eine auf 2400 px begrenzte Version
    $id = basename(parse_url($info['url'], PHP_URL_PATH));
    $data = fetch_url($info['url'] . '/v1/fit/w_2400,h_2400,q_90/' . $id) ?? fetch_url($info['url']);
    if ($data === null) {
        fwrite(STDERR, "✘ $name konnte nicht geladen werden\n");
        $fail++;
        continue;
    }
    $tmp = $tmpDir . '/' . $name;
    file_put_contents($tmp, $data);
    try {
        Media::importImage($tmp, pathinfo($name, PATHINFO_FILENAME), (string) $info['alt'], $name);
        echo "✔ $name\n";
        $ok++;
    } catch (Throwable $e) {
        fwrite(STDERR, "✘ $name: " . $e->getMessage() . "\n");
        $fail++;
    }
    @unlink($tmp);
}

foreach ($manifest['docs'] as $name => $info) {
    $target = UPLOAD_DIR . '/docs/' . $name;
    if (is_file($target)) {
        $skip++;
        continue;
    }
    $data = fetch_url($info['url']);
    if ($data === null || !str_starts_with($data, '%PDF-')) {
        fwrite(STDERR, "✘ $name konnte nicht geladen werden\n");
        $fail++;
        continue;
    }
    file_put_contents($target, $data);
    $all = Media::all();
    $all['docs/' . $name] = ['type' => 'doc', 'title' => $info['title'], 'size' => strlen($data), 'uploaded' => date('c')];
    ksort($all);
    Store::put('media', $all);
    echo "✔ $name\n";
    $ok++;
}

echo "\nFertig: $ok neu, $skip bereits vorhanden, $fail Fehler.\n";
exit($fail > 0 && $ok === 0 && $skip === 0 ? 1 : 0);
