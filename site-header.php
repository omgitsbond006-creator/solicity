<?php
defined('SOLICITY_ENTRY') or die('Direct access forbidden.');
$page_title = $page_title ?? 'Solicity Bank';
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
<div id="toast-stack"></div>
<header class="topnav">
  <div class="bar">
    <a class="brand" href="<?= e(url('index.php')) ?>"><span class="mark">S</span>Solicity Bank</a>
    <button class="nav-toggle" aria-label="Menu">&#9776;</button>
    <nav class="navlinks">
      <a class="link" href="<?= e(url('index.php#features')) ?>">Features</a>
      <a class="link" href="<?= e(url('index.php#how-it-works')) ?>">How it works</a>
      <a class="link" href="<?= e(url('index.php#faq')) ?>">FAQ</a>
      <a class="link" href="<?= e(url('login.php')) ?>">Sign in</a>
      <a class="btn small" href="<?= e(url('register.php')) ?>">Open an account</a>
    </nav>
  </div>
</header>
<?php if ($flash): ?>
  <div style="max-width:1200px;margin:1.25rem auto 0;padding:0 1.5rem;">
    <div class="flash <?= e($flash['type']) ?>" data-flash="<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
  </div>
<?php endif; ?>
