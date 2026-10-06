<?php
declare(strict_types=1);

namespace Muehle;

/** Zeitbasierte Einmal-Passwörter (RFC 6238) für die 2-Faktor-Anmeldung. */
final class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function secret(): string
    {
        $bytes = random_bytes(20);
        $bits = '';
        foreach (str_split($bytes) as $c) {
            $bits .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 5) as $chunk) {
            $out .= self::ALPHABET[bindec(str_pad($chunk, 5, '0'))];
        }
        return $out;
    }

    public static function uri(string $secret, string $account, string $issuer): string
    {
        return 'otpauth://totp/' . rawurlencode($issuer . ':' . $account)
            . '?secret=' . $secret . '&issuer=' . rawurlencode($issuer) . '&algorithm=SHA1&digits=6&period=30';
    }

    /** Prüft Code mit ±1 Zeitfenster; gibt das genutzte Zeitfenster zurück (gegen Wiederverwendung). */
    public static function verify(string $secret, string $code, ?int $lastStep = null): ?int
    {
        $code = preg_replace('~\s+~', '', $code) ?? '';
        if (!preg_match('~^\d{6}$~', $code)) {
            return null;
        }
        $key = self::base32Decode($secret);
        $step = intdiv(time(), 30);
        for ($i = -1; $i <= 1; $i++) {
            $s = $step + $i;
            if ($lastStep !== null && $s <= $lastStep) {
                continue;
            }
            if (hash_equals(self::code($key, $s), $code)) {
                return $s;
            }
        }
        return null;
    }

    public static function code(string $key, int $step): string
    {
        $bin = pack('N*', 0) . pack('N*', $step);
        $hash = hash_hmac('sha1', $bin, $key, true);
        $offset = ord($hash[19]) & 0x0f;
        $num = ((ord($hash[$offset]) & 0x7f) << 24) | ((ord($hash[$offset + 1]) & 0xff) << 16)
            | ((ord($hash[$offset + 2]) & 0xff) << 8) | (ord($hash[$offset + 3]) & 0xff);
        return str_pad((string) ($num % 1_000_000), 6, '0', STR_PAD_LEFT);
    }

    public static function base32Decode(string $b32): string
    {
        $b32 = strtoupper(preg_replace('~[^A-Za-z2-7]~', '', $b32) ?? '');
        $bits = '';
        foreach (str_split($b32) as $c) {
            $bits .= str_pad(decbin((int) strpos(self::ALPHABET, $c)), 5, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $out .= chr(bindec($byte));
            }
        }
        return $out;
    }
}
