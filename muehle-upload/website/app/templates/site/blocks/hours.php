<?php
$wanted = (array) ($block['groups'] ?? []);
$groups = array_values(array_filter($hours, static fn($g) => !$wanted || in_array($g['id'] ?? '', $wanted, true)));
if ($wanted) {
    usort($groups, static fn($a, $b) => array_search($a['id'], $wanted, true) <=> array_search($b['id'], $wanted, true));
}
?>
<section class="section section--wood block-hours" id="<?= e($block['anchor'] ?? 'oeffnungszeiten-' . $index) ?>">
  <div class="wrap">
    <h2 class="section-title section-title--light"><?= e($block['heading'] ?? 'Öffnungszeiten') ?></h2>
    <div class="boards boards--<?= count($groups) ?>">
      <?php foreach ($groups as $g): ?>
        <div class="board">
          <h3 class="board__title"><?= icon('clock') ?> <?= e($g['title'] ?? '') ?></h3>
          <dl class="board__rows">
            <?php foreach ((array) ($g['rows'] ?? []) as $r): ?>
              <div class="board__row"><dt><?= e($r['days'] ?? '') ?></dt><dd><?= e($r['time'] ?? '') ?></dd></div>
            <?php endforeach; ?>
          </dl>
          <?php if (!empty($g['notes'])): ?>
            <ul class="board__notes">
              <?php foreach ((array) $g['notes'] as $note): if (trim((string) $note) === '') { continue; } ?>
                <li><?= \Muehle\Markdown::inline((string) $note) ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
