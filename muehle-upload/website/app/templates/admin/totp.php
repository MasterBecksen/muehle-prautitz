<?php use Muehle\Admin; ?>
<h1>Bestätigungscode</h1>
<p>Bitte geben Sie den 6-stelligen Code aus Ihrer Authenticator-App ein.</p>
<form method="post" action="<?= e(Admin::link('totp')) ?>" class="stack">
  <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
  <div class="fld"><label for="c">Code</label><input id="c" name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9 ]{6,7}" maxlength="7" required autofocus class="code-input"></div>
  <button class="btn btn--primary btn--block" type="submit">Bestätigen</button>
</form>
<p class="small"><a href="<?= e(Admin::link('login')) ?>">Zurück zur Anmeldung</a></p>
