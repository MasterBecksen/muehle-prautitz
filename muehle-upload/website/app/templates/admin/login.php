<?php use Muehle\Admin; ?>
<h1>Anmelden</h1>
<form method="post" action="<?= e(Admin::link('login')) ?>" class="stack">
  <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
  <div class="fld"><label for="u">Benutzername</label><input id="u" name="username" autocomplete="username" required autofocus autocapitalize="none" spellcheck="false"></div>
  <div class="fld"><label for="p">Passwort</label><input id="p" name="password" type="password" autocomplete="current-password" required></div>
  <button class="btn btn--primary btn--block" type="submit">Anmelden</button>
</form>
<p class="muted small">Diese Seite ist nur für die Pflege der Webseite gedacht. Anmeldeversuche werden protokolliert.</p>
