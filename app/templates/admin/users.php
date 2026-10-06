<?php use Muehle\Admin; use Muehle\Auth; ?>
<div class="page-head"><h1>Benutzer</h1><p class="muted">Redakteure dürfen Inhalte pflegen, Administratoren zusätzlich Einstellungen, Benutzer und Sicherungen verwalten.</p></div>
<div class="table-wrap"><table class="table">
  <thead><tr><th>Benutzer</th><th>Rolle</th><th>2FA</th><th>Letzte Anmeldung</th><th><span class="sr-only">Aktionen</span></th></tr></thead>
  <tbody>
  <?php foreach ($users as $name => $u): ?>
    <tr>
      <td><strong><?= e($u['name'] ?? $name) ?></strong><br><span class="small muted"><?= e($name) ?></span></td>
      <td><?= e(Auth::ROLES[$u['role']] ?? $u['role']) ?></td>
      <td><?= !empty($u['totp_enabled']) ? '<span class="tag tag--ok">an</span>' : '<span class="tag tag--warning">aus</span>' ?></td>
      <td class="small"><?= !empty($u['last_login']) ? e(date('d.m.Y H:i', strtotime($u['last_login']))) : '–' ?></td>
      <td class="actions">
        <?php if ($name !== $user['username']): ?>
        <form method="post" action="<?= e(Admin::link('user-delete')) ?>" data-confirm="Benutzer „<?= e($name) ?>“ löschen?">
          <input type="hidden" name="_csrf" value="<?= e($csrf) ?>"><input type="hidden" name="username" value="<?= e($name) ?>">
          <button class="btn btn--small btn--danger" type="submit">Löschen</button>
        </form>
        <?php else: ?><span class="small muted">(Sie)</span><?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<section class="panel">
  <h2>Neuen Benutzer anlegen</h2>
  <form method="post" action="<?= e(Admin::link('user-add')) ?>" class="grid-fields">
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
    <div class="fld"><label for="un">Name</label><input id="un" name="name"></div>
    <div class="fld"><label for="uu">Benutzername</label><input id="uu" name="username" required pattern="[a-z0-9._\-]{3,40}" autocapitalize="none" autocomplete="off"></div>
    <div class="fld"><label for="up">Startpasswort (mind. 12 Zeichen)</label><input id="up" name="password" type="password" required minlength="12" autocomplete="new-password" data-pw-meter></div>
    <div class="fld"><label for="ur">Rolle</label><select id="ur" name="role"><?php foreach (Auth::ROLES as $k => $l): ?><option value="<?= e($k) ?>"><?= e($l) ?></option><?php endforeach; ?></select></div>
    <div><button class="btn btn--primary" type="submit">Benutzer anlegen</button></div>
  </form>
</section>
