<?php
require_once __DIR__ . "/auth.php";

$isAdminPage = strpos($_SERVER['PHP_SELF'], '/ADMIN/') !== false;
$base = $isAdminPage ? '../' : '';
$bodyClass = $isAdminPage ? 'admin-page' : 'public-page';

// Get unread notification count for the bell icon
$unread_notif_count = 0;
if (isLoggedIn() && !$isAdminPage) {
    require_once __DIR__ . "/db.php";
    $user_id = $_SESSION["user_id"];
    $count_stmt = $conn->prepare("SELECT COUNT(*) AS c FROM notification WHERE user_id = ? AND is_read = 0");
    $count_stmt->bind_param("i", $user_id);
    $count_stmt->execute();
    $unread_notif_count = $count_stmt->get_result()->fetch_assoc()["c"];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
  <title>Kasi2Kasi Connect</title>


  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='20' fill='url(%23grad)'/><defs><linearGradient id='grad' x1='0%' y1='0%' x2='100%' y2='100%'><stop offset='0%' style='stop-color:%23f7c948'/><stop offset='100%' style='stop-color:%23f97316'/></linearGradient></defs><text x='50' y='72' text-anchor='middle' font-size='48' font-weight='900' font-family='Arial' fill='%23171717'>K2K</text></svg>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    

  <link rel="stylesheet" href="https://kasi2kasi-connect-production.up.railway.app/ASSETS/CSS/styles.css">
  <script src="https://kasi2kasi-connect-production.up.railway.app/ASSETS/JavaScript/app.js" defer></script>
  <script src="https://kasi2kasi-connect-production.up.railway.app/ASSETS/JavaScript/animations.js" defer></script>
</head>

<body class="<?= $bodyClass ?>">

<nav class="navbar">
  <div class="nav-inner">

    <!-- Logo -->
    <a href="<?= $base ?>index.php" class="brand">
      <span class="brand-mark"><span>K2K</span></span>
      <span class="brand-text">Kasi2Kasi</span>
    </a>

    <!-- Search Bar - Full width, between logo and menu -->
    <form class="search-bar" action="<?= $base ?>products.php" method="GET">
      <span class="search-icon">🔍</span>
      <input type="text" name="q" placeholder="Search sneakers, phones, furniture...">
    </form>

    <!-- Menu Toggle Button -->
    <button class="nav-toggle" type="button" aria-label="Open menu" aria-expanded="false">
      <span></span>
      <span></span>
      <span></span>
    </button>

    <!-- Navigation Menu -->
    <div class="nav-links">
      <a class="nav-link" href="<?= $base ?>index.php">Home</a>
      <a class="nav-link" href="<?= $base ?>products.php">Browse</a>
      <a class="nav-link" href="<?= $base ?>sell.php">Sell</a>
      
      <?php if (isLoggedIn()): ?>
        <a class="nav-link" href="<?= $base ?>seller_orders.php">My Sales</a>
      <?php endif; ?>
      
      <a class="nav-link" href="<?= $base ?>orders.php">Orders</a>
      <a class="nav-link" href="<?= $base ?>messages.php">Messages</a>
      <a class="nav-link" href="<?= $base ?>cart.php">Cart</a>

      <?php if (isLoggedIn()): ?>
        <?php if (in_array(currentUserRole(), ['Super Admin', 'Verification Officer', 'Content Moderator'])): ?>
          <a class="nav-link admin-link" href="<?= $base ?>ADMIN/index.php">Admin</a>
        <?php endif; ?>

        <a class="nav-link" href="<?= $base ?>notifications.php" style="position:relative">
          🔔 
          <?php if ($unread_notif_count > 0): ?>
            <span class="notif-badge"><?= $unread_notif_count ?></span>
          <?php endif; ?>
        </a>

        <a class="nav-link" href="<?= $base ?>profile.php">
          <?= htmlspecialchars(currentUserName()) ?>
        </a>

        <a class="btn btn-outline btn-sm" href="<?= $base ?>logout.php">Logout</a>
      <?php else: ?>
        <a class="btn btn-primary btn-sm" href="<?= $base ?>login.php">Sign in</a>
      <?php endif; ?>
    </div>

  </div>
</nav>

<style>
  
/* ============================================================
   CLEAN NAVBAR 
   ============================================================ */

.nav-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  max-width: 1200px;
  margin: 0 auto;
  padding: 12px 18px;
}

/* Logo - fixed width */
.brand {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}

/* Search Bar - stretches to fill space */
.search-bar {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 10px;
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.25);
  border-radius: 30px;
  padding: 8px 16px;
  max-width: 500px;
  margin: 0 auto;
}

.search-bar .search-icon {
  font-size: 1rem;
  opacity: 0.7;
}

.search-bar input {
  background: transparent;
  border: none;
  outline: none;
  flex: 1;
  font-size: 0.9rem;
  color: inherit;
}

.search-bar input::placeholder {
  color: rgba(0, 0, 0, 0.45);
}

/* Menu Toggle - fixed width */
.nav-toggle {
  display: none;
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.25);
  border-radius: 12px;
  width: 42px;
  height: 42px;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 5px;
  cursor: pointer;
  flex-shrink: 0;
}

.nav-toggle span {
  width: 18px;
  height: 2px;
  background: var(--ink);
  border-radius: 2px;
  transition: 0.2s;
}

/* Desktop Navigation Links */
.nav-links {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-shrink: 0;
}

.nav-link {
  padding: 8px 14px;
  border-radius: 30px;
  font-weight: 600;
  font-size: 0.85rem;
  transition: 0.2s;
  white-space: nowrap;
}

.nav-link:hover {
  background: rgba(0, 0, 0, 0.05);
}

/* Mobile Styles - Exactly like your screenshot */
@media (max-width: 900px) {
  .nav-inner {
    flex-wrap: nowrap;
    padding: 10px 15px;
    gap: 8px;
  }
  
  /* Logo stays on left */
  .brand {
    flex-shrink: 0;
  }
  
  .brand-text {
    display: none; /* Hide "Kasi2Kasi" text on mobile, just show K2K logo */
  }
  
  /* Search bar stretches in middle */
  .search-bar {
    flex: 1;
    max-width: none;
    margin: 0;
    padding: 7px 12px;
  }
  
  .search-bar input {
    font-size: 0.85rem;
  }
  
  .search-bar input::placeholder {
    font-size: 0.85rem;
  }
  
  /* Menu icon on right */
  .nav-toggle {
    display: flex;
  }
  
  /* Hide desktop navigation, show mobile menu */
  .nav-links {
    position: fixed;
    top: 70px;
    left: 0;
    right: 0;
    background: var(--surface);
    flex-direction: column;
    align-items: stretch;
    gap: 0;
    padding: 12px;
    border-radius: 0 0 20px 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    border-bottom: 1px solid var(--border);
    transform: translateY(-150%);
    transition: transform 0.3s ease;
    z-index: 1000;
  }
  
  .nav-links.open {
    transform: translateY(0);
  }
  
  .nav-links .nav-link,
  .nav-links .btn {
    padding: 14px 16px;
    margin: 2px 0;
    border-radius: 14px;
    text-align: left;
    justify-content: flex-start;
    width: 100%;
  }
  
  .nav-links .btn-primary {
    background: var(--primary);
    color: white;
  }
  
  .nav-links .btn-outline {
    background: transparent;
    border: 1px solid var(--border);
  }
  
  .admin-link {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: white !important;
  }
}

/* Desktop - hide mobile toggle, show normal nav */
@media (min-width: 901px) {
  .nav-toggle {
    display: none;
  }
  
  .nav-links {
    display: flex;
    position: static;
    transform: none;
    box-shadow: none;
    background: transparent;
    padding: 0;
  }
}

/* Admin page dark mode */
body.admin-page .search-bar {
  background: rgba(255, 255, 255, 0.1);
  border-color: rgba(255, 255, 255, 0.2);
}

body.admin-page .search-bar input {
  color: white;
}

body.admin-page .search-bar input::placeholder {
  color: rgba(255, 255, 255, 0.5);
}

body.admin-page .nav-toggle {
  background: rgba(255, 255, 255, 0.1);
  border-color: rgba(255, 255, 255, 0.2);
}

body.admin-page .nav-toggle span {
  background: white;
}

body.admin-page .nav-links {
  background: #1a1a1a;
  border-color: rgba(255, 255, 255, 0.1);
}

body.admin-page .nav-links .nav-link {
  color: #e0e0e0;
}

/* Notification badge */
.notif-badge {
  position: absolute;
  top: -5px;
  right: -5px;
  background: var(--danger);
  color: white;
  font-size: 10px;
  padding: 2px 6px;
  border-radius: 999px;
  font-weight: bold;
}

/* Active toggle animation */
.nav-toggle.active span:nth-child(1) {
  transform: translateY(7px) rotate(45deg);
}
.nav-toggle.active span:nth-child(2) {
  opacity: 0;
}
.nav-toggle.active span:nth-child(3) {
  transform: translateY(-7px) rotate(-45deg);
}
</style>
