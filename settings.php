<?php
define('SOLICITY_ENTRY', true);
require_once dirname(__DIR__) . '/lib/config.php';

$user = require_customer();
$accounts = user_accounts($user['id']);

$page_title = 'Settings';
$shell_sub = 'Personal Banking';
$nav_items = [
    ['href' => url('app/dashboard.php'), 'label' => 'Dashboard', 'icon' => 'grid'],
    ['href' => url('app/transactions.php'), 'label' => 'Transactions', 'icon' => 'list'],
    ['href' => url('app/transfer.php'), 'label' => 'Transfers', 'icon' => 'transfer'],
    ['href' => url('app/cards.php'), 'label' => 'Cards', 'icon' => 'card'],
    ['href' => url('app/settings.php'), 'label' => 'Settings', 'icon' => 'gear', 'active' => true],
];
$chip_name = $user['name']; $chip_role = 'Personal Banking'; $chip_initials = initials($user['name']); $logout_redirect = 'index.php';
require dirname(__DIR__) . '/lib/partials/app-shell-start.php';
$api = url('api/api.php');
$here = 'app/settings.php';
?>
<div class="topbar"><div class="flex gap-1"><div class="page-icon"><?= icon('gear') ?></div><div><div class="crumb">Solicity Bank / Settings</div><h1>Settings</h1><div class="sub">Manage your profile, security, and accounts.</div></div></div></div>

<div class="grid cols-2">
  <div class="glass panel">
    <div class="panel-title"><h3>Profile</h3></div>
    <form method="post" action="<?= e($api) ?>" class="form-grid">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="update_profile">
      <input type="hidden" name="redirect" value="<?= e($here) ?>">
      <div class="field">
        <label>Full name</label>
        <input type="text" name="name" value="<?= e($user['name']) ?>" required>
      </div>
      <div class="field">
        <label>Email</label>
        <input type="email" value="<?= e($user['email']) ?>" disabled style="opacity:.6;">
        <p class="field-hint">Email can't be changed on this demo.</p>
      </div>
      <div class="field">
        <label>Phone</label>
        <input type="tel" name="phone" value="<?= e($user['phone']) ?>">
      </div>
      <button class="btn block" type="submit">Save changes</button>
    </form>
  </div>

  <div class="glass panel">
    <div class="panel-title"><h3>Security</h3></div>
    <form method="post" action="<?= e($api) ?>" class="form-grid">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="change_password">
      <input type="hidden" name="redirect" value="<?= e($here) ?>">
      <div class="field">
        <label>Current password</label>
        <input type="password" name="current_password" required>
      </div>
      <div class="field">
        <label>New password</label>
        <input type="password" name="new_password" required minlength="6">
      </div>
      <button class="btn subtle block" type="submit">Change password</button>
    </form>
  </div>

  <div class="glass panel" style="grid-column:1/-1;">
    <div class="panel-title"><h3>Your accounts</h3></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Type</th><th>Account number</th><th>Balance</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($accounts as $a): ?>
          <tr>
            <td><?= e($a['type']) ?></td>
            <td><?= e(mask_account($a['account_number'])) ?></td>
            <td><?= money($a['balance']) ?></td>
            <td><span class="badge <?= $a['status'] === 'active' ? 'active' : 'frozen' ?>"><?= e(ucfirst($a['status'])) ?></span></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require dirname(__DIR__) . '/lib/partials/app-shell-end.php'; ?>
