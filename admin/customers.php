<?php
define('SOLICITY_ENTRY', true);
require_once dirname(__DIR__) . '/lib/config.php';

require_admin();
$pdo = db();

$search = trim($_GET['q'] ?? '');
$sql = "SELECT u.*, COALESCE(SUM(a.balance),0) as total_balance, COUNT(a.id) as account_count
        FROM users u LEFT JOIN accounts a ON a.user_id = u.id";
$params = [];
if ($search !== '') {
    $sql .= ' WHERE u.name LIKE ? OR u.email LIKE ?';
    $params = ["%$search%", "%$search%"];
}
$sql .= ' GROUP BY u.id ORDER BY u.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();

$page_title = 'Customers';
$shell_sub = 'Administrator';
$admin = current_admin();
$nav_items = [
    ['href' => url('admin/index.php'), 'label' => 'Overview', 'icon' => 'grid'],
    ['href' => url('admin/customers.php'), 'label' => 'Customers', 'icon' => 'users', 'active' => true],
    ['href' => url('admin/transactions.php'), 'label' => 'Transactions', 'icon' => 'list'],
];
$chip_name = $admin['name']; $chip_role = 'Administrator'; $chip_initials = initials($admin['name']); $logout_redirect = 'index.php';
require dirname(__DIR__) . '/lib/partials/app-shell-start.php';
$here = 'admin/customers.php' . ($search !== '' ? '?q=' . urlencode($search) : '');
?>
<div class="topbar">
  <div class="flex gap-1"><div class="page-icon"><?= icon('users') ?></div><div><div class="crumb">Solicity Bank Admin / Customers</div><h1>Customers</h1><div class="sub"><?= count($customers) ?> customers</div></div></div>
  <button class="btn" data-modal-open="add-customer-modal"><?= icon('plus') ?> Add customer</button>
</div>

<div class="glass panel">
  <form method="get" class="form-grid">
    <div class="field mb-0">
      <label>Search by name or email</label>
      <input type="text" name="q" value="<?= e($search) ?>" placeholder="ada@demo.test" onchange="this.form.submit()">
    </div>
  </form>
</div>

<div class="glass panel mt-2">
  <div class="table-wrap">
    <table>
      <thead><tr><th>Name</th><th>Email</th><th>Status</th><th>Accounts</th><th>Total balance</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($customers as $c): ?>
        <tr>
          <td><?= e($c['name']) ?></td>
          <td><?= e($c['email']) ?></td>
          <td><span class="badge <?= $c['status'] === 'active' ? 'active' : 'frozen' ?>"><?= e(ucfirst($c['status'])) ?></span></td>
          <td><?= (int) $c['account_count'] ?></td>
          <td class="amt-pos"><?= money((float) $c['total_balance']) ?></td>
          <td><a class="btn subtle small" href="<?= e(url('admin/customer.php?id=' . urlencode($c['id']))) ?>">Manage &rarr;</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$customers): ?><tr><td colspan="6" class="muted">No customers match that search.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal-backdrop" id="add-customer-modal">
  <div class="modal-box glass wide">
    <button type="button" class="close-x" data-modal-close aria-label="Close">&times;</button>
    <div class="icon-badge"><?= icon('users') ?></div>
    <h2 style="font-size:1.4rem;">Add a customer</h2>
    <p class="field-hint" style="margin-bottom:1.2rem;">Opens a Checking and Savings account and issues a debit card automatically.</p>
    <form method="post" action="<?= e(url('api/api.php')) ?>" class="form-grid">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="admin_create_customer">
      <input type="hidden" name="redirect" value="<?= e($here) ?>">
      <div class="grid cols-2">
        <div class="field mb-0">
          <label>Full name</label>
          <input type="text" name="name" required placeholder="Jordan Ellis">
        </div>
        <div class="field mb-0">
          <label>Email</label>
          <input type="email" name="email" required placeholder="jordan@example.com">
        </div>
      </div>
      <div class="grid cols-2">
        <div class="field mb-0">
          <label>Phone</label>
          <input type="tel" name="phone" placeholder="+1 (555) 000-0000">
        </div>
        <div class="field mb-0">
          <label>Temporary password</label>
          <input type="password" name="password" required minlength="6" placeholder="At least 6 characters">
        </div>
      </div>
      <div class="grid cols-2">
        <div class="field mb-0">
          <label>Opening Checking balance</label>
          <input type="number" name="checking_balance" step="0.01" min="0" value="0.00">
        </div>
        <div class="field mb-0">
          <label>Opening Savings balance</label>
          <input type="number" name="savings_balance" step="0.01" min="0" value="0.00">
        </div>
      </div>
      <div class="field mb-0">
        <label>Status</label>
        <select name="status">
          <option value="active">Active</option>
          <option value="frozen">Frozen</option>
        </select>
      </div>
      <button class="btn block mt-1" type="submit">Create customer</button>
    </form>
  </div>
</div>

<?php require dirname(__DIR__) . '/lib/partials/app-shell-end.php'; ?>
