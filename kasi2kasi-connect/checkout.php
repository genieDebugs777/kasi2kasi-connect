<?php
require_once "includes/db.php";
require_once "includes/auth.php";
requireLogin();

$user_id = $_SESSION["user_id"];
$error = "";

/* Get user's cart */
$cart_stmt = $conn->prepare("SELECT cart_id FROM cart WHERE user_id = ?");
$cart_stmt->bind_param("i", $user_id);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();

if ($cart_result->num_rows === 0) {
    header("Location: cart.php");
    exit;
}

$cart_id = $cart_result->fetch_assoc()["cart_id"];

/* Fetch cart items WITH CURRENT STOCK QUANTITY */
$items_stmt = $conn->prepare("
    SELECT
        cart_item.cart_item_id,
        cart_item.quantity AS cart_quantity,
        product.product_id,
        product.title,
        product.price,
        product.quantity AS stock_quantity,
        product.image_url,
        user.name AS seller_name
    FROM cart_item
    JOIN product ON cart_item.product_id = product.product_id
    JOIN user ON product.seller_id = user.user_id
    WHERE cart_item.cart_id = ?
");

$items_stmt->bind_param("i", $cart_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();

$cart_items = [];
$subtotal = 0;
$stock_errors = [];

while ($row = $items_result->fetch_assoc()) {
    if ($row["cart_quantity"] > $row["stock_quantity"]) {
        $stock_errors[] = [
            "title" => $row["title"],
            "requested" => $row["cart_quantity"],
            "available" => $row["stock_quantity"]
        ];
    }
    
    $row["line_total"] = $row["price"] * $row["cart_quantity"];
    $subtotal += $row["line_total"];
    $cart_items[] = $row;
}

if (!empty($stock_errors)) {
    $_SESSION["cart_errors"] = $stock_errors;
    header("Location: cart.php?stock_error=1");
    exit;
}

if (empty($cart_items)) {
    header("Location: cart.php");
    exit;
}

/* Get selected delivery type from POST or default to delivery */
$selected_delivery = isset($_POST["delivery_type"]) ? $_POST["delivery_type"] : (isset($_GET["delivery"]) ? $_GET["delivery"] : "delivery");
$delivery_fee = ($selected_delivery === "pickup") ? 0 : 50;
$total = $subtotal + $delivery_fee;

/* Place order */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $delivery_address = trim($_POST["delivery_address"]);
    $delivery_phone   = trim($_POST["delivery_phone"]);
    $payment_method   = $_POST["payment_method"];
    $delivery_type    = $_POST["delivery_type"] ?? "delivery";

    $delivery_fee = ($delivery_type === "pickup") ? 0 : 50;
    $total = $subtotal + $delivery_fee;

    if (empty($delivery_address) || empty($delivery_phone) || empty($payment_method)) {
        $error = "Please complete all checkout fields.";
    } else {
        
        $conn->begin_transaction();
        
        try {
            // Final stock check
            $stock_check_stmt = $conn->prepare("
                SELECT cart_item.quantity AS cart_quantity, cart_item.product_id, product.quantity AS stock_quantity, product.title
                FROM cart_item
                JOIN product ON cart_item.product_id = product.product_id
                WHERE cart_item.cart_id = ?
            ");
            $stock_check_stmt->bind_param("i", $cart_id);
            $stock_check_stmt->execute();
            $stock_check_result = $stock_check_stmt->get_result();
            
            $insufficient_stock = [];
            while ($item = $stock_check_result->fetch_assoc()) {
                if ($item["cart_quantity"] > $item["stock_quantity"]) {
                    $insufficient_stock[] = $item["title"];
                }
            }
            
            if (!empty($insufficient_stock)) {
                throw new Exception("Some items are no longer available: " . implode(", ", $insufficient_stock));
            }
            
            // Create order
            $order_stmt = $conn->prepare("
                INSERT INTO orders
                (buyer_id, total_amount, status, delivery_address, delivery_phone)
                VALUES (?, ?, 'pending', ?, ?)
            ");

            $order_stmt->bind_param("idss", $user_id, $total, $delivery_address, $delivery_phone);
            $order_stmt->execute();
            $order_id = $order_stmt->insert_id;

            // Create order items AND reduce inventory
            $order_item_stmt = $conn->prepare("
                INSERT INTO order_item
                (order_id, product_id, quantity, unit_price)
                VALUES (?, ?, ?, ?)
            ");
            
            $update_stock_stmt = $conn->prepare("
                UPDATE product 
                SET quantity = quantity - ? 
                WHERE product_id = ? AND quantity >= ?
            ");

            foreach ($cart_items as $item) {
                $order_item_stmt->bind_param("iiid", $order_id, $item["product_id"], $item["cart_quantity"], $item["price"]);
                $order_item_stmt->execute();
                
                $update_stock_stmt->bind_param("iii", $item["cart_quantity"], $item["product_id"], $item["cart_quantity"]);
                $update_stock_stmt->execute();
                
                if ($update_stock_stmt->affected_rows === 0) {
                    throw new Exception("Stock update failed for product: " . $item["title"]);
                }
            }

            // Create payment record
            $reference = "K2K-" . time() . "-" . $order_id;
            $payment_stmt = $conn->prepare("
                INSERT INTO payment
                (order_id, method, status, amount, reference)
                VALUES (?, ?, 'pending', ?, ?)
            ");
            $payment_stmt->bind_param("isds", $order_id, $payment_method, $total, $reference);
            $payment_stmt->execute();

            // Clear cart
            $clear_stmt = $conn->prepare("DELETE FROM cart_item WHERE cart_id = ?");
            $clear_stmt->bind_param("i", $cart_id);
            $clear_stmt->execute();

            $conn->commit();

            header("Location: orders.php?placed=" . $order_id);
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            $error = "Order could not be placed: " . $e->getMessage();
        }
    }
}
?>

<?php include "includes/header.php"; ?>

<div class="container">

    <section class="hero" style="padding:40px 30px;margin-bottom:24px;">
        <div class="hero-content">
            <div class="kicker">💳 Secure Checkout</div>
            <h1 style="font-size:clamp(2rem,4vw,3.5rem)">Complete your local trade.</h1>
            <p>Review your items, choose delivery preferences, and securely place your order.</p>
        </div>
    </section>

    <?php if ($error): ?>
        <div class="card auto-hide" style="padding:16px;background:#fee2e2;border-left:4px solid var(--danger);margin-bottom:20px;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="layout-2">

        <form method="POST" class="form-card" style="max-width:none;margin:0;" id="checkoutForm">
            <span class="badge badge-trusted">Secure Checkout</span>
            <h1 style="margin-top:15px;">Delivery & Payment</h1>
            <p class="sub">Complete the information below to place your order.</p>

            <!-- DELIVERY TYPE with JavaScript to update total -->
            <h3>Delivery Method</h3>
            <div class="delivery-options" id="deliveryOptions">
                <label class="checkout-option <?= $selected_delivery === 'delivery' ? 'active-option' : '' ?>">
                    <input type="radio" name="delivery_type" value="delivery" <?= $selected_delivery === 'delivery' ? 'checked' : '' ?> onchange="updateTotal()">
                    <div>
                        <h4>🚚 Delivery</h4>
                        <p>Delivered to your address</p>
                        <strong>+ R50</strong>
                    </div>
                </label>
                <label class="checkout-option <?= $selected_delivery === 'pickup' ? 'active-option' : '' ?>">
                    <input type="radio" name="delivery_type" value="pickup" <?= $selected_delivery === 'pickup' ? 'checked' : '' ?> onchange="updateTotal()">
                    <div>
                        <h4>📦 Pickup</h4>
                        <p>Collect directly from seller</p>
                        <strong>FREE</strong>
                    </div>
                </label>
            </div>

            <!-- Delivery Address (only shown if delivery selected) -->
            <div id="deliveryAddressField" class="field mt-3" style="<?= $selected_delivery === 'pickup' ? 'display:none' : '' ?>">
                <label>Delivery Address</label>
                <input type="text" name="delivery_address" value="<?= htmlspecialchars($_POST['delivery_address'] ?? '') ?>" required placeholder="Street, township/suburb, city">
            </div>

            <div class="field">
                <label>Phone Number</label>
                <input type="tel" name="delivery_phone" value="<?= htmlspecialchars($_POST['delivery_phone'] ?? '') ?>" required placeholder="+27 82 000 0000">
            </div>

            <!-- PAYMENT METHOD SELECTION -->
            <h3 style="margin-top:20px;">Payment Method</h3>
            <div class="payment-grid" id="paymentMethods">
                <label class="payment-card" data-method="eft">
                    <input type="radio" name="payment_method" value="eft" checked onchange="showPaymentInstructions('eft')">
                    <div>
                        <span>🏦</span>
                        <h4>EFT / Bank Transfer</h4>
                        <p>Secure bank transfer payment</p>
                    </div>
                </label>
                <label class="payment-card" data-method="card">
                    <input type="radio" name="payment_method" value="card" onchange="showPaymentInstructions('card')">
                    <div>
                        <span>💳</span>
                        <h4>Card Payment</h4>
                        <p>Simulated card gateway</p>
                    </div>
                </label>
                <label class="payment-card" data-method="mobile_money">
                    <input type="radio" name="payment_method" value="mobile_money" onchange="showPaymentInstructions('mobile_money')">
                    <div>
                        <span>📱</span>
                        <h4>Scan To Pay</h4>
                        <p>QR payment simulation</p>
                    </div>
                </label>
                <label class="payment-card" data-method="cash">
                    <input type="radio" name="payment_method" value="cash" onchange="showPaymentInstructions('cash')">
                    <div>
                        <span>💵</span>
                        <h4>Cash On Delivery</h4>
                        <p>Pay when receiving goods</p>
                    </div>
                </label>
            </div>

            <!-- Payment Instructions (dynamic) -->
            <div id="paymentInstructions" class="card mt-3" style="padding:16px;background:var(--cream);box-shadow:none;display:none;">
                <strong id="paymentInstructionTitle">💰 Payment Instructions</strong>
                <p id="paymentInstructionText" class="text-sm" style="margin-top:8px;line-height:1.6;"></p>
            </div>

            <div class="card mt-3" style="padding:16px;background:var(--cream);box-shadow:none;">
                <strong>Why shop on Kasi2Kasi?</strong>
                <p class="text-sm text-muted" style="margin-top:8px;line-height:1.6;">
                    ✅ Verified sellers<br>
                    ✅ Secure transaction records<br>
                    ✅ Community marketplace trust
                </p>
            </div>

            <button class="btn btn-primary btn-block mt-3" type="submit">Place Order</button>
        </form>

        <aside class="summary">
            <span class="badge badge-verified">Order Summary</span>
            <h3 style="margin:15px 0;">Your Items</h3>

            <?php foreach ($cart_items as $item): ?>
                <div class="checkout-item">
                    <img src="<?= htmlspecialchars($item["image_url"]) ?>" alt="<?= htmlspecialchars($item["title"]) ?>">
                    <div class="checkout-item-info">
                        <h4 style="margin:0;"><?= htmlspecialchars($item["title"]) ?></h4>
                        <small class="text-muted">Seller: <?= htmlspecialchars($item["seller_name"]) ?></small>
                        <p class="text-sm" style="margin:5px 0;">Qty: <?= $item["cart_quantity"] ?></p>
                    </div>
                    <strong>R <?= number_format($item["line_total"], 2) ?></strong>
                </div>
            <?php endforeach; ?>

            <div class="summary-card mt-3">
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span>R <?= number_format($subtotal, 2) ?></span>
                </div>
                <div class="summary-row" id="deliveryFeeRow">
                    <span>Delivery</span>
                    <span id="deliveryFeeAmount">R <?= number_format($delivery_fee, 2) ?></span>
                </div>
                <div class="summary-total" id="totalRow">
                    <span>Total</span>
                    <span id="totalAmount">R <?= number_format($total, 2) ?></span>
                </div>
            </div>
        </aside>

    </div>
</div>

<script>
// Store original values for JavaScript
const subtotal = <?= $subtotal ?>;
const deliveryFeeRow = document.getElementById('deliveryFeeRow');
const deliveryFeeAmount = document.getElementById('deliveryFeeAmount');
const totalAmount = document.getElementById('totalAmount');
const deliveryAddressField = document.getElementById('deliveryAddressField');
const paymentInstructionsDiv = document.getElementById('paymentInstructions');
const paymentInstructionTitle = document.getElementById('paymentInstructionTitle');
const paymentInstructionText = document.getElementById('paymentInstructionText');

function updateTotal() {
    const deliveryRadios = document.querySelectorAll('input[name="delivery_type"]');
    let selectedDelivery = 'delivery';
    for (let radio of deliveryRadios) {
        if (radio.checked) {
            selectedDelivery = radio.value;
            break;
        }
    }
    
    let deliveryFee = (selectedDelivery === 'pickup') ? 0 : 50;
    let total = subtotal + deliveryFee;
    
    // Update delivery fee display
    if (deliveryFee === 0) {
        deliveryFeeAmount.innerHTML = 'R 0.00';
        deliveryAddressField.style.display = 'none';
        // Remove required attribute when pickup selected
        const addressInput = document.querySelector('input[name="delivery_address"]');
        if (addressInput) addressInput.required = false;
    } else {
        deliveryFeeAmount.innerHTML = 'R 50.00';
        deliveryAddressField.style.display = 'block';
        const addressInput = document.querySelector('input[name="delivery_address"]');
        if (addressInput) addressInput.required = true;
    }
    
    totalAmount.innerHTML = 'R ' + total.toFixed(2);
}

function showPaymentInstructions(method) {
    const instructions = {
        'eft': {
            title: '🏦 EFT / Bank Transfer Instructions',
            text: 'Please use the reference code provided after order placement to complete your bank transfer. The seller will confirm receipt within 24 hours.'
        },
        'card': {
            title: '💳 Card Payment Instructions',
            text: 'You will be redirected to a secure payment gateway after placing your order. Enter your card details to complete payment instantly.'
        },
        'mobile_money': {
            title: '📱 Scan To Pay Instructions',
            text: 'A QR code will be displayed after order placement. Scan with your mobile money app (SnapScan, Zapper, or MoMo) to complete payment.'
        },
        'cash': {
            title: '💵 Cash On Delivery Instructions',
            text: 'Pay in cash when your order is delivered or when you pick up from the seller. Please have exact change ready.'
        }
    };
    
    const inst = instructions[method] || instructions['eft'];
    paymentInstructionTitle.innerHTML = inst.title;
    paymentInstructionText.innerHTML = inst.text;
    paymentInstructionsDiv.style.display = 'block';
}

// Show default payment instructions on page load
document.addEventListener('DOMContentLoaded', function() {
    showPaymentInstructions('eft');
    updateTotal();
    
    // Add active styling for selected options
    const options = document.querySelectorAll('.checkout-option, .payment-card');
    options.forEach(option => {
        const radio = option.querySelector('input[type="radio"]');
        if (radio && radio.checked) {
            option.classList.add('active-option');
        }
        option.addEventListener('click', function(e) {
            const radio = this.querySelector('input[type="radio"]');
            if (radio && !radio.disabled) {
                radio.checked = true;
                // Update active class for all options in same group
                const parent = this.parentElement;
                const siblings = parent.querySelectorAll('.checkout-option, .payment-card');
                siblings.forEach(sib => sib.classList.remove('active-option'));
                this.classList.add('active-option');
                
                // Trigger change events
                if (radio.name === 'delivery_type') {
                    updateTotal();
                }
                const event = new Event('change');
                radio.dispatchEvent(event);
            }
        });
    });
});
</script>

<style>
.active-option {
    border: 2px solid var(--primary) !important;
    background: rgba(91,45,245,.04) !important;
}
.checkout-option, .payment-card {
    cursor: pointer;
    transition: all 0.2s ease;
}
.checkout-option:hover, .payment-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow);
}
</style>

<?php include "includes/footer.php"; ?>