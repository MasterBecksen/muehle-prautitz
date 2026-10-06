<div class="page-head"><h1>Protokoll</h1><p class="muted">Anmeldungen, Änderungen und Formular-Ereignisse der letzten Monate. IP-Adressen werden nur gehasht gespeichert.</p></div>
<section class="panel">
  <?php if (!$lines): ?><p class="muted">Keine Einträge.</p><?php else: ?>
  <pre class="log"><?php foreach ($lines as $l): ?><?= e($l) ?>
<?php endforeach; ?></pre>
  <?php endif; ?>
</section>
