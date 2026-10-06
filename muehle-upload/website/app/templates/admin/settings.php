<?php use Muehle\Admin; use Muehle\AdminForm; use Muehle\Schema; $cf = (array) ($site['contact_form'] ?? []); ?>
<div class="page-head"><h1>Einstellungen</h1><p class="muted">Kontaktdaten erscheinen in Kopfzeile, Fußzeile, Kontaktbereich und in den Google-Daten.</p></div>
<form method="post" action="<?= e(Admin::link('settings')) ?>" data-unsaved-warning>
  <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
  <section class="panel">
    <h2>Betrieb &amp; Kontakt</h2>
    <div class="grid-fields">
      <?php foreach (Schema::siteFields() as $f => $def): ?><?= AdminForm::field('site[' . $f . ']', $def, $site[$f] ?? '') ?><?php endforeach; ?>
    </div>
  </section>
  <section class="panel">
    <h2>Kontaktformular</h2>
    <div class="grid-fields">
      <?= AdminForm::field('site[contact_form][enabled]', ['checkbox', 'Kontaktformular aktiv'], !empty($cf['enabled'])) ?>
      <?= AdminForm::field('site[contact_form][recipient]', ['text', 'Nachrichten senden an (E-Mail)'], $cf['recipient'] ?? '') ?>
      <?= AdminForm::field('site[contact_form][success]', ['textarea', 'Bestätigungstext nach dem Absenden'], $cf['success'] ?? '') ?>
    </div>
  </section>
  <div class="savebar"><span class="savebar__hint" data-dirty-hint>Keine ungespeicherten Änderungen</span><button class="btn btn--primary btn--large" type="submit">Speichern &amp; veröffentlichen</button></div>
</form>
