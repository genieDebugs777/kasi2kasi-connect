<?php
require_once "../includes/db.php";
require_once "../includes/admin_auth.php";

requireRole(["Super Admin", "Verification Officer"]);

/* Approve request */
if (isset($_GET["approve"])) {
    $request_id = intval($_GET["approve"]);
    $admin_id = $_SESSION["user_id"];

    $stmt = $conn->prepare("
        UPDATE verification_request
        SET status = 'approved',
            reviewed_by = ?,
            reviewed_at = NOW(),
            notes = 'Seller verification approved.'
        WHERE request_id = ?
    ");
    $stmt->bind_param("ii", $admin_id, $request_id);
    $stmt->execute();

    $user_stmt = $conn->prepare("
        UPDATE user
        SET is_verified = 1
        WHERE user_id = (
            SELECT user_id
            FROM verification_request
            WHERE request_id = ?
        )
    ");
    $user_stmt->bind_param("i", $request_id);
    $user_stmt->execute();

    header("Location: verify.php?approved=1");
    exit;
}

/* Reject request */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["reject_request"])) {

    $request_id = intval($_POST["request_id"]);
    $admin_id = $_SESSION["user_id"];
    $notes = trim($_POST["notes"]);

    if (empty($notes)) {
        $notes = "Seller verification rejected.";
    }

    $stmt = $conn->prepare("
        UPDATE verification_request
        SET status = 'rejected',
            reviewed_by = ?,
            reviewed_at = NOW(),
            notes = ?
        WHERE request_id = ?
    ");

    $stmt->bind_param("isi", $admin_id, $notes, $request_id);
    $stmt->execute();

    header("Location: verify.php?rejected=1");
    exit;
}

/* Pending */
$requests = $conn->query("
    SELECT 
        verification_request.*,
        user.name,
        user.email,
        user.phone,
        user.created_at AS joined_at
    FROM verification_request
    JOIN user ON verification_request.user_id = user.user_id
    WHERE verification_request.status = 'pending'
    ORDER BY verification_request.created_at ASC
");

/* Reviewed */
$reviewed = $conn->query("
    SELECT 
        verification_request.*,
        user.name,
        reviewer.name AS reviewer_name
    FROM verification_request
    JOIN user ON verification_request.user_id = user.user_id
    LEFT JOIN user reviewer 
        ON verification_request.reviewed_by = reviewer.user_id
    WHERE verification_request.status IN ('approved','rejected')
    ORDER BY verification_request.reviewed_at DESC
    LIMIT 8
");

$pending_count = $requests->num_rows;

$approved_count = $conn->query("
    SELECT COUNT(*) AS c
    FROM verification_request
    WHERE status='approved'
")->fetch_assoc()["c"];

$rejected_count = $conn->query("
    SELECT COUNT(*) AS c
    FROM verification_request
    WHERE status='rejected'
")->fetch_assoc()["c"];
?>

<?php include "../includes/header.php"; ?>

<div class="admin-control">

  <aside class="admin-sidebar">

    <div class="admin-side-brand">
      <span class="brand-mark"><span>K2K</span></span>

      <div>
        <strong>Kasi2Kasi</strong>
        <small>Admin Console</small>
      </div>
    </div>

    <nav class="admin-side-nav">
      <a href="index.php">📊 Dashboard</a>

      <?php if (currentUserRole() === "Super Admin"): ?>
        <a href="users.php">👥 Users</a>
        <a href="roles.php">🔐 Roles</a>
      <?php endif; ?>

      <a class="active" href="verify.php">✅ Verifications</a>

      <?php if (in_array(currentUserRole(), ["Super Admin", "Content Moderator"])): ?>
        <a href="products.php">🛍 Products</a>
      <?php endif; ?>

      <a href="../index.php">↩ Back to Site</a>
    </nav>

  </aside>

  <main class="admin-main-panel">

    <section class="admin-hero-pro">
      <div>
        <span class="admin-chip">SELLER TRUST SYSTEM</span>

        <h1>Verification Review Centre</h1>

        <p>
          Approve or reject identity verification submissions to maintain marketplace trust and buyer safety.
        </p>
      </div>

      <div class="admin-orb">
        <span>VF</span>
      </div>
    </section>

    <?php if (isset($_GET["approved"])): ?>
      <div class="admin-alert success auto-hide">
        Seller verification approved successfully.
      </div>
    <?php endif; ?>

    <?php if (isset($_GET["rejected"])): ?>
      <div class="admin-alert danger auto-hide">
        Seller verification rejected successfully.
      </div>
    <?php endif; ?>

    <section class="admin-metrics">

      <div class="admin-metric-card warning">
        <span class="metric-icon">⏳</span>
        <small>Pending Requests</small>
        <strong><?= $pending_count ?></strong>
        <p>Awaiting review</p>
      </div>

      <div class="admin-metric-card">
        <span class="metric-icon">✅</span>
        <small>Approved</small>
        <strong><?= $approved_count ?></strong>
        <p>Trusted sellers</p>
      </div>

      <div class="admin-metric-card danger">
        <span class="metric-icon">❌</span>
        <small>Rejected</small>
        <strong><?= $rejected_count ?></strong>
        <p>Failed reviews</p>
      </div>

    </section>

    <section class="admin-glass-panel">

      <div class="admin-panel-head">
        <div>
          <h2>Pending Verification Requests</h2>
          <p>Review identity submissions before granting seller verification.</p>
        </div>
      </div>

      <?php if ($requests->num_rows > 0): ?>

        <div class="admin-verify-grid">

          <?php while ($r = $requests->fetch_assoc()): ?>

            <div class="admin-verify-card">

              <div class="admin-verify-top">

                <div class="admin-user-cell">

                  <div class="admin-user-avatar">
                    <?= strtoupper(substr($r["name"], 0, 1)) ?>
                  </div>

                  <div>
                    <strong><?= htmlspecialchars($r["name"]) ?></strong>
                    <span><?= htmlspecialchars($r["email"]) ?></span>
                  </div>

                </div>

                <span class="admin-status pending">
                  pending
                </span>

              </div>

              <div class="admin-verify-info">

                <div class="admin-verify-box">
                  <small>ID Number</small>
                  <strong><?= htmlspecialchars($r["id_number"]) ?></strong>
                </div>

                <div class="admin-verify-box">
                  <small>Joined</small>
                  <strong><?= date("d M Y", strtotime($r["joined_at"])) ?></strong>
                </div>

                <div class="admin-verify-box">
                  <small>Submitted</small>
                  <strong><?= date("d M Y", strtotime($r["created_at"])) ?></strong>
                </div>

              </div>

              <div class="admin-verify-docs">

                <?php if (!empty($r["id_document_url"])): ?>
                  <a
                    href="<?= htmlspecialchars($r["id_document_url"]) ?>"
                    target="_blank"
                    class="admin-doc-link"
                  >
                    📄 View ID Document
                  </a>
                <?php endif; ?>

                <?php if (!empty($r["selfie_url"])): ?>
                  <a
                    href="<?= htmlspecialchars($r["selfie_url"]) ?>"
                    target="_blank"
                    class="admin-doc-link"
                  >
                    📸 View Selfie
                  </a>
                <?php endif; ?>

              </div>

              <div class="admin-verify-actions">

                <a
                  href="verify.php?approve=<?= $r["request_id"] ?>"
                  class="admin-approve-btn"
                  onclick="return confirm('Approve this seller verification?')"
                >
                  Approve
                </a>

                <form method="POST" class="admin-reject-form">

                  <input type="hidden" name="reject_request" value="1">

                  <input
                    type="hidden"
                    name="request_id"
                    value="<?= $r["request_id"] ?>"
                  >

                  <input
                    type="text"
                    name="notes"
                    placeholder="Reason for rejection"
                  >

                  <button
                    class="admin-reject-btn"
                    onclick="return confirm('Reject this verification request?')"
                  >
                    Reject
                  </button>

                </form>

              </div>

            </div>

          <?php endwhile; ?>

        </div>

      <?php else: ?>

        <div class="admin-empty">
          <div>✅</div>
          <h2>No pending requests</h2>
          <p>All seller verification requests have been reviewed.</p>
        </div>

      <?php endif; ?>

    </section>

    <section class="admin-glass-panel">

      <div class="admin-panel-head">
        <div>
          <h2>Recent Verification Decisions</h2>
          <p>Recently approved and rejected verification reviews.</p>
        </div>
      </div>

      <?php if ($reviewed->num_rows > 0): ?>

        <div class="admin-table-wrap">

          <table>

            <thead>
              <tr>
                <th>Seller</th>
                <th>Status</th>
                <th>Reviewed By</th>
                <th>Date</th>
                <th>Notes</th>
              </tr>
            </thead>

            <tbody>

              <?php while ($row = $reviewed->fetch_assoc()): ?>

                <tr>

                  <td>
                    <strong><?= htmlspecialchars($row["name"]) ?></strong>
                  </td>

                  <td>
                    <span class="admin-status <?= $row["status"] === "approved" ? "approved" : "suspended" ?>">
                      <?= htmlspecialchars($row["status"]) ?>
                    </span>
                  </td>

                  <td>
                    <?= htmlspecialchars($row["reviewer_name"] ?? "Unknown") ?>
                  </td>

                  <td>
                    <?= $row["reviewed_at"] ? date("d M Y", strtotime($row["reviewed_at"])) : "—" ?>
                  </td>

                  <td>
                    <?= htmlspecialchars($row["notes"] ?? "—") ?>
                  </td>

                </tr>

              <?php endwhile; ?>

            </tbody>

          </table>

        </div>

      <?php else: ?>

        <div class="admin-empty">
          <div>📋</div>
          <h2>No verification history</h2>
          <p>No verification decisions have been recorded yet.</p>
        </div>

      <?php endif; ?>

    </section>

  </main>
</div>

<?php include "../includes/footer.php"; ?>