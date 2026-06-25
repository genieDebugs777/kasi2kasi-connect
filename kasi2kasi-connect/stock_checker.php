<?php
/**
 * Stock Checker Script - Enhanced with logging
 * Run this file to check for low stock products and create notifications.
 * Can be run manually or via cron job every hour.
 * 
 * Manual execution: Visit yoursite.com/stock_checker.php
 * Cron job: 0 * * * * php /path/to/stock_checker.php
 */

require_once "INCLUDES/db.php";

// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/stock_checker.log');

$log = [];
$log[] = "=== Stock Checker Run: " . date("Y-m-d H:i:s") . " ===";

// ============================================================
// 1. CHECK LOW STOCK PRODUCTS (≤ 10 units)
// ============================================================
$log[] = "Checking low stock products...";

$low_stock_stmt = $conn->prepare("
    SELECT 
        product.product_id,
        product.title,
        product.quantity,
        user.user_id AS seller_id,
        user.name AS seller_name,
        user.email AS seller_email
    FROM product
    JOIN user ON product.seller_id = user.user_id
    WHERE product.quantity <= 10 
    AND product.quantity > 0
    AND product.status = 'active'
    AND (
        SELECT COUNT(*) 
        FROM notification 
        WHERE notification.product_id = product.product_id 
        AND notification.type = 'low_stock'
        AND DATE(notification.created_at) = CURDATE()
    ) = 0
");

$low_stock_stmt->execute();
$low_stock_products = $low_stock_stmt->get_result();

$notifications_created = 0;
$low_stock_found = $low_stock_products->num_rows;

while ($product = $low_stock_products->fetch_assoc()) {
    // Determine message severity based on stock level
    if ($product['quantity'] <= 3) {
        $message = "🚨 CRITICAL: '{$product['title']}' has only {$product['quantity']} units remaining! Restock immediately.";
        $severity = "critical";
    } else {
        $message = "⚠️ Low Stock Alert: '{$product['title']}' has only {$product['quantity']} units left. Restock soon!";
        $severity = "warning";
    }
    
    $insert_stmt = $conn->prepare("
        INSERT INTO notification (user_id, product_id, type, title, message, is_read)
        VALUES (?, ?, 'low_stock', 'Low Stock Warning', ?, 0)
    ");
    $insert_stmt->bind_param("iis", $product['seller_id'], $product['product_id'], $message);
    
    if ($insert_stmt->execute()) {
        $notifications_created++;
        $log[] = "✓ Low stock notification created for: {$product['title']} (Qty: {$product['quantity']})";
    } else {
        $log[] = "✗ Failed to create notification for: {$product['title']}";
    }
}

$log[] = "Low stock notifications created: $notifications_created";

// ============================================================
// 2. CHECK OUT OF STOCK PRODUCTS (quantity = 0)
// ============================================================
$log[] = "Checking out of stock products...";

$out_of_stock_stmt = $conn->prepare("
    SELECT 
        product.product_id,
        product.title,
        user.user_id AS seller_id
    FROM product
    JOIN user ON product.seller_id = user.user_id
    WHERE product.quantity = 0 
    AND product.status = 'active'
");

$out_of_stock_stmt->execute();
$out_of_stock_products = $out_of_stock_stmt->get_result();
$out_of_stock_count = $out_of_stock_products->num_rows;

while ($product = $out_of_stock_products->fetch_assoc()) {
    // Update product status to 'sold'
    $update_stmt = $conn->prepare("UPDATE product SET status = 'sold' WHERE product_id = ?");
    $update_stmt->bind_param("i", $product['product_id']);
    $update_stmt->execute();
    
    // Create notification for seller
    $insert_stmt = $conn->prepare("
        INSERT INTO notification (user_id, product_id, type, title, message, is_read)
        VALUES (?, ?, 'out_of_stock', 'Product Sold Out', 
                'Your product \"{$product['title']}\" is now out of stock and marked as sold.', 0)
    ");
    $insert_stmt->bind_param("ii", $product['seller_id'], $product['product_id']);
    $insert_stmt->execute();
    
    $log[] = "✓ Marked as sold: {$product['title']}";
}

$log[] = "Out of stock products marked: $out_of_stock_count";

// ============================================================
// 3. CHECK OVERDUE ORDERS (Pending > 7 days)
// ============================================================
$log[] = "Checking overdue orders...";

$overdue_stmt = $conn->prepare("
    SELECT order_id, buyer_id, created_at
    FROM orders
    WHERE status = 'pending' 
    AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
    AND (
        SELECT COUNT(*) 
        FROM notification 
        WHERE notification.type = 'order_overdue' 
        AND notification.user_id = orders.buyer_id
        AND DATE(notification.created_at) = CURDATE()
    ) = 0
");

$overdue_stmt->execute();
$overdue_orders = $overdue_stmt->get_result();

while ($order = $overdue_orders->fetch_assoc()) {
    $notif_stmt = $conn->prepare("
        INSERT INTO notification (user_id, type, title, message, is_read)
        VALUES (?, 'order_overdue', 'Order Action Required', 
                'Your order #{$order['order_id']} is still pending after 7 days. Contact the seller or support.', 0)
    ");
    $notif_stmt->bind_param("i", $order['buyer_id']);
    $notif_stmt->execute();
    $log[] = "✓ Overdue notification for order #{$order['order_id']}";
}

// ============================================================
// 4. CLEAN UP OLD NOTIFICATIONS (Keep last 30 days)
// ============================================================
$log[] = "Cleaning up old notifications...";

$cleanup_stmt = $conn->prepare("
    DELETE FROM notification 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
    AND is_read = 1
");

$cleanup_stmt->execute();
$deleted_count = $cleanup_stmt->affected_rows;
$log[] = "Deleted $deleted_count old read notifications";

// ============================================================
// 5. GENERATE SUMMARY
// ============================================================
$summary = [
    "status" => "success",
    "timestamp" => date("Y-m-d H:i:s"),
    "low_stock_found" => $low_stock_found,
    "low_stock_notifications_created" => $notifications_created,
    "out_of_stock_products_marked" => $out_of_stock_count,
    "overdue_orders_notified" => $overdue_orders->num_rows,
    "old_notifications_deleted" => $deleted_count
];

$log[] = "=== SUMMARY ===";
foreach ($summary as $key => $value) {
    $log[] = "$key: $value";
}
$log[] = "=== Stock Checker Complete ===";

// Write log file
file_put_contents(__DIR__ . '/stock_checker.log', implode("\n", $log) . "\n", FILE_APPEND);

// Return JSON for API/cron
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json');
    echo json_encode($summary);
}

// Also display if accessed via browser
?>
<!DOCTYPE html>
<html>
<head>
    <title>Stock Checker</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #eee; }
        .container { max-width: 800px; margin: 0 auto; }
        .card { background: #2a2a2a; padding: 20px; border-radius: 12px; margin-bottom: 20px; }
        .success { color: #22c55e; }
        .warning { color: #f7c948; }
        .danger { color: #ef4444; }
        h1 { color: #f7c948; }
        pre { background: #333; padding: 15px; border-radius: 12px; overflow-x: auto; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔧 Stock Checker</h1>
    
    <div class="card">
        <h2>📊 Summary</h2>
        <pre><?php print_r($summary); ?></pre>
    </div>
    
    <div class="card">
        <h2>📋 Detailed Log</h2>
        <pre><?php echo implode("\n", array_slice($log, 0, -1)); ?></pre>
    </div>
    
    <div class="card">
        <h2>⏰ Cron Setup</h2>
        <p>Add this to your crontab for automatic hourly checks:</p>
        <pre>0 * * * * php <?= __DIR__ ?>/stock_checker.php</pre>
        <p><a href="cron_setup.php" style="color:#f7c948;">View full cron setup instructions →</a></p>
    </div>
</div>
</body>
</html>
