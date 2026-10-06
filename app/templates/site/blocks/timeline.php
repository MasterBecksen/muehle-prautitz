<?php $items = (array) ($block['items'] ?? []); if (!$items) { return; } ?>
<section class="section section--paper block-timeline">
  <div class="wrap">
    <?php if (!empty($block['heading'])): ?><h2 class="section-title"><?= e($block['heading']) ?></h2><?php endif; ?>
    <ol class="timeline">
      <?php foreach ($items as $it): ?>
        <li class="timeline__item">
          <span class="timeline__year"><?= e($it['year'] ?? '') ?></span>
          <div class="timeline__card">
            <h3><?= e($it['title'] ?? '') ?></h3>
            <p><?= \Muehle\Markdown::inline((string) ($it['text'] ?? '')) ?></p>
          </div>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>
