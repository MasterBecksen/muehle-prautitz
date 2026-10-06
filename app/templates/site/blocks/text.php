<?php use Muehle\Markdown;
$img = (string) ($block['image'] ?? '');
$pos = ($block['image_position'] ?? 'right') === 'left' ? 'left' : 'right';
?>
<section class="section block-text<?= $img ? ' block-text--media block-text--' . $pos : '' ?>"<?= !empty($block['anchor']) ? ' id="' . e($block['anchor']) . '"' : '' ?>>
  <div class="wrap block-text__grid">
    <div class="block-text__body prose">
      <?php if (!empty($block['heading'])): ?><h2><?= e($block['heading']) ?></h2><?php endif; ?>
      <?= Markdown::render((string) ($block['body'] ?? '')) ?>
    </div>
    <?php if ($img): ?>
      <figure class="framed block-text__figure">
        <?= picture($img, (string) ($block['image_alt'] ?? ''), '(min-width: 900px) 45vw, 100vw') ?>
      </figure>
    <?php endif; ?>
  </div>
</section>
