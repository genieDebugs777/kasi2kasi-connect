<?php
require_once "includes/db.php";
require_once "includes/auth.php";
requireLogin();

$user_id = $_SESSION["user_id"];
$product_id = isset($_GET["id"]) ? intval($_GET["id"]) : (isset($_POST["product_id"]) ? intval($_POST["product_id"]) : 0);
$error = "";
$success = "";

if (!$product_id) {
    header("Location: profile.php");
    exit;
}

// Get product and verify ownership
$product_stmt = $conn->prepare("
    SELECT product.*, category.name AS category_name
    FROM product
    JOIN category ON product.category_id = category.category_id
    WHERE product.product_id = ? AND product.seller_id = ?
");
$product_stmt->bind_param("ii", $product_id, $user_id);
$product_stmt->execute();
$product = $product_stmt->get_result()->fetch_assoc();

if (!$product) {
    header("Location: profile.php");
    exit;
}

// Get categories for dropdown
$categories = $conn->query("SELECT * FROM category ORDER BY name ASC");

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_product"])) {
    $title = trim($_POST["title"]);
    $category_id = intval($_POST["category_id"]);
    $price = floatval($_POST["price"]);
    $quantity = intval($_POST["quantity"]);
    $image_url = trim($_POST["image_url"]);
    $description = trim($_POST["description"]);
    $status = $_POST["status"];

    if (empty($title) || empty($category_id) || empty($price) || empty($description)) {
        $error = "Please complete all required fields.";
    } else {
        $update_stmt = $conn->prepare("
            UPDATE product 
            SET title = ?, category_id = ?, price = ?, quantity = ?, image_url = ?, description = ?, status = ?
            WHERE product_id = ? AND seller_id = ?
        ");
        $update_stmt->bind_param(
            "sidissii",
            $title,
            $category_id,
            $price,
            $quantity,
            $image_url,
            $description,
            $status,
            $product_id,
            $user_id
        );

        if ($update_stmt->execute()) {
            // If quantity became 0, mark as sold automatically
            if ($quantity == 0 && $status != 'sold') {
                $auto_sold = $conn->prepare("UPDATE product SET status = 'sold' WHERE product_id = ?");
                $auto_sold->bind_param("i", $product_id);
                $auto_sold->execute();
            }
            
            $success = "Product updated successfully!";
            
            // Refresh product data
            $refresh_stmt = $conn->prepare("SELECT * FROM product WHERE product_id = ? AND seller_id = ?");
            $refresh_stmt->bind_param("ii", $product_id, $user_id);
            $refresh_stmt->execute();
            $product = $refresh_stmt->get_result()->fetch_assoc();
        } else {
            $error = "Failed to update product. Please try again.";
        }
    }
}
?>

<?php include "includes/header.php"; ?>

<div class="container">

  <section class="hero" style="padding:40px 30px;margin-bottom:22px">
    <div class="hero-content">
      <div class="kicker">✏️ Edit Listing</div>
      <h1 style="font-size:clamp(2rem,4vw,3.4rem)">Update your product.</h1>
      <p>
        Edit product details, update stock quantity, or change the listing status.
      </p>
    </div>
  </section>

  <?php if ($error): ?>
    <div class="card auto-hide" style="padding:16px;background:#fee2e2;border-left:4px solid var(--danger);margin-bottom:16px">
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <?php if ($success): ?>
    <div class="card auto-hide" style="padding:16px;background:rgba(22,163,74,.08);border-left:4px solid var(--ubuntu);margin-bottom:16px">
      <?= htmlspecialchars($success) ?>
      <a href="product.php?id=<?= $product_id ?>" style="margin-left:12px;color:var(--primary)">View product →</a>
    </div>
  <?php endif; ?>

  <div class="grid grid-2" style="align-items:start">

    <!-- Edit Form -->
    <form method="POST" class="form-card" style="max-width:none;margin:0">
      <span class="badge badge-trusted">Product Details</span>

      <h1 style="margin-top:12px">Edit Listing</h1>
      <p class="sub">Update the information below to keep your listing accurate.</p>

      <input type="hidden" name="update_product" value="1">
      <input type="hidden" name="product_id" value="<?= $product_id ?>">

      <div class="field">
        <label>Product Title *</label>
        <input 
          name="title" 
          value="<?= htmlspecialchars($product["title"]) ?>" 
          required 
          maxlength="150"
        >
      </div>

      <div class="field">
        <label>Category *</label>
        <select name="category_id" required>
          <?php while ($cat = $categories->fetch_assoc()): ?>
            <option value="<?= $cat["category_id"] ?>" <?= $cat["category_id"] == $product["category_id"] ? "selected" : "" ?>>
              <?= htmlspecialchars($cat["name"]) ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <div class="grid grid-2">
        <div class="field">
          <label>Price (R) *</label>
          <input 
            name="price" 
            type="number" 
            min="1" 
            step="0.01" 
            value="<?= $product["price"] ?>" 
            required
          >
        </div>

        <div class="field">
          <label>Quantity *</label>
          <input 
            name="quantity" 
            type="number" 
            min="0" 
            value="<?= $product["quantity"] ?>" 
            required
          >
          <small class="text-muted">Set to 0 to mark as out of stock</small>
        </div>
      </div>

      <div class="field">
        <label>Status</label>
        <select name="status">
          <option value="active" <?= $product["status"] === "active" ? "selected" : "" ?>>Active (Visible to buyers)</option>
          <option value="sold" <?= $product["status"] === "sold" ? "selected" : "" ?>>Sold (Hidden from search)</option>
          <option value="removed" <?= $product["status"] === "removed" ? "selected" : "" ?>>Removed (Hidden)</option>
        </select>
      </div>

      <div class="field">
        <label>Image URL</label>
        <input 
          name="image_url" 
          type="url" 
          value="<?= htmlspecialchars($product["image_url"]) ?>" 
          placeholder="https://example.com/product-image.jpg"
        >
        <?php if ($product["image_url"]): ?>
          <div class="mt-2">
            <img src="<?= htmlspecialchars($product["image_url"]) ?>" style="max-width:100px;border-radius:12px" alt="Current image">
          </div>
        <?php endif; ?>
      </div>

      <div class="field">
        <label>Description *</label>
        <textarea 
          name="description" 
          required 
          placeholder="Describe condition, pickup location, colour, size..."
        ><?= htmlspecialchars($product["description"]) ?></textarea>
      </div>

      <div class="flex between" style="gap:12px;margin-top:8px">
        <a href="profile.php#listings" class="btn btn-outline">← Cancel</a>
        <button class="btn btn-primary">Save Changes</button>
      </div>
    </form>

    <!-- Help Card -->
    <div>
      <div class="card" style="padding:26px;margin-bottom:20px">
        <span class="badge badge-verified">📊 Stock Tips</span>
        <h2 style="margin:12px 0 8px">Managing your inventory</h2>
        <p class="text-muted" style="line-height:1.6">
          Keep your stock accurate to avoid overselling and disappointing buyers.
        </p>

        <div class="grid" style="gap:12px;margin-top:16px">
          <div class="seller-card" style="box-shadow:none">
            <div class="avatar">1</div>
            <div>
              <strong>Low stock alert</strong>
              <p class="text-muted text-sm" style="margin:4px 0 0">
                Products with ≤10 units trigger low stock notifications.
              </p>
            </div>
          </div>

          <div class="seller-card" style="box-shadow:none">
            <div class="avatar">2</div>
            <div>
              <strong>Out of stock</strong>
              <p class="text-muted text-sm" style="margin:4px 0 0">
                Set quantity to 0 or status to "Sold" to hide from buyers.
              </p>
            </div>
          </div>

          <div class="seller-card" style="box-shadow:none">
            <div class="avatar">3</div>
            <div>
              <strong>Auto sold status</strong>
              <p class="text-muted text-sm" style="margin:4px 0 0">
                When quantity reaches 0, product auto-marks as "sold".
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Current Stock Status -->
      <div class="card" style="padding:20px;text-align:center">
        <span class="badge badge-trusted">Current Status</span>
        <h2 style="margin:12px 0 8px">
          <?php if ($product["quantity"] <= 0): ?>
            <span style="color:var(--danger)">❌ Out of Stock</span>
          <?php elseif ($product["quantity"] <= 5): ?>
            <span style="color:var(--sunset)">⚠️ Low Stock: <?= $product["quantity"] ?> left</span>
          <?php else: ?>
            <span style="color:var(--ubuntu)">✓ In Stock: <?= $product["quantity"] ?> units</span>
          <?php endif; ?>
        </h2>
        <p class="text-muted text-sm">
          Status: <strong><?= strtoupper($product["status"]) ?></strong>
        </p>
        <a href="product.php?id=<?= $product_id ?>" class="btn btn-outline btn-sm mt-2">
          View live listing →
        </a>
      </div>
    </div>

  </div>
</div>

<?php include "includes/footer.php"; ?>