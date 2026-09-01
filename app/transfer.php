<?php
define('SOLICITY_ENTRY', true);
require_once dirname(__DIR__) . '/lib/config.php';

$user = require_customer();
$accounts = user_accounts($user['id']);

$billers = ['Pacific Gas & Electric', 'City Water Department', 'Comcast Internet', 'Verizon Wireless', 'Meridian Property Group (Rent)', 'Blue Shield Insurance'];

$page_title = 'Transfers';
$shell_sub = 'Personal Banking';
$nav_items = [
    ['href' => url('app/dashboard.php'), 'label' => 'Dashboard', 'icon' => 'grid'],
    ['href' => url('app/transactions.php'), 'label' => 'Transactions', 'icon' => 'list'],
    ['href' => url('app/transfer.php'), 'label' => 'Transfers', 'icon' => 'transfer', 'active' => true],
    ['href' => url('app/cards.php'), 'label' => 'Cards', 'icon' => 'card'],
    ['href' => url('app/settings.php'), 'label' => 'Settings', 'icon' => 'gear'],
];
$chip_name = $user['name']; $chip_role = 'Personal Banking'; $chip_initials = initials($user['name']); $logout_redirect = 'index.php';
require dirname(__DIR__) . '/lib/partials/app-shell-start.php';
$api = url('api/api.php');
$here = 'app/transfer.php';
?>
<div class="topbar"><div class="flex gap-1"><div class="page-icon"><?= icon('transfer') ?></div><div><div class="crumb">Solicity Bank / Transfers</div><h1>Transfers</h1><div class="sub">Move money between accounts, to other customers, or pay a bill.</div></div></div></div>

<div class="grid cols-2">
  <div class="glass panel">
    <div class="panel-title"><h3>Between my accounts</h3></div>
    <form method="post" action="<?= e($api) ?>" class="form-grid">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="transfer_own">
      <input type="hidden" name="redirect" value="<?= e($here) ?>">
      <div class="field">
        <label>From</label>
        <select name="from" required>
          <?php foreach ($accounts as $a): ?><option value="<?= e($a['id']) ?>"><?= e($a['type']) ?> &middot; <?= money($a['balance']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>To</label>
        <select name="to" required>
          <?php foreach (array_reverse($accounts) as $a): ?><option value="<?= e($a['id']) ?>"><?= e($a['type']) ?> &middot; <?= money($a['balance']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Amount</label>
        <input type="number" name="amount" min="0.01" step="0.01" required placeholder="0.00">
      </div>
      <button class="btn block" type="submit">Transfer</button>
    </form>
  </div>

  <div class="glass panel">
    <div class="panel-title"><h3>To another Solicity customer</h3></div>
    <form method="post" action="<?= e($api) ?>" class="form-grid">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="transfer_peer">
      <input type="hidden" name="redirect" value="<?= e($here) ?>">
      <div class="field">
        <label>From</label>
        <select name="from" required>
          <?php foreach ($accounts as $a): ?><option value="<?= e($a['id']) ?>"><?= e($a['type']) ?> &middot; <?= money($a['balance']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Recipient email</label>
        <input type="email" name="to_email" required placeholder="marcus@demo.test">
      </div>
      <div class="field">
        <label>Amount</label>
        <input type="number" name="amount" min="0.01" step="0.01" required placeholder="0.00">
      </div>
      <button class="btn block" type="submit">Send</button>
    </form>
  </div>

  <div class="glass panel" style="grid-column:1 / -1;">
    <div class="panel-title"><h3>To an external bank</h3></div>
    <p class="field-hint mb-1">Send to an account at another bank. Provide a routing number for a domestic transfer, a SWIFT/BIC code for an international one, or both.</p>
    <form method="post" action="<?= e($api) ?>" class="grid cols-2">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="transfer_external">
      <input type="hidden" name="redirect" value="<?= e($here) ?>">
      <div class="field">
        <label>From</label>
        <select name="from" required>
          <?php foreach ($accounts as $a): ?><option value="<?= e($a['id']) ?>"><?= e($a['type']) ?> &middot; <?= money($a['balance']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Amount</label>
        <input type="number" name="amount" min="0.01" step="0.01" required placeholder="0.00">
      </div>
      <div class="field">
        <label>Account holder name</label>
        <input type="text" name="account_name" required placeholder="Full name on the receiving account">
      </div>
      <div class="field">
        <label>Bank name</label>
        <input type="text" name="bank_name" required placeholder="e.g. Chase Bank">
      </div>
      <div class="field">
        <label>Account number</label>
        <input type="text" name="account_number" required placeholder="Receiving account number" inputmode="numeric">
      </div>
      <div class="field">
        <label>Routing number</label>
        <input type="text" name="routing_number" placeholder="9-digit ABA routing number" inputmode="numeric">
      </div>
      <div class="field" style="grid-column:1/-1;">
        <label>SWIFT / BIC code</label>
        <input type="text" name="swift_code" placeholder="For international transfers, e.g. CHASUS33" style="text-transform:uppercase;">
      </div>
      <div style="grid-column:1/-1;">
        <button class="btn subtle" type="submit">Send external transfer</button>
      </div>
    </form>
  </div>

  <div class="glass panel" style="grid-column:1 / -1;">
    <div class="panel-title"><h3>Pay a bill</h3></div>
    <form method="post" action="<?= e($api) ?>" class="grid cols-3" style="align-items:end;">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="pay_bill">
      <input type="hidden" name="redirect" value="<?= e($here) ?>">
      <div class="field">
        <label>From</label>
        <select name="from" required>
          <?php foreach ($accounts as $a): ?><option value="<?= e($a['id']) ?>"><?= e($a['type']) ?> &middot; <?= money($a['balance']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Payee</label>
        <select name="payee" required>
          <?php foreach ($billers as $b): ?><option value="<?= e($b) ?>"><?= e($b) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Amount</label>
        <input type="number" name="amount" min="0.01" step="0.01" required placeholder="0.00">
      </div>
      <div style="grid-column:1/-1;">
        <button class="btn subtle" type="submit">Pay bill</button>
      </div>
    </form>
  </div>
</div>

<?php require dirname(__DIR__) . '/lib/partials/app-shell-end.php'; ?>
