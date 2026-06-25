<?php
require_once "INCLUDES/db.php";
require_once "INCLUDES/auth.php";
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
    $delivery_address = ($delivery_type === "pickup") ? "Pickup - Contact seller for location" : $delivery_address;

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
            
            // ============================================================
            // CREATE ORDER - FIXED BIND PARAMETERS
            // ============================================================
            // Determine initial order status based on payment method
            $initial_status = ($payment_method === 'cash') ? 'paid' : 'pending';
            
            $order_stmt = $conn->prepare("
                INSERT INTO orders
                (buyer_id, total_amount, status, delivery_address, delivery_phone)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            // FIXED: Added 5th 's' for delivery_phone
            // idsss = i (integer) + d (decimal) + sss (3 strings)
            $order_stmt->bind_param("idsss", $user_id, $total, $initial_status, $delivery_address, $delivery_phone);
            $order_stmt->execute();
            $order_id = $order_stmt->insert_id;

            // Create order items
            $order_item_stmt = $conn->prepare("
                INSERT INTO order_item
                (order_id, product_id, quantity, unit_price)
                VALUES (?, ?, ?, ?)
            ");

            foreach ($cart_items as $item) {
                $order_item_stmt->bind_param("iiid", $order_id, $item["product_id"], $item["cart_quantity"], $item["price"]);
                $order_item_stmt->execute();
            }

            // Create payment record
            $reference = "K2K-" . time() . "-" . $order_id;
            
            if ($payment_method === 'cash') {
                // Cash payment - auto-complete
                $payment_stmt = $conn->prepare("
                    INSERT INTO payment
                    (order_id, method, status, amount, reference, paid_at)
                    VALUES (?, ?, 'completed', ?, ?, NOW())
                ");
                $payment_stmt->bind_param("isds", $order_id, $payment_method, $total, $reference);
            } else {
                // EFT/Card/Mobile - pending
                $payment_stmt = $conn->prepare("
                    INSERT INTO payment
                    (order_id, method, status, amount, reference)
                    VALUES (?, ?, 'pending', ?, ?)
                ");
                $payment_stmt->bind_param("isds", $order_id, $payment_method, $total, $reference);
            }
            $payment_stmt->execute();

            // IF CASH PAYMENT - Reduce stock immediately
            if ($payment_method === 'cash') {
                $update_stock_stmt = $conn->prepare("
                    UPDATE product 
                    SET quantity = quantity - ? 
                    WHERE product_id = ? AND quantity >= ?
                ");
                
                foreach ($cart_items as $item) {
                    $update_stock_stmt->bind_param("iii", $item["cart_quantity"], $item["product_id"], $item["cart_quantity"]);
                    $update_stock_stmt->execute();
                    
                    if ($update_stock_stmt->affected_rows === 0) {
                        throw new Exception("Stock update failed for product: " . $item["title"]);
                    }
                    
                    // Check if product is now out of stock
                    $check_stock = $conn->prepare("SELECT quantity FROM product WHERE product_id = ?");
                    $check_stock->bind_param("i", $item["product_id"]);
                    $check_stock->execute();
                    $stock_result = $check_stock->get_result()->fetch_assoc();
                    
                    if ($stock_result['quantity'] <= 0) {
                        $status_stmt = $conn->prepare("UPDATE product SET status = 'sold' WHERE product_id = ?");
                        $status_stmt->bind_param("i", $item["product_id"]);
                        $status_stmt->execute();
                    }
                }
            }

            // Clear cart
            $clear_stmt = $conn->prepare("DELETE FROM cart_item WHERE cart_id = ?");
            $clear_stmt->bind_param("i", $cart_id);
            $clear_stmt->execute();

            $conn->commit();

            // Create notification for seller(s)
            $seller_stmt = $conn->prepare("
                SELECT DISTINCT product.seller_id
                FROM order_item
                JOIN product ON order_item.product_id = product.product_id
                WHERE order_item.order_id = ?
            ");
            $seller_stmt->bind_param("i", $order_id);
            $seller_stmt->execute();
            $sellers = $seller_stmt->get_result();
            
            while ($seller = $sellers->fetch_assoc()) {
                $notif_stmt = $conn->prepare("
                    INSERT INTO notification (user_id, type, title, message, is_read)
                    VALUES (?, 'new_order', 'New Order Received!', 
                        '🛒 You have a new order #{$order_id}. Check your sales dashboard to manage it.', 0)
                ");
                $notif_stmt->bind_param("i", $seller['seller_id']);
                $notif_stmt->execute();
            }
            
            // Create notification for buyer
            $payment_message = ($payment_method === 'cash') 
                ? "Your order #{$order_id} is confirmed and marked as PAID. The seller will prepare your items."
                : "Your order #{$order_id} has been placed. Complete payment using the method you selected.";
            
            $buyer_notif = $conn->prepare("
                INSERT INTO notification (user_id, type, title, message, is_read)
                VALUES (?, 'order_update', 'Order Placed Successfully', ?, 0)
            ");
            $buyer_notif->bind_param("is", $user_id, $payment_message);
            $buyer_notif->execute();

            // Redirect to order confirmation
            header("Location: order_confirmation.php?id=" . $order_id);
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            $error = "Order could not be placed: " . $e->getMessage();
        }
    }
}
?>

<?php include "INCLUDES/header.php"; ?>

<div class="container">

    <section class="hero" style="padding:40px 30px;margin-bottom:24px;">
        <div class="hero-content">
            <div class="kicker">💳 Secure Checkout</div>
            <h1 style="font-size:clamp(2rem,4vw,3.5rem);">Complete your local trade.</h1>
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

            <!-- DELIVERY TYPE -->
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

            <!-- Delivery Address -->
            <div id="deliveryAddressField" class="field mt-3" style="<?= $selected_delivery === 'pickup' ? 'display:none' : '' ?>">
                <label>Delivery Address</label>
                <input type="text" name="delivery_address" value="<?= htmlspecialchars($_POST['delivery_address'] ?? '') ?>" required placeholder="Street, township/suburb, city">
                <small class="text-muted">For pickup orders, you'll arrange collection with the seller directly.</small>
            </div>

            <div class="field">
                <label>Phone Number</label>
                <input type="tel" name="delivery_phone" value="<?= htmlspecialchars($_POST['delivery_phone'] ?? '') ?>" required placeholder="+27 82 000 0000">
                <small class="text-muted">Used for delivery updates and seller contact.</small>
            </div>

            <!-- PAYMENT METHOD SELECTION -->
            <h3 style="margin-top:20px;">Payment Method</h3>
            <div class="payment-grid" id="paymentMethods">
                <label class="payment-card active-option" data-method="eft">
                    <input type="radio" name="payment_method" value="eft" checked onchange="showPaymentInstructions('eft')">
                    <div>
                        <span>🏦</span>
                        <h4>EFT / Bank Transfer</h4>
                        <p>Secure bank transfer payment</p>
                        <small style="color:var(--muted)">Payment pending confirmation</small>
                    </div>
                </label>
                <label class="payment-card" data-method="card">
                    <input type="radio" name="payment_method" value="card" onchange="showPaymentInstructions('card')">
                    <div>
                        <span>💳</span>
                        <h4>Card Payment</h4>
                        <p>Credit or debit card</p>
                        <small style="color:var(--muted)">Simulated gateway</small>
                    </div>
                </label>
                <label class="payment-card" data-method="mobile_money">
                    <input type="radio" name="payment_method" value="mobile_money" onchange="showPaymentInstructions('mobile_money')">
                    <div>
                        <span>📱</span>
                        <h4>Scan To Pay</h4>
                        <p>QR payment simulation</p>
                        <small style="color:var(--muted)">Mobile money support</small>
                    </div>
                </label>
                <label class="payment-card" data-method="cash">
                    <input type="radio" name="payment_method" value="cash" onchange="showPaymentInstructions('cash')">
                    <div>
                        <span>💵</span>
                        <h4>Cash On Delivery</h4>
                        <p>Pay when receiving goods</p>
                        <small style="color:var(--ubuntu);font-weight:bold;">✓ Auto-confirmed on order</small>
                    </div>
                </label>
            </div>

            <!-- Payment Instructions -->
            <div id="paymentInstructions" class="card mt-3" style="padding:16px;background:var(--cream);box-shadow:none;display:block;">
                <strong id="paymentInstructionTitle">🏦 EFT / Bank Transfer Instructions</strong>
                <p id="paymentInstructionText" class="text-sm" style="margin-top:8px;line-height:1.6;">
                    Please use the reference code provided after order placement to complete your bank transfer. 
                    The seller will confirm receipt within 24 hours.
                </p>
            </div>

            <div class="card mt-3" style="padding:16px;background:var(--cream);box-shadow:none;">
                <strong>Why shop on Kasi2Kasi?</strong>
                <p class="text-sm text-muted" style="margin-top:8px;line-height:1.6;">
                    ✅ Verified sellers<br>
                    ✅ Secure transaction records<br>
                    ✅ Community marketplace trust
                </p>
            </div>

            <button class="btn btn-primary btn-block mt-3" type="submit">
                <?= (isset($_POST['payment_method']) && $_POST['payment_method'] === 'cash') ? '✓ Confirm Cash Order' : 'Place Order' ?>
            </button>
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
            
            <!-- Payment method note -->
            <div class="card mt-3" style="padding:12px;background:rgba(247,201,72,.15);border-left:4px solid var(--taxi);font-size:0.85rem;">
                <strong>💡 Payment Note:</strong>
                <span id="paymentNote" class="text-muted" style="display:block;margin-top:4px;">
                    EFT payments require seller confirmation. Cash payments are auto-confirmed.
                </span>
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
const paymentNote = document.getElementById('paymentNote');

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
    
    if (deliveryFee === 0) {
        deliveryFeeAmount.innerHTML = 'R 0.00';
        deliveryAddressField.style.display = 'none';
        const addressInput = document.querySelector('input[name="delivery_address"]');
        if (addressInput) {
            addressInput.required = false;
            addressInput.value = 'Pickup - Contact seller for location';
        }
    } else {
        deliveryFeeAmount.innerHTML = 'R 50.00';
        deliveryAddressField.style.display = 'block';
        const addressInput = document.querySelector('input[name="delivery_address"]');
        if (addressInput) {
            addressInput.required = true;
            if (addressInput.value === 'Pickup - Contact seller for location') {
                addressInput.value = '';
            }
        }
    }
    
    totalAmount.innerHTML = 'R ' + total.toFixed(2);
}

function showPaymentInstructions(method) {
    const instructions = {
        'eft': {
            title: '🏦 EFT / Bank Transfer Instructions',
            text: 'Please use the reference code provided after order placement to complete your bank transfer. The seller will confirm receipt within 24 hours.',
            note: 'EFT payments require seller confirmation. The order will show as "Pending" until confirmed.'
        },
        'card': {
            title: '💳 Card Payment Instructions',
            text: 'You will be redirected to a secure payment gateway after placing your order. Enter your card details to complete payment instantly.',
            note: 'Card payments are processed through our secure gateway. You will receive confirmation immediately.'
        },
        'mobile_money': {
            title: '📱 Scan To Pay Instructions',
            text: 'A QR code will be displayed after order placement. Scan with your mobile money app (SnapScan, Zapper, or MoMo) to complete payment.',
            note: 'Mobile money payments are processed through your provider. Payment confirmation may take a few minutes.'
        },
        'cash': {
            title: '💵 Cash On Delivery Instructions',
            text: 'Pay in cash when your order is delivered or when you pick up from the seller. Please have exact change ready. Your order will be marked as "Paid" immediately.',
            note: '✅ Cash orders are AUTO-CONFIRMED. The seller will see your order as "Paid" immediately and can start preparing it.'
        }
    };
    
    const inst = instructions[method] || instructions['eft'];
    paymentInstructionTitle.innerHTML = inst.title;
    paymentInstructionText.innerHTML = inst.text;
    paymentNote.innerHTML = inst.note;
    paymentInstructionsDiv.style.display = 'block';
    
    // Update button text
    const submitBtn = document.querySelector('button[type="submit"]');
    if (method === 'cash') {
        submitBtn.innerHTML = '✓ Confirm Cash Order';
        submitBtn.style.background = 'var(--ubuntu)';
    } else {
        submitBtn.innerHTML = 'Place Order';
        submitBtn.style.background = '';
    }
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
                const parent = this.parentElement;
                const siblings = parent.querySelectorAll('.checkout-option, .payment-card');
                siblings.forEach(sib => sib.classList.remove('active-option'));
                this.classList.add('active-option');
                
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
.btn-danger {
    background: var(--danger);
    color: white;
}
.btn-danger:hover {
    background: #b91c1c;
}
</style>

<?php include "INCLUDES/footer.php"; ?>
