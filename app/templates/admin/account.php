<?php use Muehle\Admin; ?>
<div class="page-head"><h1>Mein Konto</h1><p class="muted">Angemeldet als <strong><?= e($user['username']) ?></strong> (<?= e(\Muehle\Auth::ROLES[$user['role']] ?? $user['role']) ?>)<?= !empty($user['last_login']) ? ' · letzte Anmeldung ' . e(date('d.m.Y H:i', strtotime($user['last_login']))) : '' ?></p></div>
<div class="cols">
<section class="panel">
  <h2>Passwort ändern</h2>
  <form method="post" action="<?= e(Admin::link('account')) ?>" class="stack">
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>"><input type="hidden" name="action" value="password">
    <div class="fld"><label for="cur">Aktuelles Passwort</label><input id="cur" name="current" type="password" autocomplete="current-password" required></div>
    <div class="fld"><label for="new">Neues Passwort</label><input id="new" name="new" type="password" autocomplete="new-password" minlength="12" required data-pw-meter><small class="help">Mindestens 12 Zeichen.</small></div>
    <div class="fld"><label for="new2">Neues Passwort wiederholen</label><input id="new2" name="new2" type="password" autocomplete="new-password" minlength="12" required></div>
    <button class="btn btn--primary" type="submit">Passwort ändern</button>
  </form>
</section>
<section class="panel">
  <h2>2-Faktor-Anmeldung</h2>
  <?php if (!empty($user['totp_enabled'])): ?>
    <p><span class="tag tag--ok">Aktiv</span> Bei der Anmeldung wird zusätzlich ein Code aus Ihrer App abgefragt.</p>
    <form method="post" action="<?= e(Admin::link('account')) ?>" class="stack" data-confirm="2-Faktor-Anmeldung wirklich ausschalten? Ihr Konto ist dann weniger geschützt.">
      <input type="hidden" name="_csrf" value="<?= e($csrf) ?>"><input type="hidden" name="action" value="totp-disable">
      <div class="fld"><label for="cur3">Aktuelles Passwort zur Bestätigung</label><input id="cur3" name="current" type="password" autocomplete="current-password" required></div>
      <button class="btn btn--danger" type="submit">Ausschalten</button>
    </form>
  <?php else: ?>
    <p><span class="tag tag--warning">Aus</span> Empfohlen: Schützen Sie Ihr Konto zusätzlich mit einer App wie <em>Google Authenticator</em>, <em>Microsoft Authenticator</em> oder <em>2FAS</em>.</p>
    <ol class="steps">
      <li>App auf dem Smartphone öffnen und diesen QR-Code scannen:
        <div class="qr" data-qr="<?= e($totpUri) ?>" role="img" aria-label="QR-Code für die Authenticator-App"></div>
        <details><summary class="small">QR-Code lässt sich nicht scannen?</summary><p class="small">Schlüssel manuell eingeben: <code class="secret"><?= e(trim(chunk_split($totpSecret, 4, ' '))) ?></code></p></details>
      </li>
      <li>Den 6-stelligen Code aus der App und Ihr Passwort eingeben:</li>
    </ol>
    <form method="post" action="<?= e(Admin::link('account')) ?>" class="stack">
      <input type="hidden" name="_csrf" value="<?= e($csrf) ?>"><input type="hidden" name="action" value="totp-enable">
      <div class="fld"><label for="code">Code aus der App</label><input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="7" required class="code-input"></div>
      <div class="fld"><label for="cur2">Aktuelles Passwort</label><input id="cur2" name="current" type="password" autocomplete="current-password" required></div>
      <button class="btn btn--primary" type="submit">2-Faktor-Anmeldung aktivieren</button>
    </form>
  <?php endif; ?>
</section>
</div>
