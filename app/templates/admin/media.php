<?php use Muehle\Admin; use Muehle\Media; ?>
<div class="page-head"><h1>Bilder &amp; Dokumente</h1><p class="muted">Bilder werden beim Hochladen automatisch verkleinert, von Metadaten (z. B. GPS-Position) befreit und in schnelle Web-Formate umgewandelt.</p></div>

<section class="panel">
  <h2>Hochladen</h2>
  <form method="post" action="<?= e(Admin::link('media')) ?>" enctype="multipart/form-data" class="upload-form" data-dropzone>
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
    <label class="dropzone">
      <input type="file" name="files[]" multiple accept="image/jpeg,image/png,image/webp,application/pdf" required>
      <span><strong>Dateien hierher ziehen</strong> oder klicken zum Auswählen<br><small>JPG, PNG, WebP oder PDF · max. <?= (int) config('uploads.max_mb') ?> MB pro Datei</small></span>
    </label>
    <div class="fld"><label for="alt">Beschreibung / Titel (optional, für alle gewählten Dateien)</label><input id="alt" name="alt" maxlength="300"></div>
    <button class="btn btn--primary" type="submit">Hochladen</button>
  </form>
</section>

<nav class="filter" aria-label="Filter">
  <a href="<?= e(Admin::link('media')) ?>"<?= $filter === '' ? ' aria-current="page"' : '' ?>>Alle</a>
  <a href="<?= e(Admin::link('media', ['type' => 'image'])) ?>"<?= $filter === 'image' ? ' aria-current="page"' : '' ?>>Bilder</a>
  <a href="<?= e(Admin::link('media', ['type' => 'doc'])) ?>"<?= $filter === 'doc' ? ' aria-current="page"' : '' ?>>PDF-Dokumente</a>
</nav>

<?php if (!$media): ?>
  <p class="muted">Noch keine Dateien vorhanden.</p>
<?php endif; ?>
<ul class="media-grid">
  <?php foreach ($media as $key => $m):
      $path = '/uploads/' . $key;
      $used = $usage[$path] ?? [];
      $v = ($m['type'] ?? '') === 'image' ? Media::variants($path) : []; ?>
    <li class="media-card">
      <a class="media-card__thumb" href="../<?= e(ltrim($path, '/')) ?>" target="_blank" rel="noopener">
        <?php if (($m['type'] ?? '') === 'image'): ?>
          <img src="../<?= e(ltrim($v ? reset($v) : $path, '/')) ?>" alt="" loading="lazy">
        <?php else: ?>
          <span class="doc-badge doc-badge--big">PDF</span>
        <?php endif; ?>
      </a>
      <div class="media-card__body">
        <p class="media-card__name" title="<?= e($key) ?>"><?= e(basename($key)) ?></p>
        <p class="small muted"><?= isset($m['width']) ? (int) $m['width'] . '×' . (int) $m['height'] . ' px · ' : '' ?><?= e(number_format(($m['size'] ?? 0) / 1024, 0, ',', '.')) ?> KB</p>
        <p class="small"><?= $used ? 'Verwendet auf: ' . e(implode(', ', $used)) : '<span class="muted">Nicht verwendet</span>' ?></p>
        <form method="post" action="<?= e(Admin::link('media-update')) ?>" class="media-card__form">
          <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
          <input type="hidden" name="key" value="<?= e($key) ?>">
          <?php if (($m['type'] ?? '') === 'image'): ?>
            <label class="small" for="alt-<?= e(md5($key)) ?>">Bildbeschreibung</label>
            <input id="alt-<?= e(md5($key)) ?>" name="alt" value="<?= e($m['alt'] ?? '') ?>" maxlength="300">
          <?php else: ?>
            <label class="small" for="t-<?= e(md5($key)) ?>">Titel</label>
            <input id="t-<?= e(md5($key)) ?>" name="title" value="<?= e($m['title'] ?? '') ?>" maxlength="300">
          <?php endif; ?>
          <button class="btn btn--small" type="submit">Speichern</button>
        </form>
        <div class="media-card__actions">
          <button type="button" class="btn btn--small btn--quiet" data-copy="<?= e($path) ?>">Pfad kopieren</button>
          <form method="post" action="<?= e(Admin::link('media-delete')) ?>" data-confirm="Datei „<?= e(basename($key)) ?>“ endgültig löschen?">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="key" value="<?= e($key) ?>">
            <button class="btn btn--small btn--danger" type="submit"<?= $used ? ' disabled title="Wird noch verwendet"' : '' ?>>Löschen</button>
          </form>
        </div>
      </div>
    </li>
  <?php endforeach; ?>
</ul>
<p class="help">Tipp: Um eine PDF (z. B. die Preisliste) zu ersetzen, laden Sie die neue Datei hoch und wählen sie auf der Seite „Angelteich“ im Baustein „Downloads“ aus. Danach kann die alte Datei gelöscht werden.</p>
