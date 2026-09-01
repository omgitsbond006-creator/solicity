<?php
define('SOLICITY_ENTRY', true);
require_once dirname(__DIR__) . '/lib/config.php';

require_admin();
$pdo = db();

$search = trim($_GET['q'] ?? '');
$filterType = $_GET['type'] ?? 'all';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;

$where = ['1=1'];
$params = [];
if ($search !== '') {
    $where[] = '(u.name LIKE ? OR u.email LIKE ? OR t.description LIKE ?)';
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}
if ($filterType !== 'all') { $where[] = 't.type = ?'; $params[] = $filterType; }
$whereSql = implode(' AND ', $where);

$base = "FROM transactions t JOIN accounts a ON t.account_id = a.id JOIN users u ON a.user_id = u.id WHERE $whereSql";

$countStmt = $pdo->prepare("SELECT COUNT(*) c $base");
$countStmt->execute($params);
$total = (int) $countStmt->fetch()['c'];
$totalPages = max(1, (int) ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("SELECT t.*, u.id as user_id, u.name as user_name, a.type as account_type $base ORDER BY t.created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$rows = $stmt->fetchAll();

function admin_qs(array $overrides): string {
    return '?' . http_build_query(array_merge($_GET, $overrides));
}

$page_title = 'Transactions';
$shell_sub = 'Administrator';
$admin = current_admin();
$nav_items = [
    ['href' => url('admin/index.php'), 'label' => 'Overview', 'icon' => 'grid'],
    ['href' => url('admin/customers.php'), 'label' => 'Customers', 'icon' => 'users'],
    ['href' => url('admin/transactions.php'), 'label' => 'Transactions', 'icon' => 'list', 'active' => true],
];
$chip_name = $admin['name']; $chip_role = 'Administrator'; $chip_initials = initials($admin['name']); $logout_redirect = 'index.php';
require dirname(__DIR__) . '/lib/partials/app-shell-start.php';
?>
<div class="topbar"><div class="flex gap-1"><div class="page-icon"><?= icon('list') ?></div><div><div class="crumb">Solicity Bank Admin / Transactions</div><h1>Transactions</h1><div class="sub"><?= $total ?> bank-wide results</div></div></div></div>

<div class="glass panel">
  <form method="get" class="grid cols-2">
    <div class="field">
      <label>Search customer or description</label>
      <input type="text" name="q" value="<?= e($search) ?>" placeholder="Name, email, or description">
    </div>
    <div class="field">
      <label>Type</label>
      <select name="type" onchange="this.form.submit()">
        <?php foreach (['all' => 'All types', 'deposit' => 'Deposit', 'withdrawal' => 'Withdrawal', 'transfer_out' => 'Transfer out', 'transfer_in' => 'Transfer in', 'transfer_external' => 'External transfer', 'bill_pay' => 'Bill pay', 'adjustment' => 'Admin adjustment', 'manual' => 'Manual (admin)'] as $val => $label): ?>
          <option value="<?= e($val) ?>" <?= $filterType === $val ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div style="grid-column:1/-1;"><button class="btn subtle small" type="submit">Search</button></div>
  </form>
</div>

<div class="glass panel mt-2">
  <div class="table-wrap">
    <table>
      <thead><tr><th>Date</th><th>Customer</th><th>Description</th><th>Account</th><th>Type</th><th>Amount</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $tx): ?>
        <tr>
          <td><?= e(date('M j, Y g:ia', strtotime($tx['created_at']))) ?></td>
          <td><a href="<?= e(url('admin/customer.php?id=' . urlencode($tx['user_id']))) ?>" style="color:var(--gold);"><?= e($tx['user_name']) ?></a></td>
          <td><?= e($tx['description']) ?></td>
          <td><?= e($tx['account_type']) ?></td>
          <td><span class="cat-pill"><?= e($tx['type']) ?></span></td>
          <td class="<?= $tx['amount'] >= 0 ? 'amt-pos' : 'amt-neg' ?>"><?= money($tx['amount']) ?></td>
          <td><a class="btn subtle small" href="<?= e(url('admin/transaction.php?id=' . urlencode($tx['id']))) ?>">Edit</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="7" class="muted">No transactions match those filters.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($totalPages > 1): ?>
    <div class="flex-between mt-2">
      <span class="muted" style="font-size:.85rem;">Page <?= $page ?> of <?= $totalPages ?></span>
      <div class="flex gap-1">
        <a class="btn subtle small" href="<?= $page > 1 ? e(admin_qs(['page' => $page - 1])) : '#' ?>">&larr; Prev</a>
        <a class="btn subtle small" href="<?= $page < $totalPages ? e(admin_qs(['page' => $page + 1])) : '#' ?>">Next &rarr;</a>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php require dirname(__DIR__) . '/lib/partials/app-shell-end.php'; ?>
