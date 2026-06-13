<?php
require_once "includes/db.php";
require_once "includes/auth.php";
requireLogin();

$user_id = $_SESSION["user_id"];
$seller_id = isset($_GET["seller_id"]) ? intval($_GET["seller_id"]) : null;

/* Create or find conversation when coming from product page */
if ($seller_id && $seller_id !== $user_id) {
    $u1 = min($user_id, $seller_id);
    $u2 = max($user_id, $seller_id);

    $check = $conn->prepare("
        SELECT conversation_id
        FROM conversation
        WHERE user1_id = ? AND user2_id = ?
        LIMIT 1
    ");
    $check->bind_param("ii", $u1, $u2);
    $check->execute();
    $existing = $check->get_result();

    if ($existing->num_rows > 0) {
        $conversation_id = $existing->fetch_assoc()["conversation_id"];
    } else {
        $create = $conn->prepare("INSERT INTO conversation (user1_id, user2_id) VALUES (?, ?)");
        $create->bind_param("ii", $u1, $u2);
        $create->execute();
        $conversation_id = $create->insert_id;
    }

    header("Location: messages.php?conversation_id=" . $conversation_id);
    exit;
}

$active_conversation_id = isset($_GET["conversation_id"]) ? intval($_GET["conversation_id"]) : null;

/* Send message */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["conversation_id"], $_POST["content"])) {
    $conversation_id = intval($_POST["conversation_id"]);
    $content = trim($_POST["content"]);

    if (!empty($content)) {
        $stmt = $conn->prepare("
            INSERT INTO message (conversation_id, sender_id, content)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param("iis", $conversation_id, $user_id, $content);
        $stmt->execute();
    }

    header("Location: messages.php?conversation_id=" . $conversation_id);
    exit;
}

/* Fetch conversations */
$conversations_stmt = $conn->prepare("
    SELECT 
        conversation.conversation_id,
        conversation.created_at,
        CASE 
          WHEN conversation.user1_id = ? THEN u2.name
          ELSE u1.name
        END AS other_user,
        CASE 
          WHEN conversation.user1_id = ? THEN u2.is_verified
          ELSE u1.is_verified
        END AS other_verified
    FROM conversation
    JOIN user u1 ON conversation.user1_id = u1.user_id
    JOIN user u2 ON conversation.user2_id = u2.user_id
    WHERE conversation.user1_id = ? OR conversation.user2_id = ?
    ORDER BY conversation.created_at DESC
");
$conversations_stmt->bind_param("iiii", $user_id, $user_id, $user_id, $user_id);
$conversations_stmt->execute();
$conversations = $conversations_stmt->get_result();

if (!$active_conversation_id && $conversations->num_rows > 0) {
    $first = $conversations->fetch_assoc();
    $active_conversation_id = $first["conversation_id"];
    $conversations->data_seek(0);
}

$messages = null;
$active_name = "Select a conversation";
$active_verified = false;

if ($active_conversation_id) {
    $name_stmt = $conn->prepare("
        SELECT 
            CASE 
              WHEN conversation.user1_id = ? THEN u2.name
              ELSE u1.name
            END AS other_user,
            CASE 
              WHEN conversation.user1_id = ? THEN u2.is_verified
              ELSE u1.is_verified
            END AS other_verified
        FROM conversation
        JOIN user u1 ON conversation.user1_id = u1.user_id
        JOIN user u2 ON conversation.user2_id = u2.user_id
        WHERE conversation.conversation_id = ?
    ");
    $name_stmt->bind_param("iii", $user_id, $user_id, $active_conversation_id);
    $name_stmt->execute();
    $active_name_result = $name_stmt->get_result()->fetch_assoc();

    if ($active_name_result) {
        $active_name = $active_name_result["other_user"];
        $active_verified = $active_name_result["other_verified"];
    }

    $msg_stmt = $conn->prepare("
        SELECT *
        FROM message
        WHERE conversation_id = ?
        ORDER BY sent_at ASC
    ");
    $msg_stmt->bind_param("i", $active_conversation_id);
    $msg_stmt->execute();
    $messages = $msg_stmt->get_result();

    $read_stmt = $conn->prepare("
        UPDATE message
        SET is_read = 1
        WHERE conversation_id = ? AND sender_id != ?
    ");
    $read_stmt->bind_param("ii", $active_conversation_id, $user_id);
    $read_stmt->execute();
}
?>

<?php include "includes/header.php"; ?>

<div class="container">

  <section class="hero" style="padding:40px 30px;margin-bottom:22px">
    <div class="hero-content">
      <div class="kicker">💬 Messages</div>
      <h1 style="font-size:clamp(2rem,4vw,3.4rem)">Chat before you trade.</h1>
      <p>
        Ask questions, confirm pickup details, and build trust before completing a local transaction.
      </p>
    </div>
  </section>

  <div class="chat-wrap">

    <div class="chat-list">
      <?php if ($conversations->num_rows > 0): ?>
        <?php while ($conv = $conversations->fetch_assoc()): ?>
          <a 
            class="chat-item <?= $conv["conversation_id"] == $active_conversation_id ? 'active' : '' ?>"
            href="messages.php?conversation_id=<?= $conv["conversation_id"] ?>"
          >
            <div class="avatar">
              <?= strtoupper(substr($conv["other_user"], 0, 1)) ?>
            </div>

            <div style="flex:1;min-width:0">
              <div class="name">
                <?= htmlspecialchars($conv["other_user"]) ?>

                <?php if ($conv["other_verified"]): ?>
                  <span class="badge badge-verified">✓</span>
                <?php endif; ?>
              </div>

              <div class="preview">
                Open conversation
              </div>
            </div>
          </a>
        <?php endwhile; ?>
      <?php else: ?>
        <div style="padding:24px;text-align:center">
          <div style="font-size:2.5rem">💬</div>
          <h3>No conversations yet</h3>
          <p class="text-muted text-sm">
            Message a seller from a product page to start chatting.
          </p>
          <a href="products.php" class="btn btn-primary btn-sm">Browse products</a>
        </div>
      <?php endif; ?>
    </div>

    <div class="chat-main">

      <div class="chat-head">
        <div class="avatar" style="width:40px;height:40px;font-size:.9rem">
          <?= strtoupper(substr($active_name, 0, 1)) ?>
        </div>

        <div>
          <strong><?= htmlspecialchars($active_name) ?></strong>

          <div class="text-sm text-muted">
            <?php if ($active_verified): ?>
              <span class="badge badge-verified">✓ Verified Seller</span>
            <?php else: ?>
              Local marketplace chat
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="chat-msgs">

        <?php if ($messages && $messages->num_rows > 0): ?>
          <?php while ($msg = $messages->fetch_assoc()): ?>
            <div class="msg <?= $msg["sender_id"] == $user_id ? 'out' : 'in' ?>">
              <?= htmlspecialchars($msg["content"]) ?>

              <div style="font-size:.7rem;opacity:.65;margin-top:4px">
                <?= date("H:i", strtotime($msg["sent_at"])) ?>
              </div>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <div class="card" style="padding:24px;text-align:center;box-shadow:none">
            <div style="font-size:2.5rem">👋</div>
            <h3>Start the conversation</h3>
            <p class="text-muted text-sm">
              Ask about product condition, pickup location, delivery options, or payment arrangements.
            </p>
          </div>
        <?php endif; ?>

      </div>

      <?php if ($active_conversation_id): ?>
        <form method="POST" class="chat-input">
          <input type="hidden" name="conversation_id" value="<?= $active_conversation_id ?>">

          <input 
            name="content" 
            placeholder="Type your message..." 
            autocomplete="off" 
            required
          >

          <button class="btn btn-primary">
            Send
          </button>
        </form>
      <?php else: ?>
        <div class="chat-input">
          <input disabled placeholder="Select a conversation to send a message">
          <button class="btn btn-primary" disabled>Send</button>
        </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<?php include "includes/footer.php"; ?>