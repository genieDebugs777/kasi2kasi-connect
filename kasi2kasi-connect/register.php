<?php
require_once "includes/db.php";
require_once "includes/auth.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    $password = $_POST["password"];

    if (empty($name) || empty($email) || empty($password)) {
        $error = "Please fill in all required fields.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        $check = $conn->prepare("SELECT user_id FROM user WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $error = "Email already exists.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $role_id = 4;

            $stmt = $conn->prepare("
                INSERT INTO user (name, email, password_hash, phone, role_id)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("ssssi", $name, $email, $hash, $phone, $role_id);

            if ($stmt->execute()) {
                header("Location: login.php?registered=1");
                exit;
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>

<?php include "includes/header.php"; ?>

<div class="container">
  <div class="grid grid-2" style="align-items:center;min-height:calc(100vh - 190px);gap:28px">

    <section class="hero" style="margin:24px 0;padding:44px 32px">
      <div class="hero-content">
        <div class="kicker">🇿🇦 Join the marketplace</div>
        <h1 style="font-size:clamp(2.1rem,4vw,3.8rem)">
          Create your kasi storefront.
        </h1>
        <p>
          Buy, sell, message local traders, build trust through reviews, and grow your side-hustle online.
        </p>

        <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-top:18px">
          <div class="card" style="padding:14px;background:rgba(255,255,255,.12);border-color:rgba(255,255,255,.25);box-shadow:none;color:#fff">
            <strong>✓ Sell locally</strong>
          </div>
          <div class="card" style="padding:14px;background:rgba(255,255,255,.12);border-color:rgba(255,255,255,.25);box-shadow:none;color:#fff">
            <strong>✓ Message buyers</strong>
          </div>
          <div class="card" style="padding:14px;background:rgba(255,255,255,.12);border-color:rgba(255,255,255,.25);box-shadow:none;color:#fff">
            <strong>✓ Get verified</strong>
          </div>
        </div>
      </div>
    </section>

    <form method="POST" class="form-card" style="margin:0;max-width:none">
      <span class="badge badge-verified">Create Account</span>

      <h1 style="margin-top:12px">Join Kasi2Kasi</h1>
      <p class="sub">Start buying and selling with people in your community.</p>

      <?php if ($error): ?>
        <div class="card" style="padding:14px;color:var(--danger);margin-bottom:16px">
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <div class="field">
        <label>Full Name</label>
        <input name="name" required placeholder="Thabo Mokoena">
      </div>

      <div class="field">
        <label>Email Address</label>
        <input name="email" type="email" required placeholder="you@example.com">
      </div>

      <div class="field">
        <label>Phone Number</label>
        <input name="phone" type="tel" placeholder="+27 82 000 0000">
      </div>

      <div class="field">
        <label>Password</label>
        <input name="password" type="password" required placeholder="Minimum 6 characters">
      </div>

      <button class="btn btn-primary btn-block">Create Account</button>

      <div class="form-foot">
        Already have an account? <a href="login.php"><strong>Sign in</strong></a>
      </div>
    </form>

  </div>
</div>

<?php include "includes/footer.php"; ?>