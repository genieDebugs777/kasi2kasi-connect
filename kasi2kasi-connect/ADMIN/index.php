<?php
echo '<link rel="stylesheet" href="https://kasi2kasi-connect-production.up.railway.app/ASSETS/CSS/styles.css">';
require_once "../INCLUDES/db.php";
require_once "../INCLUDES/admin_auth.php";

requireRole(["Super Admin", "Verification Officer", "Content Moderator"]);

$total_users = $conn->query("SELECT COUNT(*) AS c FROM user")->fetch_assoc()["c"];
$total_products = $conn->query("SELECT COUNT(*) AS c FROM product")->fetch_assoc()["c"];
$total_orders = $conn->query("SELECT COUNT(*) AS c FROM orders")->fetch_assoc()["c"];

$pending_verifications = $conn->query("
    SELECT COUNT(*) AS c 
    FROM verification_request 
    WHERE status = 'pending'
")->fetch_assoc()["c"];

$recent_products = $conn->query("
    SELECT product.title, product.price, product.status, user.name AS seller_name
    FROM product
    JOIN user ON product.seller_id = user.user_id
    ORDER BY product.created_at DESC
    LIMIT 5
");

$recent_users = $conn->query("
    SELECT user.name, user.email, user.status, role.role_name
    FROM user
    JOIN role ON user.role_id = role.role_id
    ORDER BY user.created_at DESC
    LIMIT 5
");
?>

<?php include "../INCLUDES/header.php"; ?>

<div class="admin-control">

  <aside class="admin-sidebar">
    <div class="admin-side-brand">
      <span class="brand-mark"><span>K2K</span></span>
      <div>
        <strong>Kasi2Kasi</strong>
        <small>Admin Console</small>
      </div>
    </div>

    <nav class="admin-side-nav">
      <a class="active" href="index.php">📊 Dashboard</a>

      <?php if (currentUserRole() === "Super Admin"): ?>
        <a href="users.php">👥 Users</a>
        <a href="roles.php">🔐 Roles</a>
      <?php endif; ?>

      <?php if (in_array(currentUserRole(), ["Super Admin", "Verification Officer"])): ?>
        <a href="verify.php">✅ Verifications</a>
      <?php endif; ?>

      <?php if (in_array(currentUserRole(), ["Super Admin", "Content Moderator"])): ?>
        <a href="products.php">🛍 Products</a>
      <?php endif; ?>

      <a href="../index.php">↩ Back to Site</a>
    </nav>
  </aside>

  <main class="admin-main-panel">

    <section class="admin-hero-pro">
      <div>
        <span class="admin-chip">CONTROL ROOM</span>
        <h1>Marketplace Command Centre</h1>
        <p>
          Welcome, <?= htmlspecialchars(currentUserName()) ?>.
          You are operating as <strong><?= htmlspecialchars(currentUserRole()) ?></strong>.
        </p>
      </div>

      <div class="admin-orb">
        <span><?= strtoupper(substr(currentUserRole(), 0, 2)) ?></span>
      </div>
    </section>

    <section class="admin-metrics">
      <div class="admin-metric-card">
        <span class="metric-icon">👥</span>
        <small>Total Users</small>
        <strong><?= $total_users ?></strong>
        <p>Registered accounts</p>
      </div>

      <div class="admin-metric-card">
        <span class="metric-icon">🛍</span>
        <small>Listings</small>
        <strong><?= $total_products ?></strong>
        <p>Marketplace products</p>
      </div>

      <div class="admin-metric-card">
        <span class="metric-icon">📦</span>
        <small>Orders</small>
        <strong><?= $total_orders ?></strong>
        <p>Transactions recorded</p>
      </div>

      <div class="admin-metric-card warning">
        <span class="metric-icon">⚠️</span>
        <small>Pending Verifications</small>
        <strong><?= $pending_verifications ?></strong>
        <p>Awaiting review</p>
      </div>
    </section>

    <section class="admin-dashboard-grid">

      <div class="admin-glass-panel">
        <div class="admin-panel-head">
          <div>
            <h2>Admin Actions</h2>
            <p>Role-aware operational tools.</p>
          </div>
        </div>

        <div class="admin-action-grid">

          <?php if (currentUserRole() === "Super Admin"): ?>
            <a href="users.php" class="admin-action-card">
              <span>👥</span>
              <strong>Manage Users</strong>
              <small>Create, suspend, activate, and review users.</small>
            </a>

            <a href="roles.php" class="admin-action-card">
              <span>🔐</span>
              <strong>RBAC Roles</strong>
              <small>View role permissions and assignments.</small>
            </a>
          <?php endif; ?>

          <?php if (in_array(currentUserRole(), ["Super Admin", "Verification Officer"])): ?>
            <a href="verify.php" class="admin-action-card">
              <span>✅</span>
              <strong>Seller Verification</strong>
              <small>Approve or reject identity requests.</small>
            </a>
          <?php endif; ?>

          <?php if (in_array(currentUserRole(), ["Super Admin", "Content Moderator"])): ?>
            <a href="products.php" class="admin-action-card">
              <span>🛍</span>
              <strong>Moderate Listings</strong>
              <small>Remove suspicious or invalid products.</small>
            </a>
          <?php endif; ?>

        </div>
      </div>

      <div class="admin-glass-panel">
        <div class="admin-panel-head">
          <div>
            <h2>Recent Users</h2>
            <p>Latest platform accounts.</p>
          </div>
        </div>

        <div class="admin-table-wrap">
          <table>
            <thead>
              <tr>
                <th>User</th>
                <th>Role</th>
                <th>Status</th>
              </tr>
            </thead>

            <tbody>
              <?php while ($u = $recent_users->fetch_assoc()): ?>
                <tr>
                  <td>
                    <strong><?= htmlspecialchars($u["name"]) ?></strong>
                    <span><?= htmlspecialchars($u["email"]) ?></span>
                  </td>
                  <td><?= htmlspecialchars($u["role_name"]) ?></td>
                  <td>
                    <span class="admin-status <?= htmlspecialchars($u["status"]) ?>">
                      <?= htmlspecialchars($u["status"]) ?>
                    </span>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>

    </section>

    <section class="admin-glass-panel">
      <div class="admin-panel-head">
        <div>
          <h2>Recent Product Listings</h2>
          <p>Newest items entering the marketplace.</p>
        </div>

        <?php if (in_array(currentUserRole(), ["Super Admin", "Content Moderator"])): ?>
          <a href="products.php" class="admin-mini-link">View all →</a>
        <?php endif; ?>
      </div>

      <div class="admin-table-wrap">
        <table>
          <thead>
            <tr>
              <th>Product</th>
              <th>Seller</th>
              <th>Price</th>
              <th>Status</th>
            </tr>
          </thead>

          <tbody>
            <?php while ($p = $recent_products->fetch_assoc()): ?>
              <tr>
                <td><strong><?= htmlspecialchars($p["title"]) ?></strong></td>
                <td><?= htmlspecialchars($p["seller_name"]) ?></td>
                <td>R <?= number_format($p["price"], 2) ?></td>
                <td>
                  <span class="admin-status <?= htmlspecialchars($p["status"]) ?>">
                    <?= htmlspecialchars($p["status"]) ?>
                  </span>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </section>

  </main>
</div>

<?php include "../INCLUDES/footer.php"; ?>
