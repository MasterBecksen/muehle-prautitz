<?php $images = array_values(array_filter((array) ($block['images'] ?? []), static fn($i) => !empty($i['src'])));
$story = ($block['style'] ?? '') === 'story';
if (!$images) { return; } ?>
<section class="section block-gallery<?= $story ? ' block-gallery--story' : '' ?>">
  <div class="wrap">
    <?php if (!empty($block['heading'])): ?><h2 class="section-title"><?= e($block['heading']) ?></h2><?php endif; ?>
    <ul class="gallery" data-gallery>
      <?php foreach ($images as $n => $img): ?>
        <li class="gallery__item">
          <figure class="framed">
            <a href="<?= e(url($img['src'])) ?>" class="gallery__link" data-lightbox data-caption="<?= e($img['caption'] ?? '') ?>">
              <?= picture($img['src'], (string) ($img['alt'] ?? ''), $story ? '(min-width: 900px) 60vw, 100vw' : '(min-width: 900px) 33vw, (min-width: 600px) 50vw, 100vw') ?>
            </a>
            <?php if (!empty($img['caption'])): ?><figcaption><?php if ($story): ?><span class="gallery__num"><?= $n + 1 ?></span><?php endif; ?><?= \Muehle\Markdown::inline((string) $img['caption']) ?></figcaption><?php endif; ?>
          </figure>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
