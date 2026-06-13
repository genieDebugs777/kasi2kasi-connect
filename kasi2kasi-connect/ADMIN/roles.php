<?php
require_once "../INCLUDES/db.php";
require_once "../INCLUDES/admin_auth.php";

requireRole(["Super Admin"]);

$roles = $conn->query("
    SELECT 
        role.role_id,
        role.role_name,
        role.description,
        COUNT(user.user_id) AS user_count
    FROM role
    LEFT JOIN user ON role.role_id = user.role_id
    GROUP BY role.role_id, role.role_name, role.description
    ORDER BY role.role_id ASC
");

$users = $conn->query("
    SELECT 
        user.user_id,
        user.name,
        user.email,
        user.status,
        role.role_name
    FROM user
    JOIN role ON user.role_id = role.role_id
    ORDER BY user.created_at DESC
    LIMIT 10
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
      <a href="index.php">📊 Dashboard</a>
      <a href="users.php">👥 Users</a>
      <a class="active" href="roles.php">🔐 Roles</a>
      <a href="verify.php">✅ Verifications</a>
      <a href="products.php">🛍 Products</a>
      <a href="../index.php">↩ Back to Site</a>
    </nav>
  </aside>

  <main class="admin-main-panel">

    <section class="admin-hero-pro">
      <div>
        <span class="admin-chip">RBAC CONTROL</span>
        <h1>Access Governance Centre</h1>
        <p>
          Manage role visibility, understand permission boundaries, and monitor user access across the platform.
        </p>
      </div>

      <div class="admin-orb">
        <span>RB</span>
      </div>
    </section>

    <section class="admin-glass-panel">
      <div class="admin-panel-head">
        <div>
          <h2>System Roles</h2>
          <p>Each role defines what users can access in Kasi2Kasi.</p>
        </div>

        <a href="users.php" class="admin-mini-link">Assign roles →</a>
      </div>

      <div class="admin-role-grid">
        <?php while ($r = $roles->fetch_assoc()): ?>
          <div class="admin-role-card">
            <span class="admin-status pending">
              <?= htmlspecialchars($r["role_name"]) ?>
            </span>

            <strong>
              <?= htmlspecialchars($r["user_count"]) ?> user<?= $r["user_count"] == 1 ? "" : "s" ?>
            </strong>

            <p>
              <?= htmlspecialchars($r["description"] ?? "No description available.") ?>
            </p>
          </div>
        <?php endwhile; ?>
      </div>
    </section>

    <section class="admin-dashboard-grid">

      <div class="admin-glass-panel">
        <div class="admin-panel-head">
          <div>
            <h2>Permission Summary</h2>
            <p>Clear separation of administrative responsibilities.</p>
          </div>
        </div>

        <div class="admin-action-grid">

          <div class="admin-action-card">
            <span>👑</span>
            <strong>Super Admin</strong>
            <small>Full access to dashboard, users, roles, verifications, product moderation, and reporting.</small>
          </div>

          <div class="admin-action-card">
            <span>✅</span>
            <strong>Verification Officer</strong>
            <small>Reviews seller verification requests and approves or rejects identity submissions.</small>
          </div>

          <div class="admin-action-card">
            <span>🛍</span>
            <strong>Content Moderator</strong>
            <small>Reviews listings and removes inappropriate, suspicious, or invalid product content.</small>
          </div>

          <div class="admin-action-card">
            <span>👤</span>
            <strong>User</strong>
            <small>Standard marketplace buyer/seller account with access to shopping, selling, orders, and messages.</small>
          </div>

        </div>
      </div>

      <div class="admin-glass-panel">
        <div class="admin-panel-head">
          <div>
            <h2>Recent Role Assignments</h2>
            <p>Latest users and their assigned roles.</p>
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
              <?php while ($u = $users->fetch_assoc()): ?>
                <tr>
                  <td>
                    <strong><?= htmlspecialchars($u["name"]) ?></strong>
                    <span><?= htmlspecialchars($u["email"]) ?></span>
                  </td>

                  <td>
                    <span class="admin-status pending">
                      <?= htmlspecialchars($u["role_name"]) ?>
                    </span>
                  </td>

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

        <div style="margin-top:16px">
          <a href="users.php" class="admin-action-submit" style="width:100%">
            Manage User Roles
          </a>
        </div>
      </div>

    </section>

  </main>
</div>

<?php include "../INCLUDES/footer.php"; ?>
