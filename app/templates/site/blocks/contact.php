<?php
use Muehle\Site;
$phone = (string) ($site['phone'] ?? '');
$phoneLink = 'tel:+49' . ltrim(preg_replace('~\D~', '', $phone) ?? '', '0');
$showForm = !empty($block['show_form']) && !empty($site['contact_form']['enabled']);
$formDisabled = Site::$target === 'github';
?>
<section class="section section--paper block-contact" id="kontakt">
  <div class="wrap block-contact__grid">
    <div class="prose">
      <?php if (!empty($block['heading'])): ?><h2><?= e($block['heading']) ?></h2><?php endif; ?>
      <?= \Muehle\Markdown::render((string) ($block['text'] ?? '')) ?>
      <ul class="contact-list">
        <li><?= icon('pin') ?><span><?= e($site['name'] ?? '') ?>, Inh. <?= e($site['owner'] ?? '') ?><br><?= e($site['street'] ?? '') ?><br><?= e(($site['zip'] ?? '') . ' ' . ($site['city'] ?? '')) ?></span></li>
        <li><?= icon('phone') ?><a href="<?= e($phoneLink) ?>"><?= e($phone) ?></a></li>
        <li><?= icon('mail') ?><a href="mailto:<?= e($site['email'] ?? '') ?>"><?= e($site['email'] ?? '') ?></a></li>
      </ul>
      <?php if (!empty($site['map_url'])): ?>
        <p><a class="btn btn--outline" href="<?= e($site['map_url']) ?>" target="_blank" rel="noopener"><?= icon('map') ?> Route planen</a></p>
      <?php endif; ?>
    </div>

    <?php if ($showForm): ?>
    <form class="contact-form" method="post" action="<?= e(url('/api/contact.php')) ?>" data-contact-form<?= $formDisabled ? ' data-disabled' : '' ?> novalidate>
      <h3 class="contact-form__title">Nachricht schreiben</h3>
      <?php if ($formDisabled): ?>
        <p class="form-hint form-hint--warn">Vorschau-Version: Das Formular ist hier deaktiviert. Bitte schreiben Sie an <a href="mailto:<?= e($site['email'] ?? '') ?>"><?= e($site['email'] ?? '') ?></a>.</p>
      <?php endif; ?>
      <div class="field">
        <label for="cf-name">Name <span aria-hidden="true">*</span></label>
        <input id="cf-name" name="name" type="text" autocomplete="name" required maxlength="100">
      </div>
      <div class="field-row">
        <div class="field">
          <label for="cf-email">E-Mail <span aria-hidden="true">*</span></label>
          <input id="cf-email" name="email" type="email" autocomplete="email" required maxlength="150">
        </div>
        <div class="field">
          <label for="cf-phone">Telefon <span class="optional">(optional)</span></label>
          <input id="cf-phone" name="phone" type="tel" autocomplete="tel" maxlength="40">
        </div>
      </div>
      <div class="field">
        <label for="cf-message">Ihre Nachricht <span aria-hidden="true">*</span></label>
        <textarea id="cf-message" name="message" rows="6" required maxlength="5000"></textarea>
      </div>
      <div class="field field--hp" aria-hidden="true">
        <label for="cf-website">Bitte leer lassen</label>
        <input id="cf-website" name="website" type="text" tabindex="-1" autocomplete="off">
      </div>
      <input type="hidden" name="token" value="" data-contact-token>
      <div class="field field--check">
        <input id="cf-privacy" name="privacy" type="checkbox" value="1" required>
        <label for="cf-privacy">Ich habe die <a href="<?= e(url('/datenschutz/')) ?>">Datenschutzerklärung</a> gelesen und bin mit der Verarbeitung meiner Angaben zur Beantwortung einverstanden. <span aria-hidden="true">*</span></label>
      </div>
      <p class="form-status" role="status" aria-live="polite" data-form-status></p>
      <button class="btn btn--primary" type="submit"<?= $formDisabled ? ' disabled' : '' ?>>Nachricht senden</button>
    </form>
    <?php else: ?>
    <figure class="framed block-contact__figure">
      <?= picture('/uploads/images/angelteich-fischerhaus.jpg', 'Der Angelteich mit Fischerhaus', '(min-width: 900px) 45vw, 100vw') ?>
    </figure>
    <?php endif; ?>
  </div>
</section>
