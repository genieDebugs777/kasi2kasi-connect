<?php
require_once "INCLUDES/db.php";
require_once "INCLUDES/auth.php";
requireLogin();

$user_id = $_SESSION["user_id"];

// ============================================================
// CHECK FOR NEW ORDERS AND CREATE NOTIFICATIONS
// ============================================================
$new_orders_check = $conn->prepare("
    SELECT DISTINCT
        orders.order_id,
        orders.created_at,
        COUNT(DISTINCT notification.notification_id) AS notif_exists
    FROM orders
    JOIN order_item ON orders.order_id = order_item.order_id
    JOIN product ON order_item.product_id = product.product_id
    LEFT JOIN notification ON notification.user_id = product.seller_id 
        AND notification.type = 'new_order' 
        AND notification.product_id = order_item.product_id
        AND DATE(notification.created_at) = CURDATE()
    WHERE product.seller_id = ?
        AND orders.status = 'pending'
        AND DATE(orders.created_at) = CURDATE()
    GROUP BY orders.order_id
");

$new_orders_check->bind_param("i", $user_id);
$new_orders_check->execute();
$new_orders = $new_orders_check->get_result();

while ($order = $new_orders->fetch_assoc()) {
    if ($order["notif_exists"] == 0) {
        // Get product titles for this order
        $product_stmt = $conn->prepare("
            SELECT product.title, product.product_id
            FROM order_item
            JOIN product ON order_item.product_id = product.product_id
            WHERE order_item.order_id = ?
        ");
        $product_stmt->bind_param("i", $order["order_id"]);
        $product_stmt->execute();
        $products = $product_stmt->get_result();
        
        $product_names = [];
        $product_ids = [];
        while ($p = $products->fetch_assoc()) {
            $product_names[] = $p["title"];
            $product_ids[] = $p["product_id"];
        }
        
        $product_list = implode(", ", $product_names);
        $message = "🛒 New Order #{$order['order_id']}! Customer purchased: {$product_list}. Log in to manage this order.";
        
        // Create notification for each product
        $insert_stmt = $conn->prepare("
            INSERT INTO notification (user_id, product_id, type, title, message, is_read)
            VALUES (?, ?, 'new_order', 'New Order Received!', ?, 0)
        ");
        
        foreach ($product_ids as $pid) {
            $insert_stmt->bind_param("iis", $user_id, $pid, $message);
            $insert_stmt->execute();
        }
    }
}

// ============================================================
// FETCH ALL ORDERS FOR THIS SELLER
// ============================================================
$orders_stmt = $conn->prepare("
    SELECT DISTINCT
        orders.order_id,
        orders.total_amount,
        orders.status,
        orders.delivery_address,
        orders.delivery_phone,
        orders.created_at,
        orders.buyer_id,
        buyer.name AS buyer_name,
        buyer.email AS buyer_email,
        buyer.phone AS buyer_phone
    FROM orders
    JOIN order_item ON orders.order_id = order_item.order_id
    JOIN product ON order_item.product_id = product.product_id
    JOIN user AS buyer ON orders.buyer_id = buyer.user_id
    WHERE product.seller_id = ?
    ORDER BY orders.created_at DESC
");

$orders_stmt->bind_param("i", $user_id);
$orders_stmt->execute();
$orders = $orders_stmt->get_result();

// ============================================================
// HANDLE ORDER STATUS UPDATE
// ============================================================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_status"])) {
    $order_id = intval($_POST["order_id"]);
    $new_status = $_POST["new_status"];
    
    // Verify this seller has permission to update this order
    $check_stmt = $conn->prepare("
        SELECT COUNT(*) as count
        FROM orders
        JOIN order_item ON orders.order_id = order_item.order_id
        JOIN product ON order_item.product_id = product.product_id
        WHERE orders.order_id = ? AND product.seller_id = ?
    ");
    $check_stmt->bind_param("ii", $order_id, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result()->fetch_assoc();
    
    if ($check_result["count"] > 0) {
        $update_stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
        $update_stmt->bind_param("si", $new_status, $order_id);
        $update_stmt->execute();
        
        // Create notification for buyer about order update
        $buyer_info = $conn->prepare("SELECT buyer_id FROM orders WHERE order_id = ?");
        $buyer_info->bind_param("i", $order_id);
        $buyer_info->execute();
        $buyer_id = $buyer_info->get_result()->fetch_assoc()["buyer_id"];
        
        $status_messages = [
            "paid" => "✅ Your order #{$order_id} has been marked as PAID. The seller will prepare your items soon.",
            "shipped" => "🚚 Your order #{$order_id} has been SHIPPED! Track your delivery for updates.",
            "delivered" => "📦 Your order #{$order_id} has been DELIVERED. Thank you for shopping on Kasi2Kasi!"
        ];
        
        if (isset($status_messages[$new_status])) {
            $notif_stmt = $conn->prepare("
                INSERT INTO notification (user_id, type, title, message, is_read)
                VALUES (?, 'order_update', 'Order Status Updated', ?, 0)
            ");
            $notif_stmt->bind_param("is", $buyer_id, $status_messages[$new_status]);
            $notif_stmt->execute();
        }
        
        // If order is delivered, also mark payment as completed
        if ($new_status === "delivered") {
            $update_payment = $conn->prepare("UPDATE payment SET status = 'completed', paid_at = NOW() WHERE order_id = ?");
            $update_payment->bind_param("i", $order_id);
            $update_payment->execute();
        }
        
        header("Location: seller_orders.php?updated=1");
        exit;
    }
}

// ============================================================
// HANDLE BULK STATUS UPDATE
// ============================================================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["bulk_update"])) {
    $bulk_status = $_POST["bulk_status"];
    $selected_orders = $_POST["selected_orders"] ?? [];
    
    if (!empty($selected_orders)) {
        foreach ($selected_orders as $order_id) {
            $order_id = intval($order_id);
            
            $check_stmt = $conn->prepare("
                SELECT COUNT(*) as count
                FROM orders
                JOIN order_item ON orders.order_id = order_item.order_id
                JOIN product ON order_item.product_id = product.product_id
                WHERE orders.order_id = ? AND product.seller_id = ?
            ");
            $check_stmt->bind_param("ii", $order_id, $user_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result()->fetch_assoc();
            
            if ($check_result["count"] > 0) {
                $update_stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
                $update_stmt->bind_param("si", $bulk_status, $order_id);
                $update_stmt->execute();
            }
        }
        header("Location: seller_orders.php?bulk_updated=1");
        exit;
    }
}

// ============================================================
// GET STATISTICS
// ============================================================
$stats_stmt = $conn->prepare("
    SELECT 
        COUNT(DISTINCT orders.order_id) AS total_orders,
        SUM(CASE WHEN orders.status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
        SUM(CASE WHEN orders.status = 'paid' THEN 1 ELSE 0 END) AS paid_count,
        SUM(CASE WHEN orders.status = 'shipped' THEN 1 ELSE 0 END) AS shipped_count,
        SUM(CASE WHEN orders.status = 'delivered' THEN 1 ELSE 0 END) AS delivered_count,
        SUM(orders.total_amount) AS total_revenue
    FROM orders
    JOIN order_item ON orders.order_id = order_item.order_id
    JOIN product ON order_item.product_id = product.product_id
    WHERE product.seller_id = ?
");

$stats_stmt->bind_param("i", $user_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

$total_orders = $stats["total_orders"] ?? 0;
$pending_count = $stats["pending_count"] ?? 0;
$paid_count = $stats["paid_count"] ?? 0;
$shipped_count = $stats["shipped_count"] ?? 0;
$delivered_count = $stats["delivered_count"] ?? 0;
$total_revenue = $stats["total_revenue"] ?? 0;

// ============================================================
// GET LOW STOCK STATISTICS
// ============================================================
$low_stock_count_stmt = $conn->prepare("
    SELECT COUNT(*) AS c 
    FROM product 
    WHERE seller_id = ? 
    AND quantity <= 10 
    AND quantity > 0 
    AND status = 'active'
");
$low_stock_count_stmt->bind_param("i", $user_id);
$low_stock_count_stmt->execute();
$low_stock_count = $low_stock_count_stmt->get_result()->fetch_assoc()["c"];

$out_of_stock_count_stmt = $conn->prepare("
    SELECT COUNT(*) AS c 
    FROM product 
    WHERE seller_id = ? 
    AND quantity = 0 
    AND status = 'sold'
");
$out_of_stock_count_stmt->bind_param("i", $user_id);
$out_of_stock_count_stmt->execute();
$out_of_stock_count = $out_of_stock_count_stmt->get_result()->fetch_assoc()["c"];

$status_filter = $_GET["status"] ?? "";
?>

<?php include "INCLUDES/header.php"; ?>

<div class="container">

  <section class="hero" style="padding:40px 30px;margin-bottom:22px">
    <div class="hero-content">
      <div class="kicker">📦 Seller Dashboard</div>
      <h1 style="font-size:clamp(2rem,4vw,3.4rem)">Manage your orders.</h1>
      <p>
        Track incoming purchases, update order status, and keep buyers informed.
      </p>
    </div>
  </section>

  <?php if (isset($_GET["updated"])): ?>
    <div class="card auto-hide" style="padding:16px;background:rgba(22,163,74,.08);border-left:4px solid var(--ubuntu);margin-bottom:16px">
      Order status updated successfully.
    </div>
  <?php endif; ?>

  <?php if (isset($_GET["bulk_updated"])): ?>
    <div class="card auto-hide" style="padding:16px;background:rgba(91,45,245,.08);border-left:4px solid var(--primary);margin-bottom:16px">
      Bulk order status updated successfully.
    </div>
  <?php endif; ?>

  <!-- Statistics Cards -->
  <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));margin-bottom:24px">
    <div class="stat">
      <div class="label">Total Orders</div>
      <div class="value"><?= $total_orders ?></div>
      <div class="delta">All time</div>
    </div>
    <div class="stat">
      <div class="label">Pending</div>
      <div class="value" style="color:var(--sunset)"><?= $pending_count ?></div>
      <div class="delta">Awaiting action</div>
    </div>
    <div class="stat">
      <div class="label">Paid / Ready</div>
      <div class="value" style="color:var(--primary)"><?= $paid_count ?></div>
      <div class="delta">Payment confirmed</div>
    </div>
    <div class="stat">
      <div class="label">Shipped</div>
      <div class="value" style="color:var(--ubuntu)"><?= $shipped_count ?></div>
      <div class="delta">On the way</div>
    </div>
    <div class="stat">
      <div class="label">Delivered</div>
      <div class="value" style="color:var(--ubuntu)"><?= $delivered_count ?></div>
      <div class="delta">Completed</div>
    </div>
    <div class="stat">
      <div class="label">Total Revenue</div>
      <div class="value">R <?= number_format($total_revenue, 2) ?></div>
      <div class="delta">Lifetime sales</div>
    </div>
    <div class="stat">
      <div class="label">Low Stock</div>
      <div class="value" style="color:var(--sunset)"><?= $low_stock_count ?></div>
      <div class="delta">Need restock</div>
    </div>
    <div class="stat">
      <div class="label">Sold Out</div>
      <div class="value" style="color:var(--muted)"><?= $out_of_stock_count ?></div>
      <div class="delta">Marked as sold</div>
    </div>
  </div>

  <!-- Low Stock Warning Banner -->
  <?php if ($low_stock_count > 0): ?>
    <div class="card" style="padding:16px;margin-bottom:20px;background:rgba(247,201,72,.12);border-left:4px solid var(--taxi)">
      <strong>⚠️ Low Stock Alert:</strong> You have <?= $low_stock_count ?> product<?= $low_stock_count != 1 ? 's' : '' ?> with 10 or fewer units remaining. 
      <a href="profile.php#tab-listings" style="color:var(--primary)">Update stock now →</a>
    </div>
  <?php endif; ?>

  <!-- Status Filter Tabs -->
  <div class="pills" style="margin-bottom:24px">
    <a class="pill <?= empty($status_filter) ? 'active' : '' ?>" href="seller_orders.php">All Orders</a>
    <a class="pill <?= $status_filter === 'pending' ? 'active' : '' ?>" href="seller_orders.php?status=pending">Pending</a>
    <a class="pill <?= $status_filter === 'paid' ? 'active' : '' ?>" href="seller_orders.php?status=paid">Paid</a>
    <a class="pill <?= $status_filter === 'shipped' ? 'active' : '' ?>" href="seller_orders.php?status=shipped">Shipped</a>
    <a class="pill <?= $status_filter === 'delivered' ? 'active' : '' ?>" href="seller_orders.php?status=delivered">Delivered</a>
  </div>

  <!-- Bulk Action Form -->
  <?php if ($orders->num_rows > 0): ?>
    <form method="POST" id="bulkForm">
      <div class="card" style="padding:16px;margin-bottom:20px;display:flex;align-items:center;gap:12px;flex-wrap:wrap">
        <label style="display:flex;align-items:center;gap:6px">
          <input type="checkbox" id="selectAll">
          <strong>Select All</strong>
        </label>
        
        <select name="bulk_status" style="padding:8px 12px;border-radius:12px;border:1px solid var(--border)">
          <option value="pending">Mark as Pending</option>
          <option value="paid">Mark as Paid</option>
          <option value="shipped">Mark as Shipped</option>
          <option value="delivered">Mark as Delivered</option>
        </select>
        
        <button type="submit" name="bulk_update" class="btn btn-primary btn-sm" onclick="return confirm('Update selected orders?')">
          Apply to Selected
        </button>
      </div>
    </form>
  <?php endif; ?>

  <!-- Orders List -->
  <?php if ($orders->num_rows > 0): ?>

    <?php 
    $orders->data_seek(0);
    while ($order = $orders->fetch_assoc()): 
    ?>
      <?php
        // Filter by status if selected
        if (!empty($status_filter) && $order["status"] !== $status_filter) {
            continue;
        }
        
        // Fetch items for this order
        $items_stmt = $conn->prepare("
            SELECT 
                order_item.*,
                product.title,
                product.image_url,
                product.quantity AS current_stock
            FROM order_item
            JOIN product ON order_item.product_id = product.product_id
            WHERE order_item.order_id = ?
        ");
        $items_stmt->bind_param("i", $order["order_id"]);
        $items_stmt->execute();
        $items = $items_stmt->get_result();
        
        $order_total = 0;
        $items_list = [];
        while ($item = $items->fetch_assoc()) {
            $item["line_total"] = $item["unit_price"] * $item["quantity"];
            $order_total += $item["line_total"];
            $items_list[] = $item;
        }
        
        $statusClass = "";
        $statusIcon = "";
        switch($order["status"]) {
            case "pending":
                $statusClass = "badge-pending";
                $statusIcon = "⏳";
                break;
            case "paid":
                $statusClass = "badge-verified";
                $statusIcon = "💳";
                break;
            case "shipped":
                $statusClass = "badge-trusted";
                $statusIcon = "🚚";
                break;
            case "delivered":
                $statusClass = "badge-active";
                $statusIcon = "✅";
                break;
            case "cancelled":
                $statusClass = "badge-suspended";
                $statusIcon = "❌";
                break;
        }
      ?>

      <div class="order-card" style="margin-bottom:20px">
        <div class="head" style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px">
          <div>
            <input type="checkbox" class="order-checkbox" form="bulkForm" name="selected_orders[]" value="<?= $order["order_id"] ?>">
            <strong style="font-size:1.05rem;margin-left:8px">
              Order #<?= $order["order_id"] ?>
            </strong>
            <div class="text-sm text-muted">
              Placed on <?= date("d M Y, H:i", strtotime($order["created_at"])) ?>
            </div>
          </div>

          <span class="badge <?= $statusClass ?>">
            <?= $statusIcon ?> <?= strtoupper(htmlspecialchars($order["status"])) ?>
          </span>
        </div>

        <!-- Buyer Information -->
        <div class="grid grid-2" style="margin:16px 0;gap:16px">
          <div class="card" style="padding:16px;background:var(--cream);box-shadow:none">
            <strong>👤 Buyer Details</strong>
            <p class="text-muted text-sm" style="margin:6px 0 0;line-height:1.5">
              Name: <?= htmlspecialchars($order["buyer_name"]) ?><br>
              Email: <?= htmlspecialchars($order["buyer_email"]) ?><br>
              Phone: <?= htmlspecialchars($order["buyer_phone"] ?? "Not provided") ?>
            </p>
          </div>

          <div class="card" style="padding:16px;background:#fff;box-shadow:none;border:1px solid var(--border)">
            <strong>📍 Delivery Information</strong>
            <p class="text-muted text-sm" style="margin:6px 0 0;line-height:1.5">
              Address: <?= htmlspecialchars($order["delivery_address"]) ?><br>
              Phone: <?= htmlspecialchars($order["delivery_phone"]) ?>
            </p>
          </div>
        </div>

        <!-- Items in this order -->
        <div style="margin:16px 0">
          <strong>📦 Items in this order:</strong>
          <div style="margin-top:10px">
            <?php foreach ($items_list as $item): ?>
              <div class="seller-card" style="margin-bottom:8px;box-shadow:none">
                <div style="width:50px;height:50px;border-radius:12px;background-image:url('<?= htmlspecialchars($item["image_url"]) ?>');background-size:cover;background-position:center;flex-shrink:0"></div>
                <div style="flex:1">
                  <strong><?= htmlspecialchars($item["title"]) ?></strong>
                  <div class="text-sm text-muted">
                    Qty: <?= $item["quantity"] ?> × R <?= number_format($item["unit_price"], 2) ?>
                    <?php if ($item["quantity"] > $item["current_stock"]): ?>
                      <span style="color:var(--danger);margin-left:10px">⚠️ Low stock alert!</span>
                    <?php endif; ?>
                  </div>
                </div>
                <strong>R <?= number_format($item["line_total"], 2) ?></strong>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Order Total and Actions -->
        <div class="flex between center" style="margin-top:16px;gap:12px;flex-wrap:wrap">
          <strong style="font-size:1.15rem">
            Order Total: R <?= number_format($order_total, 2) ?>
          </strong>

          <div style="display:flex;gap:8px">
            <?php if ($order["status"] === "pending"): ?>
              <form method="POST" style="display:inline">
                <input type="hidden" name="order_id" value="<?= $order["order_id"] ?>">
                <input type="hidden" name="new_status" value="paid">
                <button type="submit" name="update_status" class="btn btn-primary btn-sm">✓ Mark as Paid</button>
              </form>
            <?php endif; ?>

            <?php if ($order["status"] === "paid"): ?>
              <form method="POST" style="display:inline">
                <input type="hidden" name="order_id" value="<?= $order["order_id"] ?>">
                <input type="hidden" name="new_status" value="shipped">
                <button type="submit" name="update_status" class="btn btn-primary btn-sm">🚚 Mark as Shipped</button>
              </form>
            <?php endif; ?>

            <?php if ($order["status"] === "shipped"): ?>
              <form method="POST" style="display:inline">
                <input type="hidden" name="order_id" value="<?= $order["order_id"] ?>">
                <input type="hidden" name="new_status" value="delivered">
                <button type="submit" name="update_status" class="btn btn-success btn-sm">✅ Mark as Delivered</button>
              </form>
            <?php endif; ?>

            <?php if (in_array($order["status"], ["pending", "paid"])): ?>
              <a href="messages.php?user_id=<?= $order["buyer_id"] ?>" class="btn btn-outline btn-sm">💬 Message Buyer</a>
            <?php endif; ?>
          </div>
        </div>
      </div>

    <?php endwhile; ?>

  <?php else: ?>
    <div class="card" style="padding:60px;text-align:center">
      <div style="font-size:4rem">📦</div>
      <h2 style="margin:16px 0 8px">No orders yet</h2>
      <p class="text-muted">
        When customers buy your products, orders will appear here.
      </p>
      <a href="sell.php" class="btn btn-primary">List more products</a>
    </div>
  <?php endif; ?>

</div>

<script>
// Select All functionality
document.getElementById('selectAll')?.addEventListener('change', function(e) {
    document.querySelectorAll('.order-checkbox').forEach(cb => {
        cb.checked = e.target.checked;
    });
});
</script>

<?php include "INCLUDES/footer.php"; ?>
