<?php
require_once "includes/db.php";
require_once "includes/auth.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        $stmt = $conn->prepare("
            SELECT user.user_id, user.name, user.email, user.password_hash, user.status, role.role_name
            FROM user
            JOIN role ON user.role_id = role.role_id
            WHERE user.email = ?
            LIMIT 1
        ");

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if ($user["status"] === "suspended") {
                $error = "Your account has been suspended. Please contact support.";
            } elseif (password_verify($password, $user["password_hash"])) {
                $_SESSION["user_id"] = $user["user_id"];
                $_SESSION["name"] = $user["name"];
                $_SESSION["email"] = $user["email"];
                $_SESSION["role_name"] = $user["role_name"];

                header("Location: index.php");
                exit;
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>

<?php include "includes/header.php"; ?>

<div class="container">
  <div class="grid grid-2" style="align-items:center;min-height:calc(100vh - 190px);gap:28px">

    <section class="hero" style="margin:24px 0;padding:44px 32px">
      <div class="hero-content">
        <div class="kicker">🔐 Welcome back</div>
        <h1 style="font-size:clamp(2.1rem,4vw,3.8rem)">
          Sign in and continue your kasi trade.
        </h1>
        <p>
          Access your cart, listings, orders, messages and seller verification tools from one secure account.
        </p>
        <div class="hero-actions">
          <a href="products.php" class="btn btn-outline">Browse marketplace</a>
        </div>
      </div>
    </section>

    <form method="POST" class="form-card" style="margin:0;max-width:none">
      <span class="badge badge-trusted">Kasi2Kasi Account</span>

      <h1 style="margin-top:12px">Sign in</h1>
      <p class="sub">Enter your details to access your marketplace profile.</p>

      <?php if (isset($_GET["registered"])): ?>
        <div class="card auto-hide" style="padding:14px;background:rgba(22,163,74,.08);border-left:4px solid var(--ubuntu);margin-bottom:16px">
          Account created successfully. Please sign in.
        </div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="card" style="padding:14px;color:var(--danger);margin-bottom:16px">
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <div class="field">
        <label>Email Address</label>
        <input name="email" type="email" required placeholder="you@example.com">
      </div>

      <div class="field">
        <label>Password</label>
        <input name="password" type="password" required placeholder="Enter your password">
      </div>

      <button class="btn btn-primary btn-block">Sign In</button>

      <div class="form-foot">
        New to Kasi2Kasi? <a href="register.php"><strong>Create an account</strong></a>
      </div>
    </form>

  </div>
</div>

<?php include "includes/footer.php"; ?>