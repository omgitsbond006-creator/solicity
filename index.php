<?php
define('SOLICITY_ENTRY', true);
require_once dirname(__DIR__) . '/lib/config.php';

$admin = current_admin();

if (!$admin):
    $page_title = 'Admin Sign in';
    $flash = flash_take();
    ?><!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($page_title) ?> · Solicity Bank</title>
    <link rel="stylesheet" href="<?= e(asset('css/fonts.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
    </head>
    <body>
    <div class="grain"></div>
    <div class="auth-shell">
      <div class="auth-visual">
        <div class="mesh" style="position:absolute;inset:0;"></div>
        <a class="brand" style="position:relative;z-index:1;" href="<?= e(url('index.php')) ?>"><span class="mark">S</span>Solicity Bank</a>
        <div style="position:relative;z-index:1;">
          <h2 class="serif" style="font-size:2rem;max-width:380px;">Staff access.</h2>
          <p style="max-width:360px;">Bank-wide oversight — balances, accounts, and every transaction, in one place.</p>
        </div>
      </div>
      <div class="auth-form-side">
        <div class="auth-box">
          <?php if ($flash): ?><div class="flash <?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div><?php endif; ?>
          <h1>Admin sign in</h1>
          <p class="mt-1">Staff access to Solicity Bank's back office.</p>
          <form method="post" action="<?= e(url('api/api.php')) ?>" class="form-grid mt-2">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="login_admin">
            <input type="hidden" name="redirect" value="admin/index.php">
            <div class="field">
              <label for="username">Username</label>
              <input type="text" id="username" name="username" required value="admin">
            </div>
            <div class="field">
              <label for="password">Password</label>
              <input type="password" id="password" name="password" required value="admin123">
            </div>
            <button class="btn block" type="submit">Sign in</button>
          </form>
          <p class="auth-hint">Demo admin: <strong>admin</strong> / <strong>admin123</strong></p>
          <p class="auth-switch">Customer? <a href="<?= e(url('login.php')) ?>">Sign in here</a></p>
        </div>
      </div>
    </div>
    <script src="<?= e(asset('js/app.js')) ?>"></script>
    </body>
    </html>
    <?php
    exit;
endif;

// ---- logged in: dashboard ----
$pdo = db();
$totalDeposits = (float) $pdo->query('SELECT COALESCE(SUM(balance),0) t FROM accounts')->fetch()['t'];
$totalCustomers = (int) $pdo->query('SELECT COUNT(*) c FROM users')->fetch()['c'];
$totalAccounts = (int) $pdo->query('SELECT COUNT(*) c FROM accounts')->fetch()['c'];
$frozenCount = (int) $pdo->query("SELECT COUNT(*) c FROM accounts WHERE status = 'frozen'")->fetch()['c'];

$volLabels = []; $volData = [];
for ($i = 13; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-{$i} days"));
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(ABS(amount)),0) v FROM transactions WHERE date(created_at) = ?");
    $stmt->execute([$day]);
    $volLabels[] = date('M j', strtotime($day));
    $volData[] = round((float) $stmt->fetch()['v'], 2);
}

$recentStmt = $pdo->query("SELECT t.*, u.name as user_name, a.type as account_type FROM transactions t JOIN accounts a ON t.account_id = a.id JOIN users u ON a.user_id = u.id ORDER BY t.created_at DESC LIMIT 10");
$recent = $recentStmt->fetchAll();

$page_title = 'Admin Overview';
$shell_sub = 'Administrator';
$nav_items = [
    ['href' => url('admin/index.php'), 'label' => 'Overview', 'icon' => 'grid', 'active' => true],
    ['href' => url('admin/customers.php'), 'label' => 'Customers', 'icon' => 'users'],
    ['href' => url('admin/transactions.php'), 'label' => 'Transactions', 'icon' => 'list'],
];
$chip_name = $admin['name']; $chip_role = 'Administrator'; $chip_initials = initials($admin['name']); $logout_redirect = 'index.php';
require dirname(__DIR__) . '/lib/partials/app-shell-start.php';
?>
<div class="topbar"><div class="flex gap-1"><div class="page-icon"><?= icon('grid') ?></div><div><div class="crumb">Solicity Bank Admin / Overview</div><h1>Welcome, <?= e($admin['name']) ?>.</h1><div class="sub">A snapshot of Solicity Bank right now.</div></div></div></div>

<div class="tile-row">
  <div class="tile accent"><div class="lbl">Total deposits</div><div class="num"><?= money($totalDeposits) ?></div></div>
  <div class="tile"><div class="lbl">Customers</div><div class="num"><?= $totalCustomers ?></div></div>
  <div class="tile"><div class="lbl">Accounts</div><div class="num"><?= $totalAccounts ?></div></div>
  <div class="tile"><div class="lbl">Frozen accounts</div><div class="num"><?= $frozenCount ?></div></div>
</div>

<div class="glass panel">
  <div class="panel-title"><h3>Transaction volume &middot; last 14 days</h3></div>
  <div class="chart-box"><canvas id="volChart"></canvas></div>
</div>

<div class="glass panel mt-2">
  <div class="panel-title">
    <h3>Latest activity</h3>
    <a class="btn subtle small" href="<?= e(url('admin/transactions.php')) ?>">View all</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Date</th><th>Customer</th><th>Description</th><th>Account</th><th>Amount</th></tr></thead>
      <tbody>
      <?php foreach ($recent as $tx): ?>
        <tr>
          <td><?= e(time_ago($tx['created_at'])) ?></td>
          <td><?= e($tx['user_name']) ?></td>
          <td><?= e($tx['description']) ?></td>
          <td><?= e($tx['account_type']) ?></td>
          <td class="<?= $tx['amount'] >= 0 ? 'amt-pos' : 'amt-neg' ?>"><?= money($tx['amount']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  solicityChartDefaults();
  const ctx = document.getElementById('volChart');
  if (ctx) {
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: <?= json_encode($volLabels) ?>,
        datasets: [{ data: <?= json_encode($volData) ?>, backgroundColor: '#d4af6a', borderRadius: 6, maxBarThickness: 28 }]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        scales: {
          x: { grid: { display: false } },
          y: { grid: { color: 'rgba(255,255,255,.06)' }, ticks: { callback: v => '$' + v.toLocaleString() } }
        }
      }
    });
  }
});
</script>

<?php require dirname(__DIR__) . '/lib/partials/app-shell-end.php'; ?>
