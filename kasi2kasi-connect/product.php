<?php
require_once "includes/db.php";
require_once "includes/auth.php";

$product_id = $_GET["id"] ?? null;

if (!$product_id) {
    die("Product not found.");
}

$stmt = $conn->prepare("
    SELECT 
        product.*, 
        category.name AS category_name,
        user.name AS seller_name,
        user.is_verified,
        user.is_trusted,
        user.user_id AS seller_id
    FROM product
    JOIN category ON product.category_id = category.category_id
    JOIN user ON product.seller_id = user.user_id
    WHERE product.product_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    die("Product not found.");
}

/* Reviews */
$review_stmt = $conn->prepare("
    SELECT review.*, user.name AS reviewer_name
    FROM review
    JOIN user ON review.user_id = user.user_id
    WHERE review.product_id = ?
    ORDER BY review.created_at DESC
");

$review_stmt->bind_param("i", $product_id);
$review_stmt->execute();
$reviews = $review_stmt->get_result();

$stock_error = isset($_GET["stock_error"]) ? true : false;
?>

<?php include "includes/header.php"; ?>

<div class="container">

  <?php if ($stock_error && isset($_SESSION["cart_error"])): ?>
    <div class="card auto-hide" style="padding:16px;background:#fee2e2;border-left:4px solid var(--danger);margin-bottom:16px">
      <?= htmlspecialchars($_SESSION["cart_error"]) ?>
    </div>
    <?php unset($_SESSION["cart_error"]); ?>
  <?php endif; ?>

  <!-- PRODUCT SECTION -->
  <div class="pd-grid">

    <!-- IMAGE -->
    <div>
      <div class="pd-img" style="background-image:url('<?= htmlspecialchars($product['image_url']) ?>')"></div>

      <!-- TRUST STRIP -->
      <div class="card mt-3" style="padding:14px">
        <div class="flex between text-sm">
          <span>🔒 Secure trade</span>
          <span>📍 Local pickup or delivery</span>
        </div>
      </div>
    </div>

    <!-- INFO -->
    <div class="pd-info">

      <span class="badge" style="background:rgba(255,255,255,.92);color:var(--ink)">
        <?= htmlspecialchars($product['category_name']) ?>
      </span>

      <h1><?= htmlspecialchars($product['title']) ?></h1>

      <div class="pd-price">
        R <?= number_format($product['price'], 2) ?>
      </div>

      <p class="text-muted" style="line-height:1.6">
        <?= nl2br(htmlspecialchars($product['description'])) ?>
      </p>

      <p class="text-sm">
        <?php if ($product['quantity'] <= 0): ?>
          <strong style="color:var(--danger);">❌ Out of stock</strong>
        <?php elseif ($product['quantity'] <= 5): ?>
          <strong style="color:var(--sunset);">⚠️ Only <?= $product['quantity'] ?> left!</strong>
        <?php else: ?>
          <strong><?= $product['quantity'] ?></strong> available
        <?php endif; ?>
      </p>

      <!-- SELLER -->
      <div class="seller-card mt-3">
        <div class="avatar"><?= strtoupper(substr($product['seller_name'], 0, 1)) ?></div>

        <div style="flex:1">
          <div style="font-weight:800">
            <?= htmlspecialchars($product['seller_name']) ?>
          </div>

          <div class="text-sm text-muted">
            <?php if ($product['is_verified']): ?>
              <span class="badge badge-verified">✓ Verified Seller</span>
            <?php endif; ?>

            <?php if ($product['is_trusted']): ?>
              <span class="badge badge-trusted">★ Trusted</span>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- ACTIONS -->
      <div class="actions">

        <?php if ($product['quantity'] <= 0): ?>
          <!-- OUT OF STOCK -->
          <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
            <span class="badge badge-suspended" style="font-size:1rem; padding:10px 20px;">
              ⚠️ Out of Stock
            </span>
            <a class="btn btn-outline" href="messages.php?seller_id=<?= $product['seller_id'] ?>">
              💬 Message Seller
            </a>
          </div>
          
        <?php elseif (isLoggedIn()): ?>
          <!-- IN STOCK - ENABLE BUTTONS -->
          <form method="POST" action="cart.php">
            <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
            <input type="hidden" name="quantity" value="1">
            <button class="btn btn-primary">🛒 Add to Cart</button>
          </form>

          <a class="btn btn-outline" href="messages.php?seller_id=<?= $product['seller_id'] ?>">
            💬 Message Seller
          </a>
        <?php else: ?>
          <a class="btn btn-primary" href="login.php">Sign in to buy</a>
        <?php endif; ?>

      </div>

      <!-- TRUST BADGES -->
      <div class="card mt-3" style="padding:16px">
        <div class="grid" style="grid-template-columns:1fr 1fr;gap:10px;font-size:.85rem">
          <div>✔ Community verified sellers</div>
          <div>✔ Direct buyer-seller messaging</div>
          <div>✔ No middleman fees</div>
          <div>✔ Built for local trade</div>
        </div>
      </div>

    </div>
  </div>

  <!-- REVIEWS -->
  <section class="section">
    <div class="section-head">
      <h2>Reviews</h2>
    </div>

    <?php if (isset($_GET["reviewed"])): ?>
      <div class="card auto-hide" style="padding:16px;background:rgba(22,163,74,.08);border-left:4px solid var(--ubuntu);margin-bottom:16px">
        Review submitted successfully.
      </div>
    <?php endif; ?>

    <div class="card" style="padding:16px">

      <?php if ($reviews->num_rows > 0): ?>
        <?php while ($review = $reviews->fetch_assoc()): ?>
          <div class="review">
            <div class="head">
              <div class="avatar" style="width:36px;height:36px;font-size:.85rem">
                <?= strtoupper(substr($review['reviewer_name'], 0, 1)) ?>
              </div>

              <div>
                <div style="font-weight:700">
                  <?= htmlspecialchars($review['reviewer_name']) ?>
                </div>

                <div class="text-sm text-muted">
                  <?= date("d M Y", strtotime($review['created_at'])) ?>
                </div>
              </div>

              <div class="stars" style="margin-left:auto">
                <?= str_repeat("★", $review['rating']) ?>
                <?= str_repeat("☆", 5 - $review['rating']) ?>
              </div>
            </div>

            <p><?= htmlspecialchars($review['comment']) ?></p>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p class="text-muted">No reviews yet.</p>
      <?php endif; ?>

    </div>

    <!-- REVIEW FORM -->
    <?php if (isLoggedIn()): ?>
      <form method="POST" action="submit-review.php" class="form-card" style="max-width:none;margin-top:18px">
        <h3 style="margin:0 0 12px">Leave a review</h3>

        <input type="hidden" name="product_id" value="<?= $product["product_id"] ?>">

        <div class="field">
          <label>Rating</label>
          <select name="rating" required>
            <option value="5">★★★★★ 5</option>
            <option value="4">★★★★ 4</option>
            <option value="3">★★★ 3</option>
            <option value="2">★★ 2</option>
            <option value="1">★ 1</option>
          </select>
        </div>

        <div class="field">
          <label>Comment</label>
          <textarea name="comment" required placeholder="Share your experience..."></textarea>
        </div>

        <button class="btn btn-primary">Submit Review</button>
      </form>
    <?php else: ?>
      <div class="card mt-3" style="padding:18px;text-align:center">
        <p class="text-muted">Sign in to leave a review.</p>
        <a href="login.php" class="btn btn-primary">Sign in</a>
      </div>
    <?php endif; ?>

  </section>
</div>

<?php include "includes/footer.php"; ?>