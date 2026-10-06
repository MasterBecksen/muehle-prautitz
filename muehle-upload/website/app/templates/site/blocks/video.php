<?php
$id = preg_replace('~\D~', '', (string) ($block['video_id'] ?? ''));
if (!$id) { return; }
$src = 'https://player.vimeo.com/video/' . $id . '?dnt=1&autoplay=1';
?>
<section class="section block-video">
  <div class="wrap wrap--narrow">
    <?php if (!empty($block['heading'])): ?><h2 class="section-title"><?= e($block['heading']) ?></h2><?php endif; ?>
    <div class="video framed" data-video data-src="<?= e($src) ?>">
      <?= picture((string) ($block['poster'] ?? ''), '', '(min-width: 900px) 800px, 100vw', 'video__poster') ?>
      <div class="video__consent">
        <button type="button" class="video__play" data-video-load>
          <?= icon('play', 'icon video__icon') ?>
          <span>Video laden</span>
        </button>
        <p class="video__note">Beim Abspielen wird eine Verbindung zu Vimeo (USA) hergestellt. <a href="<?= e(url('/datenschutz/')) ?>">Mehr zum Datenschutz</a></p>
      </div>
    </div>
    <?php if (!empty($block['caption'])): ?><p class="video__caption"><?= e($block['caption']) ?></p><?php endif; ?>
  </div>
</section>
