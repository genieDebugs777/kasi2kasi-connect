<?php
require_once "includes/db.php";
require_once "includes/auth.php";
requireLogin();

$user_id = $_SESSION["user_id"];

/* Ensure user has a cart */
$cart_stmt = $conn->prepare("SELECT cart_id FROM cart WHERE user_id = ?");
$cart_stmt->bind_param("i", $user_id);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();

if ($cart_result->num_rows === 0) {
    $create_cart = $conn->prepare("INSERT INTO cart (user_id) VALUES (?)");
    $create_cart->bind_param("i", $user_id);
    $create_cart->execute();
    $cart_id = $create_cart->insert_id;
} else {
    $cart_id = $cart_result->fetch_assoc()["cart_id"];
}

/* Add product to cart with stock validation */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["product_id"])) {
    $product_id = intval($_POST["product_id"]);
    $quantity = intval($_POST["quantity"] ?? 1);
    
    // Check current stock
    $stock_stmt = $conn->prepare("SELECT quantity FROM product WHERE product_id = ?");
    $stock_stmt->bind_param("i", $product_id);
    $stock_stmt->execute();
    $stock_result = $stock_stmt->get_result();
    $current_stock = $stock_result->fetch_assoc();
    
    if (!$current_stock) {
        header("Location: products.php?error=product_not_found");
        exit;
    }
    
    // Get current cart quantity for this product
    $cart_qty_stmt = $conn->prepare("SELECT quantity FROM cart_item WHERE cart_id = ? AND product_id = ?");
    $cart_qty_stmt->bind_param("ii", $cart_id, $product_id);
    $cart_qty_stmt->execute();
    $cart_qty_result = $cart_qty_stmt->get_result();
    $current_cart_qty = $cart_qty_result->num_rows > 0 ? $cart_qty_result->fetch_assoc()["quantity"] : 0;
    
    $new_total_qty = $current_cart_qty + $quantity;
    
    if ($new_total_qty > $current_stock["quantity"]) {
        // Not enough stock
        $_SESSION["cart_error"] = "Cannot add {$quantity} of this item. Only " . $current_stock["quantity"] . " available.";
        header("Location: product.php?id=" . $product_id . "&stock_error=1");
        exit;
    }

    $check = $conn->prepare("SELECT cart_item_id, quantity FROM cart_item WHERE cart_id = ? AND product_id = ?");
    $check->bind_param("ii", $cart_id, $product_id);
    $check->execute();
    $existing = $check->get_result();

    if ($existing->num_rows > 0) {
        $item = $existing->fetch_assoc();
        $new_qty = $item["quantity"] + $quantity;

        $update = $conn->prepare("UPDATE cart_item SET quantity = ? WHERE cart_item_id = ?");
        $update->bind_param("ii", $new_qty, $item["cart_item_id"]);
        $update->execute();
    } else {
        $insert = $conn->prepare("INSERT INTO cart_item (cart_id, product_id, quantity) VALUES (?, ?, ?)");
        $insert->bind_param("iii", $cart_id, $product_id, $quantity);
        $insert->execute();
    }

    header("Location: cart.php?added=1");
    exit;
}

/* Remove item */
if (isset($_GET["remove"])) {
    $cart_item_id = intval($_GET["remove"]);

    $delete = $conn->prepare("DELETE FROM cart_item WHERE cart_item_id = ? AND cart_id = ?");
    $delete->bind_param("ii", $cart_item_id, $cart_id);
    $delete->execute();

    header("Location: cart.php?removed=1");
    exit;
}

/* Update quantity - with stock validation */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_cart"])) {
    $has_error = false;
    $error_items = [];
    
    foreach ($_POST["quantities"] as $cart_item_id => $qty) {
        $cart_item_id = intval($cart_item_id);
        $qty = max(1, intval($qty));
        
        // Get product stock for this cart item
        $stock_check = $conn->prepare("
            SELECT product.quantity, product.title 
            FROM cart_item 
            JOIN product ON cart_item.product_id = product.product_id 
            WHERE cart_item.cart_item_id = ? AND cart_item.cart_id = ?
        ");
        $stock_check->bind_param("ii", $cart_item_id, $cart_id);
        $stock_check->execute();
        $stock = $stock_check->get_result()->fetch_assoc();
        
        if ($qty > $stock["quantity"]) {
            $has_error = true;
            $error_items[] = $stock["title"];
        } else {
            $update = $conn->prepare("UPDATE cart_item SET quantity = ? WHERE cart_item_id = ? AND cart_id = ?");
            $update->bind_param("iii", $qty, $cart_item_id, $cart_id);
            $update->execute();
        }
    }
    
    if ($has_error) {
        $_SESSION["cart_stock_error"] = "Some items exceed available stock: " . implode(", ", $error_items);
        header("Location: cart.php?stock_error=1");
    } else {
        header("Location: cart.php?updated=1");
    }
    exit;
}

/* Fetch cart items */
$items_stmt = $conn->prepare("
    SELECT 
        cart_item.cart_item_id,
        cart_item.quantity,
        product.product_id,
        product.title,
        product.price,
        product.image_url,
        product.quantity AS stock_quantity,
        user.name AS seller_name,
        user.is_verified
    FROM cart_item
    JOIN product ON cart_item.product_id = product.product_id
    JOIN user ON product.seller_id = user.user_id
    WHERE cart_item.cart_id = ?
");

$items_stmt->bind_param("i", $cart_id);
$items_stmt->execute();
$items = $items_stmt->get_result();

$cart_items = [];
$subtotal = 0;
$stock_warnings = [];

while ($row = $items->fetch_assoc()) {
    // Check if requested quantity exceeds available stock
    if ($row["quantity"] > $row["stock_quantity"]) {
        $stock_warnings[] = [
            "title" => $row["title"],
            "cart_item_id" => $row["cart_item_id"],
            "requested" => $row["quantity"],
            "available" => $row["stock_quantity"]
        ];
    }
    $row["line_total"] = $row["price"] * $row["quantity"];
    $subtotal += $row["line_total"];
    $cart_items[] = $row;
}

$delivery_fee = count($cart_items) > 0 ? 50 : 0;
$total = $subtotal + $delivery_fee;
?>

<?php include "includes/header.php"; ?>

<div class="container">

  <section class="hero" style="padding:40px 30px;margin-bottom:22px">
    <div class="hero-content">
      <div class="kicker">🛒 Cart</div>
      <h1 style="font-size:clamp(2rem,4vw,3.4rem)">Review your kasi haul.</h1>
      <p>
        Check your items, update quantities, and get ready to complete your local purchase securely.
      </p>
    </div>
  </section>

  <?php if (isset($_GET["added"])): ?>
    <div class="card auto-hide" style="padding:16px;background:rgba(22,163,74,.08);border-left:4px solid var(--ubuntu);margin-bottom:16px">
      Item added to cart successfully.
    </div>
  <?php endif; ?>

  <?php if (isset($_GET["updated"])): ?>
    <div class="card auto-hide" style="padding:16px;background:rgba(91,45,245,.08);border-left:4px solid var(--primary);margin-bottom:16px">
      Cart updated successfully.
    </div>
  <?php endif; ?>

  <?php if (isset($_GET["removed"])): ?>
    <div class="card auto-hide" style="padding:16px;background:rgba(220,38,38,.08);border-left:4px solid var(--danger);margin-bottom:16px">
      Item removed from cart.
    </div>
  <?php endif; ?>

  <?php if (isset($_GET["stock_error"]) && isset($_SESSION["cart_stock_error"])): ?>
    <div class="card" style="padding:16px;background:#fee2e2;border-left:4px solid var(--danger);margin-bottom:16px">
      <strong>⚠️ Stock Issue</strong>
      <p style="margin:8px 0 0;"><?= htmlspecialchars($_SESSION["cart_stock_error"]) ?></p>
    </div>
    <?php unset($_SESSION["cart_stock_error"]); ?>
  <?php endif; ?>

  <?php if (!empty($stock_warnings)): ?>
    <div class="card" style="padding:16px;background:#fee2e2;border-left:4px solid var(--danger);margin-bottom:16px">
      <strong>⚠️ Stock Issues Detected</strong>
      <ul style="margin:8px 0 0 20px;">
        <?php foreach ($stock_warnings as $warning): ?>
          <li><?= htmlspecialchars($warning["title"]) ?>: Requested <?= $warning["requested"] ?>, Only <?= $warning["available"] ?> available</li>
        <?php endforeach; ?>
      </ul>
      <p class="text-sm" style="margin-top:8px;">Please update quantities before checkout.</p>
    </div>
  <?php endif; ?>

  <div class="layout-2 mt-3">

    <div>
      <?php if (empty($cart_items)): ?>
        <div class="card" style="padding:40px;text-align:center">
          <div style="font-size:3rem">🛒</div>
          <h2 style="margin:8px 0">Your cart is empty</h2>
          <p class="text-muted">Start browsing local listings and add something you like.</p>
          <a class="btn btn-primary" href="products.php">Browse products</a>
        </div>
      <?php else: ?>

        <form method="POST">
          <input type="hidden" name="update_cart" value="1">

          <?php foreach ($cart_items as $item): ?>
            <div class="cart-row">
              <a href="product.php?id=<?= $item['product_id'] ?>">
                <div class="thumb" style="background-image:url('<?= htmlspecialchars($item['image_url']) ?>')"></div>
              </a>

              <div class="info">
                <h4>
                  <a href="product.php?id=<?= $item['product_id'] ?>">
                    <?= htmlspecialchars($item['title']) ?>
                  </a>
                </h4>

                <div class="text-sm text-muted">
                  Seller: <?= htmlspecialchars($item['seller_name']) ?>

                  <?php if ($item["is_verified"]): ?>
                    <span class="badge badge-verified">✓ Verified</span>
                  <?php endif; ?>
                </div>

                <div class="p">
                  R <?= number_format($item['price'], 2) ?>
                </div>

                <div class="text-sm <?= $item["stock_quantity"] <= 0 ? 'text-danger' : 'text-muted' ?>" style="<?= $item["stock_quantity"] <= 0 ? 'color:var(--danger)' : '' ?>">
                  <?php if ($item["stock_quantity"] <= 0): ?>
                    ❌ Out of stock
                  <?php elseif ($item["stock_quantity"] <= 5): ?>
                    ⚠️ Only <?= $item["stock_quantity"] ?> left!
                  <?php else: ?>
                    <?= $item["stock_quantity"] ?> in stock
                  <?php endif; ?>
                </div>
              </div>

              <div class="field" style="margin:0;width:90px">
                <label style="font-size:.75rem">Qty</label>
                <input 
                  type="number" 
                  name="quantities[<?= $item['cart_item_id'] ?>]" 
                  value="<?= $item['quantity'] ?>" 
                  min="1"
                  max="<?= $item["stock_quantity"] ?>"
                  <?= $item["stock_quantity"] <= 0 ? 'disabled' : '' ?>
                >
              </div>

              <div style="font-weight:950;min-width:100px;text-align:right">
                R <?= number_format($item['line_total'], 2) ?>
              </div>

              <a 
                class="btn btn-outline btn-sm" 
                href="cart.php?remove=<?= $item['cart_item_id'] ?>"
                onclick="return confirm('Remove this item from your cart?')"
              >
                Remove
              </a>
            </div>
          <?php endforeach; ?>

          <div class="flex between center mt-3" style="gap:12px;flex-wrap:wrap">
            <a href="products.php" class="btn btn-outline">← Continue shopping</a>
            <button class="btn btn-primary" <?= !empty($stock_warnings) ? 'disabled style="opacity:0.5"' : '' ?>>Update Cart</button>
          </div>
        </form>

      <?php endif; ?>
    </div>

    <aside class="summary">
      <span class="badge badge-trusted">Order Summary</span>

      <h3 style="margin:12px 0 14px">
        Your total
      </h3>

      <div class="row">
        <span>Subtotal</span>
        <span>R <?= number_format($subtotal, 2) ?></span>
      </div>

      <div class="row">
        <span>Delivery</span>
        <span>R <?= number_format($delivery_fee, 2) ?></span>
      </div>

      <div class="row total">
        <span>Total</span>
        <span>R <?= number_format($total, 2) ?></span>
      </div>

      <div class="card" style="padding:14px;background:var(--cream);box-shadow:none;margin:16px 0">
        <strong>Buyer protection note</strong>
        <p class="text-muted text-sm" style="margin:6px 0 0;line-height:1.5">
          Always confirm seller identity and use secure payment options where possible.
        </p>
      </div>

      <?php if (!empty($cart_items) && empty($stock_warnings)): ?>
        <a href="checkout.php" class="btn btn-primary btn-block">
          Proceed to Checkout
        </a>
      <?php elseif (!empty($cart_items) && !empty($stock_warnings)): ?>
        <button class="btn btn-primary btn-block" disabled style="opacity:0.5">
          Fix stock issues to checkout
        </button>
      <?php else: ?>
        <a href="products.php" class="btn btn-primary btn-block">
          Browse Products
        </a>
      <?php endif; ?>
    </aside>

  </div>
</div>

<?php include "includes/footer.php"; ?>