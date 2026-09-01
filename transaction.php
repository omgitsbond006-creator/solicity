<?php
define('SOLICITY_ENTRY', true);
require_once dirname(__DIR__) . '/lib/config.php';

require_admin();
$pdo = db();

$txId = $_GET['id'] ?? '';
$newAccountId = $_GET['account_id'] ?? '';

if ($txId !== '') {
    // ---- edit an existing transaction ----
    $stmt = $pdo->prepare('SELECT t.*, a.type as account_type, a.user_id FROM transactions t JOIN accounts a ON t.account_id = a.id WHERE t.id = ?');
    $stmt->execute([$txId]);
    $tx = $stmt->fetch();
    if (!$tx) { flash_set('error', 'Transaction not found.'); redirect('admin/customers.php'); }
    $accountId = $tx['account_id'];
    $accountType = $tx['account_type'];
    $userId = $tx['user_id'];
} elseif ($newAccountId !== '') {
    // ---- add a new transaction to this account ----
    $stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
    $stmt->execute([$newAccountId]);
    $acc = $stmt->fetch();
    if (!$acc) { flash_set('error', 'Account not found.'); redirect('admin/customers.php'); }
    $tx = null;
    $accountId = $acc['id'];
    $accountType = $acc['type'];
    $userId = $acc['user_id'];
} else {
    redirect('admin/customers.php');
}

$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$userId]);
$customer = $stmt->fetch();
if (!$customer) { flash_set('error', 'Customer not found.'); redirect('admin/customers.php'); }

$externalDetails = null;
if ($tx) {
    $stmt = $pdo->prepare('SELECT * FROM external_transfers WHERE transaction_id = ?');
    $stmt->execute([$tx['id']]);
    $externalDetails = $stmt->fetch() ?: null;
}

$here = 'admin/customer.php?id=' . urlencode($userId);

$page_title = $tx ? 'Edit transaction' : 'Add transaction';
$shell_sub = 'Administrator';
$admin = current_admin();
$nav_items = [
    ['href' => url('admin/index.php'), 'label' => 'Overview', 'icon' => 'grid'],
    ['href' => url('admin/customers.php'), 'label' => 'Customers', 'icon' => 'users', 'active' => true],
    ['href' => url('admin/transactions.php'), 'label' => 'Transactions', 'icon' => 'list'],
];
$chip_name = $admin['name']; $chip_role = 'Administrator'; $chip_initials = initials($admin['name']); $logout_redirect = 'index.php';
require dirname(__DIR__) . '/lib/partials/app-shell-start.php';

$dtValue = $tx ? str_replace(' ', 'T', substr($tx['created_at'], 0, 16)) : date('Y-m-d\TH:i');
?>
<div class="topbar">
  <div class="flex gap-1">
    <div class="page-icon"><?= icon('list') ?></div>
    <div>
      <div class="crumb"><a href="<?= e(url('admin/customers.php')) ?>">Customers</a> / <a href="<?= e(url($here)) ?>"><?= e($customer['name']) ?></a> / <?= $tx ? 'Edit transaction' : 'Add transaction' ?></div>
      <h1><?= $tx ? 'Edit transaction' : 'Add transaction' ?></h1>
      <div class="sub"><?= e($customer['name']) ?> &middot; <?= e($accountType) ?> account</div>
    </div>
  </div>
</div>

<div class="grid cols-2">
  <div class="glass panel">
    <div class="panel-title"><h3><?= $tx ? 'Transaction details' : 'New transaction' ?></h3></div>
    <form method="post" action="<?= e(url('api/api.php')) ?>" class="form-grid">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="<?= $tx ? 'admin_update_transaction' : 'admin_add_transaction' ?>">
      <input type="hidden" name="redirect" value="<?= e($here) ?>">
      <?php if ($tx): ?>
        <input type="hidden" name="transaction_id" value="<?= e($tx['id']) ?>">
      <?php else: ?>
        <input type="hidden" name="account_id" value="<?= e($accountId) ?>">
      <?php endif; ?>
      <div class="field">
        <label>Description</label>
        <input type="text" name="description" required value="<?= e($tx['description'] ?? '') ?>" placeholder="e.g. Amazon">
      </div>
      <div class="field">
        <label>Category</label>
        <input type="text" name="category" value="<?= e($tx['category'] ?? 'General') ?>" placeholder="e.g. Shopping">
      </div>
      <div class="field">
        <label>Amount</label>
        <input type="number" name="amount" step="0.01" required value="<?= e($tx ? number_format((float) $tx['amount'], 2, '.', '') : '') ?>" placeholder="-45.00">
        <p class="field-hint">Positive adds money in, negative takes money out.</p>
      </div>
      <div class="field">
        <label>Date &amp; time</label>
        <input type="datetime-local" name="created_at" value="<?= e($dtValue) ?>">
      </div>
      <button class="btn block" type="submit"><?= $tx ? 'Save changes' : 'Add transaction' ?></button>
    </form>
  </div>

  <div>
    <div class="glass panel">
      <div class="panel-title"><h3>About this account</h3></div>
      <p class="field-hint mb-0">Editing, adding, or deleting a transaction automatically recalculates the running balance for every transaction on this account, and updates the account's current balance to match.</p>
    </div>

    <?php if ($externalDetails): ?>
    <div class="glass panel mt-2">
      <div class="panel-title"><h3>External transfer details</h3></div>
      <div class="form-grid" style="gap:.7rem;">
        <div><span class="field-hint" style="display:block;">Bank name</span><?= e($externalDetails['bank_name']) ?></div>
        <div><span class="field-hint" style="display:block;">Account holder</span><?= e($externalDetails['account_name']) ?></div>
        <div><span class="field-hint" style="display:block;">Account number</span><?= e($externalDetails['account_number']) ?></div>
        <?php if ($externalDetails['routing_number']): ?><div><span class="field-hint" style="display:block;">Routing number</span><?= e($externalDetails['routing_number']) ?></div><?php endif; ?>
        <?php if ($externalDetails['swift_code']): ?><div><span class="field-hint" style="display:block;">SWIFT / BIC code</span><?= e($externalDetails['swift_code']) ?></div><?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($tx): ?>
    <div class="glass panel danger-zone mt-2">
      <div class="panel-title"><h3>Danger zone</h3></div>
      <p class="field-hint mb-1">Permanently removes this transaction and recalculates the account balance.</p>
      <form method="post" action="<?= e(url('api/api.php')) ?>" onsubmit="return confirm('Delete this transaction? The account balance will be recalculated.');">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="admin_delete_transaction">
        <input type="hidden" name="redirect" value="<?= e($here) ?>">
        <input type="hidden" name="transaction_id" value="<?= e($tx['id']) ?>">
        <button class="btn danger block" type="submit"><?= icon('trash') ?> Delete transaction</button>
      </form>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require dirname(__DIR__) . '/lib/partials/app-shell-end.php'; ?>
