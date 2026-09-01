<?php
define('SOLICITY_ENTRY', true);
require_once __DIR__ . '/lib/config.php';

if (current_user()) redirect('app/dashboard.php');

$page_title = 'Sign in';
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
      <h2 class="serif" style="font-size:2rem;max-width:380px;">Your money, always in view.</h2>
      <p style="max-width:360px;">Real-time balances, instant transfers, and a card you can freeze from your pocket.</p>
    </div>
  </div>
  <div class="auth-form-side">
    <div class="auth-box">
      <?php if ($flash): ?><div class="flash <?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div><?php endif; ?>
      <h1>Welcome back</h1>
      <p class="mt-1">Sign in to your Solicity Bank account.</p>
      <form method="post" action="<?= e(url('api/api.php')) ?>" class="form-grid mt-2">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="login_customer">
        <input type="hidden" name="redirect" value="login.php">
        <div class="field">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" required value="ada@demo.test">
        </div>
        <div class="field">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" required value="demo123">
        </div>
        <button class="btn block" type="submit">Sign in &rarr;</button>
      </form>
      <p class="auth-hint">Demo: <strong>ada@demo.test</strong> / <strong>demo123</strong> (or <strong>marcus@demo.test</strong> / <strong>demo123</strong>)</p>
      <p class="auth-switch">New to Solicity? <a href="<?= e(url('register.php')) ?>">Open an account</a></p>
      <p class="auth-switch">Staff? <a href="<?= e(url('admin/index.php')) ?>">Admin sign in</a></p>
    </div>
  </div>
</div>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
