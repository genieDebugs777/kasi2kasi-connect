<?php
require_once "INCLUDES/db.php";
require_once "INCLUDES/auth.php";
requireLogin();

$user_id = $_SESSION["user_id"];

$orders_stmt = $conn->prepare("
    SELECT *
    FROM orders
    WHERE buyer_id = ?
    ORDER BY created_at DESC
");

$orders_stmt->bind_param("i", $user_id);
$orders_stmt->execute();
$orders = $orders_stmt->get_result();

$placed_id = $_GET["placed"] ?? null;
?>

<?php include "includes/header.php"; ?>

<div class="container">

  <section class="hero" style="padding:40px 30px;margin-bottom:22px">
    <div class="hero-content">
      <div class="kicker">📦 Orders</div>
      <h1 style="font-size:clamp(2rem,4vw,3.4rem)">Track your kasi purchases.</h1>
      <p>
        View your order history, payment status, delivery details, and items bought from local sellers.
      </p>
    </div>
  </section>

  <?php if ($placed_id): ?>
    <div class="card auto-hide" style="padding:16px;background:rgba(22,163,74,.08);border-left:4px solid var(--ubuntu);margin-bottom:16px">
      ✓ Order #<?= htmlspecialchars($placed_id) ?> placed successfully.
    </div>
  <?php endif; ?>

  <section class="section">
    <div class="section-head">
      <div>
        <h2>My Orders</h2>
        <p>Your latest purchases and payment records.</p>
      </div>

      <a href="products.php" class="btn btn-outline btn-sm">Continue shopping</a>
    </div>

    <?php if ($orders->num_rows > 0): ?>

      <?php while ($order = $orders->fetch_assoc()): ?>
        <?php
          $items_stmt = $conn->prepare("
              SELECT 
                  order_item.quantity,
                  order_item.unit_price,
                  product.title,
                  product.image_url,
                  user.name AS seller_name,
                  user.user_id AS seller_id,
                  user.is_verified AS seller_verified
              FROM order_item
              JOIN product ON order_item.product_id = product.product_id
              JOIN user ON product.seller_id = user.user_id
              WHERE order_item.order_id = ?
          ");
          $items_stmt->bind_param("i", $order["order_id"]);
          $items_stmt->execute();
          $items = $items_stmt->get_result();

          $payment_stmt = $conn->prepare("
              SELECT method, status, reference
              FROM payment
              WHERE order_id = ?
              LIMIT 1
          ");
          $payment_stmt->bind_param("i", $order["order_id"]);
          $payment_stmt->execute();
          $payment = $payment_stmt->get_result()->fetch_assoc();

          $statusClass = "badge-pending";

          if ($order["status"] === "paid") {
              $statusClass = "badge-paid";
          } elseif ($order["status"] === "shipped") {
              $statusClass = "badge-trusted";
          } elseif ($order["status"] === "delivered") {
              $statusClass = "badge-delivered";
          } elseif ($order["status"] === "cancelled") {
              $statusClass = "badge-suspended";
          }
        ?>

        <div class="order-card">

          <div class="head">
            <div>
              <strong style="font-size:1.05rem">
                Order #<?= $order["order_id"] ?>
              </strong>

              <div class="text-sm text-muted">
                Placed on <?= date("d M Y", strtotime($order["created_at"])) ?>
              </div>
            </div>

            <span class="badge <?= $statusClass ?>">
              <?= strtoupper(htmlspecialchars($order["status"])) ?>
            </span>
          </div>

          <div class="grid" style="gap:12px;margin:16px 0">
            <?php while ($item = $items->fetch_assoc()): ?>
              <div class="seller-card" style="box-shadow:none">
                <div 
                  style="
                    width:56px;
                    height:56px;
                    border-radius:14px;
                    background-image:url('<?= htmlspecialchars($item["image_url"]) ?>');
                    background-size:cover;
                    background-position:center;
                    flex-shrink:0;
                  "
                ></div>

                <div style="flex:1">
                  <strong><?= htmlspecialchars($item["title"]) ?></strong>
                  <div class="text-sm text-muted">
                    Seller: <?= htmlspecialchars($item["seller_name"]) ?>
                    <?php if ($item["seller_verified"]): ?>
                      <span class="badge badge-verified">✓ Verified</span>
                    <?php endif; ?>
                    · Qty: <?= $item["quantity"] ?>
                  </div>
                </div>

                <div style="text-align:right">
                  <strong>
                    R <?= number_format($item["unit_price"] * $item["quantity"], 2) ?>
                  </strong>
                  <br>
                  <a href="messages.php?seller_id=<?= $item["seller_id"] ?>" class="text-sm" style="color:var(--primary)">Message Seller</a>
                </div>
              </div>
            <?php endwhile; ?>
          </div>

          <div class="grid grid-2" style="gap:14px;margin-top:14px">
            <div class="card" style="padding:14px;background:var(--cream);box-shadow:none">
              <strong>Delivery Details</strong>
              <p class="text-muted text-sm" style="margin:6px 0 0;line-height:1.5">
                <?= htmlspecialchars($order["delivery_address"]) ?><br>
                <?= htmlspecialchars($order["delivery_phone"]) ?>
              </p>
            </div>

            <div class="card" style="padding:14px;background:#fff;box-shadow:none;border:1px solid var(--border)">
              <strong>Payment Details</strong>

              <?php if ($payment): ?>
                <p class="text-muted text-sm" style="margin:6px 0 0;line-height:1.5">
                  Method: <?= strtoupper(htmlspecialchars($payment["method"])) ?><br>
                  Status: <?= strtoupper(htmlspecialchars($payment["status"])) ?><br>
                  Ref: <?= htmlspecialchars($payment["reference"]) ?>
                </p>
              <?php else: ?>
                <p class="text-muted text-sm" style="margin:6px 0 0">
                  No payment record found.
                </p>
              <?php endif; ?>
            </div>
          </div>

          <div class="flex between center mt-3" style="gap:12px;flex-wrap:wrap">
            <span class="text-muted text-sm">
              Keep this reference for follow-up with the seller.
            </span>

            <strong style="font-size:1.15rem">
              Total: R <?= number_format($order["total_amount"], 2) ?>
            </strong>
          </div>

        </div>

      <?php endwhile; ?>

    <?php else: ?>
      <div class="card" style="padding:42px;text-align:center">
        <div style="font-size:3rem">📦</div>
        <h2 style="margin:8px 0">No orders yet</h2>
        <p class="text-muted">
          Once you buy something from a seller, your order will appear here.
        </p>
        <a class="btn btn-primary" href="products.php">Browse marketplace</a>
      </div>
    <?php endif; ?>
  </section>

</div>

<?php include "INCLUDES/footer.php"; ?>
