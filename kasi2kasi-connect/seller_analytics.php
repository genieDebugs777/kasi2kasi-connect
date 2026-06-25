<?php
require_once "INCLUDES/db.php";
require_once "INCLUDES/auth.php";
requireLogin();

$user_id = $_SESSION["user_id"];

// ============================================================
// SELLER DATA
// ============================================================

// 1. SELLER STATS
$stats_stmt = $conn->prepare("
    SELECT 
        COUNT(DISTINCT orders.order_id) AS total_orders,
        SUM(CASE WHEN orders.status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
        SUM(CASE WHEN orders.status = 'paid' THEN 1 ELSE 0 END) AS paid_count,
        SUM(CASE WHEN orders.status = 'shipped' THEN 1 ELSE 0 END) AS shipped_count,
        SUM(CASE WHEN orders.status = 'delivered' THEN 1 ELSE 0 END) AS delivered_count,
        SUM(orders.total_amount) AS total_revenue,
        AVG(orders.total_amount) AS avg_order_value,
        COUNT(DISTINCT product.product_id) AS total_products,
        SUM(product.quantity) AS total_stock
    FROM orders
    JOIN order_item ON orders.order_id = order_item.order_id
    JOIN product ON order_item.product_id = product.product_id
    WHERE product.seller_id = ?
");
$stats_stmt->bind_param("i", $user_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

$total_orders = $stats["total_orders"] ?? 0;
$pending_count = $stats["pending_count"] ?? 0;
$paid_count = $stats["paid_count"] ?? 0;
$shipped_count = $stats["shipped_count"] ?? 0;
$delivered_count = $stats["delivered_count"] ?? 0;
$total_revenue = $stats["total_revenue"] ?? 0;
$avg_order_value = $stats["avg_order_value"] ?? 0;
$total_products = $stats["total_products"] ?? 0;
$total_stock = $stats["total_stock"] ?? 0;

// 2. DAILY SALES (Last 30 days)
$daily_stmt = $conn->prepare("
    SELECT 
        DATE(orders.created_at) AS date,
        SUM(orders.total_amount) AS revenue,
        COUNT(orders.order_id) AS orders
    FROM orders
    JOIN order_item ON orders.order_id = order_item.order_id
    JOIN product ON order_item.product_id = product.product_id
    WHERE product.seller_id = ?
        AND orders.status IN ('paid', 'shipped', 'delivered')
        AND orders.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(orders.created_at)
    ORDER BY date ASC
");
$daily_stmt->bind_param("i", $user_id);
$daily_stmt->execute();
$daily_data = $daily_stmt->get_result();

$daily_dates = [];
$daily_revenue = [];
$daily_orders = [];

while ($row = $daily_data->fetch_assoc()) {
    $daily_dates[] = date("d M", strtotime($row['date']));
    $daily_revenue[] = floatval($row['revenue']);
    $daily_orders[] = intval($row['orders']);
}

// 3. PRODUCT PERFORMANCE
$product_stmt = $conn->prepare("
    SELECT 
        product.product_id,
        product.title,
        product.price,
        product.quantity,
        product.status,
        COUNT(order_item.order_item_id) AS times_ordered,
        SUM(order_item.quantity) AS total_sold,
        SUM(order_item.unit_price * order_item.quantity) AS revenue
    FROM product
    LEFT JOIN order_item ON product.product_id = order_item.product_id
    LEFT JOIN orders ON order_item.order_id = orders.order_id
        AND orders.status IN ('paid', 'shipped', 'delivered')
    WHERE product.seller_id = ?
    GROUP BY product.product_id
    ORDER BY revenue DESC
");
$product_stmt->bind_param("i", $user_id);
$product_stmt->execute();
$products = $product_stmt->get_result();

// 4. LOW STOCK PRODUCTS
$low_stock_stmt = $conn->prepare("
    SELECT product_id, title, quantity
    FROM product
    WHERE seller_id = ? 
        AND quantity <= 10 
        AND quantity > 0 
        AND status = 'active'
    ORDER BY quantity ASC
");
$low_stock_stmt->bind_param("i", $user_id);
$low_stock_stmt->execute();
$low_stock = $low_stock_stmt->get_result();

// 5. MONTHLY TREND (Last 12 months)
$monthly_stmt = $conn->prepare("
    SELECT 
        DATE_FORMAT(orders.created_at, '%Y-%m') AS month,
        SUM(orders.total_amount) AS revenue,
        COUNT(orders.order_id) AS orders
    FROM orders
    JOIN order_item ON orders.order_id = order_item.order_id
    JOIN product ON order_item.product_id = product.product_id
    WHERE product.seller_id = ?
        AND orders.status IN ('paid', 'shipped', 'delivered')
        AND orders.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(orders.created_at, '%Y-%m')
    ORDER BY month ASC
");
$monthly_stmt->bind_param("i", $user_id);
$monthly_stmt->execute();
$monthly_data = $monthly_stmt->get_result();

$monthly_labels = [];
$monthly_revenue = [];
$monthly_orders = [];

while ($row = $monthly_data->fetch_assoc()) {
    $monthly_labels[] = date("M Y", strtotime($row['month'] . '-01'));
    $monthly_revenue[] = floatval($row['revenue']);
    $monthly_orders[] = intval($row['orders']);
}

// 6. REVIEW STATS
$review_stmt = $conn->prepare("
    SELECT 
        AVG(rating) AS avg_rating,
        COUNT(*) AS total_reviews,
        SUM(CASE WHEN rating >= 4 THEN 1 ELSE 0 END) AS positive_reviews
    FROM review
    JOIN product ON review.product_id = product.product_id
    WHERE product.seller_id = ?
");
$review_stmt->bind_param("i", $user_id);
$review_stmt->execute();
$review_stats = $review_stmt->get_result()->fetch_assoc();

$avg_rating = round($review_stats['avg_rating'] ?? 0, 1);
$total_reviews = $review_stats['total_reviews'] ?? 0;
$positive_reviews = $review_stats['positive_reviews'] ?? 0;
$positive_percentage = $total_reviews > 0 ? round(($positive_reviews / $total_reviews) * 100) : 0;
?>

<?php include "INCLUDES/header.php"; ?>

<style>
.seller-analytics {
    padding: 20px 0;
}

.seller-metric-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.seller-metric-card {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 20px;
    text-align: center;
    box-shadow: var(--shadow);
}

.seller-metric-card .value {
    font-size: 1.8rem;
    font-weight: 900;
    color: var(--ink);
    margin: 8px 0;
}

.seller-metric-card .label {
    color: var(--muted);
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.seller-metric-card .change {
    font-size: 0.8rem;
    margin-top: 8px;
}

.seller-chart-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-bottom: 24px;
}

.seller-chart-card {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 20px;
    box-shadow: var(--shadow);
}

.seller-chart-card h3 {
    margin-top: 0;
    margin-bottom: 16px;
    font-size: 1.1rem;
}

.seller-chart-card canvas {
    max-height: 280px;
    max-width: 100%;
}

.full-width {
    grid-column: 1 / -1;
}

@media (max-width: 900px) {
    .seller-chart-grid {
        grid-template-columns: 1fr;
    }
    .full-width {
        grid-column: 1;
    }
}

.rating-stars {
    color: var(--taxi);
    font-size: 1.4rem;
    letter-spacing: 2px;
}

.rating-empty {
    color: #ddd;
}

.low-stock-badge {
    background: rgba(247,201,72,0.2);
    color: #8a5a00;
    padding: 2px 10px;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 700;
}

.critical-stock {
    background: rgba(220,38,38,0.15);
    color: var(--danger);
}
</style>

<div class="container">
    <section class="hero" style="padding:40px 30px;margin-bottom:22px;">
        <div class="hero-content">
            <div class="kicker">📊 Seller Analytics</div>
            <h1 style="font-size:clamp(2rem,4vw,3.4rem);">Your Sales Performance</h1>
            <p>Track your revenue, product performance, and customer satisfaction.</p>
        </div>
    </section>

    <!-- METRICS -->
    <div class="seller-metric-grid">
        <div class="seller-metric-card">
            <div class="label">Total Revenue</div>
            <div class="value" style="color: var(--primary);">R <?= number_format($total_revenue, 2) ?></div>
            <div class="change">💰 Lifetime sales</div>
        </div>
        <div class="seller-metric-card">
            <div class="label">Total Orders</div>
            <div class="value"><?= $total_orders ?></div>
            <div class="change">📦 All time</div>
        </div>
        <div class="seller-metric-card">
            <div class="label">Avg Order Value</div>
            <div class="value">R <?= number_format($avg_order_value, 2) ?></div>
            <div class="change">📊 Per transaction</div>
        </div>
        <div class="seller-metric-card">
            <div class="label">Products</div>
            <div class="value"><?= $total_products ?></div>
            <div class="change">🛍 Active listings</div>
        </div>
        <div class="seller-metric-card">
            <div class="label">Total Stock</div>
            <div class="value"><?= $total_stock ?></div>
            <div class="change">📦 Units available</div>
        </div>
        <div class="seller-metric-card">
            <div class="label">Avg Rating</div>
            <div class="value" style="color: var(--taxi);">
                <?= $avg_rating > 0 ? $avg_rating : 'N/A' ?>
                <?php if ($avg_rating > 0): ?>
                <span style="font-size:1rem;">⭐</span>
                <?php endif; ?>
            </div>
            <div class="change"><?= $total_reviews ?> reviews · <?= $positive_percentage ?>% positive</div>
        </div>
    </div>

    <!-- CHARTS -->
    <div class="seller-chart-grid">

        <!-- Daily Revenue Trend -->
        <div class="seller-chart-card full-width">
            <h3>📈 Daily Revenue Trend (Last 30 Days)</h3>
            <canvas id="sellerDailyChart"></canvas>
        </div>

        <!-- Monthly Revenue -->
        <div class="seller-chart-card">
            <h3>📊 Monthly Revenue (Last 12 Months)</h3>
            <canvas id="sellerMonthlyChart"></canvas>
        </div>

        <!-- Order Status Distribution -->
        <div class="seller-chart-card">
            <h3>📦 Order Status</h3>
            <canvas id="sellerStatusChart"></canvas>
        </div>

    </div>

    <!-- LOW STOCK ALERT -->
    <?php if ($low_stock->num_rows > 0): ?>
    <div class="card" style="padding:20px;margin-bottom:24px;background:rgba(247,201,72,0.1);border-left:4px solid var(--taxi);">
        <h3 style="margin-top:0;">⚠️ Low Stock Alert</h3>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;margin-top:12px;">
            <?php while ($product = $low_stock->fetch_assoc()): ?>
            <div style="background:#fff;padding:12px 16px;border-radius:12px;border:1px solid var(--border);">
                <strong><?= htmlspecialchars($product['title']) ?></strong>
                <div>
                    <span class="low-stock-badge <?= $product['quantity'] <= 3 ? 'critical-stock' : '' ?>">
                        <?= $product['quantity'] ?> units left
                    </span>
                </div>
                <a href="edit_product.php?id=<?= $product['product_id'] ?>" style="font-size:0.8rem;color:var(--primary);">
                    Update stock →
                </a>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- PRODUCT PERFORMANCE TABLE -->
    <div class="card" style="padding:24px;margin-bottom:24px;">
        <div class="section-head" style="margin-bottom:16px;">
            <div>
                <h3>🏆 Product Performance</h3>
                <p class="text-muted">Sales performance of your products.</p>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Sold</th>
                        <th>Revenue</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($products->num_rows > 0): ?>
                        <?php while ($p = $products->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($p['title']) ?></strong>
                            </td>
                            <td>R <?= number_format($p['price'], 2) ?></td>
                            <td>
                                <?php if ($p['quantity'] <= 0): ?>
                                    <span style="color:var(--danger);">Out of stock</span>
                                <?php elseif ($p['quantity'] <= 5): ?>
                                    <span style="color:var(--sunset);"><?= $p['quantity'] ?> left</span>
                                <?php else: ?>
                                    <?= $p['quantity'] ?>
                                <?php endif; ?>
                            </td>
                            <td><?= $p['total_sold'] ?? 0 ?></td>
                            <td>R <?= number_format($p['revenue'] ?? 0, 2) ?></td>
                            <td>
                                <span class="badge <?= $p['status'] === 'active' ? 'badge-active' : 'badge-suspended' ?>">
                                    <?= strtoupper($p['status']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align:center;color:var(--muted);">
                                No products listed yet. <a href="sell.php" style="color:var(--primary);">Start selling →</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
// ============================================================
// SELLER CHARTS
// ============================================================

const sellerColors = {
    primary: '#5b2df5',
    secondary: '#f7c948',
    success: '#22c55e',
    danger: '#dc2626',
    warning: '#f97316',
};

// 1. DAILY REVENUE CHART
const ctx1 = document.getElementById('sellerDailyChart').getContext('2d');
new Chart(ctx1, {
    type: 'bar',
    data: {
        labels: <?= json_encode($daily_dates) ?>,
        datasets: [{
            label: 'Revenue (R)',
            data: <?= json_encode($daily_revenue) ?>,
            backgroundColor: 'rgba(91,45,245,0.6)',
            borderColor: '#5b2df5',
            borderWidth: 2,
            borderRadius: 6,
            order: 1
        },
        {
            label: 'Orders',
            data: <?= json_encode($daily_orders) ?>,
            type: 'line',
            borderColor: '#f7c948',
            backgroundColor: 'rgba(247,201,72,0.1)',
            borderWidth: 3,
            pointBackgroundColor: '#f7c948',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 4,
            tension: 0.3,
            order: 0,
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                labels: { font: { weight: '600' } }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { callback: function(v) { return 'R' + v.toLocaleString(); } }
            },
            y1: {
                position: 'right',
                beginAtZero: true,
                grid: { display: false },
                ticks: { stepSize: 1 }
            }
        }
    }
});

// 2. MONTHLY REVENUE CHART
const ctx2 = document.getElementById('sellerMonthlyChart').getContext('2d');
new Chart(ctx2, {
    type: 'line',
    data: {
        labels: <?= json_encode($monthly_labels) ?>,
        datasets: [{
            label: 'Monthly Revenue',
            data: <?= json_encode($monthly_revenue) ?>,
            backgroundColor: 'rgba(91,45,245,0.1)',
            borderColor: '#5b2df5',
            borderWidth: 3,
            fill: true,
            tension: 0.3,
            pointBackgroundColor: '#5b2df5',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 5,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                labels: { font: { weight: '600' } }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { callback: function(v) { return 'R' + v.toLocaleString(); } }
            }
        }
    }
});

// 3. ORDER STATUS CHART
const ctx3 = document.getElementById('sellerStatusChart').getContext('2d');
new Chart(ctx3, {
    type: 'doughnut',
    data: {
        labels: ['Pending', 'Paid', 'Shipped', 'Delivered'],
        datasets: [{
            data: [
                <?= $pending_count ?>,
                <?= $paid_count ?>,
                <?= $shipped_count ?>,
                <?= $delivered_count ?>
            ],
            backgroundColor: ['#f7c948', '#5b2df5', '#16a34a', '#22c55e'],
            borderColor: 'rgba(255,255,255,0.2)',
            borderWidth: 2,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 12,
                    font: { weight: '600' }
                }
            }
        },
        cutout: '60%'
    }
});
</script>

<?php include "INCLUDES/footer.php"; ?>
