<?php use Muehle\Admin;
$groupEditor = static function (string $k, array $g): string {
    ob_start(); ?>
  <section class="panel group" data-block>
    <div class="group__head">
      <div class="fld"><label>Bezeichnung der Tafel</label><input type="text" name="groups[<?= $k ?>][title]" value="<?= e($g['title'] ?? '') ?>" required maxlength="80" placeholder="z. B. Angelteich"></div>
      <input type="hidden" name="groups[<?= $k ?>][id]" value="<?= e($g['id'] ?? '') ?>">
      <span class="block-editor__actions">
        <button type="button" class="icon-btn" data-move="up" title="Nach oben">↑</button>
        <button type="button" class="icon-btn" data-move="down" title="Nach unten">↓</button>
        <button type="button" class="icon-btn icon-btn--danger" data-remove="block" title="Tafel entfernen">🗑</button>
      </span>
    </div>
    <div class="list-editor" data-list>
      <h4>Zeiten</h4>
      <ol class="list-editor__items" data-list-items>
        <?php foreach ((array) ($g['rows'] ?? []) as $i => $r): ?>
          <li class="list-item list-item--row" data-item>
            <div class="list-item__fields grid-fields grid-fields--2">
              <div class="fld"><label>Tage</label><input type="text" name="groups[<?= $k ?>][rows][<?= $i ?>][days]" value="<?= e($r['days'] ?? '') ?>" placeholder="Mittwoch bis Sonntag"></div>
              <div class="fld"><label>Uhrzeit / Hinweis</label><input type="text" name="groups[<?= $k ?>][rows][<?= $i ?>][time]" value="<?= e($r['time'] ?? '') ?>" placeholder="08:00 – 18:00 Uhr"></div>
            </div>
            <div class="list-item__actions"><button type="button" class="icon-btn" data-move="up">↑</button><button type="button" class="icon-btn" data-move="down">↓</button><button type="button" class="icon-btn icon-btn--danger" data-remove="item" title="Zeile entfernen">🗑</button></div>
          </li>
        <?php endforeach; ?>
      </ol>
      <template data-list-template>
        <li class="list-item list-item--row" data-item>
          <div class="list-item__fields grid-fields grid-fields--2">
            <div class="fld"><label>Tage</label><input type="text" name="groups[<?= $k ?>][rows][__IKEY__][days]" placeholder="Samstag"></div>
            <div class="fld"><label>Uhrzeit / Hinweis</label><input type="text" name="groups[<?= $k ?>][rows][__IKEY__][time]" placeholder="09:00 – 12:00 Uhr"></div>
          </div>
          <div class="list-item__actions"><button type="button" class="icon-btn" data-move="up">↑</button><button type="button" class="icon-btn" data-move="down">↓</button><button type="button" class="icon-btn icon-btn--danger" data-remove="item">🗑</button></div>
        </li>
      </template>
      <button type="button" class="btn btn--small" data-list-add>+ Zeile hinzufügen</button>
    </div>
    <div class="fld">
      <label>Zusätzliche Hinweise (eine Zeile pro Hinweis)</label>
      <textarea name="groups[<?= $k ?>][notes]" rows="4" placeholder="z. B. Ostersonntag geschlossen"><?= e(implode("\n", (array) ($g['notes'] ?? []))) ?></textarea>
    </div>
  </section>
<?php return (string) ob_get_clean();
};
?>
<div class="page-head"><h1>Öffnungszeiten</h1><p class="muted">Diese Tafeln erscheinen auf den Seiten und in der Fußzeile. Für einmalige Schließtage eignet sich auch ein Hinweis unter „Aktuelles“ mit Ablaufdatum.</p></div>
<form method="post" action="<?= e(Admin::link('hours')) ?>" data-unsaved-warning>
  <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
  <div data-blocks>
    <?php foreach ($groups as $i => $g): ?><?= $groupEditor((string) $i, $g) ?><?php endforeach; ?>
  </div>
  <template data-block-template="group"><?= $groupEditor('__KEY__', ['rows' => [['days' => '', 'time' => '']]]) ?></template>
  <p><button type="button" class="btn" data-add-block="group">+ Neue Tafel</button></p>
  <div class="savebar"><span class="savebar__hint" data-dirty-hint>Keine ungespeicherten Änderungen</span><button class="btn btn--primary btn--large" type="submit">Speichern &amp; veröffentlichen</button></div>
</form>
