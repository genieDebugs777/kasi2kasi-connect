<?php
require_once "INCLUDES/db.php";
require_once "INCLUDES/auth.php";
requireLogin();

$user_id = $_SESSION["user_id"];
$order_id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;

if (!$order_id) {
    header("Location: orders.php");
    exit;
}

// Get order details
$order_stmt = $conn->prepare("
    SELECT orders.*, 
           buyer.name AS buyer_name, 
           buyer.email AS buyer_email,
           buyer.phone AS buyer_phone,
           payment.method AS payment_method,
           payment.status AS payment_status,
           payment.reference AS payment_reference,
           payment.paid_at
    FROM orders
    JOIN user AS buyer ON orders.buyer_id = buyer.user_id
    LEFT JOIN payment ON orders.order_id = payment.order_id
    WHERE orders.order_id = ? AND orders.buyer_id = ?
");

$order_stmt->bind_param("ii", $order_id, $user_id);
$order_stmt->execute();
$order = $order_stmt->get_result()->fetch_assoc();

if (!$order) {
    header("Location: orders.php");
    exit;
}

// Get order items
$items_stmt = $conn->prepare("
    SELECT order_item.*, product.title, product.image_url
    FROM order_item
    JOIN product ON order_item.product_id = product.product_id
    WHERE order_item.order_id = ?
");

$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items = $items_stmt->get_result();
?>

<?php include "INCLUDES/header.php"; ?>

<div class="container">

    <section class="hero" style="padding:40px 30px;margin-bottom:22px;background: linear-gradient(135deg, rgba(22,163,74,.15), rgba(22,163,74,.05));">
        <div class="hero-content">
            <div class="kicker">✅ Order Confirmed</div>
            <h1 style="font-size:clamp(2rem,4vw,3.4rem);color:var(--ubuntu);">
                Thank you for your order!
            </h1>
            <p>
                Your order has been placed successfully. Here are the details you need to know.
            </p>
        </div>
    </section>

    <div class="card" style="padding:30px;margin-bottom:24px;border-left:6px solid var(--ubuntu);">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
            <div>
                <h2 style="margin:0;">Order #<?= $order_id ?></h2>
                <p class="text-muted" style="margin:4px 0 0;">
                    Placed on <?= date("d M Y, H:i", strtotime($order["created_at"])) ?>
                </p>
            </div>
            <div>
                <span class="badge <?= $order["status"] === 'paid' ? 'badge-verified' : 'badge-pending' ?>">
                    <?= strtoupper(htmlspecialchars($order["status"])) ?>
                </span>
                <?php if ($order["payment_status"] === 'completed'): ?>
                    <span class="badge badge-verified">✓ Payment Complete</span>
                <?php else: ?>
                    <span class="badge badge-pending">⏳ Payment Pending</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="grid grid-2" style="margin-bottom:24px;gap:24px;">
        <!-- Delivery Information -->
        <div class="card" style="padding:24px;">
            <h3 style="margin-top:0;">📍 Delivery Information</h3>
            <div style="line-height:1.8;">
                <p><strong>Address:</strong> <?= htmlspecialchars($order["delivery_address"]) ?></p>
                <p><strong>Phone:</strong> <?= htmlspecialchars($order["delivery_phone"]) ?></p>
                <p><strong>Delivery Type:</strong> 
                    <?= strpos($order["delivery_address"], 'Pickup') !== false ? '📦 Pickup' : '🚚 Delivery' ?>
                </p>
            </div>
        </div>

        <!-- Payment Information -->
        <div class="card" style="padding:24px;">
            <h3 style="margin-top:0;">💳 Payment Information</h3>
            <div style="line-height:1.8;">
                <p><strong>Method:</strong> <?= strtoupper(htmlspecialchars($order["payment_method"])) ?></p>
                <p><strong>Status:</strong> 
                    <?php if ($order["payment_status"] === 'completed'): ?>
                        <span style="color:var(--ubuntu);font-weight:bold;">✓ Completed</span>
                    <?php else: ?>
                        <span style="color:var(--sunset);font-weight:bold;">⏳ Pending</span>
                    <?php endif; ?>
                </p>
                <p><strong>Reference:</strong> <code><?= htmlspecialchars($order["payment_reference"]) ?></code></p>
                <?php if ($order["paid_at"]): ?>
                    <p><strong>Paid At:</strong> <?= date("d M Y, H:i", strtotime($order["paid_at"])) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Order Items -->
    <div class="card" style="padding:24px;margin-bottom:24px;">
        <h3 style="margin-top:0;">📦 Order Items</h3>
        <?php 
        $order_total = 0;
        while ($item = $items->fetch_assoc()): 
            $line_total = $item["unit_price"] * $item["quantity"];
            $order_total += $line_total;
        ?>
            <div class="seller-card" style="margin-bottom:12px;box-shadow:none;border:1px solid var(--border);">
                <div style="width:60px;height:60px;border-radius:12px;background-image:url('<?= htmlspecialchars($item["image_url"]) ?>');background-size:cover;background-position:center;flex-shrink:0;"></div>
                <div style="flex:1;">
                    <strong><?= htmlspecialchars($item["title"]) ?></strong>
                    <div class="text-muted text-sm">
                        Qty: <?= $item["quantity"] ?> × R <?= number_format($item["unit_price"], 2) ?>
                    </div>
                </div>
                <strong>R <?= number_format($line_total, 2) ?></strong>
            </div>
        <?php endwhile; ?>
        
        <div style="border-top:2px solid var(--border);padding-top:16px;margin-top:8px;display:flex;justify-content:space-between;font-size:1.2rem;font-weight:bold;">
            <span>Order Total</span>
            <span>R <?= number_format($order_total, 2) ?></span>
        </div>
    </div>

    <!-- What Happens Next -->
    <div class="card" style="padding:24px;margin-bottom:24px;background:var(--cream);">
        <h3 style="margin-top:0;">📋 What Happens Next</h3>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-top:12px;">
            <div>
                <div style="font-size:2rem;">1️⃣</div>
                <strong>Seller Prepares</strong>
                <p class="text-sm text-muted">The seller will confirm your order and prepare the items.</p>
            </div>
            <div>
                <div style="font-size:2rem;">2️⃣</div>
                <strong>Payment Confirmation</strong>
                <p class="text-sm text-muted">
                    <?php if ($order["payment_method"] === 'cash'): ?>
                        ✓ Your cash payment is confirmed. Ready for delivery/pickup.
                    <?php else: ?>
                        Complete your payment using the reference above.
                    <?php endif; ?>
                </p>
            </div>
            <div>
                <div style="font-size:2rem;">3️⃣</div>
                <strong>Shipping</strong>
                <p class="text-sm text-muted">Once paid, the seller will ship or prepare for pickup.</p>
            </div>
            <div>
                <div style="font-size:2rem;">4️⃣</div>
                <strong>Delivery</strong>
                <p class="text-sm text-muted">Receive your items and leave a review!</p>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:24px;">
        <a href="orders.php" class="btn btn-primary">📋 View My Orders</a>
        <a href="products.php" class="btn btn-outline">🛍 Continue Shopping</a>
        <a href="messages.php" class="btn btn-outline">💬 Contact Seller</a>
        <?php if ($order["payment_method"] !== 'cash' && $order["payment_status"] !== 'completed'): ?>
            <a href="#" class="btn btn-accent" onclick="alert('Payment instructions will be sent to your email. Use reference: <?= htmlspecialchars($order["payment_reference"]) ?>')">
                💳 Payment Instructions
            </a>
        <?php endif; ?>
    </div>

</div>

<?php include "INCLUDES/footer.php"; ?>
