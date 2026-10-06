<?php
declare(strict_types=1);

namespace Muehle;

use RuntimeException;

/**
 * Medienverwaltung: sicheres Hochladen, Neuberechnen von Bildern, responsive Varianten.
 * Index liegt in content/media.json.
 */
final class Media
{
    private const IMAGE_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    private const DOC_TYPES = ['application/pdf' => 'pdf'];

    /** @return array<string,array<string,mixed>> Schlüssel = Pfad relativ zu uploads/ */
    public static function all(): array
    {
        $items = Store::get('media', []);
        return is_array($items) ? $items : [];
    }

    public static function find(?string $publicPath): ?array
    {
        if (!$publicPath) {
            return null;
        }
        $key = preg_replace('~^/?uploads/~', '', $publicPath);
        return self::all()[$key] ?? null;
    }

    /**
     * Hochgeladene Datei prüfen und übernehmen.
     * @param array{name:string,tmp_name:string,error:int,size:int} $file
     */
    public static function upload(array $file, string $alt = ''): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload fehlgeschlagen (Fehlercode ' . (int) $file['error'] . ').');
        }
        $maxBytes = (int) config('uploads.max_mb', 15) * 1024 * 1024;
        if ($file['size'] > $maxBytes) {
            throw new RuntimeException('Datei ist zu groß (max. ' . config('uploads.max_mb') . ' MB).');
        }
        if (!is_uploaded_file($file['tmp_name']) && PHP_SAPI !== 'cli') {
            throw new RuntimeException('Ungültiger Upload.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: '';
        $base = slugify(pathinfo($file['name'], PATHINFO_FILENAME)) ?: 'datei';
        $base = substr($base, 0, 60);

        if (isset(self::IMAGE_TYPES[$mime])) {
            return self::importImage($file['tmp_name'], $base, $alt);
        }
        if (isset(self::DOC_TYPES[$mime])) {
            $head = (string) file_get_contents($file['tmp_name'], false, null, 0, 5);
            if ($head !== '%PDF-') {
                throw new RuntimeException('Die Datei ist kein gültiges PDF.');
            }
            $name = self::uniqueName('docs', $base, 'pdf');
            if (!copy($file['tmp_name'], UPLOAD_DIR . '/docs/' . $name)) {
                throw new RuntimeException('PDF konnte nicht gespeichert werden.');
            }
            return self::register('docs/' . $name, ['type' => 'doc', 'title' => $alt ?: $base, 'size' => filesize(UPLOAD_DIR . '/docs/' . $name)]);
        }
        throw new RuntimeException('Dateityp nicht erlaubt. Erlaubt sind JPG, PNG, WebP und PDF.');
    }

    /** Bild neu berechnen (entfernt Metadaten + eingeschleusten Code) und Varianten erzeugen. */
    public static function importImage(string $source, string $base, string $alt = '', ?string $fixedName = null): array
    {
        $info = @getimagesize($source);
        if (!$info || $info[0] < 10 || $info[1] < 10) {
            throw new RuntimeException('Bild konnte nicht gelesen werden.');
        }
        if ($info[0] * $info[1] > 60_000_000) {
            throw new RuntimeException('Bild hat zu viele Pixel.');
        }
        $img = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($source),
            IMAGETYPE_PNG => @imagecreatefrompng($source),
            IMAGETYPE_WEBP => @imagecreatefromwebp($source),
            default => false,
        };
        if (!$img) {
            throw new RuntimeException('Bildformat wird nicht unterstützt.');
        }
        if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $img = self::autoRotate($img, $source);
        }

        $dir = UPLOAD_DIR . '/images';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $name = $fixedName ?? self::uniqueName('images', $base, 'jpg');
        $stem = pathinfo($name, PATHINFO_FILENAME);

        $maxW = (int) config('uploads.image_max_width', 2400);
        $main = self::resize($img, $maxW);
        $flat = self::flatten($main);
        imagejpeg($flat, $dir . '/' . $name, (int) config('uploads.jpeg_quality', 82));
        $w = imagesx($flat);
        $h = imagesy($flat);

        foreach ((array) config('uploads.image_widths', [480, 960, 1600]) as $vw) {
            if ($vw >= $w && $vw !== min((array) config('uploads.image_widths'))) {
                continue;
            }
            $v = self::resize($flat, (int) $vw);
            imagewebp($v, $dir . '/' . $stem . '-' . $vw . '.webp', (int) config('uploads.webp_quality', 78));
            if ($v !== $flat) {
                imagedestroy($v);
            }
        }
        imagedestroy($img);

        return self::register('images/' . $name, [
            'type' => 'image',
            'width' => $w,
            'height' => $h,
            'alt' => $alt,
            'size' => filesize($dir . '/' . $name),
        ]);
    }

    public static function delete(string $key): void
    {
        $all = self::all();
        if (!isset($all[$key])) {
            throw new RuntimeException('Datei nicht gefunden.');
        }
        $path = UPLOAD_DIR . '/' . $key;
        if (is_file($path)) {
            unlink($path);
        }
        if ($all[$key]['type'] === 'image') {
            $stem = pathinfo($key, PATHINFO_FILENAME);
            foreach (glob(UPLOAD_DIR . '/images/' . $stem . '-*.webp') ?: [] as $v) {
                if (preg_match('~-\d+\.webp$~', $v)) {
                    unlink($v);
                }
            }
        }
        unset($all[$key]);
        Store::put('media', $all);
    }

    public static function update(string $key, array $fields): void
    {
        $all = self::all();
        if (!isset($all[$key])) {
            throw new RuntimeException('Datei nicht gefunden.');
        }
        foreach (['alt', 'title'] as $f) {
            if (array_key_exists($f, $fields)) {
                $all[$key][$f] = trim(mb_substr((string) $fields[$f], 0, 300));
            }
        }
        Store::put('media', $all);
    }

    /** Liste der Breiten-Varianten, die für ein Bild existieren. */
    public static function variants(string $publicPath): array
    {
        $key = preg_replace('~^/?uploads/~', '', $publicPath) ?? '';
        $stem = pathinfo($key, PATHINFO_FILENAME);
        $dir = pathinfo($key, PATHINFO_DIRNAME);
        $out = [];
        foreach ((array) config('uploads.image_widths', [480, 960, 1600]) as $w) {
            $rel = $dir . '/' . $stem . '-' . $w . '.webp';
            if (is_file(UPLOAD_DIR . '/' . $rel)) {
                $out[(int) $w] = '/uploads/' . $rel;
            }
        }
        return $out;
    }

    private static function register(string $key, array $meta): array
    {
        $all = self::all();
        $all[$key] = array_merge($all[$key] ?? [], $meta, ['uploaded' => date('c')]);
        ksort($all);
        Store::put('media', $all);
        return ['key' => $key, 'path' => '/uploads/' . $key] + $all[$key];
    }

    private static function uniqueName(string $folder, string $base, string $ext): string
    {
        $name = $base . '.' . $ext;
        $i = 2;
        while (file_exists(UPLOAD_DIR . '/' . $folder . '/' . $name)) {
            $name = $base . '-' . $i++ . '.' . $ext;
        }
        return $name;
    }

    private static function resize(\GdImage $img, int $maxW): \GdImage
    {
        $w = imagesx($img);
        $h = imagesy($img);
        if ($w <= $maxW) {
            return $img;
        }
        $nh = (int) round($h * $maxW / $w);
        $dst = imagecreatetruecolor($maxW, $nh);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $img, 0, 0, 0, 0, $maxW, $nh, $w, $h);
        return $dst;
    }

    private static function flatten(\GdImage $img): \GdImage
    {
        $w = imagesx($img);
        $h = imagesy($img);
        $dst = imagecreatetruecolor($w, $h);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        imagecopy($dst, $img, 0, 0, 0, 0, $w, $h);
        return $dst;
    }

    private static function autoRotate(\GdImage $img, string $file): \GdImage
    {
        $exif = @exif_read_data($file);
        $o = (int) ($exif['Orientation'] ?? 1);
        return match ($o) {
            3 => imagerotate($img, 180, 0) ?: $img,
            6 => imagerotate($img, -90, 0) ?: $img,
            8 => imagerotate($img, 90, 0) ?: $img,
            default => $img,
        };
    }
}
