<?php
declare(strict_types=1);

namespace Muehle;

/**
 * Bewusst kleiner, sicherer Markdown-Umfang für Redakteure.
 * Unterstützt: ## Überschriften, **fett**, *kursiv*, [Link](url), Listen (- / 1.), > Zitat, Zeilenumbruch.
 * Rohes HTML wird immer maskiert – kein XSS über Inhalte möglich.
 */
final class Markdown
{
    public static function render(?string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", trim((string) $text));
        if ($text === '') {
            return '';
        }
        $blocks = preg_split("~\n{2,}~", $text) ?: [];
        $html = [];
        foreach ($blocks as $block) {
            $lines = explode("\n", $block);
            if (preg_match('~^(#{2,4})\s+(.+)$~', $lines[0], $m) && count($lines) === 1) {
                $lvl = strlen($m[1]);
                $html[] = "<h$lvl>" . self::inline($m[2]) . "</h$lvl>";
            } elseif (self::all($lines, '~^\s*[-*]\s+~')) {
                $html[] = '<ul>' . implode('', array_map(
                    static fn($l) => '<li>' . self::inline(preg_replace('~^\s*[-*]\s+~', '', $l) ?? '') . '</li>',
                    $lines
                )) . '</ul>';
            } elseif (self::all($lines, '~^\s*\d+[.)]\s+~')) {
                $html[] = '<ol>' . implode('', array_map(
                    static fn($l) => '<li>' . self::inline(preg_replace('~^\s*\d+[.)]\s+~', '', $l) ?? '') . '</li>',
                    $lines
                )) . '</ol>';
            } elseif (self::all($lines, '~^>\s?~')) {
                $inner = implode('<br>', array_map(
                    static fn($l) => self::inline(preg_replace('~^>\s?~', '', $l) ?? ''),
                    $lines
                ));
                $html[] = "<blockquote><p>$inner</p></blockquote>";
            } else {
                $html[] = '<p>' . implode('<br>', array_map([self::class, 'inline'], $lines)) . '</p>';
            }
        }
        return implode("\n", $html);
    }

    /** Einzeilig, ohne <p> – z. B. für Bildunterschriften. */
    public static function inline(string $text): string
    {
        $s = e($text);
        // Links [Text](url)
        $s = preg_replace_callback('~\[([^\]]+)\]\(([^)\s]+)\)~', static function (array $m): string {
            $href = html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if (!preg_match('~^(https?://|mailto:|tel:|/|#)~i', $href)) {
                return $m[1];
            }
            $external = (bool) preg_match('~^https?://~i', $href);
            $attrs = $external ? ' rel="noopener" target="_blank"' : '';
            return '<a href="' . e(url($href)) . '"' . $attrs . '>' . $m[1] . '</a>';
        }, $s) ?? $s;
        $s = preg_replace('~\*\*(.+?)\*\*~', '<strong>$1</strong>', $s) ?? $s;
        $s = preg_replace('~(?<![*\w])\*(?!\s)(.+?)(?<!\s)\*(?![*\w])~', '<em>$1</em>', $s) ?? $s;
        return $s;
    }

    /** @param list<string> $lines */
    private static function all(array $lines, string $re): bool
    {
        foreach ($lines as $l) {
            if (!preg_match($re, $l)) {
                return false;
            }
        }
        return true;
    }

    /** Reiner Text (für Meta-Beschreibungen). */
    public static function plain(?string $text, int $max = 160): string
    {
        $t = preg_replace(['~\[([^\]]+)\]\([^)]+\)~', '~[*#>_]+~', '~\s+~'], ['$1', '', ' '], (string) $text) ?? '';
        $t = trim($t);
        return mb_strlen($t) > $max ? rtrim(mb_substr($t, 0, $max - 1)) . '…' : $t;
    }
}
