<?php
require_once "INCLUDES/db.php";

// SQL Updates
$sqls = [
    "ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivery_type ENUM('delivery','pickup') DEFAULT 'delivery' AFTER delivery_phone;",
    
    "ALTER TABLE payment ADD COLUMN IF NOT EXISTS paid_at TIMESTAMP NULL DEFAULT NULL AFTER reference;",
    
    "ALTER TABLE orders MODIFY COLUMN status ENUM('pending','paid','shipped','delivered','cancelled') DEFAULT 'pending';",
    
    "ALTER TABLE orders ADD INDEX IF NOT EXISTS idx_status (status);",
    "ALTER TABLE orders ADD INDEX IF NOT EXISTS idx_created_at (created_at);",
    
    "ALTER TABLE notification MODIFY COLUMN type ENUM('low_stock','out_of_stock','new_order','order_update','verification','review_request','order_overdue') DEFAULT 'order_update';",
    
    "ALTER TABLE notification ADD INDEX IF NOT EXISTS idx_user_read_created (user_id, is_read, created_at);"
];

echo "<h1>Database Updates</h1>";
echo "<pre>";

foreach ($sqls as $sql) {
    echo "Running: $sql\n";
    if ($conn->query($sql)) {
        echo "✅ SUCCESS\n\n";
    } else {
        echo "❌ ERROR: " . $conn->error . "\n\n";
    }
}

echo "</pre>";
echo "<p>Database updates complete!</p>";
?>
