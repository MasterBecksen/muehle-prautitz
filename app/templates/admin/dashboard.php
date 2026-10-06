<?php use Muehle\Admin; use Muehle\Site; ?>
<div class="page-head">
  <h1>Guten <?= (int) date('G') < 11 ? 'Morgen' : ((int) date('G') < 18 ? 'Tag' : 'Abend') ?>, <?= e($user['name'] ?? $user['username']) ?>!</h1>
  <p class="muted">Was möchten Sie heute auf der Webseite ändern?</p>
</div>

<div class="tiles">
  <a class="tile tile--accent" href="<?= e(Admin::link('news')) ?>">
    <span class="tile__ico">✎</span><strong>Hinweis veröffentlichen</strong>
    <span>Schließtage, Abfischen, Reservierungen – mit automatischem Ablaufdatum.</span>
  </a>
  <a class="tile" href="<?= e(Admin::link('hours')) ?>">
    <span class="tile__ico">◷</span><strong>Öffnungszeiten ändern</strong>
    <span>Angelteich, Fischverkauf und Mühle.</span>
  </a>
  <a class="tile" href="<?= e(Admin::link('pages')) ?>">
    <span class="tile__ico">▤</span><strong>Seiten bearbeiten</strong>
    <span>Texte, Bilder, Galerien und Downloads.</span>
  </a>
  <a class="tile" href="<?= e(Admin::link('media')) ?>">
    <span class="tile__ico">▣</span><strong>Bilder &amp; PDFs</strong>
    <span><?= (int) $media ?> Dateien – z. B. neue Preisliste hochladen.</span>
  </a>
</div>

<div class="cols">
  <section class="panel">
    <h2>Gerade sichtbare Hinweise</h2>
    <?php if (!$activeNews): ?>
      <p class="muted">Derzeit wird kein Hinweis angezeigt.</p>
    <?php else: ?>
      <ul class="plain-list">
        <?php foreach ($activeNews as $n): ?>
          <li><span class="tag tag--<?= e($n['type'] ?? 'info') ?>"><?= e(['info' => 'Info', 'warning' => 'Achtung', 'event' => 'Termin'][$n['type'] ?? 'info'] ?? 'Info') ?></span>
            <strong><?= e($n['title']) ?></strong>
            <?php if (!empty($n['until'])): ?><span class="muted small">bis <?= e(date_de($n['until'])) ?></span><?php endif; ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
    <?php if ($expiredNews): ?><p class="small muted"><?= (int) $expiredNews ?> abgelaufene(r) Hinweis(e) – werden automatisch nicht mehr angezeigt.</p><?php endif; ?>
    <p><a href="<?= e(Admin::link('news')) ?>">Hinweise verwalten →</a></p>
  </section>

  <section class="panel">
    <h2>Öffnungszeiten (Kurzfassung)</h2>
    <?php foreach ($hours as $g): ?>
      <p><strong><?= e($g['title']) ?>:</strong> <?= e($g['rows'][0]['days'] ?? '') ?>, <?= e($g['rows'][0]['time'] ?? '') ?></p>
    <?php endforeach; ?>
    <p><a href="<?= e(Admin::link('hours')) ?>">Öffnungszeiten bearbeiten →</a></p>
  </section>
</div>

<section class="panel">
  <h2>Webseite</h2>
  <p>Zuletzt erzeugt: <strong><?= $lastBuild ? e(date('d.m.Y, H:i', $lastBuild)) . ' Uhr' : 'noch nie' ?></strong>. Jede gespeicherte Änderung wird sofort veröffentlicht.</p>
  <form method="post" action="<?= e(Admin::link('rebuild')) ?>"><input type="hidden" name="_csrf" value="<?= e($csrf) ?>"><button class="btn" type="submit">Alle Seiten neu erzeugen</button> <span class="small muted">– nur nötig nach einem Software-Update.</span></form>
</section>
