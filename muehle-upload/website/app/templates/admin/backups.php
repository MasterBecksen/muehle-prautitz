<?php use Muehle\Admin; ?>
<div class="page-head"><h1>Sicherungen</h1><p class="muted">Vor jeder Änderung wird automatisch der vorherige Stand gesichert (je Datei die letzten <?= (int) config('backups_keep') ?> Stände).</p></div>
<section class="panel">
  <h2>Komplettsicherung</h2>
  <p>Alle Inhalte, Bilder und Dokumente als ZIP-Datei herunterladen – am besten regelmäßig, z. B. einmal im Monat.</p>
  <form method="post" action="<?= e(Admin::link('backup-download')) ?>"><input type="hidden" name="_csrf" value="<?= e($csrf) ?>"><button class="btn btn--primary" type="submit">ZIP herunterladen</button></form>
</section>
<section class="panel">
  <h2>Frühere Stände</h2>
  <?php if (!$backups): ?><p class="muted">Noch keine Sicherungen vorhanden.</p><?php else: ?>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Inhalt</th><th>Gesichert am</th><th><span class="sr-only">Aktion</span></th></tr></thead>
    <tbody>
    <?php foreach ($backups as $b): ?>
      <tr>
        <td><code><?= e($b['name']) ?></code></td>
        <td><?= e(date('d.m.Y H:i:s', $b['time'])) ?></td>
        <td class="actions"><form method="post" action="<?= e(Admin::link('restore')) ?>" data-confirm="Diesen Stand von „<?= e($b['name']) ?>“ wiederherstellen? Der aktuelle Stand wird vorher gesichert.">
          <input type="hidden" name="_csrf" value="<?= e($csrf) ?>"><input type="hidden" name="file" value="<?= e($b['file']) ?>">
          <button class="btn btn--small" type="submit">Wiederherstellen</button></form></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</section>
