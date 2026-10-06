<?php use Muehle\Admin; /** @var string $content */
$nav = [
    'dashboard' => ['Übersicht', '⌂'],
    'news' => ['Aktuelles', '✎'],
    'hours' => ['Öffnungszeiten', '◷'],
    'pages' => ['Seiten', '▤'],
    'media' => ['Bilder & PDFs', '▣'],
];
$adminNav = [
    'settings' => ['Einstellungen', '⚙'],
    'users' => ['Benutzer', '☺'],
    'backups' => ['Sicherungen', '⟲'],
    'log' => ['Protokoll', '≡'],
];
$active = $route === 'page' ? 'pages' : $route;
$v = static fn(string $f) => substr(md5_file(PUBLIC_DIR . '/admin/assets/' . $f) ?: '1', 0, 8);
?><!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title ?? 'Redaktion') ?> · Mühle Prautitz CMS</title>
<link rel="icon" href="../assets/img/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="assets/admin.css?v=<?= $v('admin.css') ?>">
<script src="assets/admin.js?v=<?= $v('admin.js') ?>" defer></script>
</head>
<body class="<?= $user ? 'is-auth' : 'is-guest' ?>" data-csrf="<?= e($csrf) ?>">
<?php if ($user): ?>
<header class="topbar">
  <a class="topbar__brand" href="<?= e(Admin::link('dashboard')) ?>">
    <img src="../assets/img/favicon.svg" alt="" width="28" height="28"> <span>Mühle Prautitz <small>Redaktion</small></span>
  </a>
  <button class="topbar__menu" type="button" data-sidebar-toggle aria-expanded="false" aria-controls="sidebar">Menü</button>
  <div class="topbar__right">
    <a class="btn btn--small btn--light" href="../" target="_blank" rel="noopener">Webseite ansehen ↗</a>
    <a class="topbar__user" href="<?= e(Admin::link('account')) ?>" title="Mein Konto"><?= e($user['name'] ?? $user['username']) ?></a>
    <form method="post" action="<?= e(Admin::link('logout')) ?>"><input type="hidden" name="_csrf" value="<?= e($csrf) ?>"><button class="btn btn--small btn--light" type="submit">Abmelden</button></form>
  </div>
</header>
<div class="shell">
  <nav class="sidebar" id="sidebar" aria-label="Redaktionsmenü">
    <ul>
      <?php foreach ($nav as $r => [$label, $ico]): ?>
        <li><a href="<?= e(Admin::link($r)) ?>"<?= $active === $r ? ' aria-current="page"' : '' ?>><span class="ico" aria-hidden="true"><?= $ico ?></span><?= e($label) ?></a></li>
      <?php endforeach; ?>
    </ul>
    <?php if (($user['role'] ?? '') === 'admin'): ?>
      <p class="sidebar__label">Verwaltung</p>
      <ul>
        <?php foreach ($adminNav as $r => [$label, $ico]): ?>
          <li><a href="<?= e(Admin::link($r)) ?>"<?= $active === $r ? ' aria-current="page"' : '' ?>><span class="ico" aria-hidden="true"><?= $ico ?></span><?= e($label) ?></a></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
    <p class="sidebar__label">Konto</p>
    <ul><li><a href="<?= e(Admin::link('account')) ?>"<?= $active === 'account' ? ' aria-current="page"' : '' ?>><span class="ico" aria-hidden="true">🔒</span>Mein Konto</a></li></ul>
  </nav>
  <main class="main" id="main">
    <?php foreach ($flash as [$type, $msg]): ?>
      <div class="flash flash--<?= e($type) ?>" role="<?= $type === 'error' ? 'alert' : 'status' ?>"><?= e($msg) ?></div>
    <?php endforeach; ?>
    <?= $content ?>
  </main>
</div>
<?php else: ?>
<main class="guest">
  <div class="guest__card">
    <div class="guest__brand"><img src="../assets/img/favicon.svg" alt="" width="48" height="48"><div><strong>Mühle Prautitz</strong><span>Redaktionssystem</span></div></div>
    <?php foreach ($flash as [$type, $msg]): ?>
      <div class="flash flash--<?= e($type) ?>" role="<?= $type === 'error' ? 'alert' : 'status' ?>"><?= e($msg) ?></div>
    <?php endforeach; ?>
    <?= $content ?>
  </div>
</main>
<?php endif; ?>
<dialog class="picker" data-picker aria-label="Datei auswählen">
  <div class="picker__head">
    <h2 data-picker-title>Bild auswählen</h2>
    <label class="btn btn--small btn--primary picker__upload">Neu hochladen<input type="file" data-picker-upload multiple hidden></label>
    <button type="button" class="icon-btn" data-picker-close title="Schließen">✕</button>
  </div>
  <input type="search" class="picker__search" placeholder="Suchen …" data-picker-search>
  <p class="picker__status" data-picker-status></p>
  <ul class="picker__grid" data-picker-grid></ul>
</dialog>
</body>
</html>
