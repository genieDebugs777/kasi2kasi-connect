<?php
require_once "INCLUDES/db.php";
include "INCLUDES/header.php";

$products = $conn->query("
    SELECT product.*, user.name AS seller_name, user.is_verified, user.is_trusted
    FROM product
    JOIN user ON product.seller_id = user.user_id
    WHERE product.status = 'active'
    ORDER BY product.created_at DESC
    LIMIT 8
");

$categories = $conn->query("SELECT * FROM category ORDER BY name ASC");

$total_products = $conn->query("SELECT COUNT(*) AS c FROM product WHERE status='active'")->fetch_assoc()["c"];
$total_users = $conn->query("SELECT COUNT(*) AS c FROM user")->fetch_assoc()["c"];
$total_verified = $conn->query("SELECT COUNT(*) AS c FROM user WHERE is_verified=1")->fetch_assoc()["c"];
?>

<div class="container">
  <section class="hero">
    <div class="hero-content">
      <div class="kicker">🇿🇦 Township-first C2C marketplace</div>

      <h1>Buy local. Sell fast. Build your kasi economy.</h1>

      <p>
        Kasi2Kasi Connect helps neighbours trade safely — from pre-loved sneakers and phones
        to handmade crafts, textbooks and home goods. Find trusted sellers close to you.
      </p>

      <div class="hero-actions">
        <a href="products.php" class="btn btn-primary">Start browsing</a>
        <a href="sell.php" class="btn btn-outline">List an item</a>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="grid grid-2">
      <div class="card" style="padding:24px;background:linear-gradient(135deg,#fff,#fff3df)">
        <span class="badge badge-verified">✓ Verified Sellers</span>
        <h2 style="margin:12px 0 8px;letter-spacing:-.04em">Trust built for local trade</h2>
        <p class="text-muted" style="margin:0;line-height:1.6">
          Seller verification and community reviews help buyers avoid scams and trade with confidence.
        </p>
      </div>

      <div class="card" style="padding:24px;background:linear-gradient(135deg,#fff,#f0fff5)">
        <span class="badge badge-trusted">★ Trusted Seller</span>
        <h2 style="margin:12px 0 8px;letter-spacing:-.04em">From side-hustle to scale</h2>
        <p class="text-muted" style="margin:0;line-height:1.6">
          Kasi2Kasi gives informal sellers a digital storefront without needing a registered business.
        </p>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="section-head">
      <div>
        <h2>Browse by category</h2>
        <p>Find what your community is selling today.</p>
      </div>
    </div>

    <div class="pills">
      <a class="pill active" href="products.php">All</a>

      <?php while ($cat = $categories->fetch_assoc()): ?>
        <a class="pill" href="products.php?category=<?= urlencode($cat['slug']) ?>">
          <?= htmlspecialchars($cat['name']) ?>
        </a>
      <?php endwhile; ?>
    </div>
  </section>

  <section class="section">
    <div class="section-head">
      <div>
        <h2>Fresh kasi listings</h2>
        <p>New products from nearby sellers.</p>
      </div>

      <a href="products.php" class="btn btn-ghost btn-sm">See all →</a>
    </div>

    <div class="grid grid-products">
      <?php if ($products && $products->num_rows > 0): ?>
        <?php while ($p = $products->fetch_assoc()): ?>
          <a href="product.php?id=<?= $p['product_id'] ?>" class="card product-card">
            <div class="img" style="background-image:url('<?= htmlspecialchars($p['image_url']) ?>')"></div>

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
      <?php else: ?>
        <div class="card" style="padding:30px;text-align:center">
          <h3>No listings yet</h3>
          <p class="text-muted">Be the first seller to list something in your kasi.</p>
          <a href="sell.php" class="btn btn-primary">Create listing</a>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <section class="section">
    <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr))">
      <div class="stat">
        <div class="label">Active Listings</div>
        <div class="value"><?= $total_products ?></div>
        <div class="delta">Community marketplace</div>
      </div>

      <div class="stat">
        <div class="label">Registered Users</div>
        <div class="value"><?= $total_users ?></div>
        <div class="delta">Buyers and sellers</div>
      </div>

      <div class="stat">
        <div class="label">Verified Sellers</div>
        <div class="value"><?= $total_verified ?></div>
        <div class="delta">Trust-first trading</div>
      </div>
    </div>
  </section>
</div>

<?php include "INCLUDES/footer.php"; ?>
