<?php
declare(strict_types=1);

namespace Muehle;

/**
 * Beschreibt alle Inhaltsbausteine. Wird für den Editor (Formulare) und
 * für die serverseitige Bereinigung der Eingaben verwendet (Whitelist).
 */
final class Schema
{
    /** Feldtypen: text, textarea, markdown, image, doc, link, select:<a|b>, checkbox, hoursgroups, int, date */
    public static function blocks(): array
    {
        return [
            'text' => [
                'label' => 'Text (mit Bild)',
                'help' => 'Absatz mit Überschrift, optional mit Foto daneben.',
                'fields' => [
                    'heading' => ['text', 'Überschrift'],
                    'body' => ['markdown', 'Text'],
                    'image' => ['image', 'Bild (optional)'],
                    'image_alt' => ['text', 'Bildbeschreibung (für Blinde & Google)'],
                    'image_position' => ['select:right=Bild rechts|left=Bild links', 'Bildposition'],
                    'anchor' => ['anchor', 'Sprungmarke (optional, z. B. „anfahrt“)'],
                ],
            ],
            'hours' => [
                'label' => 'Öffnungszeiten',
                'help' => 'Zeigt eine oder mehrere Öffnungszeiten-Tafeln. Die Zeiten selbst pflegen Sie unter „Öffnungszeiten“.',
                'fields' => [
                    'heading' => ['text', 'Überschrift'],
                    'groups' => ['hoursgroups', 'Welche Tafeln anzeigen?'],
                    'anchor' => ['anchor', 'Sprungmarke (optional)'],
                ],
            ],
            'gallery' => [
                'label' => 'Bildergalerie',
                'help' => 'Fotos mit Bildunterschrift. Ein Klick vergrößert das Bild.',
                'fields' => [
                    'heading' => ['text', 'Überschrift'],
                    'style' => ['select:grid=Raster|story=Bildergeschichte (untereinander, nummeriert)', 'Darstellung'],
                ],
                'list' => ['images', 'Bilder', 'Bild', [
                    'src' => ['image', 'Bild'],
                    'alt' => ['text', 'Bildbeschreibung'],
                    'caption' => ['text', 'Bildunterschrift'],
                ]],
            ],
            'cards' => [
                'label' => 'Kacheln',
                'help' => 'Mehrere Kästen nebeneinander – z. B. Angebote, Produkte oder Personen.',
                'fields' => [
                    'heading' => ['text', 'Überschrift'],
                    'style' => ['select:features=Mit Bild und Link|products=Produkte (mit Fisch-Symbol)|people=Personen (Hochformat)', 'Darstellung'],
                    'intro' => ['markdown', 'Einleitung (optional)'],
                ],
                'list' => ['items', 'Kacheln', 'Kachel', [
                    'title' => ['text', 'Titel'],
                    'text' => ['textarea', 'Text'],
                    'image' => ['image', 'Bild'],
                    'link' => ['link', 'Link (z. B. /angelteich/)'],
                    'link_label' => ['text', 'Linktext'],
                ]],
            ],
            'notice' => [
                'label' => 'Hinweisbox',
                'help' => 'Dauerhafter Hinweis auf dieser Seite. Für zeitlich begrenzte Meldungen nutzen Sie „Aktuelles“.',
                'fields' => [
                    'style' => ['select:info=Information (blau)|warning=Achtung (rot)|event=Termin (gold)', 'Art'],
                    'text' => ['markdown', 'Text'],
                ],
            ],
            'downloads' => [
                'label' => 'Downloads (PDF)',
                'help' => 'Dokumente wie Teichordnung oder Preisliste.',
                'fields' => [
                    'heading' => ['text', 'Überschrift'],
                    'intro' => ['markdown', 'Einleitung'],
                ],
                'list' => ['items', 'Dokumente', 'Dokument', [
                    'label' => ['text', 'Name'],
                    'file' => ['doc', 'PDF-Datei'],
                    'description' => ['text', 'Kurzbeschreibung'],
                ]],
            ],
            'timeline' => [
                'label' => 'Zeitleiste',
                'help' => 'Geschichte in Jahreszahlen.',
                'fields' => [
                    'heading' => ['text', 'Überschrift'],
                ],
                'list' => ['items', 'Einträge', 'Eintrag', [
                    'year' => ['text', 'Jahr'],
                    'title' => ['text', 'Titel'],
                    'text' => ['textarea', 'Text'],
                ]],
            ],
            'video' => [
                'label' => 'Video (Vimeo)',
                'help' => 'Wird datenschutzfreundlich erst nach Klick geladen.',
                'fields' => [
                    'heading' => ['text', 'Überschrift'],
                    'video_id' => ['digits', 'Vimeo-Nummer (z. B. 261382118)'],
                    'poster' => ['image', 'Vorschaubild'],
                    'caption' => ['text', 'Untertitel'],
                ],
            ],
            'contact' => [
                'label' => 'Kontakt',
                'help' => 'Adresse, Telefon, E-Mail – optional mit Kontaktformular.',
                'fields' => [
                    'heading' => ['text', 'Überschrift'],
                    'text' => ['markdown', 'Text'],
                    'show_form' => ['checkbox', 'Kontaktformular anzeigen'],
                ],
            ],
        ];
    }

    public static function cleanValue(string $type, mixed $v): mixed
    {
        $type = explode(':', $type)[0];
        $s = is_scalar($v) ? (string) $v : '';
        $s = preg_replace('~[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]~u', '', $s) ?? '';
        $s = str_replace("\r\n", "\n", $s);
        return match ($type) {
            'text' => mb_substr(trim(str_replace("\n", ' ', $s)), 0, 300),
            'textarea' => mb_substr(trim($s), 0, 2000),
            'markdown' => mb_substr(trim($s), 0, 20000),
            'image' => preg_match('~^/uploads/images/[a-z0-9][a-z0-9._-]*\.(jpg|jpeg|png|webp)$~', trim($s)) ? trim($s) : '',
            'doc' => preg_match('~^/uploads/docs/[a-z0-9][a-z0-9._-]*\.pdf$~', trim($s)) ? trim($s) : '',
            'link' => self::cleanLink($s),
            'anchor' => slugify($s),
            'digits' => preg_replace('~\D~', '', $s) ?? '',
            'int' => (int) $s,
            'date' => preg_match('~^\d{4}-\d{2}-\d{2}$~', $s) ? $s : '',
            'checkbox' => $s === '1' || $s === 'on',
            'hoursgroups' => array_values(array_filter(array_map('slugify', is_array($v) ? $v : []))),
            'select' => $s,
            default => '',
        };
    }

    public static function cleanLink(string $s): string
    {
        $s = trim($s);
        if ($s === '') {
            return '';
        }
        if (preg_match('~^(https?://[^\s"<>]+|mailto:[^\s"<>]+|tel:[0-9+ /-]+|/[a-z0-9/_#.-]*|#[a-z0-9_-]+)$~i', $s)) {
            return mb_substr($s, 0, 500);
        }
        return '';
    }

    private static function cleanSelect(string $def, string $value): string
    {
        $opts = [];
        foreach (explode('|', substr($def, 7)) as $o) {
            $opts[] = explode('=', $o, 2)[0];
        }
        return in_array($value, $opts, true) ? $value : $opts[0];
    }

    /** Fields nach Definition bereinigen. */
    public static function cleanFields(array $defs, array $input): array
    {
        $out = [];
        foreach ($defs as $name => [$type]) {
            $raw = $input[$name] ?? null;
            $out[$name] = str_starts_with($type, 'select:')
                ? self::cleanSelect($type, is_scalar($raw) ? (string) $raw : '')
                : self::cleanValue($type, $raw);
        }
        return $out;
    }

    public static function cleanBlock(array $input): ?array
    {
        $type = (string) ($input['type'] ?? '');
        $schema = self::blocks()[$type] ?? null;
        if (!$schema) {
            return null;
        }
        $block = ['type' => $type] + self::cleanFields($schema['fields'], $input);
        if (isset($schema['list'])) {
            [$key, , , $itemDefs] = $schema['list'];
            $items = [];
            foreach ((array) ($input[$key] ?? []) as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $clean = self::cleanFields($itemDefs, $item);
                if (implode('', array_map(static fn($v) => is_bool($v) ? '' : (string) $v, $clean)) !== '') {
                    $items[] = $clean;
                }
            }
            $block[$key] = array_slice($items, 0, 100);
        }
        return $block;
    }

    public static function pageFields(): array
    {
        return [
            'title' => ['text', 'Seitentitel'],
            'nav_label' => ['text', 'Name im Menü'],
            'nav_order' => ['int', 'Reihenfolge im Menü'],
            'in_nav' => ['checkbox', 'Im Hauptmenü anzeigen'],
            'in_footer' => ['checkbox', 'In der Fußzeile verlinken'],
            'draft' => ['checkbox', 'Entwurf (nicht veröffentlichen)'],
            'noindex' => ['checkbox', 'Nicht von Suchmaschinen erfassen'],
            'meta_title' => ['text', 'Titel für Google (Browser-Tab)'],
            'meta_description' => ['textarea', 'Beschreibung für Google (ca. 150 Zeichen)'],
        ];
    }

    public static function heroFields(): array
    {
        return [
            'image' => ['image', 'Titelbild'],
            'image_alt' => ['text', 'Bildbeschreibung'],
            'eyebrow' => ['text', 'Kleine Zeile über der Überschrift'],
            'heading' => ['text', 'Große Überschrift'],
            'text' => ['textarea', 'Untertitel'],
        ];
    }

    public static function newsFields(): array
    {
        return [
            'title' => ['text', 'Titel'],
            'text' => ['markdown', 'Text'],
            'type' => ['select:info=Information|warning=Achtung / Schließung|event=Termin', 'Art'],
            'from' => ['date', 'Anzeigen ab (leer = sofort)'],
            'until' => ['date', 'Anzeigen bis einschließlich (leer = unbegrenzt)'],
            'active' => ['checkbox', 'Aktiv'],
            'pinned' => ['checkbox', 'Oben anheften'],
        ];
    }

    public static function siteFields(): array
    {
        return [
            'name' => ['text', 'Name des Betriebs'],
            'claim' => ['text', 'Untertitel im Logo'],
            'since' => ['text', 'Gründungsjahr (Stempel)'],
            'owner' => ['text', 'Inhaber'],
            'street' => ['text', 'Straße und Hausnummer'],
            'zip' => ['text', 'PLZ'],
            'city' => ['text', 'Ort'],
            'phone' => ['text', 'Telefon'],
            'email' => ['text', 'E-Mail'],
            'base_url' => ['link', 'Adresse der Webseite (https://…)'],
            'map_url' => ['link', 'Link zur Karte / Routenplaner'],
            'footer_text' => ['textarea', 'Kurztext in der Fußzeile'],
            'og_image' => ['image', 'Vorschaubild für soziale Netzwerke'],
        ];
    }
}
