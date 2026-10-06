<?php
declare(strict_types=1);

namespace Muehle;

/** Formularfelder für das Redaktionssystem, passend zum Schema. */
final class AdminForm
{
    /** @var list<array{id:string,title:string}> */
    public static array $hoursGroups = [];

    private static int $n = 0;

    public static function field(string $name, array $def, mixed $value): string
    {
        [$type, $label] = $def;
        $id = 'f' . (++self::$n) . '-' . substr(md5($name), 0, 6);
        $kind = explode(':', $type)[0];
        $v = is_scalar($value) ? (string) $value : '';
        $n = e($name);
        $l = e($label);

        switch ($kind) {
            case 'checkbox':
                return '<div class="fld fld--check"><input type="hidden" name="' . $n . '" value="0"><input type="checkbox" id="' . $id . '" name="' . $n . '" value="1"' . ($value ? ' checked' : '') . '><label for="' . $id . '">' . $l . '</label></div>';
            case 'markdown':
            case 'textarea':
                $rows = $kind === 'markdown' ? max(4, min(16, substr_count($v, "\n") + 3)) : 3;
                $tb = $kind === 'markdown'
                    ? '<div class="md-toolbar" data-md-toolbar><button type="button" data-md="bold" title="Fett"><b>F</b></button><button type="button" data-md="italic" title="Kursiv"><i>K</i></button><button type="button" data-md="link" title="Link">Link</button><button type="button" data-md="list" title="Aufzählung">• Liste</button><button type="button" data-md="h2" title="Zwischenüberschrift">Überschrift</button><span class="md-help">**fett** · *kursiv* · [Text](https://…) · „- “ für Listen</span></div>'
                    : '';
                return '<div class="fld"><label for="' . $id . '">' . $l . '</label>' . $tb . '<textarea id="' . $id . '" name="' . $n . '" rows="' . $rows . '"' . ($kind === 'markdown' ? ' data-md-input' : '') . '>' . e($v) . '</textarea></div>';
            case 'select':
                $opts = '';
                foreach (explode('|', substr($type, 7)) as $o) {
                    [$val, $txt] = array_pad(explode('=', $o, 2), 2, '');
                    $opts .= '<option value="' . e($val) . '"' . ($val === $v ? ' selected' : '') . '>' . e($txt ?: $val) . '</option>';
                }
                return '<div class="fld"><label for="' . $id . '">' . $l . '</label><select id="' . $id . '" name="' . $n . '">' . $opts . '</select></div>';
            case 'image':
            case 'doc':
                $preview = '';
                if ($kind === 'image' && $v !== '') {
                    $vars = Media::variants($v);
                    $preview = '<img src="' . e(url($vars ? reset($vars) : $v)) . '" alt="">';
                }
                return '<div class="fld fld--media" data-media-field data-type="' . $kind . '"><label for="' . $id . '">' . $l . '</label>'
                    . '<div class="media-input"><div class="media-input__preview" data-preview>' . ($kind === 'doc' && $v ? '<span class="doc-badge">PDF</span>' : $preview) . '</div>'
                    . '<input type="text" id="' . $id . '" name="' . $n . '" value="' . e($v) . '" readonly data-media-value placeholder="' . ($kind === 'image' ? 'Kein Bild gewählt' : 'Keine Datei gewählt') . '">'
                    . '<button type="button" class="btn btn--small" data-media-pick>Auswählen</button>'
                    . '<button type="button" class="btn btn--small btn--quiet" data-media-clear title="Entfernen">✕</button></div></div>';
            case 'hoursgroups':
                $sel = is_array($value) ? $value : [];
                $out = '<fieldset class="fld"><legend>' . $l . '</legend><div class="checks">';
                foreach (self::$hoursGroups as $g) {
                    $cid = $id . '-' . e($g['id']);
                    $out .= '<label for="' . $cid . '"><input type="checkbox" id="' . $cid . '" name="' . $n . '[]" value="' . e($g['id']) . '"' . (in_array($g['id'], $sel, true) ? ' checked' : '') . '> ' . e($g['title']) . '</label>';
                }
                return $out . '</div></fieldset>';
            case 'int':
                return '<div class="fld fld--short"><label for="' . $id . '">' . $l . '</label><input type="number" id="' . $id . '" name="' . $n . '" value="' . e($v) . '" min="0" max="999"></div>';
            case 'date':
                return '<div class="fld fld--short"><label for="' . $id . '">' . $l . '</label><input type="date" id="' . $id . '" name="' . $n . '" value="' . e($v) . '"></div>';
            default:
                $extra = $kind === 'link' ? ' placeholder="/seite/ oder https://…"' : '';
                $max = $kind === 'text' ? ' maxlength="300"' : '';
                return '<div class="fld"><label for="' . $id . '">' . $l . '</label><input type="text" id="' . $id . '" name="' . $n . '" value="' . e($v) . '"' . $extra . $max . '></div>';
        }
    }

    /** Editor für einen Baustein (auch als Vorlage mit Platzhalter __KEY__ nutzbar). */
    public static function block(string $key, array $block): string
    {
        $type = (string) ($block['type'] ?? '');
        $schema = Schema::blocks()[$type] ?? null;
        if (!$schema) {
            return '';
        }
        $p = 'blocks[' . $key . ']';
        $title = (string) ($block['heading'] ?? '') ?: Markdown::plain((string) ($block['text'] ?? $block['body'] ?? ''), 50);
        $html = '<section class="block-editor" data-block>'
            . '<header class="block-editor__head">'
            . '<span class="block-editor__handle" aria-hidden="true">⋮⋮</span>'
            . '<button type="button" class="block-editor__toggle" data-block-toggle aria-expanded="true"><strong>' . e($schema['label']) . '</strong> <span class="block-editor__summary">' . e($title) . '</span></button>'
            . '<span class="block-editor__actions">'
            . '<button type="button" class="icon-btn" data-move="up" title="Nach oben">↑</button>'
            . '<button type="button" class="icon-btn" data-move="down" title="Nach unten">↓</button>'
            . '<button type="button" class="icon-btn icon-btn--danger" data-remove="block" title="Baustein entfernen">🗑</button>'
            . '</span></header>'
            . '<div class="block-editor__body">'
            . '<p class="help">' . e($schema['help']) . '</p>'
            . '<input type="hidden" name="' . $p . '[type]" value="' . e($type) . '">';
        $html .= '<div class="grid-fields">';
        foreach ($schema['fields'] as $f => $def) {
            $html .= self::field($p . '[' . $f . ']', $def, $block[$f] ?? ($def[0] === 'checkbox' ? false : ''));
        }
        $html .= '</div>';

        if (isset($schema['list'])) {
            [$lk, $llabel, $single, $defs] = $schema['list'];
            $html .= '<div class="list-editor" data-list><h4>' . e($llabel) . '</h4><ol class="list-editor__items" data-list-items>';
            foreach ((array) ($block[$lk] ?? []) as $i => $item) {
                $html .= self::listItem($p . '[' . $lk . '][' . $i . ']', $defs, (array) $item, $single);
            }
            $html .= '</ol><template data-list-template>' . self::listItem($p . '[' . $lk . '][__IKEY__]', $defs, [], $single) . '</template>'
                . '<button type="button" class="btn btn--small" data-list-add>+ ' . e($single) . ' hinzufügen</button></div>';
        }
        return $html . '</div></section>';
    }

    public static function listItem(string $prefix, array $defs, array $item, string $label): string
    {
        $html = '<li class="list-item" data-item><div class="list-item__fields">';
        foreach ($defs as $f => $def) {
            $html .= self::field($prefix . '[' . $f . ']', $def, $item[$f] ?? '');
        }
        return $html . '</div><div class="list-item__actions">'
            . '<button type="button" class="icon-btn" data-move="up" title="Nach oben">↑</button>'
            . '<button type="button" class="icon-btn" data-move="down" title="Nach unten">↓</button>'
            . '<button type="button" class="icon-btn icon-btn--danger" data-remove="item" title="' . e($label) . ' entfernen">🗑</button>'
            . '</div></li>';
    }
}
