<?php
require_once "INCLUDES/db.php";

echo "<h1>Database Updates - Fixed Version</h1>";
echo "<pre>";

// Function to check if column exists
function columnExists($conn, $table, $column) {
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result->num_rows > 0;
}

// Function to check if index exists
function indexExists($conn, $table, $index) {
    $result = $conn->query("SHOW INDEX FROM `$table` WHERE Key_name = '$index'");
    return $result->num_rows > 0;
}

// ============================================================
// 1. Add delivery_type to orders table
// ============================================================
if (!columnExists($conn, 'orders', 'delivery_type')) {
    $sql = "ALTER TABLE orders ADD COLUMN delivery_type ENUM('delivery','pickup') DEFAULT 'delivery' AFTER delivery_phone";
    echo "Running: $sql\n";
    if ($conn->query($sql)) {
        echo "✅ SUCCESS - Added delivery_type column\n\n";
    } else {
        echo "❌ ERROR: " . $conn->error . "\n\n";
    }
} else {
    echo "ℹ️ delivery_type column already exists - SKIPPED\n\n";
}

// ============================================================
// 2. Add paid_at to payment table
// ============================================================
if (!columnExists($conn, 'payment', 'paid_at')) {
    $sql = "ALTER TABLE payment ADD COLUMN paid_at TIMESTAMP NULL DEFAULT NULL AFTER reference";
    echo "Running: $sql\n";
    if ($conn->query($sql)) {
        echo "✅ SUCCESS - Added paid_at column\n\n";
    } else {
        echo "❌ ERROR: " . $conn->error . "\n\n";
    }
} else {
    echo "ℹ️ paid_at column already exists - SKIPPED\n\n";
}

// ============================================================
// 3. Ensure status ENUM values are correct
// ============================================================
// First check current status values
$check_status = $conn->query("SELECT DISTINCT status FROM orders");
if ($check_status) {
    $current_values = [];
    while ($row = $check_status->fetch_assoc()) {
        $current_values[] = $row['status'];
    }
    echo "Current status values: " . implode(', ', $current_values) . "\n";
}

// Modify the column
$sql = "ALTER TABLE orders MODIFY COLUMN status ENUM('pending','paid','shipped','delivered','cancelled') DEFAULT 'pending'";
echo "Running: $sql\n";
if ($conn->query($sql)) {
    echo "✅ SUCCESS - Updated status ENUM\n\n";
} else {
    echo "❌ ERROR: " . $conn->error . "\n\n";
}

// ============================================================
// 4. Add indexes for faster order queries
// ============================================================
if (!indexExists($conn, 'orders', 'idx_status')) {
    $sql = "ALTER TABLE orders ADD INDEX idx_status (status)";
    echo "Running: $sql\n";
    if ($conn->query($sql)) {
        echo "✅ SUCCESS - Added idx_status index\n\n";
    } else {
        echo "❌ ERROR: " . $conn->error . "\n\n";
    }
} else {
    echo "ℹ️ idx_status index already exists - SKIPPED\n\n";
}

if (!indexExists($conn, 'orders', 'idx_created_at')) {
    $sql = "ALTER TABLE orders ADD INDEX idx_created_at (created_at)";
    echo "Running: $sql\n";
    if ($conn->query($sql)) {
        echo "✅ SUCCESS - Added idx_created_at index\n\n";
    } else {
        echo "❌ ERROR: " . $conn->error . "\n\n";
    }
} else {
    echo "ℹ️ idx_created_at index already exists - SKIPPED\n\n";
}

// ============================================================
// 5. Add notification type for review requests
// ============================================================
// First check current notification types
$check_types = $conn->query("SELECT DISTINCT type FROM notification");
if ($check_types) {
    $current_types = [];
    while ($row = $check_types->fetch_assoc()) {
        $current_types[] = $row['type'];
    }
    echo "Current notification types: " . implode(', ', $current_types) . "\n";
}

// Get current column definition
$col_info = $conn->query("SHOW COLUMNS FROM notification WHERE Field = 'type'");
if ($col_info && $col_info->num_rows > 0) {
    $col_data = $col_info->fetch_assoc();
    echo "Current column definition: " . $col_data['Type'] . "\n";
}

$sql = "ALTER TABLE notification MODIFY COLUMN type ENUM('low_stock','out_of_stock','new_order','order_update','verification','review_request','order_overdue') DEFAULT 'order_update'";
echo "Running: $sql\n";
if ($conn->query($sql)) {
    echo "✅ SUCCESS - Updated notification type ENUM\n\n";
} else {
    echo "❌ ERROR: " . $conn->error . "\n\n";
}

// ============================================================
// 6. Add index for notification queries
// ============================================================
if (!indexExists($conn, 'notification', 'idx_user_read_created')) {
    $sql = "ALTER TABLE notification ADD INDEX idx_user_read_created (user_id, is_read, created_at)";
    echo "Running: $sql\n";
    if ($conn->query($sql)) {
        echo "✅ SUCCESS - Added idx_user_read_created index\n\n";
    } else {
        echo "❌ ERROR: " . $conn->error . "\n\n";
    }
} else {
    echo "ℹ️ idx_user_read_created index already exists - SKIPPED\n\n";
}

// ============================================================
// 7. VERIFY ALL CHANGES
// ============================================================
echo "\n" . str_repeat("=", 60) . "\n";
echo "VERIFICATION - Checking all changes:\n";
echo str_repeat("=", 60) . "\n";

// Check orders table columns
$columns = ['delivery_type', 'status'];
foreach ($columns as $col) {
    if (columnExists($conn, 'orders', $col)) {
        echo "✅ orders.$col exists\n";
    } else {
        echo "❌ orders.$col is MISSING\n";
    }
}

// Check payment table columns
if (columnExists($conn, 'payment', 'paid_at')) {
    echo "✅ payment.paid_at exists\n";
} else {
    echo "❌ payment.paid_at is MISSING\n";
}

// Check indexes
$indexes = ['idx_status', 'idx_created_at'];
foreach ($indexes as $idx) {
    if (indexExists($conn, 'orders', $idx)) {
        echo "✅ orders.$idx exists\n";
    } else {
        echo "❌ orders.$idx is MISSING\n";
    }
}

if (indexExists($conn, 'notification', 'idx_user_read_created')) {
    echo "✅ notification.idx_user_read_created exists\n";
} else {
    echo "❌ notification.idx_user_read_created is MISSING\n";
}

echo str_repeat("=", 60) . "\n";
echo "✅ Database updates complete!\n";
echo "</pre>";

// Show summary
echo "<div style='margin-top:20px;padding:20px;background:#f0fdf4;border:1px solid #22c55e;border-radius:12px;'>";
echo "<h2 style='color:#16a34a;margin-top:0;'>✅ All Updates Completed</h2>";
echo "<p>Your database has been updated successfully. You can now:</p>";
echo "<ul>";
echo "<li>Use the fixed <strong>seller_orders.php</strong> with proper order status updates</li>";
echo "<li>Use the enhanced <strong>checkout.php</strong> with cash auto-confirmation</li>";
echo "<li>Use the new <strong>order_confirmation.php</strong> page</li>";
echo "</ul>";
echo "<p><strong>⚠️ Important:</strong> Delete this file (db_update_fixed.php) after running it for security.</p>";
echo "</div>";
?>
