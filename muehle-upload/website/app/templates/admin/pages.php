<?php use Muehle\Admin; use Muehle\Site; ?>
<div class="page-head"><h1>Seiten</h1><p class="muted">Klicken Sie auf eine Seite, um Texte und Bilder zu bearbeiten.</p></div>
<div class="table-wrap">
<table class="table">
  <thead><tr><th>Seite</th><th>Adresse</th><th>Menü</th><th>Status</th><th>Geändert</th><th><span class="sr-only">Aktionen</span></th></tr></thead>
  <tbody>
  <?php foreach ($pages as $p): ?>
    <tr>
      <td><a href="<?= e(Admin::link('page', ['id' => $p['slug']])) ?>"><strong><?= e($p['title']) ?></strong></a></td>
      <td><code><?= e($p['slug'] === 'start' ? '/' : '/' . $p['slug'] . '/') ?></code></td>
      <td><?= !empty($p['in_nav']) ? e($p['nav_order'] ?? '') : (!empty($p['in_footer']) ? 'Fußzeile' : '–') ?></td>
      <td><?= !empty($p['draft']) ? '<span class="tag tag--warning">Entwurf</span>' : '<span class="tag tag--ok">Online</span>' ?></td>
      <td class="small muted"><?= !empty($p['updated']) ? e(date('d.m.Y', strtotime($p['updated']))) : '' ?></td>
      <td class="actions">
        <a class="btn btn--small" href="<?= e(Admin::link('page', ['id' => $p['slug']])) ?>">Bearbeiten</a>
        <a class="btn btn--small btn--quiet" href="<?= e($p['slug'] === 'start' ? '../' : '../' . $p['slug'] . '/') ?>" target="_blank" rel="noopener">Ansehen ↗</a>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>

<section class="panel">
  <h2>Neue Seite anlegen</h2>
  <form method="post" action="<?= e(Admin::link('page-new')) ?>" class="inline-form">
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
    <div class="fld"><label for="nt">Titel</label><input id="nt" name="title" required maxlength="80" placeholder="z. B. Veranstaltungen"></div>
    <div class="fld"><label for="ns">Adresse (optional)</label><input id="ns" name="slug" pattern="[a-z0-9-]*" placeholder="veranstaltungen"></div>
    <button class="btn btn--primary" type="submit">Anlegen</button>
  </form>
</section>
