<?php
define('SOLICITY_ENTRY', true);
require_once dirname(__DIR__) . '/lib/config.php';

$user = require_customer();
$accounts = user_accounts($user['id']);
$accountIds = array_column($accounts, 'id');
$pdo = db();

$filterAccount = $_GET['account'] ?? 'all';
$filterCategory = $_GET['category'] ?? 'all';
$search = trim($_GET['q'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 15;

$where = ['account_id IN (' . implode(',', array_fill(0, count($accountIds), '?')) . ')'];
$params = $accountIds;

if ($filterAccount !== 'all') {
    $ids = array_column($accounts, 'id', 'type');
    if (isset($ids[$filterAccount])) { $where[] = 'account_id = ?'; $params[] = $ids[$filterAccount]; }
}
if ($filterCategory !== 'all') { $where[] = 'category = ?'; $params[] = $filterCategory; }
if ($search !== '') { $where[] = '(description LIKE ? OR counterparty LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }

$whereSql = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) c FROM transactions WHERE $whereSql");
$countStmt->execute($params);
$total = (int) $countStmt->fetch()['c'];
$totalPages = max(1, (int) ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("SELECT t.*, a.type as account_type FROM transactions t JOIN accounts a ON t.account_id = a.id WHERE $whereSql ORDER BY t.created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$catStmt = $pdo->prepare('SELECT DISTINCT category FROM transactions WHERE account_id IN (' . implode(',', array_fill(0, count($accountIds), '?')) . ') ORDER BY category');
$catStmt->execute($accountIds);
$categories = array_column($catStmt->fetchAll(), 'category');

function qs(array $overrides): string {
    $params = array_merge($_GET, $overrides);
    return '?' . http_build_query($params);
}

$page_title = 'Transactions';
$shell_sub = 'Personal Banking';
$nav_items = [
    ['href' => url('app/dashboard.php'), 'label' => 'Dashboard', 'icon' => 'grid'],
    ['href' => url('app/transactions.php'), 'label' => 'Transactions', 'icon' => 'list', 'active' => true],
    ['href' => url('app/transfer.php'), 'label' => 'Transfers', 'icon' => 'transfer'],
    ['href' => url('app/cards.php'), 'label' => 'Cards', 'icon' => 'card'],
    ['href' => url('app/settings.php'), 'label' => 'Settings', 'icon' => 'gear'],
];
$chip_name = $user['name']; $chip_role = 'Personal Banking'; $chip_initials = initials($user['name']); $logout_redirect = 'index.php';
require dirname(__DIR__) . '/lib/partials/app-shell-start.php';
?>
<div class="topbar">
  <div class="flex gap-1"><div class="page-icon"><?= icon('list') ?></div><div><div class="crumb">Solicity Bank / Transactions</div><h1>Transactions</h1><div class="sub"><?= $total ?> results across your accounts</div></div></div>
</div>

<div class="glass panel">
  <form method="get" class="grid cols-3" style="align-items:end;">
    <div class="field">
      <label>Account</label>
      <select name="account" onchange="this.form.submit()">
        <option value="all" <?= $filterAccount === 'all' ? 'selected' : '' ?>>All accounts</option>
        <?php foreach ($accounts as $a): ?>
          <option value="<?= e($a['type']) ?>" <?= $filterAccount === $a['type'] ? 'selected' : '' ?>><?= e($a['type']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Category</label>
      <select name="category" onchange="this.form.submit()">
        <option value="all" <?= $filterCategory === 'all' ? 'selected' : '' ?>>All categories</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= e($c) ?>" <?= $filterCategory === $c ? 'selected' : '' ?>><?= e($c) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Search</label>
      <input type="text" name="q" value="<?= e($search) ?>" placeholder="Merchant or description">
    </div>
  </form>
</div>

<div class="glass panel mt-2">
  <div class="table-wrap">
    <table>
      <thead><tr><th>Date</th><th>Description</th><th>Account</th><th>Category</th><th>Amount</th><th>Balance after</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $tx): ?>
        <tr>
          <td><?= e(date('M j, Y g:ia', strtotime($tx['created_at']))) ?></td>
          <td><?= e($tx['description']) ?></td>
          <td><?= e($tx['account_type']) ?></td>
          <td><span class="cat-pill"><?= e($tx['category']) ?></span></td>
          <td class="<?= $tx['amount'] >= 0 ? 'amt-pos' : 'amt-neg' ?>"><?= money($tx['amount']) ?></td>
          <td class="muted"><?= money($tx['balance_after']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="6" class="muted">No transactions match those filters.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($totalPages > 1): ?>
    <div class="flex-between mt-2">
      <span class="muted" style="font-size:.85rem;">Page <?= $page ?> of <?= $totalPages ?></span>
      <div class="flex gap-1">
        <a class="btn subtle small <?= $page <= 1 ? 'disabled' : '' ?>" href="<?= $page > 1 ? e(qs(['page' => $page - 1])) : '#' ?>">&larr; Prev</a>
        <a class="btn subtle small" href="<?= $page < $totalPages ? e(qs(['page' => $page + 1])) : '#' ?>">Next &rarr;</a>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php require dirname(__DIR__) . '/lib/partials/app-shell-end.php'; ?>
