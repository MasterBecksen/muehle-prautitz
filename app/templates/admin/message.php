<?php use Muehle\Admin; ?>
<h1><?= e($title ?? 'Hinweis') ?></h1>
<?php if (!empty($message)): ?><p><?= e($message) ?></p><?php endif; ?>
<p><a class="btn" href="<?= e(Admin::link('dashboard')) ?>">Zur Übersicht</a></p>
