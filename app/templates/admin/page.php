<?php
use Muehle\Admin;
use Muehle\AdminForm;
use Muehle\Schema;
use Muehle\Site;

AdminForm::$hoursGroups = array_map(static fn($g) => ['id' => $g['id'], 'title' => $g['title']], $hoursGroups);
$slug = $page['slug'];
$hero = (array) ($page['hero'] ?? []);
$buttons = array_pad((array) ($hero['buttons'] ?? []), 2, ['label' => '', 'link' => '']);
$publicUrl = $slug === 'start' ? '../' : '../' . $slug . '/';
?>
<div class="page-head page-head--row">
  <div>
    <p class="crumb"><a href="<?= e(Admin::link('pages')) ?>">Seiten</a> /</p>
    <h1><?= e($page['title'] ?? $slug) ?></h1>
  </div>
  <a class="btn" href="<?= e($publicUrl) ?>" target="_blank" rel="noopener">Seite ansehen ↗</a>
</div>

<form method="post" action="<?= e(Admin::link('page', ['id' => $slug])) ?>" class="page-form" data-page-form data-unsaved-warning>
  <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

  <details class="panel" <?= $slug !== 'start' && empty($page['blocks']) ? 'open' : '' ?>>
    <summary><h2>Seiten-Einstellungen &amp; Google</h2></summary>
    <div class="grid-fields">
      <?php foreach (Schema::pageFields() as $f => $def):
          if ($slug === 'start' && $f === 'draft') { continue; } ?>
        <?= AdminForm::field('page[' . $f . ']', $def, $page[$f] ?? ($def[0] === 'checkbox' ? false : '')) ?>
      <?php endforeach; ?>
    </div>
    <p class="help">Adresse der Seite: <code><?= e($slug === 'start' ? '/' : '/' . $slug . '/') ?></code></p>
  </details>

  <section class="panel">
    <h2>Kopfbereich</h2>
    <div class="grid-fields">
      <?php foreach (Schema::heroFields() as $f => $def): ?>
        <?= AdminForm::field('page[hero][' . $f . ']', $def, $hero[$f] ?? '') ?>
      <?php endforeach; ?>
    </div>
    <h3 class="h-small">Schaltflächen im Kopfbereich (optional)</h3>
    <div class="grid-fields grid-fields--4">
      <?php foreach ($buttons as $i => $b): ?>
        <?= AdminForm::field('page[hero][buttons][' . $i . '][label]', ['text', 'Schaltfläche ' . ($i + 1) . ': Text'], $b['label'] ?? '') ?>
        <?= AdminForm::field('page[hero][buttons][' . $i . '][link]', ['link', 'Schaltfläche ' . ($i + 1) . ': Ziel'], $b['link'] ?? '') ?>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="blocks">
    <div class="blocks__head">
      <h2>Inhalt der Seite</h2>
      <p class="muted small">Bausteine werden in dieser Reihenfolge angezeigt. Mit ↑ ↓ verschieben.</p>
    </div>
    <div data-blocks>
      <?php foreach ((array) ($page['blocks'] ?? []) as $i => $block): ?>
        <?= AdminForm::block((string) $i, (array) $block) ?>
      <?php endforeach; ?>
    </div>
    <div class="add-block">
      <span>Baustein hinzufügen:</span>
      <?php foreach (Schema::blocks() as $type => $s): ?>
        <button type="button" class="btn btn--small" data-add-block="<?= e($type) ?>" title="<?= e($s['help']) ?>">+ <?= e($s['label']) ?></button>
      <?php endforeach; ?>
    </div>
    <?php foreach (Schema::blocks() as $type => $s): ?>
      <template data-block-template="<?= e($type) ?>"><?= AdminForm::block('__KEY__', ['type' => $type]) ?></template>
    <?php endforeach; ?>
  </section>

  <div class="savebar">
    <span class="savebar__hint" data-dirty-hint>Keine ungespeicherten Änderungen</span>
    <button class="btn btn--primary btn--large" type="submit">Speichern &amp; veröffentlichen</button>
  </div>
</form>

<?php if (!in_array($slug, ['start', 'impressum', 'datenschutz'], true)): ?>
<form method="post" action="<?= e(Admin::link('page-delete')) ?>" class="danger-zone" data-confirm="Seite „<?= e($page['title'] ?? '') ?>“ wirklich löschen?">
  <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
  <input type="hidden" name="id" value="<?= e($slug) ?>">
  <button class="btn btn--danger btn--small" type="submit">Seite löschen</button>
</form>
<?php endif; ?>
