<?php $style = preg_replace('~[^a-z]~', '', (string) ($block['style'] ?? 'features')) ?: 'features';
$items = (array) ($block['items'] ?? []); if (!$items) { return; } ?>
<section class="section block-cards block-cards--<?= e($style) ?>">
  <div class="wrap">
    <?php if (!empty($block['heading'])): ?><h2 class="section-title"><?= e($block['heading']) ?></h2><?php endif; ?>
    <?php if (!empty($block['intro'])): ?><div class="section-intro prose"><?= \Muehle\Markdown::render((string) $block['intro']) ?></div><?php endif; ?>
    <ul class="cards cards--<?= count($items) ?>">
      <?php foreach ($items as $c): ?>
        <li class="card">
          <?php if (!empty($c['image'])): ?>
            <div class="card__media"><?= picture($c['image'], $style === 'people' ? (string) ($c['title'] ?? '') : '', '(min-width: 900px) 33vw, 100vw') ?></div>
          <?php elseif ($style === 'products'): ?>
            <div class="card__badge" aria-hidden="true"><?= icon('fish') ?></div>
          <?php endif; ?>
          <div class="card__body">
            <h3 class="card__title"><?= e($c['title'] ?? '') ?></h3>
            <?php if (!empty($c['text'])): ?><p><?= \Muehle\Markdown::inline((string) $c['text']) ?></p><?php endif; ?>
            <?php if (!empty($c['link'])): ?>
              <a class="card__link" href="<?= e(url((string) $c['link'])) ?>"><?= e($c['link_label'] ?: 'Mehr erfahren') ?> <?= icon('arrow') ?></a>
            <?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
