<?php
define('SOLICITY_ENTRY', true);
require_once dirname(__DIR__) . '/lib/config.php';

require_admin();
$pdo = db();

$id = $_GET['id'] ?? '';
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$id]);
$customer = $stmt->fetch();
if (!$customer) { flash_set('error', 'Customer not found.'); redirect('admin/customers.php'); }

$accounts = user_accounts($customer['id']);
$cardsByAccount = [];
foreach ($accounts as $acc) { $cardsByAccount[$acc['id']] = account_card($acc['id']); }
$accountIds = array_column($accounts, 'id');
$txStmt = null;
if ($accountIds) {
    $placeholders = implode(',', array_fill(0, count($accountIds), '?'));
    $txStmt = $pdo->prepare("SELECT t.*, a.type as account_type FROM transactions t JOIN accounts a ON t.account_id = a.id WHERE t.account_id IN ($placeholders) ORDER BY t.created_at DESC LIMIT 20");
    $txStmt->execute($accountIds);
}
$transactions = $txStmt ? $txStmt->fetchAll() : [];

$here = 'admin/customer.php?id=' . urlencode($customer['id']);

$page_title = $customer['name'];
$shell_sub = 'Administrator';
$admin = current_admin();
$nav_items = [
    ['href' => url('admin/index.php'), 'label' => 'Overview', 'icon' => 'grid'],
    ['href' => url('admin/customers.php'), 'label' => 'Customers', 'icon' => 'users', 'active' => true],
    ['href' => url('admin/transactions.php'), 'label' => 'Transactions', 'icon' => 'list'],
];
$chip_name = $admin['name']; $chip_role = 'Administrator'; $chip_initials = initials($admin['name']); $logout_redirect = 'index.php';
require dirname(__DIR__) . '/lib/partials/app-shell-start.php';
?>
<div class="topbar">
  <div class="flex gap-1">
    <div class="page-icon"><?= icon('users') ?></div>
    <div>
      <div class="crumb"><a href="<?= e(url('admin/customers.php')) ?>">Customers</a> / <?= e($customer['name']) ?></div>
      <h1><?= e($customer['name']) ?></h1>
      <div class="sub"><?= e($customer['email']) ?> &middot; <?= e($customer['phone'] ?: 'No phone on file') ?> &middot; joined <?= e(date('M j, Y', strtotime($customer['created_at']))) ?></div>
    </div>
  </div>
  <div class="flex gap-1">
    <span class="badge <?= $customer['status'] === 'active' ? 'active' : 'frozen' ?>"><?= e(ucfirst($customer['status'])) ?></span>
    <form method="post" action="<?= e(url('api/api.php')) ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="admin_toggle_user">
      <input type="hidden" name="redirect" value="<?= e($here) ?>">
      <input type="hidden" name="user_id" value="<?= e($customer['id']) ?>">
      <button class="btn <?= $customer['status'] === 'active' ? 'danger' : '' ?> small" type="submit"><?= $customer['status'] === 'active' ? 'Freeze customer' : 'Unfreeze customer' ?></button>
    </form>
  </div>
</div>

<div class="grid cols-2">
  <?php foreach ($accounts as $acc): ?>
    <div class="glass panel">
      <div class="panel-title">
        <h3><?= e($acc['type']) ?> &middot; <?= e(mask_account($acc['account_number'])) ?></h3>
        <span class="badge <?= $acc['status'] === 'active' ? 'active' : 'frozen' ?>"><?= e(ucfirst($acc['status'])) ?></span>
      </div>
      <p style="font-family:var(--font-display);font-size:1.6rem;margin:0 0 1.2rem;"><?= money($acc['balance']) ?></p>

      <form method="post" action="<?= e(url('api/api.php')) ?>" class="form-grid" style="grid-template-columns:1fr auto;align-items:end;gap:.6rem;margin-bottom:.8rem;">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="admin_set_balance">
        <input type="hidden" name="redirect" value="<?= e($here) ?>">
        <input type="hidden" name="account_id" value="<?= e($acc['id']) ?>">
        <div class="field mb-0">
          <label>New balance</label>
          <input type="number" name="balance" step="0.01" value="<?= e(number_format($acc['balance'], 2, '.', '')) ?>">
        </div>
        <button class="btn small" type="submit">Update</button>
      </form>

      <form method="post" action="<?= e(url('api/api.php')) ?>" class="mt-1">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="admin_toggle_account">
        <input type="hidden" name="redirect" value="<?= e($here) ?>">
        <input type="hidden" name="account_id" value="<?= e($acc['id']) ?>">
        <button class="btn subtle small block" type="submit"><?= $acc['status'] === 'active' ? 'Freeze this account' : 'Unfreeze this account' ?></button>
      </form>

      <?php if ($cardsByAccount[$acc['id']]): ?>
        <p class="field-hint mt-1 mb-0">Card on file: <?= e(mask_card($cardsByAccount[$acc['id']]['card_number'])) ?> &middot; <?= e(ucfirst($cardsByAccount[$acc['id']]['status'])) ?></p>
      <?php else: ?>
        <form method="post" action="<?= e(url('api/api.php')) ?>" class="mt-1">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="admin_issue_card">
          <input type="hidden" name="redirect" value="<?= e($here) ?>">
          <input type="hidden" name="account_id" value="<?= e($acc['id']) ?>">
          <button class="btn ghost small block" type="submit"><?= icon('card') ?> Issue a card for this account</button>
        </form>
      <?php endif; ?>

      <a class="btn ghost small block mt-1" href="<?= e(url('admin/transaction.php?account_id=' . urlencode($acc['id']))) ?>"><?= icon('plus') ?> Add transaction</a>
    </div>
  <?php endforeach; ?>
</div>

<div class="glass panel mt-2">
  <div class="panel-title"><h3>Recent transactions</h3></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Date</th><th>Description</th><th>Account</th><th>Category</th><th>Amount</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($transactions as $tx): ?>
        <tr>
          <td><?= e(date('M j, Y g:ia', strtotime($tx['created_at']))) ?></td>
          <td><?= e($tx['description']) ?></td>
          <td><?= e($tx['account_type']) ?></td>
          <td><span class="cat-pill"><?= e($tx['category']) ?></span></td>
          <td class="<?= $tx['amount'] >= 0 ? 'amt-pos' : 'amt-neg' ?>"><?= money($tx['amount']) ?></td>
          <td>
            <div class="flex gap-1">
              <a class="btn subtle small" href="<?= e(url('admin/transaction.php?id=' . urlencode($tx['id']))) ?>">Edit</a>
              <form method="post" action="<?= e(url('api/api.php')) ?>" onsubmit="return confirm('Delete this transaction? The account balance will be recalculated.');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="admin_delete_transaction">
                <input type="hidden" name="redirect" value="<?= e($here) ?>">
                <input type="hidden" name="transaction_id" value="<?= e($tx['id']) ?>">
                <button class="btn danger small" type="submit">Delete</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$transactions): ?><tr><td colspan="6" class="muted">No transactions yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="grid cols-2 mt-2">
  <div class="glass panel">
    <div class="panel-title"><h3>Edit profile</h3></div>
    <form method="post" action="<?= e(url('api/api.php')) ?>" class="form-grid">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="admin_update_customer">
      <input type="hidden" name="redirect" value="<?= e($here) ?>">
      <input type="hidden" name="user_id" value="<?= e($customer['id']) ?>">
      <div class="field">
        <label>Full name</label>
        <input type="text" name="name" value="<?= e($customer['name']) ?>" required>
      </div>
      <div class="field">
        <label>Email</label>
        <input type="email" name="email" value="<?= e($customer['email']) ?>" required>
      </div>
      <div class="field">
        <label>Phone</label>
        <input type="tel" name="phone" value="<?= e($customer['phone']) ?>">
      </div>
      <div class="field">
        <label>Status</label>
        <select name="status">
          <option value="active" <?= $customer['status'] === 'active' ? 'selected' : '' ?>>Active</option>
          <option value="frozen" <?= $customer['status'] === 'frozen' ? 'selected' : '' ?>>Frozen</option>
        </select>
      </div>
      <button class="btn block" type="submit">Save changes</button>
    </form>
  </div>

  <div>
    <div class="glass panel">
      <div class="panel-title"><h3>Reset password</h3></div>
      <form method="post" action="<?= e(url('api/api.php')) ?>" class="form-grid">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="admin_reset_password">
        <input type="hidden" name="redirect" value="<?= e($here) ?>">
        <input type="hidden" name="user_id" value="<?= e($customer['id']) ?>">
        <div class="field mb-0">
          <label>New password</label>
          <input type="password" name="new_password" required minlength="6" placeholder="At least 6 characters">
        </div>
        <button class="btn subtle block" type="submit"><?= icon('key') ?> Reset password</button>
      </form>
      <p class="field-hint mt-1 mb-0">Sets the customer's password directly — no current password needed.</p>
    </div>

    <div class="glass panel danger-zone mt-2">
      <div class="panel-title"><h3>Danger zone</h3></div>
      <p class="field-hint mb-1">Permanently deletes this customer, their accounts, cards, and transaction history. This can't be undone.</p>
      <form method="post" action="<?= e(url('api/api.php')) ?>" onsubmit="return confirm('Permanently delete <?= e(addslashes($customer['name'])) ?> and all their accounts? This cannot be undone.');">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="admin_delete_customer">
        <input type="hidden" name="user_id" value="<?= e($customer['id']) ?>">
        <button class="btn danger block" type="submit"><?= icon('trash') ?> Delete customer</button>
      </form>
    </div>
  </div>
</div>

<?php require dirname(__DIR__) . '/lib/partials/app-shell-end.php'; ?>
