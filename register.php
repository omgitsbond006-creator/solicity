<?php
define('SOLICITY_ENTRY', true);
require_once __DIR__ . '/lib/config.php';

if (current_user()) redirect('app/dashboard.php');

$page_title = 'Open an account';
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
      <h2 class="serif" style="font-size:2rem;max-width:380px;">Open an account in under two minutes.</h2>
      <p style="max-width:360px;">Checking and savings, a virtual card, and a $250 welcome bonus — no minimum balance, no monthly fee.</p>
    </div>
  </div>
  <div class="auth-form-side">
    <div class="auth-box">
      <?php if ($flash): ?><div class="flash <?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div><?php endif; ?>
      <h1>Open an account</h1>
      <p class="mt-1">Takes two minutes. No paperwork.</p>
      <form method="post" action="<?= e(url('api/api.php')) ?>" class="form-grid mt-2">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="register">
        <div class="field">
          <label for="name">Full name</label>
          <input type="text" id="name" name="name" required placeholder="Jordan Ellis">
        </div>
        <div class="field">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" required placeholder="you@example.com">
        </div>
        <div class="field">
          <label for="phone">Phone</label>
          <input type="tel" id="phone" name="phone" placeholder="+1 (555) 000-0000">
        </div>
        <div class="field">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" required placeholder="At least 6 characters" minlength="6">
        </div>
        <button class="btn block" type="submit">Open my account &rarr;</button>
      </form>
      <p class="field-hint mt-1">By continuing you agree this is a demo platform for illustrative purposes — no real funds are involved.</p>
      <p class="auth-switch">Already have an account? <a href="<?= e(url('login.php')) ?>">Sign in</a></p>
    </div>
  </div>
</div>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
