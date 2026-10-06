<?php use Muehle\Admin; ?>
<h1>Ersteinrichtung</h1>
<?php if (!empty($keyMissing)): ?>
  <div class="flash flash--warn">Es ist noch kein Einrichtungsschlüssel hinterlegt. Bitte tragen Sie in <code>app/config.php</code> einen zufälligen <code>setup_key</code> (mind. 16 Zeichen) ein und laden Sie diese Seite neu.</div>
<?php else: ?>
<p>Legen Sie das erste Administrator-Konto an. Danach ist diese Seite automatisch gesperrt.</p>
<form method="post" action="<?= e(Admin::link('setup')) ?>" class="stack">
  <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
  <div class="fld"><label for="k">Einrichtungsschlüssel (aus app/config.php)</label><input id="k" name="setup_key" type="password" required autocomplete="off"></div>
  <div class="fld"><label for="n">Ihr Name</label><input id="n" name="name" autocomplete="name"></div>
  <div class="fld"><label for="u">Benutzername</label><input id="u" name="username" required pattern="[a-z0-9._\-]{3,40}" autocapitalize="none" autocomplete="username"><small class="help">3–40 Zeichen, nur Kleinbuchstaben, Ziffern, . _ -</small></div>
  <div class="fld"><label for="p">Passwort</label><input id="p" name="password" type="password" required minlength="12" autocomplete="new-password" data-pw-meter><small class="help">Mindestens 12 Zeichen. Tipp: ein Satz aus mehreren Wörtern ist sicher und gut zu merken.</small></div>
  <div class="fld"><label for="p2">Passwort wiederholen</label><input id="p2" name="password2" type="password" required minlength="12" autocomplete="new-password"></div>
  <button class="btn btn--primary btn--block" type="submit">Konto anlegen</button>
</form>
<?php endif; ?>
