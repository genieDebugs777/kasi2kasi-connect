<?php
require_once "../INCLUDES/db.php";
require_once "../INCLUDES/admin_auth.php";

requireRole(["Super Admin"]);

// ============================================================
// FETCH DATA FOR CHARTS
// ============================================================

// 1. REVENUE DATA (Last 7 days)
$revenue_stmt = $conn->prepare("
    SELECT 
        DATE(created_at) AS date,
        SUM(total_amount) AS revenue,
        COUNT(*) AS order_count
    FROM orders
    WHERE status IN ('paid', 'shipped', 'delivered')
        AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");
$revenue_stmt->execute();
$revenue_data = $revenue_stmt->get_result();

$revenue_dates = [];
$revenue_amounts = [];
$order_counts = [];

while ($row = $revenue_data->fetch_assoc()) {
    $revenue_dates[] = date("d M", strtotime($row['date']));
    $revenue_amounts[] = floatval($row['revenue']);
    $order_counts[] = intval($row['order_count']);
}

// 2. ORDER STATUS DISTRIBUTION
$status_stmt = $conn->query("
    SELECT 
        status,
        COUNT(*) AS count
    FROM orders
    GROUP BY status
");

$status_labels = [];
$status_counts = [];
$status_colors = [
    'pending' => '#f7c948',
    'paid' => '#5b2df5',
    'shipped' => '#16a34a',
    'delivered' => '#22c55e',
    'cancelled' => '#dc2626'
];

while ($row = $status_stmt->fetch_assoc()) {
    $status_labels[] = ucfirst($row['status']);
    $status_counts[] = intval($row['count']);
}

// 3. USER GROWTH (Last 30 days)
$user_stmt = $conn->prepare("
    SELECT 
        DATE(created_at) AS date,
        COUNT(*) AS new_users
    FROM user
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");
$user_stmt->execute();
$user_data = $user_stmt->get_result();

$user_dates = [];
$user_counts = [];

while ($row = $user_data->fetch_assoc()) {
    $user_dates[] = date("d M", strtotime($row['date']));
    $user_counts[] = intval($row['new_users']);
}

// 4. CATEGORY PERFORMANCE
$category_stmt = $conn->query("
    SELECT 
        category.name AS category_name,
        COUNT(order_item.order_item_id) AS items_sold,
        SUM(order_item.quantity) AS total_quantity,
        SUM(order_item.unit_price * order_item.quantity) AS total_revenue
    FROM category
    LEFT JOIN product ON category.category_id = product.category_id
    LEFT JOIN order_item ON product.product_id = order_item.product_id
    LEFT JOIN orders ON order_item.order_id = orders.order_id
    WHERE orders.status IN ('paid', 'shipped', 'delivered')
    GROUP BY category.category_id
    ORDER BY total_revenue DESC
    LIMIT 10
");

$category_labels = [];
$category_revenues = [];
$category_colors = ['#5b2df5', '#f7c948', '#16a34a', '#dc2626', '#f97316', '#8b5cf6', '#ec4899', '#14b8a6', '#f59e0b', '#6366f1'];

while ($row = $category_stmt->fetch_assoc()) {
    $category_labels[] = $row['category_name'] ?? 'Uncategorized';
    $category_revenues[] = floatval($row['total_revenue'] ?? 0);
}

// 5. PLATFORM METRICS
$total_users = $conn->query("SELECT COUNT(*) AS c FROM user")->fetch_assoc()["c"];
$total_orders = $conn->query("SELECT COUNT(*) AS c FROM orders")->fetch_assoc()["c"];
$total_revenue = $conn->query("SELECT SUM(total_amount) AS c FROM orders WHERE status IN ('paid', 'shipped', 'delivered')")->fetch_assoc()["c"] ?? 0;
$active_products = $conn->query("SELECT COUNT(*) AS c FROM product WHERE status='active'")->fetch_assoc()["c"];
$pending_orders = $conn->query("SELECT COUNT(*) AS c FROM orders WHERE status='pending'")->fetch_assoc()["c"];
$verified_sellers = $conn->query("SELECT COUNT(*) AS c FROM user WHERE is_verified=1")->fetch_assoc()["c"];

// 6. MONTHLY REVENUE (Last 12 months)
$monthly_stmt = $conn->prepare("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') AS month,
        SUM(total_amount) AS revenue,
        COUNT(*) AS orders
    FROM orders
    WHERE status IN ('paid', 'shipped', 'delivered')
        AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
");
$monthly_stmt->execute();
$monthly_data = $monthly_stmt->get_result();

$monthly_labels = [];
$monthly_revenue = [];

while ($row = $monthly_data->fetch_assoc()) {
    $monthly_labels[] = date("M Y", strtotime($row['month'] . '-01'));
    $monthly_revenue[] = floatval($row['revenue']);
}

// 7. TOP SELLING PRODUCTS
$top_products_stmt = $conn->query("
    SELECT 
        product.title,
        SUM(order_item.quantity) AS total_sold,
        SUM(order_item.unit_price * order_item.quantity) AS total_revenue
    FROM order_item
    JOIN product ON order_item.product_id = product.product_id
    JOIN orders ON order_item.order_id = orders.order_id
    WHERE orders.status IN ('paid', 'shipped', 'delivered')
    GROUP BY order_item.product_id
    ORDER BY total_sold DESC
    LIMIT 5
");

$top_products = [];
while ($row = $top_products_stmt->fetch_assoc()) {
    $top_products[] = $row;
}
?>

<?php include "../INCLUDES/header.php"; ?>

<style>
.analytics-dashboard {
    padding: 20px 0;
}

.metric-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.metric-card {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 16px;
    padding: 20px;
    text-align: center;
}

.metric-card .value {
    font-size: 2rem;
    font-weight: 900;
    color: #fff;
    margin: 8px 0;
}

.metric-card .label {
    color: rgba(255,255,255,0.6);
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.metric-card .change {
    font-size: 0.8rem;
    margin-top: 8px;
}

.metric-card .change.positive {
    color: #22c55e;
}
.metric-card .change.negative {
    color: #dc2626;
}

.chart-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-bottom: 24px;
}

.chart-card {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 16px;
    padding: 20px;
}

.chart-card h3 {
    color: #fff;
    margin-top: 0;
    margin-bottom: 16px;
    font-size: 1.1rem;
}

.chart-card canvas {
    max-height: 300px;
    max-width: 100%;
}

.full-width {
    grid-column: 1 / -1;
}

/* Responsive */
@media (max-width: 900px) {
    .chart-grid {
        grid-template-columns: 1fr;
    }
    .full-width {
        grid-column: 1;
    }
}

.admin-tabs {
    display: flex;
    gap: 12px;
    margin-bottom: 24px;
    flex-wrap: wrap;
}

.admin-tab {
    padding: 10px 20px;
    border-radius: 12px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    color: rgba(255,255,255,0.6);
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
}

.admin-tab:hover {
    background: rgba(255,255,255,0.1);
}

.admin-tab.active {
    background: rgba(247,201,72,0.2);
    border-color: var(--taxi);
    color: var(--taxi);
}
</style>

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
            <a href="users.php">👥 Users</a>
            <a href="roles.php">🔐 Roles</a>
            <a href="verify.php">✅ Verifications</a>
            <a href="products.php">🛍 Products</a>
            <a class="active" href="dashboard_analytics.php">📈 Analytics</a>
            <a href="../index.php">↩ Back to Site</a>
        </nav>
    </aside>

    <main class="admin-main-panel">
        <section class="admin-hero-pro">
            <div>
                <span class="admin-chip">📈 ANALYTICS</span>
                <h1>Platform Analytics Dashboard</h1>
                <p>Track revenue, user growth, order trends, and category performance.</p>
            </div>
            <div class="admin-orb">
                <span>📊</span>
            </div>
        </section>

        <!-- METRICS -->
        <div class="metric-grid">
            <div class="metric-card">
                <div class="label">Total Revenue</div>
                <div class="value">R <?= number_format($total_revenue, 2) ?></div>
                <div class="change positive">↑ Lifetime sales</div>
            </div>
            <div class="metric-card">
                <div class="label">Total Orders</div>
                <div class="value"><?= $total_orders ?></div>
                <div class="change positive">↗ All time</div>
            </div>
            <div class="metric-card">
                <div class="label">Pending Orders</div>
                <div class="value" style="color: var(--sunset);"><?= $pending_orders ?></div>
                <div class="change negative">⏳ Awaiting action</div>
            </div>
            <div class="metric-card">
                <div class="label">Active Products</div>
                <div class="value"><?= $active_products ?></div>
                <div class="change positive">🛍 Listings live</div>
            </div>
            <div class="metric-card">
                <div class="label">Total Users</div>
                <div class="value"><?= $total_users ?></div>
                <div class="change positive">👥 Registered</div>
            </div>
            <div class="metric-card">
                <div class="label">Verified Sellers</div>
                <div class="value"><?= $verified_sellers ?></div>
                <div class="change positive">✅ Trusted</div>
            </div>
        </div>

        <!-- TABS -->
        <div class="admin-tabs">
            <a href="#revenue" class="admin-tab active" onclick="showTab('revenue')">📈 Revenue</a>
            <a href="#orders" class="admin-tab" onclick="showTab('orders')">📦 Orders</a>
            <a href="#users" class="admin-tab" onclick="showTab('users')">👥 Users</a>
            <a href="#categories" class="admin-tab" onclick="showTab('categories')">📊 Categories</a>
        </div>

        <!-- CHARTS -->
        <div class="chart-grid" id="chartContainer">

            <!-- Revenue Trend -->
            <div class="chart-card full-width" id="revenue-tab">
                <h3>📈 Revenue Trend (Last 7 Days)</h3>
                <canvas id="revenueChart"></canvas>
            </div>

            <!-- Order Status -->
            <div class="chart-card" id="orders-tab">
                <h3>📦 Order Status Distribution</h3>
                <canvas id="statusChart"></canvas>
            </div>

            <!-- User Growth -->
            <div class="chart-card" id="users-tab">
                <h3>👥 User Growth (Last 30 Days)</h3>
                <canvas id="userChart"></canvas>
            </div>

            <!-- Category Performance -->
            <div class="chart-card" id="categories-tab">
                <h3>📊 Top Categories by Revenue</h3>
                <canvas id="categoryChart"></canvas>
            </div>

            <!-- Monthly Revenue -->
            <div class="chart-card full-width">
                <h3>📊 Monthly Revenue (Last 12 Months)</h3>
                <canvas id="monthlyChart"></canvas>
            </div>

        </div>

        <!-- TOP PRODUCTS TABLE -->
        <div class="admin-glass-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>🏆 Top Selling Products</h2>
                    <p>Best performing products on the platform.</p>
                </div>
            </div>
            <div class="admin-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Product</th>
                            <th>Units Sold</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($top_products)): ?>
                            <?php $rank = 1; foreach ($top_products as $product): ?>
                            <tr>
                                <td><strong>#<?= $rank++ ?></strong></td>
                                <td><?= htmlspecialchars($product['title']) ?></td>
                                <td><?= $product['total_sold'] ?></td>
                                <td>R <?= number_format($product['total_revenue'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center;color:rgba(255,255,255,0.5);">No sales data yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<script>
// ============================================================
// CHART.JS CONFIGURATION
// ============================================================

// Colors for charts
const colors = {
    primary: '#5b2df5',
    secondary: '#f7c948',
    success: '#22c55e',
    danger: '#dc2626',
    warning: '#f97316',
    purple: '#8b5cf6',
    pink: '#ec4899',
    cyan: '#06b6d4',
    teal: '#14b8a6',
    amber: '#f59e0b',
    indigo: '#6366f1',
    rose: '#f43f5e',
};

const chartColors = [
    colors.primary, colors.secondary, colors.success, colors.danger,
    colors.warning, colors.purple, colors.pink, colors.cyan,
    colors.teal, colors.amber, colors.indigo, colors.rose
];

// Chart defaults
Chart.defaults.color = 'rgba(255,255,255,0.7)';
Chart.defaults.borderColor = 'rgba(255,255,255,0.1)';
Chart.defaults.font.family = "'Inter', sans-serif";

// ============================================================
// 1. REVENUE CHART
// ============================================================
const ctx1 = document.getElementById('revenueChart').getContext('2d');
new Chart(ctx1, {
    type: 'bar',
    data: {
        labels: <?= json_encode($revenue_dates) ?>,
        datasets: [{
            label: 'Revenue (R)',
            data: <?= json_encode($revenue_amounts) ?>,
            backgroundColor: 'rgba(91,45,245,0.6)',
            borderColor: '#5b2df5',
            borderWidth: 2,
            borderRadius: 8,
            order: 1
        },
        {
            label: 'Orders',
            data: <?= json_encode($order_counts) ?>,
            type: 'line',
            borderColor: '#f7c948',
            backgroundColor: 'rgba(247,201,72,0.1)',
            borderWidth: 3,
            pointBackgroundColor: '#f7c948',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 5,
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
                labels: {
                    color: 'rgba(255,255,255,0.8)',
                    font: { weight: '600' }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(255,255,255,0.05)' },
                ticks: { 
                    callback: function(value) { return 'R' + value.toLocaleString(); }
                }
            },
            y1: {
                position: 'right',
                beginAtZero: true,
                grid: { display: false },
                ticks: { 
                    stepSize: 1
                }
            }
        }
    }
});

// ============================================================
// 2. ORDER STATUS CHART (PIE)
// ============================================================
const ctx2 = document.getElementById('statusChart').getContext('2d');
new Chart(ctx2, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($status_labels) ?>,
        datasets: [{
            data: <?= json_encode($status_counts) ?>,
            backgroundColor: ['#f7c948', '#5b2df5', '#16a34a', '#22c55e', '#dc2626'],
            borderColor: 'rgba(0,0,0,0.2)',
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
                    color: 'rgba(255,255,255,0.8)',
                    padding: 16,
                    font: { weight: '600' }
                }
            }
        },
        cutout: '60%'
    }
});

// ============================================================
// 3. USER GROWTH CHART
// ============================================================
const ctx3 = document.getElementById('userChart').getContext('2d');
new Chart(ctx3, {
    type: 'line',
    data: {
        labels: <?= json_encode($user_dates) ?>,
        datasets: [{
            label: 'New Users',
            data: <?= json_encode($user_counts) ?>,
            backgroundColor: 'rgba(91,45,245,0.1)',
            borderColor: '#5b2df5',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#5b2df5',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 4,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                labels: {
                    color: 'rgba(255,255,255,0.8)',
                    font: { weight: '600' }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(255,255,255,0.05)' },
                ticks: { stepSize: 1 }
            },
            x: {
                grid: { display: false }
            }
        }
    }
});

// ============================================================
// 4. CATEGORY CHART
// ============================================================
const ctx4 = document.getElementById('categoryChart').getContext('2d');
new Chart(ctx4, {
    type: 'bar',
    data: {
        labels: <?= json_encode($category_labels) ?>,
        datasets: [{
            label: 'Revenue by Category',
            data: <?= json_encode($category_revenues) ?>,
            backgroundColor: <?= json_encode(array_slice($category_colors, 0, count($category_labels))) ?>,
            borderRadius: 8,
            borderSkipped: false,
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            x: {
                beginAtZero: true,
                grid: { color: 'rgba(255,255,255,0.05)' },
                ticks: { 
                    callback: function(value) { return 'R' + value.toLocaleString(); }
                }
            },
            y: {
                grid: { display: false }
            }
        }
    }
});

// ============================================================
// 5. MONTHLY REVENUE CHART
// ============================================================
const ctx5 = document.getElementById('monthlyChart').getContext('2d');
new Chart(ctx5, {
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
                labels: {
                    color: 'rgba(255,255,255,0.8)',
                    font: { weight: '600' }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(255,255,255,0.05)' },
                ticks: { 
                    callback: function(value) { return 'R' + value.toLocaleString(); }
                }
            },
            x: {
                grid: { display: false }
            }
        }
    }
});

// ============================================================
// TAB SWITCHING
// ============================================================
function showTab(tabId) {
    // Update tabs
    document.querySelectorAll('.admin-tab').forEach(tab => tab.classList.remove('active'));
    document.querySelector(`.admin-tab[href="#${tabId}"]`)?.classList.add('active');
    
    // Show/hide charts
    const sections = {
        'revenue': 'revenue-tab',
        'orders': 'orders-tab',
        'users': 'users-tab',
        'categories': 'categories-tab'
    };
    
    // Show all charts by default, but highlight the active tab's parent
    // This is a simple implementation - you can make it more sophisticated
}
</script>

<?php include "../INCLUDES/footer.php"; ?>
