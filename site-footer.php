<?php defined('SOLICITY_ENTRY') or die('Direct access forbidden.'); ?>
<footer class="site-footer">
  <div class="footer-inner">
    <div>
      <a class="brand" href="<?= e(url('index.php')) ?>"><span class="mark">S</span>Solicity Bank</a>
      <p class="mt-1" style="max-width:280px;font-size:.85rem;">Private banking, reimagined for people who expect more from their money.</p>
    </div>
    <div class="footer-cols">
      <div class="footer-col">
        <h4>Product</h4>
        <a href="<?= e(url('index.php#features')) ?>">Features</a>
        <a href="<?= e(url('index.php#security')) ?>">Security</a>
        <a href="<?= e(url('register.php')) ?>">Open an account</a>
      </div>
      <div class="footer-col">
        <h4>Company</h4>
        <a href="#">About</a>
        <a href="#">Careers</a>
        <a href="#">Press</a>
      </div>
      <div class="footer-col">
        <h4>Support</h4>
        <a href="#">Help Center</a>
        <a href="#">Contact</a>
        <a href="#">Status</a>
      </div>
    </div>
  </div>
  <div class="footer-bottom">
    <span>&copy; <?= date('Y') ?> Solicity Bank. A demo banking platform built for illustrative purposes — no real funds are held or moved.</span>
    <span>Member FDIC (simulated) &middot; Equal Housing Lender (simulated)</span>
  </div>
</footer>
<script src="<?= e(asset('js/vendor/chart.umd.min.js')) ?>"></script>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
