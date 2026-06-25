<?php
/**
 * AJAX endpoint for getting unread notification count
 * Used by header.php to update the notification badge in real-time
 */

require_once "INCLUDES/db.php";
require_once "INCLUDES/auth.php";

// Return JSON response
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['count' => 0]);
    exit;
}

$user_id = $_SESSION["user_id"];

$stmt = $conn->prepare("SELECT COUNT(*) AS c FROM notification WHERE user_id = ? AND is_read = 0");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$count = $result->fetch_assoc()["c"];

echo json_encode(['count' => $count]);
exit;
?>
