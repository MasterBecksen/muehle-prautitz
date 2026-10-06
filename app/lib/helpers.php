<?php
declare(strict_types=1);

/** HTML-sicher ausgeben. */
function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
}

/** Interne Links/Assets mit Basis-Pfad versehen (GitHub Pages Unterordner). */
function url(string $path = '/'): string
{
    if ($path === '' || preg_match('~^(https?:|mailto:|tel:|#|data:)~i', $path)) {
        return $path;
    }
    $base = rtrim((string) (\Muehle\Site::$basePath ?? ''), '/');
    return $base . '/' . ltrim($path, '/');
}

/** Slug aus beliebigem Text erzeugen. */
function slugify(string $text): string
{
    $text = mb_strtolower(trim($text));
    $text = strtr($text, ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss']);
    $text = preg_replace('~[^a-z0-9]+~', '-', $text) ?? '';
    return trim($text, '-');
}

/** Deutsches Datum (TT.MM.JJJJ) aus ISO-Datum. */
function date_de(?string $iso): string
{
    if (!$iso) {
        return '';
    }
    $ts = strtotime($iso);
    return $ts ? date('d.m.Y', $ts) : $iso;
}

/** Atomisches Schreiben einer Datei (erst temporär, dann umbenennen). */
function write_file_atomic(string $file, string $data): void
{
    $dir = dirname($file);
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException("Verzeichnis nicht anlegbar: $dir");
    }
    $tmp = $dir . '/.' . basename($file) . '.' . bin2hex(random_bytes(4)) . '.tmp';
    if (file_put_contents($tmp, $data, LOCK_EX) === false) {
        throw new RuntimeException("Datei nicht schreibbar: $file");
    }
    @chmod($tmp, 0644);
    if (!rename($tmp, $file)) {
        @unlink($tmp);
        throw new RuntimeException("Datei nicht ersetzbar: $file");
    }
}

/** Eintrag ins Protokoll (storage/logs). */
function app_log(string $channel, string $message, array $context = []): void
{
    $line = sprintf(
        "[%s] %s %s%s\n",
        date('Y-m-d H:i:s'),
        strtoupper($channel),
        $message,
        $context ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : ''
    );
    @file_put_contents(STORAGE_DIR . '/logs/' . $channel . '-' . date('Y-m') . '.log', $line, FILE_APPEND | LOCK_EX);
}

/** Anonymisierte IP (für Drosselung, DSGVO-freundlich gehasht). */
function client_ip_hash(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return substr(hash_hmac('sha256', $ip, (string) config('app_secret', 'x')), 0, 24);
}

/**
 * Responsives Bild als <picture> mit WebP-Varianten, Breite/Höhe gegen Layout-Sprünge.
 */
function picture(?string $src, string $alt = '', string $sizes = '100vw', string $class = '', bool $lazy = true): string
{
    if (!$src) {
        return '';
    }
    $meta = \Muehle\Media::find($src) ?? [];
    $alt = $alt !== '' ? $alt : (string) ($meta['alt'] ?? '');
    $dims = isset($meta['width'], $meta['height']) ? ' width="' . (int) $meta['width'] . '" height="' . (int) $meta['height'] . '"' : '';
    $attrs = ($class ? ' class="' . e($class) . '"' : '') . $dims
        . ($lazy ? ' loading="lazy" decoding="async"' : ' fetchpriority="high" decoding="async"');
    $variants = \Muehle\Media::variants($src);
    $img = '<img src="' . e(url($src)) . '" alt="' . e($alt) . '"' . $attrs . '>';
    if (!$variants) {
        return $img;
    }
    $srcset = implode(', ', array_map(static fn($w, $p) => e(url($p)) . ' ' . $w . 'w', array_keys($variants), $variants));
    return '<picture><source type="image/webp" srcset="' . $srcset . '" sizes="' . e($sizes) . '">' . $img . '</picture>';
}

/** Inline-SVG-Icon aus dem Sprite. */
function icon(string $name, string $class = 'icon'): string
{
    return '<svg class="' . e($class) . '" aria-hidden="true" focusable="false"><use href="' . e(url('/assets/img/icons.svg')) . '#' . e($name) . '"></use></svg>';
}
