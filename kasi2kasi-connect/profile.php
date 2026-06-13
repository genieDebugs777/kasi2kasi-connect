<?php
require_once "includes/db.php";
require_once "includes/auth.php";
requireLogin();

$user_id = $_SESSION["user_id"];
$message = "";

$user_stmt = $conn->prepare("
    SELECT user.*, role.role_name
    FROM user
    JOIN role ON user.role_id = role.role_id
    WHERE user.user_id = ?
");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_profile"])) {
    $name = trim($_POST["name"]);
    $phone = trim($_POST["phone"]);

    $stmt = $conn->prepare("UPDATE user SET name = ?, phone = ? WHERE user_id = ?");
    $stmt->bind_param("ssi", $name, $phone, $user_id);
    $stmt->execute();

    $_SESSION["name"] = $name;
    header("Location: profile.php?updated=1");
    exit;
}

// Handle verification submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["submit_verification"])) {
    $id_number = trim($_POST["id_number"]);
    $id_document_url = trim($_POST["id_document_url"]);
    $selfie_url = trim($_POST["selfie_url"]);

    $stmt = $conn->prepare("
        INSERT INTO verification_request (user_id, id_number, id_document_url, selfie_url)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("isss", $user_id, $id_number, $id_document_url, $selfie_url);

    if ($stmt->execute()) {
        header("Location: profile.php?verification=1");
        exit;
    } else {
        $message = "A verification request may already exist for this account.";
    }
}

// Handle product deletion
if (isset($_GET["delete_product"])) {
    $product_id = intval($_GET["delete_product"]);
    $delete_stmt = $conn->prepare("DELETE FROM product WHERE product_id = ? AND seller_id = ?");
    $delete_stmt->bind_param("ii", $product_id, $user_id);
    $delete_stmt->execute();
    header("Location: profile.php?deleted=1");
    exit;
}

// Get listings
$listings_stmt = $conn->prepare("
    SELECT *
    FROM product
    WHERE seller_id = ?
    ORDER BY created_at DESC
");
$listings_stmt->bind_param("i", $user_id);
$listings_stmt->execute();
$listings = $listings_stmt->get_result();

// Get verification request
$verification_stmt = $conn->prepare("
    SELECT *
    FROM verification_request
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 1
");
$verification_stmt->bind_param("i", $user_id);
$verification_stmt->execute();
$verification = $verification_stmt->get_result()->fetch_assoc();

// Get counts
$order_count_stmt = $conn->prepare("SELECT COUNT(*) AS c FROM orders WHERE buyer_id = ?");
$order_count_stmt->bind_param("i", $user_id);
$order_count_stmt->execute();
$order_count = $order_count_stmt->get_result()->fetch_assoc()["c"];

$listing_count_stmt = $conn->prepare("SELECT COUNT(*) AS c FROM product WHERE seller_id = ?");
$listing_count_stmt->bind_param("i", $user_id);
$listing_count_stmt->execute();
$listing_count = $listing_count_stmt->get_result()->fetch_assoc()["c"];

$review_count_stmt = $conn->prepare("
    SELECT COUNT(*) AS c
    FROM review
    JOIN product ON review.product_id = product.product_id
    WHERE product.seller_id = ?
");
$review_count_stmt->bind_param("i", $user_id);
$review_count_stmt->execute();
$review_count = $review_count_stmt->get_result()->fetch_assoc()["c"];

// Get low stock count
$low_stock_stmt = $conn->prepare("
    SELECT COUNT(*) AS c
    FROM product
    WHERE seller_id = ? AND quantity <= 10 AND quantity > 0 AND status = 'active'
");
$low_stock_stmt->bind_param("i", $user_id);
$low_stock_stmt->execute();
$low_stock_count = $low_stock_stmt->get_result()->fetch_assoc()["c"];

// Get pending orders count (for seller)
$pending_orders_stmt = $conn->prepare("
    SELECT COUNT(DISTINCT orders.order_id) AS c
    FROM orders
    JOIN order_item ON orders.order_id = order_item.order_id
    JOIN product ON order_item.product_id = product.product_id
    WHERE product.seller_id = ? AND orders.status = 'pending'
");
$pending_orders_stmt->bind_param("i", $user_id);
$pending_orders_stmt->execute();
$pending_orders_count = $pending_orders_stmt->get_result()->fetch_assoc()["c"];
?>

<?php include "includes/header.php"; ?>

<div class="container">

  <section class="hero" style="padding:42px 30px;margin-bottom:22px">
    <div class="hero-content">
      <div class="kicker">👤 Kasi Profile</div>
      <h1 style="font-size:clamp(2rem,4vw,3.5rem)">
        Manage your buyer and seller identity.
      </h1>
      <p>
        Track your listings, update your profile, and build trust through seller verification.
      </p>
    </div>
  </section>

  <!-- Low Stock Warning Banner -->
  <?php if ($low_stock_count > 0): ?>
    <div class="card" style="padding:16px;margin-bottom:20px;background:rgba(247,201,72,.12);border-left:4px solid var(--taxi);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
      <div>
        <strong>⚠️ Low Stock Alert:</strong> You have <?= $low_stock_count ?> product<?= $low_stock_count != 1 ? 's' : '' ?> with 10 or fewer units remaining.
      </div>
      <a href="#tab-listings" class="btn btn-primary btn-sm" onclick="showTab(null, 'listings')">View & Update Stock →</a>
    </div>
  <?php endif; ?>

  <!-- Pending Orders Banner -->
  <?php if ($pending_orders_count > 0): ?>
    <div class="card" style="padding:16px;margin-bottom:20px;background:rgba(91,45,245,.08);border-left:4px solid var(--primary);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
      <div>
        <strong>🛒 New Orders:</strong> You have <?= $pending_orders_count ?> pending order<?= $pending_orders_count != 1 ? 's' : '' ?> that need your attention.
      </div>
      <a href="seller_orders.php" class="btn btn-primary btn-sm">Manage Orders →</a>
    </div>
  <?php endif; ?>

  <div class="profile-head">
    <div class="avatar">
      <?= strtoupper(substr($user["name"], 0, 1)) ?>
    </div>

    <div style="flex:1">
      <h1><?= htmlspecialchars($user["name"]) ?></h1>
      <div class="meta"><?= htmlspecialchars($user["email"]) ?></div>

      <div class="mt-2" style="display:flex;gap:8px;flex-wrap:wrap">
        <span class="badge badge-active"><?= htmlspecialchars($user["role_name"]) ?></span>

        <?php if ($user["is_verified"]): ?>
          <span class="badge badge-verified">✓ Verified Seller</span>
        <?php else: ?>
          <span class="badge badge-pending">Not Verified</span>
        <?php endif; ?>

        <?php if ($user["is_trusted"]): ?>
          <span class="badge badge-trusted">★ Trusted Seller</span>
        <?php endif; ?>
      </div>
    </div>

    <div style="display:flex;gap:10px;flex-wrap:wrap">
      <a href="sell.php" class="btn btn-primary">+ List Item</a>
      <a href="seller_orders.php" class="btn btn-outline">📦 My Sales</a>
      <a href="orders.php" class="btn btn-outline">📋 My Orders</a>
    </div>
  </div>

  <!-- Enhanced Stats Grid -->
  <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));margin-bottom:22px">
    <div class="stat">
      <div class="label">My Listings</div>
      <div class="value"><?= $listing_count ?></div>
      <div class="delta">Products you sell</div>
    </div>

    <div class="stat">
      <div class="label">Pending Orders</div>
      <div class="value" style="color:var(--primary)"><?= $pending_orders_count ?></div>
      <div class="delta">Awaiting action</div>
    </div>

    <div class="stat">
      <div class="label">Low Stock</div>
      <div class="value" style="color:var(--sunset)"><?= $low_stock_count ?></div>
      <div class="delta">Need restock</div>
    </div>

    <div class="stat">
      <div class="label">My Orders</div>
      <div class="value"><?= $order_count ?></div>
      <div class="delta">Purchases</div>
    </div>

    <div class="stat">
      <div class="label">Seller Reviews</div>
      <div class="value"><?= $review_count ?></div>
      <div class="delta">Community feedback</div>
    </div>

    <div class="stat">
      <div class="label">Verified</div>
      <div class="value"><?= $user["is_verified"] ? "Yes" : "No" ?></div>
      <div class="delta">Trust status</div>
    </div>
  </div>

  <!-- Success Messages -->
  <?php if (isset($_GET["updated"])): ?>
    <div class="card auto-hide" style="padding:16px;background:rgba(22,163,74,.08);border-left:4px solid var(--ubuntu);margin-bottom:16px">
      Profile updated successfully.
    </div>
  <?php endif; ?>

  <?php if (isset($_GET["verification"])): ?>
    <div class="card auto-hide" style="padding:16px;background:rgba(22,163,74,.08);border-left:4px solid var(--ubuntu);margin-bottom:16px">
      Verification request submitted successfully.
    </div>
  <?php endif; ?>

  <?php if (isset($_GET["deleted"])): ?>
    <div class="card auto-hide" style="padding:16px;background:rgba(220,38,38,.08);border-left:4px solid var(--danger);margin-bottom:16px">
      Product deleted successfully.
    </div>
  <?php endif; ?>

  <?php if ($message): ?>
    <div class="card" style="padding:16px;color:var(--danger);margin-bottom:16px">
      <?= htmlspecialchars($message) ?>
    </div>
  <?php endif; ?>

  <!-- Tabs -->
  <div class="tabs">
    <div class="tab active" onclick="showTab(event, 'listings')">📦 My Listings</div>
    <div class="tab" onclick="showTab(event, 'settings')">⚙️ Account Settings</div>
    <div class="tab" onclick="showTab(event, 'verify')">✅ Seller Verification</div>
  </div>

  <!-- TAB 1: MY LISTINGS -->
  <div id="tab-listings">
    <div class="section-head">
      <div>
        <h2>My Listings</h2>
        <p>Your personal kasi storefront.</p>
      </div>

      <a href="sell.php" class="btn btn-primary btn-sm">+ New Listing</a>
    </div>

    <?php if ($listings->num_rows > 0): ?>
      <div class="grid grid-products">
        <?php while ($p = $listings->fetch_assoc()): ?>
          <div class="card product-card" style="position:relative">
            <a href="product.php?id=<?= $p["product_id"] ?>">
              <div class="img" style="background-image:url('<?= htmlspecialchars($p["image_url"]) ?>')"></div>
              <div class="body">
                <h3 class="title"><?= htmlspecialchars($p["title"]) ?></h3>
                <div class="price">R <?= number_format($p["price"], 2) ?></div>
                <div class="meta">
                  <span><?= htmlspecialchars($p["status"]) ?></span>
                  <?php if ($p["quantity"] <= 0): ?>
                    <span style="color:var(--danger);font-weight:bold">❌ Out of Stock</span>
                  <?php elseif ($p["quantity"] <= 5): ?>
                    <span style="color:var(--sunset);font-weight:bold">⚠️ Only <?= $p["quantity"] ?> left</span>
                  <?php else: ?>
                    <span>📦 <?= $p["quantity"] ?> left</span>
                  <?php endif; ?>
                </div>
              </div>
            </a>
            <!-- Edit and Delete Buttons -->
            <div style="position:absolute;top:10px;right:10px;display:flex;gap:5px">
              <a href="edit_product.php?id=<?= $p["product_id"] ?>" class="badge" style="background:var(--primary);color:white">✏️ Edit</a>
              <a href="profile.php?delete_product=<?= $p["product_id"] ?>" class="badge" style="background:var(--danger);color:white" onclick="return confirm('Delete this product permanently?')">🗑️ Delete</a>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <div class="card" style="padding:40px;text-align:center">
        <div style="font-size:3rem">🏪</div>
        <h2 style="margin:8px 0">Your storefront is empty</h2>
        <p class="text-muted">List your first item and start selling to people in your community.</p>
        <a href="sell.php" class="btn btn-primary">List your first item</a>
      </div>
    <?php endif; ?>
  </div>

  <!-- TAB 2: ACCOUNT SETTINGS -->
  <div id="tab-settings" class="hidden">
    <div class="grid grid-2" style="align-items:start">
      <form method="POST" class="form-card" style="max-width:none;margin:0">
        <span class="badge badge-trusted">Profile Settings</span>

        <h1 style="margin-top:12px">Update your account</h1>
        <p class="sub">Keep your buyer and seller details accurate.</p>

        <input type="hidden" name="update_profile" value="1">

        <div class="field">
          <label>Name</label>
          <input name="name" value="<?= htmlspecialchars($user["name"]) ?>" required>
        </div>

        <div class="field">
          <label>Email</label>
          <input value="<?= htmlspecialchars($user["email"]) ?>" disabled>
        </div>

        <div class="field">
          <label>Phone</label>
          <input name="phone" value="<?= htmlspecialchars($user["phone"] ?? '') ?>" placeholder="+27 82 000 0000">
        </div>

        <button class="btn btn-primary">Save Changes</button>
      </form>

      <div class="card" style="padding:26px">
        <span class="badge badge-verified">Account Safety</span>
        <h2 style="margin:12px 0 8px">Keep your kasi profile trustworthy</h2>
        <p class="text-muted" style="line-height:1.6">
          Use accurate contact details, respond quickly to buyers, and complete seller verification to improve trust.
        </p>

        <div class="grid" style="gap:12px;margin-top:16px">
          <div class="seller-card" style="box-shadow:none">
            <div class="avatar">1</div>
            <div>
              <strong>Use your real details</strong>
              <p class="text-muted text-sm" style="margin:4px 0 0">This improves buyer confidence.</p>
            </div>
          </div>

          <div class="seller-card" style="box-shadow:none">
            <div class="avatar">2</div>
            <div>
              <strong>Get verified</strong>
              <p class="text-muted text-sm" style="margin:4px 0 0">Verified sellers stand out in listings.</p>
            </div>
          </div>

          <div class="seller-card" style="box-shadow:none">
            <div class="avatar">3</div>
            <div>
              <strong>Earn reviews</strong>
              <p class="text-muted text-sm" style="margin:4px 0 0">Good reviews help you become trusted.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- TAB 3: SELLER VERIFICATION -->
  <div id="tab-verify" class="hidden">
    <?php if ($verification): ?>
      <div class="card" style="padding:28px">
        <span class="badge <?= $verification["status"] === 'approved' ? 'badge-verified' : ($verification["status"] === 'rejected' ? 'badge-suspended' : 'badge-pending') ?>">
          <?= strtoupper(htmlspecialchars($verification["status"])) ?>
        </span>

        <h2 style="margin:12px 0 8px">Latest Verification Request</h2>

        <p class="text-muted">
          Submitted on <?= date("d M Y", strtotime($verification["created_at"])) ?>
        </p>

        <div class="grid grid-2 mt-3">
          <div class="card" style="padding:16px;background:var(--cream);box-shadow:none">
            <strong>ID Number</strong>
            <p class="text-muted text-sm"><?= htmlspecialchars($verification["id_number"]) ?></p>
          </div>

          <div class="card" style="padding:16px;background:#fff;box-shadow:none;border:1px solid var(--border)">
            <strong>Reviewed</strong>
            <p class="text-muted text-sm">
              <?= $verification["reviewed_at"] ? date("d M Y", strtotime($verification["reviewed_at"])) : "Not reviewed yet" ?>
            </p>
          </div>
        </div>

        <?php if ($verification["notes"]): ?>
          <div class="card mt-3" style="padding:16px;background:#fff;box-shadow:none;border:1px solid var(--border)">
            <strong>Admin Notes</strong>
            <p class="text-muted text-sm" style="margin-bottom:0">
              <?= htmlspecialchars($verification["notes"]) ?>
            </p>
          </div>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="grid grid-2" style="align-items:start">
        <form method="POST" class="form-card" style="max-width:none;margin:0">
          <span class="badge badge-verified">Seller Trust</span>

          <h1 style="margin-top:12px">Become a verified seller</h1>

          <p class="sub">
            Verification helps buyers trust your listings and reduces fraud on the platform.
          </p>

          <input type="hidden" name="submit_verification" value="1">

          <div class="field">
            <label>ID Number</label>
            <input name="id_number" required placeholder="SA ID / Passport number">
          </div>

          <div class="field">
            <label>ID Document URL</label>
            <input name="id_document_url" placeholder="https://...">
            <small class="text-muted">Upload a clear photo of your ID document</small>
          </div>

          <div class="field">
            <label>Selfie URL</label>
            <input name="selfie_url" placeholder="https://...">
            <small class="text-muted">Upload a selfie holding your ID</small>
          </div>

          <button class="btn btn-secondary">
            Submit Verification Request
          </button>
        </form>

        <div class="card" style="padding:26px">
          <span class="badge badge-trusted">Why verify?</span>
          <h2 style="margin:12px 0 8px">Trust is currency in C2C trade</h2>
          <p class="text-muted" style="line-height:1.6">
            Buyers are more likely to purchase from sellers with verified identities and strong reviews.
          </p>

          <div class="grid" style="gap:12px;margin-top:16px">
            <div class="seller-card" style="box-shadow:none">
              <div class="avatar">✓</div>
              <div>
                <strong>Verified badge</strong>
                <p class="text-muted text-sm" style="margin:4px 0 0">Shown on listings and product pages.</p>
              </div>
            </div>

            <div class="seller-card" style="box-shadow:none">
              <div class="avatar">★</div>
              <div>
                <strong>More buyer confidence</strong>
                <p class="text-muted text-sm" style="margin:4px 0 0">Helps reduce fraud concerns.</p>
              </div>
            </div>

            <div class="seller-card" style="box-shadow:none">
              <div class="avatar">R</div>
              <div>
                <strong>Better selling potential</strong>
                <p class="text-muted text-sm" style="margin:4px 0 0">Trust can improve conversions.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>

</div>

<script>
function showTab(event, tab) {
  // If event exists, update active class on clicked tab
  if (event) {
    document.querySelectorAll(".tab").forEach(t => t.classList.remove("active"));
    event.target.classList.add("active");
  }
  
  // Show/hide tab content
  ["listings", "settings", "verify"].forEach(name => {
    const el = document.getElementById("tab-" + name);
    if (el) {
      el.classList.toggle("hidden", name !== tab);
    }
  });
  
  // If called from banner, update URL hash
  if (!event) {
    window.location.hash = tab;
  }
}

// Check URL hash on page load
document.addEventListener('DOMContentLoaded', function() {
  const hash = window.location.hash.substring(1);
  if (hash === 'listings' || hash === 'settings' || hash === 'verify') {
    showTab(null, hash);
  }
});
</script>

<?php include "includes/footer.php"; ?>