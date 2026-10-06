<?php use Muehle\Admin; use Muehle\AdminForm; use Muehle\Schema;
$today = date('Y-m-d');
$editor = static function (string $k, array $n) use ($pages, $today): string {
    $expired = !empty($n['until']) && $n['until'] < $today;
    $future = !empty($n['from']) && $n['from'] > $today;
    $state = empty($n['active']) ? ['Ausgeschaltet', 'muted'] : ($expired ? ['Abgelaufen', 'muted'] : ($future ? ['Geplant', 'info'] : ['Wird angezeigt', 'ok']));
    ob_start(); ?>
  <section class="block-editor<?= $expired ? ' is-dim' : '' ?>" data-block>
    <header class="block-editor__head">
      <button type="button" class="block-editor__toggle" data-block-toggle aria-expanded="<?= $expired ? 'false' : 'true' ?>"><strong><?= e($n['title'] ?? 'Neuer Hinweis') ?: 'Neuer Hinweis' ?></strong> <?php if ($k !== '__KEY__'): ?><span class="tag tag--<?= $state[1] ?>"><?= $state[0] ?></span><?php endif; ?></button>
      <span class="block-editor__actions">
        <button type="button" class="icon-btn" data-move="up" title="Nach oben">↑</button>
        <button type="button" class="icon-btn" data-move="down" title="Nach unten">↓</button>
        <button type="button" class="icon-btn icon-btn--danger" data-remove="block" title="Hinweis löschen">🗑</button>
      </span>
    </header>
    <div class="block-editor__body"<?= $expired ? ' hidden' : '' ?>>
      <input type="hidden" name="news[<?= $k ?>][id]" value="<?= e($n['id'] ?? '') ?>">
      <div class="grid-fields">
        <?php foreach (Schema::newsFields() as $f => $def): ?>
          <?= AdminForm::field('news[' . $k . '][' . $f . ']', $def, $n[$f] ?? ($f === 'active' ? true : '')) ?>
        <?php endforeach; ?>
      </div>
      <fieldset class="fld"><legend>Auf welchen Seiten anzeigen?</legend><div class="checks">
        <?php foreach ($pages as $p): if (!empty($p['draft'])) { continue; } $cid = 'n' . $k . '-' . $p['slug']; ?>
          <label for="<?= e($cid) ?>"><input type="checkbox" id="<?= e($cid) ?>" name="news[<?= $k ?>][pages][]" value="<?= e($p['slug']) ?>"<?= in_array($p['slug'], (array) ($n['pages'] ?? ($k === '__KEY__' ? ['start'] : [])), true) ? ' checked' : '' ?>> <?= e($p['title']) ?></label>
        <?php endforeach; ?>
      </div></fieldset>
    </div>
  </section>
<?php return (string) ob_get_clean();
};
?>
<div class="page-head page-head--row">
  <div><h1>Aktuelles &amp; Hinweise</h1><p class="muted">Hinweise erscheinen oben auf den gewählten Seiten und verschwinden nach dem Enddatum automatisch.</p></div>
  <button type="button" class="btn btn--primary" data-add-block="news" data-add-top>+ Neuer Hinweis</button>
</div>
<form method="post" action="<?= e(Admin::link('news')) ?>" data-unsaved-warning>
  <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
  <div data-blocks>
    <?php foreach ($news as $i => $n): ?><?= $editor((string) $i, (array) $n) ?><?php endforeach; ?>
    <?php if (!$news): ?><p class="muted empty" data-empty>Noch keine Hinweise vorhanden.</p><?php endif; ?>
  </div>
  <template data-block-template="news"><?= $editor('__KEY__', ['active' => true, 'type' => 'info']) ?></template>
  <div class="savebar"><span class="savebar__hint" data-dirty-hint>Keine ungespeicherten Änderungen</span><button class="btn btn--primary btn--large" type="submit">Speichern &amp; veröffentlichen</button></div>
</form>
