<?php
$isAdminPage = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
$base = $isAdminPage ? '../' : '';

// Get current page for active link styling (optional)
$current_page = basename($_SERVER['PHP_SELF']);
?>

<footer class="footer">
  <div class="container">
    <div class="footer-brand">
      <span class="brand-mark footer-logo">
        <span>K2K</span>
      </span>
      <span>Kasi2Kasi Connect</span>
    </div>

    <p>Your township marketplace · Buy and sell with neighbours you trust.</p>

    <p style="margin-top:14px">
      <?php if (!in_array($current_page, ['admin', 'admin/index.php'])): ?>
        <a href="<?= $base ?>admin/index.php">Admin Panel</a> ·
      <?php endif; ?>
      <a href="<?= $base ?>about.php">About</a> ·
      <a href="<?= $base ?>help.php">Help</a> ·
      <a href="<?= $base ?>terms.php">Terms</a>
    </p>
  </div>
</footer>

</body>
</html>