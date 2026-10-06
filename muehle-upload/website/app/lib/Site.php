<?php
declare(strict_types=1);

namespace Muehle;

use RuntimeException;

/**
 * Erzeugt die statische Webseite aus den Inhalten (content/*.json) und Vorlagen.
 * Wird vom CMS nach jedem Speichern und vom Build-Skript (GitHub Actions) genutzt.
 */
final class Site
{
    public static string $basePath = '';
    public static string $target = 'server'; // server | github

    public const RESERVED_SLUGS = ['admin', 'assets', 'uploads', 'api', 'index', '404'];

    /** @return array<string,array> Seiten nach Slug, sortiert nach Menü-Reihenfolge */
    public static function pages(): array
    {
        $pages = [];
        foreach (Store::list('pages') as $name) {
            $p = Store::get($name);
            if (!is_array($p) || empty($p['slug'])) {
                continue;
            }
            $pages[$p['slug']] = $p;
        }
        uasort($pages, static fn($a, $b) => ((int) ($a['nav_order'] ?? 99)) <=> ((int) ($b['nav_order'] ?? 99)));
        return $pages;
    }

    public static function pageUrl(string $slug): string
    {
        return $slug === 'start' ? url('/') : url('/' . $slug . '/');
    }

    /** Aktuelle Hinweise, die am Stichtag sichtbar sind. */
    public static function activeNews(?string $page = null, ?string $today = null): array
    {
        $today ??= date('Y-m-d');
        $out = [];
        foreach ((array) Store::get('news', []) as $n) {
            if (empty($n['active'])) {
                continue;
            }
            if (!empty($n['from']) && $n['from'] > $today) {
                continue;
            }
            if (!empty($n['until']) && $n['until'] < $today) {
                continue;
            }
            if ($page !== null && !in_array($page, (array) ($n['pages'] ?? []), true)) {
                continue;
            }
            $out[] = $n;
        }
        usort($out, static fn($a, $b) => [(int) !($a['pinned'] ?? false), $a['until'] ?: '9999'] <=> [(int) !($b['pinned'] ?? false), $b['until'] ?: '9999']);
        return $out;
    }

    public static function render(string $template, array $vars = []): string
    {
        $file = APP_DIR . '/templates/' . $template . '.php';
        if (!is_file($file)) {
            throw new RuntimeException('Vorlage fehlt: ' . $template);
        }
        extract($vars, EXTR_SKIP);
        ob_start();
        try {
            include $file;
        } catch (\Throwable $t) {
            ob_end_clean();
            throw $t;
        }
        return (string) ob_get_clean();
    }

    public static function renderPage(array $page, array $ctx): string
    {
        return self::render('site/layout', $ctx + ['page' => $page]);
    }

    /**
     * Gesamte Seite erzeugen.
     * @return list<string> geschriebene Dateien (relativ zum Ziel)
     */
    public static function build(string $outDir, bool $copyAssets = false): array
    {
        $outDir = rtrim($outDir, '/');
        $site = (array) Store::get('site', []);
        $hours = (array) Store::get('hours', []);
        $pages = self::pages();
        $written = [];

        if (!is_dir($outDir)) {
            mkdir($outDir, 0755, true);
        }

        $ctx = ['site' => $site, 'hours' => $hours, 'pages' => $pages];

        // Alte, nicht mehr vorhandene Seiten entfernen (nur von uns erzeugte Ordner)
        $manifestFile = $outDir . '/.built-pages.json';
        $old = is_file($manifestFile) ? (array) json_decode((string) file_get_contents($manifestFile), true) : [];
        foreach ($old as $slug) {
            if (!isset($pages[$slug]) && $slug !== 'start' && preg_match('~^[a-z0-9-]+$~', $slug)
                && !in_array($slug, self::RESERVED_SLUGS, true)) {
                @unlink("$outDir/$slug/index.html");
                @rmdir("$outDir/$slug");
            }
        }

        foreach ($pages as $slug => $page) {
            if (in_array($slug, self::RESERVED_SLUGS, true) || !preg_match('~^[a-z0-9-]+$~', $slug)) {
                continue;
            }
            if (!empty($page['draft'])) {
                continue;
            }
            $html = self::renderPage($page, $ctx);
            $rel = $slug === 'start' ? 'index.html' : $slug . '/index.html';
            write_file_atomic("$outDir/$rel", $html);
            $written[] = $rel;
        }

        // 404-Seite
        $notFound = [
            'slug' => '404', 'title' => 'Seite nicht gefunden', 'meta_description' => '', 'noindex' => true,
            'hero' => ['heading' => 'Hier ist nichts gefangen.', 'eyebrow' => 'Fehler 404', 'text' => 'Die gesuchte Seite gibt es nicht (mehr).'],
            'blocks' => [['type' => 'text', 'heading' => '', 'body' => "Vielleicht hilft Ihnen einer dieser Wege weiter:\n\n- [Zur Startseite](/)\n- [Angelteich & Öffnungszeiten](/angelteich/)\n- [Kontakt](/kontakt/)"]],
        ];
        write_file_atomic("$outDir/404.html", self::renderPage($notFound, $ctx));
        $written[] = '404.html';

        // Sitemap + robots
        $base = rtrim((string) ($site['base_url'] ?? config('base_url')), '/');
        if (self::$target === 'github') {
            $base = '';
        }
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
        foreach ($pages as $slug => $p) {
            if (!empty($p['draft']) || !empty($p['noindex'])) {
                continue;
            }
            $loc = $base . ($slug === 'start' ? '/' : '/' . $slug . '/');
            $xml .= '  <url><loc>' . e($loc) . '</loc><lastmod>' . e(substr((string) ($p['updated'] ?? date('c')), 0, 10)) . "</lastmod></url>\n";
        }
        $xml .= "</urlset>\n";
        write_file_atomic("$outDir/sitemap.xml", $xml);
        $robots = self::$target === 'github'
            ? "User-agent: *\nDisallow: /\n" // Vorschau nicht indexieren
            : "User-agent: *\nDisallow: /admin/\nDisallow: /api/\n\nSitemap: $base/sitemap.xml\n";
        write_file_atomic("$outDir/robots.txt", $robots);
        $written[] = 'sitemap.xml';
        $written[] = 'robots.txt';

        write_file_atomic($manifestFile, json_encode(array_keys($pages)) ?: '[]');

        if ($copyAssets) {
            self::copyDir(PUBLIC_DIR . '/assets', "$outDir/assets");
            self::copyDir(PUBLIC_DIR . '/uploads', "$outDir/uploads");
        }
        return $written;
    }

    private static function copyDir(string $src, string $dst): void
    {
        if (!is_dir($src)) {
            return;
        }
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($src, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($it as $item) {
            $target = $dst . '/' . substr($item->getPathname(), strlen($src) + 1);
            if ($item->isDir()) {
                @mkdir($target, 0755, true);
            } elseif (!str_ends_with($item->getFilename(), '.php') && $item->getFilename() !== '.htaccess') {
                @mkdir(dirname($target), 0755, true);
                copy($item->getPathname(), $target);
            }
        }
    }
}
