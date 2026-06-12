<?php
require_once "includes/db.php";
require_once "includes/auth.php";
requireLogin();

$error = "";
$categories = $conn->query("SELECT * FROM category ORDER BY name ASC");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $seller_id = $_SESSION["user_id"];
    $title = trim($_POST["title"]);
    $category_id = intval($_POST["category_id"]);
    $price = floatval($_POST["price"]);
    $quantity = intval($_POST["quantity"]);
    $image_url = trim($_POST["image_url"]);
    $description = trim($_POST["description"]);

    if (empty($title) || empty($category_id) || empty($price) || empty($quantity) || empty($description)) {
        $error = "Please complete all required fields.";
    } else {
        $stmt = $conn->prepare("
            INSERT INTO product (seller_id, category_id, title, description, price, quantity, image_url)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "iissdis",
            $seller_id,
            $category_id,
            $title,
            $description,
            $price,
            $quantity,
            $image_url
        );

        if ($stmt->execute()) {
            header("Location: product.php?id=" . $stmt->insert_id);
            exit;
        } else {
            $error = "Failed to publish listing. Please try again.";
        }
    }
}
?>

<?php include "includes/header.php"; ?>

<div class="container">

  <section class="hero" style="padding:42px 30px;margin-bottom:22px">
    <div class="hero-content">
      <div class="kicker">🚀 Start selling</div>
      <h1 style="font-size:clamp(2rem,4vw,3.5rem)">Turn your hustle into a digital storefront.</h1>
      <p>
        List your item, reach local buyers, and grow your kasi side-hustle with a trusted community marketplace.
      </p>
    </div>
  </section>

  <div class="grid grid-2" style="align-items:start">

    <div class="card" style="padding:26px">
      <span class="badge badge-trusted">Seller tips</span>

      <h2 style="margin:12px 0 10px;letter-spacing:-.04em">
        Make your listing stand out
      </h2>

      <div class="grid" style="gap:14px;margin-top:18px">
        <div class="seller-card">
          <div class="avatar">1</div>
          <div>
            <strong>Use a clear photo</strong>
            <p class="text-muted text-sm" style="margin:4px 0 0">
              Bright images help buyers trust your product.
            </p>
          </div>
        </div>

        <div class="seller-card">
          <div class="avatar">2</div>
          <div>
            <strong>Write honest details</strong>
            <p class="text-muted text-sm" style="margin:4px 0 0">
              Mention condition, size, pickup area, and what is included.
            </p>
          </div>
        </div>

        <div class="seller-card">
          <div class="avatar">3</div>
          <div>
            <strong>Build your seller reputation</strong>
            <p class="text-muted text-sm" style="margin:4px 0 0">
              Respond quickly and aim for good reviews.
            </p>
          </div>
        </div>
      </div>
    </div>

    <form method="POST" class="form-card" style="max-width:none;margin:0">

      <h1>List an item</h1>
      <p class="sub">Add the details buyers need before they contact you.</p>

      <?php if ($error): ?>
        <div class="card" style="padding:14px;color:var(--danger);margin-bottom:16px">
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <div class="field">
        <label>Product Title</label>
        <input 
          name="title" 
          required 
          maxlength="150"
          placeholder="e.g. Samsung Galaxy A14 128GB"
        >
      </div>

      <div class="field">
        <label>Category</label>
        <select name="category_id" required>
          <option value="">Choose category</option>

          <?php while ($cat = $categories->fetch_assoc()): ?>
            <option value="<?= $cat["category_id"] ?>">
              <?= htmlspecialchars($cat["name"]) ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <div class="grid grid-2">
        <div class="field">
          <label>Price (R)</label>
          <input 
            name="price" 
            type="number" 
            min="1" 
            step="0.01" 
            required 
            placeholder="850"
          >
        </div>

        <div class="field">
          <label>Quantity</label>
          <input 
            name="quantity" 
            type="number" 
            min="1" 
            value="1" 
            required
          >
        </div>
      </div>

      <div class="field">
        <label>Image URL</label>
        <input 
          name="image_url" 
          type="url" 
          placeholder="https://example.com/product-image.jpg"
        >
      </div>

      <div class="field">
        <label>Description</label>
        <textarea 
          name="description" 
          required 
          placeholder="Describe condition, pickup location, colour, size, and anything buyers should know..."
        ></textarea>
      </div>

      <button class="btn btn-primary btn-block">
        Publish Listing
      </button>

    </form>
  </div>

</div>

<?php include "includes/footer.php"; ?>