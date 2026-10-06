<?php $items = array_values(array_filter((array) ($block['items'] ?? []), static fn($i) => !empty($i['file']))); ?>
<section class="section section--paper block-downloads">
  <div class="wrap block-downloads__inner">
    <div class="prose">
      <?php if (!empty($block['heading'])): ?><h2><?= e($block['heading']) ?></h2><?php endif; ?>
      <?= \Muehle\Markdown::render((string) ($block['intro'] ?? '')) ?>
    </div>
    <ul class="downloads">
      <?php foreach ($items as $d):
          $meta = \Muehle\Media::find($d['file']);
          $size = $meta['size'] ?? (is_file(PUBLIC_DIR . $d['file']) ? filesize(PUBLIC_DIR . $d['file']) : 0); ?>
        <li>
          <a class="download" href="<?= e(url($d['file'])) ?>" target="_blank" rel="noopener">
            <?= icon('file', 'icon download__icon') ?>
            <span class="download__text">
              <strong><?= e($d['label'] ?? 'Dokument') ?></strong>
              <span><?= e($d['description'] ?? '') ?><?= $size ? ' · PDF, ' . e(number_format($size / 1024, 0, ',', '.')) . ' KB' : ' · PDF' ?></span>
            </span>
            <?= icon('download', 'icon download__arrow') ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
