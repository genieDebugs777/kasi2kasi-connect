<?php
require_once "INCLUDES/db.php";
require_once "INCLUDES/auth.php";
requireLogin();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: products.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$product_id = intval($_POST["product_id"]);
$rating = intval($_POST["rating"]);
$comment = trim($_POST["comment"]);

if ($rating < 1 || $rating > 5 || empty($comment)) {
    header("Location: product.php?id=" . $product_id);
    exit;
}

$stmt = $conn->prepare("
    INSERT INTO review (user_id, product_id, rating, comment)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        rating = VALUES(rating),
        comment = VALUES(comment),
        created_at = CURRENT_TIMESTAMP
");

$stmt->bind_param("iiis", $user_id, $product_id, $rating, $comment);
$stmt->execute();

header("Location: product.php?id=" . $product_id . "&reviewed=1");
exit;
