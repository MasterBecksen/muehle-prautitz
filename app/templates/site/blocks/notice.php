<?php $style = in_array($block['style'] ?? '', ['info', 'warning', 'event'], true) ? $block['style'] : 'info'; ?>
<section class="section section--tight">
  <div class="wrap">
    <div class="news-item news-item--<?= e($style) ?> news-item--block" role="note">
      <?= icon($style === 'warning' ? 'alert' : ($style === 'event' ? 'calendar' : 'info'), 'icon news-item__icon') ?>
      <div class="news-item__text"><?= \Muehle\Markdown::render((string) ($block['text'] ?? '')) ?></div>
    </div>
  </div>
</section>
