<?php
/**
 * Stock Checker Script
 * Run this file to check for low stock products and create notifications.
 * Can be run manually or via cron job every hour.
 * 
 * Manual execution: Visit yoursite.com/stock_checker.php
 * Cron job: 0 * * * * php /path/to/stock_checker.php
 */

require_once "INCLUDES/db.php";

// Get all products with low stock (10 or less) and not yet notified today
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

while ($product = $low_stock_products->fetch_assoc()) {
    $message = "⚠️ Low Stock Alert: '{$product['title']}' has only {$product['quantity']} units left. Restock soon!";
    
    if ($product['quantity'] <= 3) {
        $message = "🚨 CRITICAL: '{$product['title']}' has only {$product['quantity']} units remaining! Restock immediately.";
    }
    
    $insert_stmt = $conn->prepare("
        INSERT INTO notification (user_id, product_id, type, title, message, is_read)
        VALUES (?, ?, 'low_stock', 'Low Stock Warning', ?, 0)
    ");
    $insert_stmt->bind_param("iis", $product['seller_id'], $product['product_id'], $message);
    
    if ($insert_stmt->execute()) {
        $notifications_created++;
    }
}

// Get out of stock products and update status + create notification
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

while ($product = $out_of_stock_products->fetch_assoc()) {
    // Update product status to 'sold'
    $update_stmt = $conn->prepare("UPDATE product SET status = 'sold' WHERE product_id = ?");
    $update_stmt->bind_param("i", $product['product_id']);
    $update_stmt->execute();
    
    // Create notification for seller
    $insert_stmt = $conn->prepare("
        INSERT INTO notification (user_id, product_id, type, title, message, is_read)
        VALUES (?, ?, 'out_of_stock', 'Product Sold Out', 'Your product \"{$product['title']}\" is now out of stock and marked as sold.', 0)
    ");
    $insert_stmt->bind_param("ii", $product['seller_id'], $product['product_id']);
    $insert_stmt->execute();
}

// Return summary (for cron logging)
echo json_encode([
    "status" => "success",
    "low_stock_notifications_created" => $notifications_created,
    "out_of_stock_products_marked" => $out_of_stock_products->num_rows,
    "timestamp" => date("Y-m-d H:i:s")
]);
?>
