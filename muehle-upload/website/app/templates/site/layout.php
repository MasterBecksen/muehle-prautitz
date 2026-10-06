<?php
/** @var array $page @var array $site @var array $pages @var array $hours */
use Muehle\Markdown;
use Muehle\Site;

$slug = (string) ($page['slug'] ?? '');
$siteName = (string) ($site['name'] ?? 'Mühle Prautitz');
$metaTitle = trim((string) ($page['meta_title'] ?? '') ?: (string) ($page['title'] ?? ''));
$fullTitle = $slug === 'start' ? $metaTitle : $metaTitle . ' | ' . $siteName;
$desc = (string) ($page['meta_description'] ?? '') ?: Markdown::plain($page['hero']['text'] ?? '');
$baseUrl = rtrim((string) ($site['base_url'] ?? ''), '/');
$canonical = $baseUrl . ($slug === 'start' ? '/' : '/' . $slug . '/');
$ogImage = (string) (($page['hero']['image'] ?? '') ?: ($site['og_image'] ?? ''));
$hero = (array) ($page['hero'] ?? []);
$news = $slug !== '' ? Site::activeNews($slug) : [];
$isPreview = Site::$target === 'github';
$phoneLink = 'tel:' . preg_replace('~[^0-9+]~', '', '+49' . ltrim(preg_replace('~\D~', '', (string) ($site['phone'] ?? '')), '0'));

$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'LocalBusiness',
    'name' => $siteName,
    'description' => (string) ($site['footer_text'] ?? ''),
    'url' => $baseUrl . '/',
    'telephone' => (string) ($site['phone'] ?? ''),
    'email' => (string) ($site['email'] ?? ''),
    'foundingDate' => (string) ($site['since'] ?? ''),
    'address' => [
        '@type' => 'PostalAddress',
        'streetAddress' => (string) ($site['street'] ?? ''),
        'postalCode' => (string) ($site['zip'] ?? ''),
        'addressLocality' => (string) ($site['city'] ?? ''),
        'addressCountry' => 'DE',
    ],
];
if ($ogImage) {
    $jsonLd['image'] = $baseUrl . $ogImage;
}
?><!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="Content-Security-Policy" content="default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; font-src 'self'; connect-src 'self'; frame-src https://player.vimeo.com; form-action 'self'; base-uri 'self'; object-src 'none'">
<title><?= e($fullTitle) ?></title>
<meta name="description" content="<?= e($desc) ?>">
<?php if (!empty($page['noindex']) || $isPreview): ?><meta name="robots" content="noindex, nofollow">
<?php endif; ?>
<link rel="canonical" href="<?= e($canonical) ?>">
<meta property="og:type" content="website">
<meta property="og:locale" content="de_DE">
<meta property="og:site_name" content="<?= e($siteName) ?>">
<meta property="og:title" content="<?= e($metaTitle) ?>">
<meta property="og:description" content="<?= e($desc) ?>">
<meta property="og:url" content="<?= e($canonical) ?>">
<?php if ($ogImage): ?><meta property="og:image" content="<?= e($baseUrl . $ogImage) ?>">
<?php endif; ?>
<meta name="theme-color" content="#2b1d14">
<link rel="icon" href="<?= e(url('/assets/img/favicon.svg')) ?>" type="image/svg+xml">
<link rel="preload" href="<?= e(url('/assets/fonts/vollkorn-latin-wght-normal.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= e(url('/assets/css/site.css')) ?>?v=<?= e(substr(md5_file(PUBLIC_DIR . '/assets/css/site.css') ?: '1', 0, 8)) ?>">
<script src="<?= e(url('/assets/js/site.js')) ?>?v=<?= e(substr(md5_file(PUBLIC_DIR . '/assets/js/site.js') ?: '1', 0, 8)) ?>" defer></script>
<script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
</head>
<body class="page-<?= e($slug) ?>">
<a class="skip-link" href="#inhalt">Zum Inhalt springen</a>

<div class="topbar">
  <div class="wrap topbar__inner">
    <span class="topbar__item"><?= icon('pin') ?> <?= e($site['street'] ?? '') ?>, <?= e($site['city'] ?? '') ?></span>
    <a class="topbar__item" href="<?= e($phoneLink) ?>"><?= icon('phone') ?> <?= e($site['phone'] ?? '') ?></a>
  </div>
</div>

<header class="site-header" data-header>
  <div class="wrap site-header__inner">
    <a class="brand" href="<?= e(url('/')) ?>" aria-label="<?= e($siteName) ?> – zur Startseite">
      <svg class="brand__mark" viewBox="0 0 64 64" aria-hidden="true"><use href="<?= e(url('/assets/img/icons.svg')) ?>#wheel"></use></svg>
      <span class="brand__text">
        <span class="brand__name"><?= e($siteName) ?></span>
        <span class="brand__claim"><?= e($site['claim'] ?? '') ?></span>
      </span>
    </a>
    <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="hauptnavigation" data-nav-toggle>
      <span class="nav-toggle__bars" aria-hidden="true"></span>
      <span class="nav-toggle__label">Menü</span>
    </button>
    <nav class="main-nav" id="hauptnavigation" aria-label="Hauptnavigation" data-nav>
      <ul>
        <?php foreach ($pages as $p): if (empty($p['in_nav']) || !empty($p['draft'])) { continue; } ?>
          <li><a href="<?= e(Site::pageUrl($p['slug'])) ?>"<?= $p['slug'] === $slug ? ' aria-current="page"' : '' ?>><?= e($p['nav_label'] ?? $p['title']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </nav>
  </div>
</header>

<main id="inhalt">
  <?php if (!empty($hero['image'])): ?>
  <section class="hero<?= $slug === 'start' ? ' hero--large' : '' ?>">
    <div class="hero__media"><?= picture($hero['image'], (string) ($hero['image_alt'] ?? ''), '100vw', 'hero__img', false) ?></div>
    <div class="wrap hero__content">
      <?php if (!empty($hero['eyebrow'])): ?><p class="eyebrow"><?= e($hero['eyebrow']) ?></p><?php endif; ?>
      <h1><?= e($hero['heading'] ?? $page['title'] ?? '') ?></h1>
      <?php if (!empty($hero['text'])): ?><p class="hero__text"><?= Markdown::inline((string) $hero['text']) ?></p><?php endif; ?>
      <?php if (!empty($hero['buttons'])): ?>
        <p class="hero__actions">
          <?php foreach ((array) $hero['buttons'] as $i => $b): if (empty($b['label'])) { continue; } ?>
            <a class="btn <?= $i === 0 ? 'btn--primary' : 'btn--ghost' ?>" href="<?= e(url((string) $b['link'])) ?>"><?= e($b['label']) ?></a>
          <?php endforeach; ?>
        </p>
      <?php endif; ?>
    </div>
    <?php if ($slug === 'start'): ?>
      <div class="stamp" aria-hidden="true"><span>seit</span><strong><?= e($site['since'] ?? '1858') ?></strong><span>Prautitz</span></div>
    <?php endif; ?>
  </section>
  <?php else: ?>
  <section class="hero hero--plain">
    <div class="wrap hero__content">
      <?php if (!empty($hero['eyebrow'])): ?><p class="eyebrow"><?= e($hero['eyebrow']) ?></p><?php endif; ?>
      <h1><?= e($hero['heading'] ?? $page['title'] ?? '') ?></h1>
      <?php if (!empty($hero['text'])): ?><p class="hero__text"><?= Markdown::inline((string) $hero['text']) ?></p><?php endif; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($news): ?>
  <section class="news" aria-label="Aktuelle Hinweise">
    <div class="wrap news__list">
      <?php foreach ($news as $n): ?>
        <article class="news-item news-item--<?= e($n['type'] ?? 'info') ?>"<?= !empty($n['until']) ? ' data-until="' . e($n['until']) . '"' : '' ?><?= !empty($n['from']) ? ' data-from="' . e($n['from']) . '"' : '' ?>>
          <?= icon(($n['type'] ?? '') === 'event' ? 'calendar' : (($n['type'] ?? '') === 'warning' ? 'alert' : 'info'), 'icon news-item__icon') ?>
          <div>
            <h2 class="news-item__title"><?= e($n['title'] ?? '') ?></h2>
            <div class="news-item__text"><?= Markdown::render((string) ($n['text'] ?? '')) ?></div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php foreach ((array) ($page['blocks'] ?? []) as $i => $block):
      $type = preg_replace('~[^a-z]~', '', (string) ($block['type'] ?? ''));
      if (!$type || !is_file(APP_DIR . '/templates/site/blocks/' . $type . '.php')) { continue; }
      echo Site::render('site/blocks/' . $type, ['block' => $block, 'site' => $site, 'hours' => $hours, 'index' => $i, 'page' => $page]);
  endforeach; ?>
</main>

<footer class="site-footer">
  <div class="divider divider--light" aria-hidden="true"></div>
  <div class="wrap site-footer__grid">
    <div>
      <p class="site-footer__name"><?= e($siteName) ?></p>
      <p><?= e($site['footer_text'] ?? '') ?></p>
    </div>
    <div>
      <h2 class="site-footer__title">Anschrift</h2>
      <address>
        <?= e($site['owner'] ?? '') ?><br>
        <?= e($site['street'] ?? '') ?><br>
        <?= e(($site['zip'] ?? '') . ' ' . ($site['city'] ?? '')) ?><br>
        <a href="<?= e($phoneLink) ?>"><?= e($site['phone'] ?? '') ?></a><br>
        <a href="mailto:<?= e($site['email'] ?? '') ?>"><?= e($site['email'] ?? '') ?></a>
      </address>
    </div>
    <div>
      <h2 class="site-footer__title">Öffnungszeiten</h2>
      <?php foreach ($hours as $g): if (!in_array($g['id'] ?? '', ['angelteich', 'fischverkauf'], true)) { continue; } ?>
        <p><strong><?= e($g['title'] ?? '') ?>:</strong> <?= e($g['rows'][0]['days'] ?? '') ?>, <?= e($g['rows'][0]['time'] ?? '') ?></p>
      <?php endforeach; ?>
    </div>
    <nav aria-label="Rechtliches und Seiten">
      <h2 class="site-footer__title">Seiten</h2>
      <ul class="site-footer__links">
        <?php foreach ($pages as $p): if (empty($p['in_footer']) && empty($p['in_nav'])) { continue; } if (!empty($p['draft'])) { continue; } ?>
          <li><a href="<?= e(Site::pageUrl($p['slug'])) ?>"><?= e($p['nav_label'] ?? $p['title']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </nav>
  </div>
  <div class="wrap site-footer__bottom">
    <p>© <?= date('Y') ?> <?= e($siteName) ?> · Familie Horbank</p>
  </div>
</footer>
</body>
</html>
