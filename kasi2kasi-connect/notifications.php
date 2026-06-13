<?php
require_once "INCLUDES/db.php";
require_once "INCLUDES/auth.php";
requireLogin();

$user_id = $_SESSION["user_id"];

// Mark single notification as read
if (isset($_GET["mark_read"])) {
    $notification_id = intval($_GET["mark_read"]);
    $stmt = $conn->prepare("UPDATE notification SET is_read = 1 WHERE notification_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notification_id, $user_id);
    $stmt->execute();
    header("Location: notifications.php");
    exit;
}

// Mark all as read
if (isset($_GET["mark_all_read"])) {
    $stmt = $conn->prepare("UPDATE notification SET is_read = 1 WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    header("Location: notifications.php");
    exit;
}

// Delete notification
if (isset($_GET["delete"])) {
    $notification_id = intval($_GET["delete"]);
    $stmt = $conn->prepare("DELETE FROM notification WHERE notification_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notification_id, $user_id);
    $stmt->execute();
    header("Location: notifications.php");
    exit;
}

// Get filter
$filter = $_GET["filter"] ?? "all";
$unread_only = ($filter === "unread");

// Fetch notifications
if ($unread_only) {
    $notif_stmt = $conn->prepare("
        SELECT n.*, p.title AS product_title
        FROM notification n
        LEFT JOIN product p ON n.product_id = p.product_id
        WHERE n.user_id = ? AND n.is_read = 0
        ORDER BY n.created_at DESC
    ");
    $notif_stmt->bind_param("i", $user_id);
} else {
    $notif_stmt = $conn->prepare("
        SELECT n.*, p.title AS product_title
        FROM notification n
        LEFT JOIN product p ON n.product_id = p.product_id
        WHERE n.user_id = ?
        ORDER BY n.created_at DESC
        LIMIT 50
    ");
    $notif_stmt->bind_param("i", $user_id);
}

$notif_stmt->execute();
$notifications = $notif_stmt->get_result();

// Get counts
$unread_count_stmt = $conn->prepare("SELECT COUNT(*) AS c FROM notification WHERE user_id = ? AND is_read = 0");
$unread_count_stmt->bind_param("i", $user_id);
$unread_count_stmt->execute();
$unread_count = $unread_count_stmt->get_result()->fetch_assoc()["c"];

$total_count_stmt = $conn->prepare("SELECT COUNT(*) AS c FROM notification WHERE user_id = ?");
$total_count_stmt->bind_param("i", $user_id);
$total_count_stmt->execute();
$total_count = $total_count_stmt->get_result()->fetch_assoc()["c"];
?>

<?php include "includes/header.php"; ?>

<div class="container">

  <section class="hero" style="padding:40px 30px;margin-bottom:22px">
    <div class="hero-content">
      <div class="kicker">🔔 Notifications</div>
      <h1 style="font-size:clamp(2rem,4vw,3.4rem)">Stay updated on your sales.</h1>
      <p>
        Get alerts for low stock, new orders, and important updates about your products.
      </p>
    </div>
  </section>

  <!-- Notification Stats -->
  <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr));margin-bottom:24px">
    <div class="stat">
      <div class="label">Unread</div>
      <div class="value" style="color:var(--primary)"><?= $unread_count ?></div>
      <div class="delta">Awaiting attention</div>
    </div>
    <div class="stat">
      <div class="label">Total</div>
      <div class="value"><?= $total_count ?></div>
      <div class="delta">All notifications</div>
    </div>
  </div>

  <!-- Filter Tabs -->
  <div class="pills" style="margin-bottom:24px">
    <a class="pill <?= $filter === 'all' ? 'active' : '' ?>" href="notifications.php?filter=all">All</a>
    <a class="pill <?= $filter === 'unread' ? 'active' : '' ?>" href="notifications.php?filter=unread">Unread</a>
  </div>

  <!-- Bulk Actions -->
  <?php if ($unread_count > 0): ?>
    <div class="card" style="padding:16px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
      <span>You have <strong><?= $unread_count ?></strong> unread notification<?= $unread_count != 1 ? 's' : '' ?>.</span>
      <a href="notifications.php?mark_all_read=1" class="btn btn-primary btn-sm" onclick="return confirm('Mark all notifications as read?')">Mark All as Read</a>
    </div>
  <?php endif; ?>

  <!-- Notifications List -->
  <?php if ($notifications->num_rows > 0): ?>

    <?php while ($notif = $notifications->fetch_assoc()): ?>
      <?php
        $notif_class = $notif["is_read"] ? "" : "unread";
        $icon = "🔔";
        $bg_color = $notif["is_read"] ? "var(--surface)" : "rgba(91,45,245,.04)";
        
        switch($notif["type"]) {
            case "low_stock":
                $icon = "⚠️";
                break;
            case "out_of_stock":
                $icon = "❌";
                break;
            case "new_order":
                $icon = "🛒";
                break;
            case "order_update":
                $icon = "📦";
                break;
            case "verification":
                $icon = "✅";
                break;
        }
      ?>
      
      <div class="card <?= $notif_class ?>" style="margin-bottom:12px;padding:16px;border-left:4px solid <?= $notif["is_read"] ? 'var(--border)' : 'var(--primary)' ?>;background:<?= $bg_color ?>">
        <div style="display:flex;gap:14px;align-items:flex-start">
          <div style="font-size:1.8rem"><?= $icon ?></div>
          
          <div style="flex:1">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px">
              <div>
                <strong style="font-size:1rem"><?= htmlspecialchars($notif["title"]) ?></strong>
                <?php if ($notif["product_title"]): ?>
                  <span class="badge badge-verified" style="margin-left:8px"><?= htmlspecialchars($notif["product_title"]) ?></span>
                <?php endif; ?>
              </div>
              <div class="text-sm text-muted"><?= date("d M Y, H:i", strtotime($notif["created_at"])) ?></div>
            </div>
            
            <p class="text-muted" style="margin:8px 0 0;line-height:1.5">
              <?= htmlspecialchars($notif["message"]) ?>
            </p>
            
            <div style="display:flex;gap:12px;margin-top:12px">
              <?php if (!$notif["is_read"]): ?>
                <a href="notifications.php?mark_read=<?= $notif["notification_id"] ?>" class="text-sm" style="color:var(--primary)">Mark as read</a>
              <?php endif; ?>
              
              <?php if ($notif["type"] === "low_stock" && $notif["product_id"]): ?>
                <a href="profile.php#tab-listings" class="text-sm" style="color:var(--ubuntu)">Update stock →</a>
              <?php endif; ?>
              
              <?php if ($notif["type"] === "new_order" || $notif["type"] === "order_update"): ?>
                <a href="seller_orders.php" class="text-sm" style="color:var(--primary)">View orders →</a>
              <?php endif; ?>
              
              <a href="notifications.php?delete=<?= $notif["notification_id"] ?>" class="text-sm" style="color:var(--danger)" onclick="return confirm('Delete this notification?')">Delete</a>
            </div>
          </div>
        </div>
      </div>
    <?php endwhile; ?>

  <?php else: ?>
    <div class="card" style="padding:60px;text-align:center">
      <div style="font-size:4rem">🔔</div>
      <h2 style="margin:16px 0 8px">No notifications</h2>
      <p class="text-muted">
        You're all caught up! Notifications will appear here when you get new orders or low stock alerts.
      </p>
      <a href="seller_orders.php" class="btn btn-primary">Go to My Sales</a>
    </div>
  <?php endif; ?>

</div>

<style>
.unread {
    border-left-color: var(--primary) !important;
}
</style>

<?php include "INCLUDES/footer.php"; ?>
