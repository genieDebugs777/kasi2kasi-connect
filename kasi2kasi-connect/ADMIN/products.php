<?php
require_once "../includes/db.php";
require_once "../includes/admin_auth.php";

requireRole(["Super Admin", "Content Moderator"]);

if (isset($_GET["remove"])) {
    $product_id = intval($_GET["remove"]);

    $stmt = $conn->prepare("UPDATE product SET status = 'removed' WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();

    header("Location: products.php?removed=1");
    exit;
}

if (isset($_GET["restore"])) {
    $product_id = intval($_GET["restore"]);

    $stmt = $conn->prepare("UPDATE product SET status = 'active' WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();

    header("Location: products.php?restored=1");
    exit;
}

$search = trim($_GET["search"] ?? "");
$status = trim($_GET["status"] ?? "");

$sql = "
    SELECT 
        product.*,
        category.name AS category_name,
        user.name AS seller_name,
        user.email AS seller_email,
        user.is_verified
    FROM product
    JOIN category ON product.category_id = category.category_id
    JOIN user ON product.seller_id = user.user_id
    WHERE 1 = 1
";

$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (product.title LIKE ? OR user.name LIKE ? OR user.email LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "sss";
}

if (!empty($status)) {
    $sql .= " AND product.status = ?";
    $params[] = $status;
    $types .= "s";
}

$sql .= " ORDER BY product.created_at DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$products = $stmt->get_result();

$total_products = $conn->query("SELECT COUNT(*) AS c FROM product")->fetch_assoc()["c"];
$active_products = $conn->query("SELECT COUNT(*) AS c FROM product WHERE status='active'")->fetch_assoc()["c"];
$removed_products = $conn->query("SELECT COUNT(*) AS c FROM product WHERE status='removed'")->fetch_assoc()["c"];
$sold_products = $conn->query("SELECT COUNT(*) AS c FROM product WHERE status='sold'")->fetch_assoc()["c"];
?>

<?php include "../includes/header.php"; ?>

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

      <?php if (currentUserRole() === "Super Admin"): ?>
        <a href="users.php">👥 Users</a>
        <a href="roles.php">🔐 Roles</a>
      <?php endif; ?>

      <?php if (in_array(currentUserRole(), ["Super Admin", "Verification Officer"])): ?>
        <a href="verify.php">✅ Verifications</a>
      <?php endif; ?>

      <a class="active" href="products.php">🛍 Products</a>
      <a href="../index.php">↩ Back to Site</a>
    </nav>
  </aside>

  <main class="admin-main-panel">

    <section class="admin-hero-pro">
      <div>
        <span class="admin-chip">PRODUCT MODERATION</span>
        <h1>Listing Control Centre</h1>
        <p>
          Review marketplace listings, remove suspicious products, restore valid items, and keep buyer trust high.
        </p>
      </div>

      <div class="admin-orb">
        <span>PR</span>
      </div>
    </section>

    <?php if (isset($_GET["removed"])): ?>
      <div class="admin-alert danger auto-hide">
        Product removed successfully.
      </div>
    <?php endif; ?>

    <?php if (isset($_GET["restored"])): ?>
      <div class="admin-alert success auto-hide">
        Product restored successfully.
      </div>
    <?php endif; ?>

    <section class="admin-metrics">
      <div class="admin-metric-card">
        <span class="metric-icon">🛍</span>
        <small>Total Listings</small>
        <strong><?= $total_products ?></strong>
        <p>All marketplace products</p>
      </div>

      <div class="admin-metric-card">
        <span class="metric-icon">✅</span>
        <small>Active</small>
        <strong><?= $active_products ?></strong>
        <p>Visible to buyers</p>
      </div>

      <div class="admin-metric-card warning">
        <span class="metric-icon">🚫</span>
        <small>Removed</small>
        <strong><?= $removed_products ?></strong>
        <p>Moderated listings</p>
      </div>

      <div class="admin-metric-card">
        <span class="metric-icon">📦</span>
        <small>Sold</small>
        <strong><?= $sold_products ?></strong>
        <p>Completed listings</p>
      </div>
    </section>

    <section class="admin-glass-panel">
      <div class="admin-panel-head">
        <div>
          <h2>Filter Product Listings</h2>
          <p>Search by product title, seller name, seller email, or listing status.</p>
        </div>
      </div>

      <form method="GET" class="admin-filter-grid">
        <div class="field">
          <label>Search listing or seller</label>
          <input 
            type="text" 
            name="search" 
            value="<?= htmlspecialchars($search) ?>" 
            placeholder="Product title, seller name, email..."
          >
        </div>

        <div class="field">
          <label>Status</label>
          <select name="status">
            <option value="">All statuses</option>
            <option value="active" <?= $status === "active" ? "selected" : "" ?>>Active</option>
            <option value="sold" <?= $status === "sold" ? "selected" : "" ?>>Sold</option>
            <option value="removed" <?= $status === "removed" ? "selected" : "" ?>>Removed</option>
          </select>
        </div>

        <button class="admin-action-submit">Filter</button>
        <a href="products.php" class="admin-action-submit admin-reset-btn">Reset</a>
      </form>
    </section>

    <section class="admin-glass-panel">
      <div class="admin-panel-head">
        <div>
          <h2>Product Listings</h2>
          <p><?= $products->num_rows ?> listing<?= $products->num_rows === 1 ? "" : "s" ?> found.</p>
        </div>
      </div>

      <?php if ($products->num_rows > 0): ?>
        <div class="admin-table-wrap">
          <table>
            <thead>
              <tr>
                <th>Product</th>
                <th>Seller</th>
                <th>Category</th>
                <th>Price</th>
                <th>Qty</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>

            <tbody>
              <?php while ($p = $products->fetch_assoc()): ?>
                <tr>
                  <td>
                    <div class="admin-product-cell">
                      <div 
                        class="admin-product-thumb"
                        style="background-image:url('<?= htmlspecialchars($p["image_url"]) ?>')"
                      ></div>

                      <div>
                        <strong><?= htmlspecialchars($p["title"]) ?></strong>
                        <span>Added <?= date("d M Y", strtotime($p["created_at"])) ?></span>
                      </div>
                    </div>
                  </td>

                  <td>
                    <strong><?= htmlspecialchars($p["seller_name"]) ?></strong>
                    <span><?= htmlspecialchars($p["seller_email"]) ?></span>

                    <?php if ($p["is_verified"]): ?>
                      <span class="admin-status approved">verified</span>
                    <?php endif; ?>
                  </td>

                  <td><?= htmlspecialchars($p["category_name"]) ?></td>

                  <td>R <?= number_format($p["price"], 2) ?></td>

                  <td><?= $p["quantity"] ?></td>

                  <td>
                    <span class="admin-status <?= htmlspecialchars($p["status"]) ?>">
                      <?= htmlspecialchars($p["status"]) ?>
                    </span>
                  </td>

                  <td>
                    <div class="admin-row-actions">
                      <a href="../product.php?id=<?= $p["product_id"] ?>" target="_blank">
                        View
                      </a>

                      <?php if ($p["status"] !== "removed"): ?>
                        <a 
                          href="products.php?remove=<?= $p["product_id"] ?>"
                          onclick="return confirm('Remove this product listing?')"
                          class="danger-link"
                        >
                          Remove
                        </a>
                      <?php else: ?>
                        <a 
                          href="products.php?restore=<?= $p["product_id"] ?>"
                          onclick="return confirm('Restore this product listing?')"
                        >
                          Restore
                        </a>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="admin-empty">
          <div>🛍️</div>
          <h2>No products found</h2>
          <p>Try changing the search or status filter.</p>
          <a href="products.php" class="admin-action-submit">Reset filters</a>
        </div>
      <?php endif; ?>
    </section>

  </main>
</div>

<?php include "../includes/footer.php"; ?>