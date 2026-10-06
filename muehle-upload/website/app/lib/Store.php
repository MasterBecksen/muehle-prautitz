<?php
declare(strict_types=1);

namespace Muehle;

use RuntimeException;

/**
 * Flat-File-Speicher: JSON-Dateien unter content/ mit automatischer Sicherung.
 */
final class Store
{
    /** Pfad relativ zu content/ prüfen (keine Verzeichnis-Ausbrüche). */
    private static function path(string $name): string
    {
        if (!preg_match('~^[a-z0-9_-]+(/[a-z0-9_-]+)?$~', $name)) {
            throw new RuntimeException('Ungültiger Datenname: ' . $name);
        }
        return CONTENT_DIR . '/' . $name . '.json';
    }

    public static function exists(string $name): bool
    {
        return is_file(self::path($name));
    }

    public static function get(string $name, mixed $default = []): mixed
    {
        $file = self::path($name);
        if (!is_file($file)) {
            return $default;
        }
        $data = json_decode((string) file_get_contents($file), true);
        return $data ?? $default;
    }

    public static function put(string $name, mixed $data): void
    {
        $file = self::path($name);
        if (is_file($file)) {
            self::backup($name, $file);
        }
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        write_file_atomic($file, $json . "\n");
    }

    public static function delete(string $name): void
    {
        $file = self::path($name);
        if (is_file($file)) {
            self::backup($name, $file);
            unlink($file);
        }
    }

    /** @return list<string> Namen aller Dateien in einem Unterordner */
    public static function list(string $folder): array
    {
        $out = [];
        foreach (glob(CONTENT_DIR . '/' . basename($folder) . '/*.json') ?: [] as $f) {
            $out[] = $folder . '/' . basename($f, '.json');
        }
        sort($out);
        return $out;
    }

    private static function backup(string $name, string $file): void
    {
        $key = str_replace('/', '__', $name);
        $target = STORAGE_DIR . '/backups/' . $key . '.' . date('Ymd-His') . '-' . bin2hex(random_bytes(2)) . '.json';
        @copy($file, $target);

        $keep = (int) config('backups_keep', 30);
        $all = glob(STORAGE_DIR . '/backups/' . $key . '.*.json') ?: [];
        sort($all);
        while (count($all) > $keep) {
            @unlink(array_shift($all));
        }
    }

    /** @return list<array{file:string,name:string,time:int,size:int}> */
    public static function backups(): array
    {
        $out = [];
        foreach (glob(STORAGE_DIR . '/backups/*.json') ?: [] as $f) {
            $base = basename($f);
            $out[] = [
                'file' => $base,
                'name' => str_replace('__', '/', explode('.', $base)[0]),
                'time' => filemtime($f) ?: 0,
                'size' => filesize($f) ?: 0,
            ];
        }
        usort($out, static fn($a, $b) => $b['time'] <=> $a['time']);
        return $out;
    }

    public static function restore(string $backupFile): string
    {
        $base = basename($backupFile);
        if (!preg_match('~^([a-z0-9_-]+(?:__[a-z0-9_-]+)?)\.\d{8}-\d{6}-[0-9a-f]{4}\.json$~', $base, $m)) {
            throw new RuntimeException('Ungültige Sicherung.');
        }
        $src = STORAGE_DIR . '/backups/' . $base;
        $data = json_decode((string) file_get_contents($src), true);
        if (!is_array($data)) {
            throw new RuntimeException('Sicherung ist beschädigt.');
        }
        $name = str_replace('__', '/', $m[1]);
        self::put($name, $data);
        return $name;
    }
}
