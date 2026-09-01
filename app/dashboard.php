<?php
define('SOLICITY_ENTRY', true);
require_once dirname(__DIR__) . '/lib/config.php';

$user = require_customer();
$accounts = user_accounts($user['id']);
$checking = null; $savings = null;
foreach ($accounts as $a) { if ($a['type'] === 'Checking') $checking = $a; if ($a['type'] === 'Savings') $savings = $a; }
$totalBalance = array_sum(array_column($accounts, 'balance'));

$pdo = db();
$accountIds = array_column($accounts, 'id');
$placeholders = implode(',', array_fill(0, count($accountIds), '?'));

// recent transactions across all accounts
$recentStmt = $pdo->prepare("SELECT t.*, a.type as account_type FROM transactions t JOIN accounts a ON t.account_id = a.id WHERE t.account_id IN ($placeholders) ORDER BY t.created_at DESC LIMIT 8");
$recentStmt->execute($accountIds);
$recent = $recentStmt->fetchAll();

// this month spending (negative amounts, current calendar month) across all accounts
$monthStmt = $pdo->prepare("SELECT COALESCE(SUM(-amount),0) as spent FROM transactions WHERE account_id IN ($placeholders) AND amount < 0 AND DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')");
$monthStmt->execute($accountIds);
$monthSpent = (float) $monthStmt->fetch()['spent'];

// 30-day balance trend for Checking
$trendLabels = []; $trendData = [];
if ($checking) {
    for ($i = 29; $i >= 0; $i--) {
        $day = date('Y-m-d', strtotime("-{$i} days"));
        $stmt = $pdo->prepare("SELECT balance_after FROM transactions WHERE account_id = ? AND date(created_at) <= ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$checking['id'], $day]);
        $row = $stmt->fetch();
        $trendLabels[] = date('M j', strtotime($day));
        $trendData[] = $row ? (float) $row['balance_after'] : null;
    }
    // backfill leading nulls with the earliest known balance
    $firstKnown = null;
    foreach ($trendData as $v) { if ($v !== null) { $firstKnown = $v; break; } }
    foreach ($trendData as $k => $v) { if ($v === null) $trendData[$k] = $firstKnown ?? 0; }
}

// spending by category, last 30 days, all accounts
$catStmt = $pdo->prepare("SELECT category, SUM(-amount) as total FROM transactions WHERE account_id IN ($placeholders) AND amount < 0 AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY category ORDER BY total DESC LIMIT 6");
$catStmt->execute($accountIds);
$catRows = $catStmt->fetchAll();

// 14-day mini balance series for the sparkline in each account tile
function balance_series(PDO $pdo, string $accountId, int $days): array {
    $out = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $day = date('Y-m-d', strtotime("-{$i} days"));
        $stmt = $pdo->prepare("SELECT balance_after FROM transactions WHERE account_id = ? AND date(created_at) <= ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$accountId, $day]);
        $row = $stmt->fetch();
        $out[] = $row ? (float) $row['balance_after'] : null;
    }
    $first = null;
    foreach ($out as $v) { if ($v !== null) { $first = $v; break; } }
    return array_map(fn($v) => $v ?? ($first ?? 0), $out);
}
$checkingSpark = $checking ? balance_series($pdo, $checking['id'], 14) : [];
$savingsSpark = $savings ? balance_series($pdo, $savings['id'], 14) : [];
$isWelcome = ($_GET['welcome'] ?? '') === '1';

$page_title = 'Dashboard';
$shell_sub = 'Personal Banking';
$nav_items = [
    ['href' => url('app/dashboard.php'), 'label' => 'Dashboard', 'icon' => 'grid', 'active' => true],
    ['href' => url('app/transactions.php'), 'label' => 'Transactions', 'icon' => 'list'],
    ['href' => url('app/transfer.php'), 'label' => 'Transfers', 'icon' => 'transfer'],
    ['href' => url('app/cards.php'), 'label' => 'Cards', 'icon' => 'card'],
    ['href' => url('app/settings.php'), 'label' => 'Settings', 'icon' => 'gear'],
];
$chip_name = $user['name'];
$chip_role = 'Personal Banking';
$chip_initials = initials($user['name']);
$logout_redirect = 'index.php';
require dirname(__DIR__) . '/lib/partials/app-shell-start.php';
?>
<div class="topbar">
  <div class="flex gap-1">
    <div class="page-icon"><?= icon('grid') ?></div>
    <div>
      <div class="crumb">Solicity Bank / Dashboard</div>
      <h1>Good to see you, <?= e(explode(' ', $user['name'])[0]) ?>.</h1>
      <div class="sub">Here's where things stand today.</div>
    </div>
  </div>
  <a class="btn" href="<?= e(url('app/transfer.php')) ?>">New transfer</a>
</div>

<div class="tile-row">
  <div class="tile accent">
    <div class="lbl">Total balance</div>
    <div class="num"><?= money($totalBalance) ?></div>
    <span class="delta up">Across <?= count($accounts) ?> accounts</span>
  </div>
  <?php if ($checking): ?>
  <div class="tile">
    <div class="lbl">Checking &middot; <?= e(mask_account($checking['account_number'])) ?></div>
    <div class="num"><?= money($checking['balance']) ?></div>
    <span class="badge <?= $checking['status'] === 'active' ? 'active' : 'frozen' ?>"><?= e(ucfirst($checking['status'])) ?></span>
    <div class="spark-wrap"><canvas class="sparkline" data-points="<?= e(implode(',', $checkingSpark)) ?>" data-color="#d4af6a"></canvas></div>
  </div>
  <?php endif; ?>
  <?php if ($savings): ?>
  <div class="tile">
    <div class="lbl">Savings &middot; <?= e(mask_account($savings['account_number'])) ?></div>
    <div class="num"><?= money($savings['balance']) ?></div>
    <span class="badge <?= $savings['status'] === 'active' ? 'active' : 'frozen' ?>"><?= e(ucfirst($savings['status'])) ?></span>
    <div class="spark-wrap"><canvas class="sparkline" data-points="<?= e(implode(',', $savingsSpark)) ?>" data-color="#3ddc97"></canvas></div>
  </div>
  <?php endif; ?>
  <div class="tile">
    <div class="lbl">Spent this month</div>
    <div class="num"><?= money($monthSpent) ?></div>
    <span class="delta down">Across all accounts</span>
  </div>
</div>

<div class="grid cols-2">
  <div class="glass panel">
    <div class="panel-title"><h3>Balance trend &middot; last 30 days</h3></div>
    <div class="chart-box"><canvas id="trendChart"></canvas></div>
  </div>
  <div class="glass panel">
    <div class="panel-title"><h3>Spending by category</h3></div>
    <?php if ($catRows): ?>
      <div class="chart-box"><canvas id="catChart"></canvas></div>
      <div class="legend-row" id="catLegend"></div>
    <?php else: ?>
      <p class="muted">No spending recorded in the last 30 days yet.</p>
    <?php endif; ?>
  </div>
</div>

<div class="glass panel mt-2">
  <div class="panel-title">
    <h3>Recent activity</h3>
    <a class="btn subtle small" href="<?= e(url('app/transactions.php')) ?>">View all</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Date</th><th>Description</th><th>Account</th><th>Category</th><th>Amount</th></tr></thead>
      <tbody>
      <?php foreach ($recent as $tx): ?>
        <tr>
          <td><?= e(time_ago($tx['created_at'])) ?></td>
          <td><?= e($tx['description']) ?></td>
          <td><?= e($tx['account_type']) ?></td>
          <td><span class="cat-pill"><?= e($tx['category']) ?></span></td>
          <td class="<?= $tx['amount'] >= 0 ? 'amt-pos' : 'amt-neg' ?>"><?= money($tx['amount']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$recent): ?><tr><td colspan="5" class="muted">No activity yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
solicityChartDefaults();
const trendCtx = document.getElementById('trendChart');
if (trendCtx) {
    const grad = trendCtx.getContext('2d').createLinearGradient(0, 0, 0, 260);
    grad.addColorStop(0, 'rgba(212,175,106,.35)');
    grad.addColorStop(1, 'rgba(212,175,106,0)');
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode($trendLabels) ?>,
            datasets: [{
                data: <?= json_encode($trendData) ?>,
                borderColor: '#d4af6a',
                backgroundColor: grad,
                fill: true,
                tension: 0.35,
                pointRadius: 0,
                pointHoverRadius: 5,
                pointHoverBackgroundColor: '#eccb8f',
                borderWidth: 2.5,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            scales: {
                x: { grid: { display: false }, ticks: { maxTicksLimit: 6 } },
                y: { grid: { color: 'rgba(255,255,255,.06)' }, ticks: { callback: v => '$' + v.toLocaleString() } }
            },
            plugins: { tooltip: { callbacks: { label: c => '$' + c.parsed.y.toLocaleString(undefined, {minimumFractionDigits:2}) } } }
        }
    });
}

const catData = <?= json_encode($catRows) ?>;
const catCtx = document.getElementById('catChart');
if (catCtx && catData.length) {
    const palette = ['#d4af6a', '#3ddc97', '#7fa8ff', '#ef6b6b', '#c98bff', '#ffce7f'];
    new Chart(catCtx, {
        type: 'doughnut',
        data: {
            labels: catData.map(r => r.category),
            datasets: [{ data: catData.map(r => r.total), backgroundColor: palette, borderWidth: 0, spacing: 3 }]
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '68%' }
    });
    const legend = document.getElementById('catLegend');
    catData.forEach((r, i) => {
        const span = document.createElement('span');
        span.innerHTML = '<i class="dot" style="background:' + palette[i % palette.length] + '"></i>' + r.category + ' &middot; $' + Number(r.total).toLocaleString(undefined, {minimumFractionDigits:2});
        legend.appendChild(span);
    });
}
});
</script>

<?php if ($isWelcome): ?>
<div class="modal-backdrop" id="welcome-modal" data-once="welcome-seen">
  <div class="modal-box glass">
    <div class="icon-badge"><?= icon('bolt') ?></div>
    <h2 style="font-size:1.5rem;">You're in, <?= e(explode(' ', $user['name'])[0]) ?>.</h2>
    <p>Your Checking and Savings accounts are live, and a $250 welcome bonus just landed. A quick tour of what's here:</p>
    <ul class="modal-feature-list">
      <li><?= icon('chart') ?> Real balance trends and spending breakdowns on your dashboard</li>
      <li><?= icon('transfer') ?> Instant transfers between accounts, to other customers, or to pay a bill</li>
      <li><?= icon('card') ?> A virtual card you can flip to reveal, and freeze in one tap</li>
    </ul>
    <button class="btn block" data-modal-close>Take me to my dashboard</button>
  </div>
</div>
<?php endif; ?>

<?php require dirname(__DIR__) . '/lib/partials/app-shell-end.php'; ?>
