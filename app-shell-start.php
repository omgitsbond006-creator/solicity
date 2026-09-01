<?php
defined('SOLICITY_ENTRY') or die('Direct access forbidden.');
/**
 * Expects, set by the including page before require:
 *   $page_title    string
 *   $shell_sub     string   "Personal Banking" | "Administrator"
 *   $nav_items     array of ['href','label','icon','active'=>bool]
 *   $chip_name     string
 *   $chip_role     string
 *   $chip_initials string
 *   $logout_redirect string
 */
$page_title = $page_title ?? 'Solicity Bank';
$nav_items = $nav_items ?? [];
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
<div class="sidebar-backdrop"></div>
<div class="app-layout">
  <aside class="sidebar">
    <a class="brand" href="<?= e(url('index.php')) ?>"><span class="mark">S</span>Solicity<?= !empty($shell_sub) ? ' <span style="font-size:.62rem;letter-spacing:.12em;text-transform:uppercase;color:var(--ink-dim);font-family:var(--font-sans);margin-left:.3rem;">' . e($shell_sub) . '</span>' : '' ?></a>
    <nav>
      <?php foreach ($nav_items as $item): ?>
        <a href="<?= e($item['href']) ?>" class="<?= !empty($item['active']) ? 'active' : '' ?>">
          <?= icon($item['icon'] ?? 'grid') ?>
          <span><?= e($item['label']) ?></span>
        </a>
      <?php endforeach; ?>
    </nav>
    <div class="user-chip">
      <div class="avatar"><?= e($chip_initials ?? 'S') ?></div>
      <div class="who">
        <div class="name"><?= e($chip_name ?? '') ?></div>
        <div class="role"><?= e($chip_role ?? '') ?></div>
      </div>
    </div>
    <form method="post" action="<?= e(url('api/api.php')) ?>" class="signout-form">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="logout">
      <input type="hidden" name="redirect" value="<?= e($logout_redirect ?? 'index.php') ?>">
      <button type="submit" class="btn ghost small block"><?= icon('logout') ?> Sign out</button>
    </form>
  </aside>
  <main class="main">
    <div class="mobile-topbar">
      <a class="brand" href="<?= e(url('index.php')) ?>" style="font-size:1.1rem;"><span class="mark">S</span>Solicity</a>
      <button data-sidebar-toggle aria-label="Menu">&#9776;</button>
    </div>
    <?php if ($flash): ?>
      <div class="flash <?= e($flash['type']) ?>" data-flash="<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
    <?php endif; ?>
