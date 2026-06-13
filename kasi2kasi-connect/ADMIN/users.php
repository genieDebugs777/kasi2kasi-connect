<?php
require_once "../INCLUDES/db.php";
require_once "../INCLUDES/admin_auth.php";

requireRole(["Super Admin"]);

if (isset($_GET["action"], $_GET["id"])) {
    $user_id = intval($_GET["id"]);
    $action = $_GET["action"];

    if ($action === "suspend") {
        $stmt = $conn->prepare("UPDATE user SET status='suspended' WHERE user_id=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    }

    if ($action === "activate") {
        $stmt = $conn->prepare("UPDATE user SET status='active' WHERE user_id=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    }

    header("Location: users.php?updated=1");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["user_id"], $_POST["role_id"])) {
    $user_id = intval($_POST["user_id"]);
    $role_id = intval($_POST["role_id"]);

    $stmt = $conn->prepare("UPDATE user SET role_id=? WHERE user_id=?");
    $stmt->bind_param("ii", $role_id, $user_id);
    $stmt->execute();

    header("Location: users.php?role_updated=1");
    exit;
}

$search = trim($_GET["search"] ?? "");

$query = "
    SELECT user.*, role.role_name 
    FROM user
    JOIN role ON user.role_id = role.role_id
";

if (!empty($search)) {
    $query .= " WHERE user.name LIKE ? OR user.email LIKE ?";
    $stmt = $conn->prepare($query);
    $like = "%$search%";
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $users = $stmt->get_result();
} else {
    $users = $conn->query($query . " ORDER BY user.created_at DESC");
}

$roles = $conn->query("SELECT * FROM role ORDER BY role_id ASC");

$total_users = $conn->query("SELECT COUNT(*) AS c FROM user")->fetch_assoc()["c"];
$active_users = $conn->query("SELECT COUNT(*) AS c FROM user WHERE status='active'")->fetch_assoc()["c"];
$suspended_users = $conn->query("SELECT COUNT(*) AS c FROM user WHERE status='suspended'")->fetch_assoc()["c"];
$verified_users = $conn->query("SELECT COUNT(*) AS c FROM user WHERE is_verified=1")->fetch_assoc()["c"];
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
      <a class="active" href="users.php">👥 Users</a>
      <a href="roles.php">🔐 Roles</a>
      <a href="verify.php">✅ Verifications</a>
      <a href="products.php">🛍 Products</a>
      <a href="../index.php">↩ Back to Site</a>
    </nav>
  </aside>

  <main class="admin-main-panel">

    <section class="admin-hero-pro">
      <div>
        <span class="admin-chip">USER CONTROL</span>
        <h1>User Management Hub</h1>
        <p>
          Manage accounts, assign RBAC roles, suspend suspicious users, and keep the marketplace safe.
        </p>
      </div>

      <div class="admin-orb">
        <span>US</span>
      </div>
    </section>

    <?php if (isset($_GET["updated"])): ?>
      <div class="admin-alert success auto-hide">
        User status updated successfully.
      </div>
    <?php endif; ?>

    <?php if (isset($_GET["role_updated"])): ?>
      <div class="admin-alert success auto-hide">
        User role updated successfully.
      </div>
    <?php endif; ?>

    <section class="admin-metrics">
      <div class="admin-metric-card">
        <span class="metric-icon">👥</span>
        <small>Total Users</small>
        <strong><?= $total_users ?></strong>
        <p>Registered accounts</p>
      </div>

      <div class="admin-metric-card">
        <span class="metric-icon">✅</span>
        <small>Active Users</small>
        <strong><?= $active_users ?></strong>
        <p>Currently allowed access</p>
      </div>

      <div class="admin-metric-card warning">
        <span class="metric-icon">🚫</span>
        <small>Suspended</small>
        <strong><?= $suspended_users ?></strong>
        <p>Restricted accounts</p>
      </div>

      <div class="admin-metric-card">
        <span class="metric-icon">🛡</span>
        <small>Verified Sellers</small>
        <strong><?= $verified_users ?></strong>
        <p>Trusted marketplace users</p>
      </div>
    </section>

    <section class="admin-glass-panel">
      <div class="admin-panel-head">
        <div>
          <h2>Search Users</h2>
          <p>Find users by name or email address.</p>
        </div>
      </div>

      <form method="GET" class="admin-filter-grid">
        <div class="field">
          <label>Search user</label>
          <input
            type="text"
            name="search"
            placeholder="Search name or email..."
            value="<?= htmlspecialchars($search) ?>"
          >
        </div>

        <button class="admin-action-submit">Search</button>

        <a href="users.php" class="admin-action-submit admin-reset-btn">Reset</a>
      </form>
    </section>

    <section class="admin-glass-panel">
      <div class="admin-panel-head">
        <div>
          <h2>Platform Users</h2>
          <p><?= $users->num_rows ?> user<?= $users->num_rows === 1 ? "" : "s" ?> found.</p>
        </div>
      </div>

      <div class="admin-table-wrap">
        <table>
          <thead>
            <tr>
              <th>User</th>
              <th>Role</th>
              <th>Status</th>
              <th>Trust</th>
              <th>Change Role</th>
              <th>Actions</th>
            </tr>
          </thead>

          <tbody>
            <?php while ($u = $users->fetch_assoc()): ?>
              <tr>
                <td>
                  <div class="admin-user-cell">
                    <div class="admin-user-avatar">
                      <?= strtoupper(substr($u["name"], 0, 1)) ?>
                    </div>

                    <div>
                      <strong><?= htmlspecialchars($u["name"]) ?></strong>
                      <span><?= htmlspecialchars($u["email"]) ?></span>
                    </div>
                  </div>
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

                <td>
                  <?php if ($u["is_verified"]): ?>
                    <span class="admin-status approved">verified</span>
                  <?php else: ?>
                    <span class="admin-status pending">unverified</span>
                  <?php endif; ?>

                  <?php if ($u["is_trusted"]): ?>
                    <span class="admin-status completed">trusted</span>
                  <?php endif; ?>
                </td>

                <td>
                  <form method="POST">
                    <input type="hidden" name="user_id" value="<?= $u["user_id"] ?>">

                    <select name="role_id" onchange="this.form.submit()" class="admin-select">
                      <?php
                      $roles->data_seek(0);
                      while ($r = $roles->fetch_assoc()):
                      ?>
                        <option value="<?= $r["role_id"] ?>" <?= $r["role_id"] == $u["role_id"] ? "selected" : "" ?>>
                          <?= htmlspecialchars($r["role_name"]) ?>
                        </option>
                      <?php endwhile; ?>
                    </select>
                  </form>
                </td>

                <td>
                  <div class="admin-row-actions">
                    <?php if ($u["status"] === "active"): ?>
                      <a
                        href="users.php?action=suspend&id=<?= $u["user_id"] ?>"
                        onclick="return confirm('Suspend this user?')"
                        class="danger-link"
                      >
                        Suspend
                      </a>
                    <?php else: ?>
                      <a
                        href="users.php?action=activate&id=<?= $u["user_id"] ?>"
                        onclick="return confirm('Activate this user?')"
                      >
                        Activate
                      </a>
                    <?php endif; ?>
                  </div>
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
