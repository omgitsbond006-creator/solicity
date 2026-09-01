<?php
define('SOLICITY_ENTRY', true);
require_once dirname(__DIR__) . '/lib/config.php';

$user = require_customer();
$accounts = user_accounts($user['id']);
$pdo = db();

$cardRows = [];
foreach ($accounts as $a) {
    $card = account_card($a['id']);
    if ($card) $cardRows[] = ['card' => $card, 'account' => $a];
}

$page_title = 'Cards';
$shell_sub = 'Personal Banking';
$nav_items = [
    ['href' => url('app/dashboard.php'), 'label' => 'Dashboard', 'icon' => 'grid'],
    ['href' => url('app/transactions.php'), 'label' => 'Transactions', 'icon' => 'list'],
    ['href' => url('app/transfer.php'), 'label' => 'Transfers', 'icon' => 'transfer'],
    ['href' => url('app/cards.php'), 'label' => 'Cards', 'icon' => 'card', 'active' => true],
    ['href' => url('app/settings.php'), 'label' => 'Settings', 'icon' => 'gear'],
];
$chip_name = $user['name']; $chip_role = 'Personal Banking'; $chip_initials = initials($user['name']); $logout_redirect = 'index.php';
require dirname(__DIR__) . '/lib/partials/app-shell-start.php';
$here = 'app/cards.php';
?>
<div class="topbar"><div class="flex gap-1"><div class="page-icon"><?= icon('card') ?></div><div><div class="crumb">Solicity Bank / Cards</div><h1>Cards</h1><div class="sub">Tap a card to flip it. Freeze it instantly if something feels off.</div></div></div></div>

<?php if (!$cardRows): ?>
  <div class="glass panel"><p class="mb-0">No cards issued yet.</p></div>
<?php endif; ?>

<div class="grid cols-2">
<?php foreach ($cardRows as $row): $card = $row['card']; $account = $row['account']; ?>
  <div class="glass panel">
    <div class="panel-title">
      <h3><?= e($account['type']) ?> Card</h3>
      <span class="badge <?= $card['status'] === 'active' ? 'active' : 'frozen' ?>"><?= e(ucfirst($card['status'])) ?></span>
    </div>

    <div class="flip-stage">
      <div class="flip-card">
        <div class="flip-face front">
          <div class="row-top">
            <div class="chip"></div>
            <div class="brandmark">Solicity</div>
          </div>
          <div class="number"><?= e($card['card_number']) ?></div>
          <div class="row-bottom">
            <div class="field"><div class="lbl">Card Holder</div><div class="val"><?= e($card['card_holder']) ?></div></div>
            <div class="field"><div class="lbl">Expires</div><div class="val"><?= e(str_pad($card['exp_month'], 2, '0', STR_PAD_LEFT)) ?>/<?= e(substr($card['exp_year'], -2)) ?></div></div>
            <div class="network"><?= e($card['network']) ?></div>
          </div>
        </div>
        <div class="flip-face back">
          <div class="stripe"></div>
          <div class="cvv-box"><?= e($card['cvv']) ?></div>
          <p class="mb-0" style="font-size:.7rem;color:rgba(242,237,224,.5);margin-top:1rem;">This card is issued for <?= e($account['type']) ?> &middot; ••••<?= e(substr($account['account_number'], -4)) ?>. Tap to flip back.</p>
        </div>
      </div>
    </div>

    <form method="post" action="<?= e(url('api/api.php')) ?>" class="mt-2">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="toggle_card">
      <input type="hidden" name="redirect" value="<?= e($here) ?>">
      <input type="hidden" name="card_id" value="<?= e($card['id']) ?>">
      <button class="btn <?= $card['status'] === 'active' ? 'danger' : '' ?> block" type="submit">
        <?= $card['status'] === 'active' ? 'Freeze this card' : 'Unfreeze this card' ?>
      </button>
    </form>
  </div>
<?php endforeach; ?>
</div>

<?php require dirname(__DIR__) . '/lib/partials/app-shell-end.php'; ?>
