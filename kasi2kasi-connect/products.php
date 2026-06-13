<?php
require_once "includes/db.php";
include "includes/header.php";

$q = trim($_GET["q"] ?? "");
$category = trim($_GET["category"] ?? "");
$sort = $_GET["sort"] ?? "newest";
$max_price = $_GET["max_price"] ?? "";

$sql = "
SELECT 
    product.*, 
    category.name AS category_name,
    category.slug AS category_slug,
    user.name AS seller_name, 
    user.is_verified,
    user.is_trusted
FROM product
JOIN category ON product.category_id = category.category_id
JOIN user ON product.seller_id = user.user_id
WHERE product.status = 'active'
";

$params = [];
$types = "";

if (!empty($q)) {
    $sql .= " AND (product.title LIKE ? OR product.description LIKE ? OR user.name LIKE ?)";
    $search = "%$q%";
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $types .= "sss";
}

if (!empty($category)) {
    $sql .= " AND category.slug = ?";
    $params[] = $category;
    $types .= "s";
}

if (!empty($max_price)) {
    $sql .= " AND product.price <= ?";
    $params[] = $max_price;
    $types .= "d";
}

if ($sort === "low") {
    $sql .= " ORDER BY product.price ASC";
} elseif ($sort === "high") {
    $sql .= " ORDER BY product.price DESC";
} else {
    $sql .= " ORDER BY product.created_at DESC";
}

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$products = $stmt->get_result();

$categories = $conn->query("SELECT * FROM category ORDER BY name ASC");
?>

<div class="container">
  <section class="section">
    <div class="hero" style="padding:40px 30px;margin-bottom:18px">
      <div class="hero-content">
        <div class="kicker">🛍️ Marketplace</div>
        <h1 style="font-size:clamp(2rem,4vw,3.4rem)">Find deals from your kasi.</h1>
        <p>
          Search trusted local listings, compare prices, and connect directly with sellers in your community.
        </p>
      </div>
    </div>

    <div class="section-head">
      <div>
        <h2>Browse listings</h2>
        <p>
          <?= $products->num_rows ?> result<?= $products->num_rows === 1 ? '' : 's' ?>
          <?= $q ? ' for “' . htmlspecialchars($q) . '”' : '' ?>
        </p>
      </div>
    </div>

    <div class="pills">
      <a class="pill <?= empty($category) ? 'active' : '' ?>" href="products.php">All</a>

      <?php while ($cat = $categories->fetch_assoc()): ?>
        <a 
          class="pill <?= $category === $cat['slug'] ? 'active' : '' ?>" 
          href="products.php?category=<?= urlencode($cat['slug']) ?>"
        >
          <?= htmlspecialchars($cat['name']) ?>
        </a>
      <?php endwhile; ?>
    </div>

    <form method="GET" class="card" style="padding:18px;margin:12px 0 24px">
      <div class="grid" style="grid-template-columns:2fr 1fr 1fr auto;gap:12px;align-items:end">
        <div class="field" style="margin:0">
          <label>Search</label>
          <input 
            type="text" 
            name="q" 
            value="<?= htmlspecialchars($q) ?>" 
            placeholder="Sneakers, phones, furniture..."
          >
        </div>

        <div class="field" style="margin:0">
          <label>Sort by</label>
          <select name="sort">
            <option value="newest" <?= $sort === "newest" ? "selected" : "" ?>>Newest first</option>
            <option value="low" <?= $sort === "low" ? "selected" : "" ?>>Price: low to high</option>
            <option value="high" <?= $sort === "high" ? "selected" : "" ?>>Price: high to low</option>
          </select>
        </div>

        <div class="field" style="margin:0">
          <label>Max price</label>
          <input 
            type="number" 
            name="max_price" 
            value="<?= htmlspecialchars($max_price) ?>" 
            placeholder="R"
          >
        </div>

        <?php if (!empty($category)): ?>
          <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">
        <?php endif; ?>

        <button class="btn btn-primary">Apply</button>
      </div>
    </form>

    <?php if ($products->num_rows > 0): ?>
      <div class="grid grid-products">
        <?php while ($p = $products->fetch_assoc()): ?>
          <a href="product.php?id=<?= $p['product_id'] ?>" class="card product-card">
            <div class="img" style="background-image:url('<?= htmlspecialchars($p['image_url']) ?>')">
              <div style="position:absolute;top:10px;left:10px">
                <span class="badge" style="background:rgba(255,255,255,.92);color:var(--ink)">
                  <?= htmlspecialchars($p['category_name']) ?>
                </span>
              </div>
            </div>

            <div class="body">
              <h3 class="title"><?= htmlspecialchars($p['title']) ?></h3>

              <div class="price">
                R <?= number_format($p['price'], 2) ?>
              </div>

              <div class="meta">
                <span><?= htmlspecialchars($p['seller_name']) ?></span>

                <?php if ($p['is_verified']): ?>
                  <span class="badge badge-verified">✓ Verified</span>
                <?php elseif ($p['is_trusted']): ?>
                  <span class="badge badge-trusted">★ Trusted</span>
                <?php endif; ?>
              </div>
            </div>
          </a>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <div class="card" style="padding:34px;text-align:center">
        <h3>No listings found</h3>
        <p class="text-muted">Try a different keyword, category, or price filter.</p>
        <a href="products.php" class="btn btn-primary">Reset filters</a>
      </div>
    <?php endif; ?>
  </section>
</div>

<?php include "includes/footer.php"; ?>