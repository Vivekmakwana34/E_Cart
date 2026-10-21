<?php
session_start();
require_once __DIR__ . '/../Database_file/config.php';

// Fetch real data from database

// Total Revenue and Orders
$ordersQuery = "SELECT 
    COUNT(*) as total_orders,
    COALESCE(SUM(total_amount), 0) as total_revenue,
    COALESCE(AVG(total_amount), 0) as avg_order_value
FROM orders 
WHERE payment_status = 'paid' OR payment_status = 'completed'";
$ordersResult = $conn->query($ordersQuery);
$ordersData = $ordersResult->fetch_assoc();

// New Customers (this month)
$newCustomersQuery = "SELECT COUNT(*) as new_customers FROM users 
WHERE created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')";
$newCustomersResult = $conn->query($newCustomersQuery);
$newCustomersData = $newCustomersResult->fetch_assoc();

// Total Customers
$totalCustomersQuery = "SELECT COUNT(*) as total_customers FROM users";
$totalCustomersResult = $conn->query($totalCustomersQuery);
$totalCustomersData = $totalCustomersResult->fetch_assoc();

// Monthly Sales Data (last 6 months)
$monthlySalesQuery = "SELECT 
    DATE_FORMAT(created_at, '%b') as month,
    COALESCE(SUM(total_amount), 0) as revenue,
    COUNT(*) as orders
FROM orders 
WHERE payment_status IN ('paid', 'completed')
    AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
GROUP BY DATE_FORMAT(created_at, '%Y-%m'), DATE_FORMAT(created_at, '%b')
ORDER BY DATE_FORMAT(created_at, '%Y-%m') ASC
LIMIT 6";
$monthlySalesResult = $conn->query($monthlySalesQuery);
$monthlyData = [];
while ($row = $monthlySalesResult->fetch_assoc()) {
    $monthlyData[] = $row;
}

// Category-wise Sales (simplified - no product join)
$categoryQuery = "SELECT 
    'Electronics' as category,
    COALESCE(SUM(CASE WHEN o.total_amount > 0 THEN o.total_amount ELSE 0 END), 0) as revenue,
    COUNT(*) as orders
FROM orders o
WHERE o.payment_status IN ('paid', 'completed')
LIMIT 1";
$categoryResult = $conn->query($categoryQuery);
$categoryData = [];
while ($row = $categoryResult->fetch_assoc()) {
    $categoryData[] = $row;
}

// Add default categories if no data
if (empty($categoryData) || $categoryData[0]['orders'] == 0) {
    $categoryData = [
        ['category' => 'Electronics', 'revenue' => 0, 'orders' => 0],
        ['category' => 'Clothing', 'revenue' => 0, 'orders' => 0],
        ['category' => 'Home', 'revenue' => 0, 'orders' => 0]
    ];
}

// Best Selling Products (simplified - no product join)
$bestProducts = [];

// Order Status Distribution
$statusQuery = "SELECT 
    payment_status,
    COUNT(*) as count
FROM orders
GROUP BY payment_status";
$statusResult = $conn->query($statusQuery);
$statusData = [];
while ($row = $statusResult->fetch_assoc()) {
    $statusData[$row['payment_status']] = $row['count'];
}

// Default data if no orders exist
if (empty($monthlyData)) {
    $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
    foreach ($months as $month) {
        $monthlyData[] = ['month' => $month, 'revenue' => 0, 'orders' => 0];
    }
}

if (empty($categoryData)) {
    $categoryData = [
        ['category' => 'Electronics', 'revenue' => 0, 'orders' => 0],
        ['category' => 'Clothing', 'revenue' => 0, 'orders' => 0],
        ['category' => 'Home', 'revenue' => 0, 'orders' => 0]
    ];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Reports & Analytics - Admin Panel</title>
    <link rel="stylesheet" href="../admin/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
    <style>
        /* Additional styles for reports page */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .stat-card i {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: #ff9800;
        }

        .stat-card h3 {
            font-size: 2rem;
            margin-bottom: 5px;
            color: #333;
        }

        .stat-card p {
            color: #666;
            font-size: 0.9rem;
        }

        .charts-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            min-height: 450px;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .chart-card h3 {
            margin-bottom: 20px;
            color: #333;
            font-size: 1.2rem;
        }

        .chart-card canvas {
            flex: 1;
            max-height: 350px !important;
            width: 100% !important;
            position: relative !important;
            z-index: 1 !important;
        }

        .table-container {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            overflow-x: auto;
        }

        .table-container h3 {
            margin-bottom: 20px;
            color: #333;
            font-size: 1.2rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        @media (max-width: 768px) {
            .charts-container {
                grid-template-columns: 1fr;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <nav class="navbar" id="sidebarNav">
            <div class="navbar-header">
                <h2>Admin Panel</h2>
                <button class="navbar-toggle" id="navbarToggle" aria-label="Toggle navigation">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
            <ul>
                <li><a href="home.php"><i class="fa-solid fa-gauge" style="margin-right: 8px;"></i>Dashboard</a></li>
                <li><a href="users.php"><i class="fa-solid fa-users" style="margin-right: 8px;"></i>Users</a></li>
                <li><a href="banner.php"><i class="fa-solid fa-image" style="margin-right: 8px;"></i>Banner</a></li>
                <li><a href="categories.php"><i class="fa-solid fa-list" style="margin-right: 8px;"></i>Categories</a></li>
                <li><a href="products.php"><i class="fa-solid fa-box" style="margin-right: 8px;"></i>Products</a></li>
                <li><a href="orders.php"><i class="fa-solid fa-cart-shopping" style="margin-right: 8px;"></i>Orders</a></li>
                <li><a href="coupons.php"><i class="fa-solid fa-ticket" style="margin-right: 8px;"></i>Coupons</a></li>
                <li><a href="Gift.php"><i class="fa-solid fa-gift" style="margin-right: 8px;"></i>Gift Cards</a></li>
                <li><a href="payments.php"><i class="fa-solid fa-credit-card" style="margin-right: 8px;"></i>Payments</a></li>
                <li><a href="shipping.php"><i class="fa-solid fa-truck" style="margin-right: 8px;"></i>Shipping</a></li>
                <li><a href="reports.php" class="active"><i class="fa-solid fa-chart-line" style="margin-right: 8px;"></i>Reports</a></li>
                <li><a href="reviews.php"><i class="fa-solid fa-star" style="margin-right: 8px;"></i>Reviews</a></li>
                <li><a href="transactions.php"><i class="fa-solid fa-wallet" style="margin-right: 8px;"></i>Transactions</a></li>
                <li><a href="contact.php"><i class="fa-solid fa-envelope" style="margin-right: 8px;"></i>Contact</a></li>
                <li><a href="support.php"><i class="fa-solid fa-headset" style="margin-right: 8px;"></i>Support</a></li>
                <li><a href="settings.php"><i class="fa-solid fa-gear" style="margin-right: 8px;"></i>Settings</a></li>
            </ul>
        </nav>
        <main class="main-content">
            <header>
                <h1>Reports & Analytics</h1>
            </header>

            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-dollar-sign"></i>
                    <h3>₹<?php echo number_format($ordersData['total_revenue'] ?? 0); ?></h3>
                    <p>Total Revenue</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-shopping-cart"></i>
                    <h3><?php echo number_format($ordersData['total_orders'] ?? 0); ?></h3>
                    <p>Total Orders</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-users"></i>
                    <h3><?php echo number_format($newCustomersData['new_customers'] ?? 0); ?></h3>
                    <p>New Customers (This Month)</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-percentage"></i>
                    <h3><?php echo $totalCustomersData['total_customers'] > 0 ? round((($newCustomersData['new_customers'] ?? 0) / $totalCustomersData['total_customers']) * 100) : 0; ?>%</h3>
                    <p>Conversion Rate</p>
                </div>
            </div>

            <div class="charts-container">
                <div class="chart-card">
                    <h3>Sales Overview</h3>
                    <canvas id="salesChart"></canvas>
                </div>
                <div class="chart-card">
                    <h3>Traffic Sources</h3>
                    <canvas id="trafficChart"></canvas>
                </div>
            </div>

            <!-- Category-wise Stats -->
            <div class="stats-grid">
                <?php 
                $categoryIcons = [
                    'Electronics' => 'fa-mobile-alt',
                    'Clothing' => 'fa-tshirt',
                    'Home' => 'fa-home',
                    'Home & Living' => 'fa-home',
                    'Books' => 'fa-book',
                    'Others' => 'fa-ellipsis-h'
                ];
                $categoryColors = [
                    'Electronics' => '#ff9800',
                    'Clothing' => '#2874f0',
                    'Home' => '#4caf50',
                    'Home & Living' => '#4caf50',
                    'Books' => '#f44336',
                    'Others' => '#9c27b0'
                ];
                $totalRevenue = array_sum(array_column($categoryData, 'revenue'));
                foreach ($categoryData as $cat): 
                    $percentage = $totalRevenue > 0 ? round(($cat['revenue'] / $totalRevenue) * 100) : 0;
                    $icon = $categoryIcons[$cat['category']] ?? 'fa-box';
                    $color = $categoryColors[$cat['category']] ?? '#666';
                ?>
                <div class="stat-card">
                    <i class="fas <?php echo $icon; ?>" style="color: <?php echo $color; ?>"></i>
                    <h3><?php echo $percentage; ?>%</h3>
                    <p><?php echo htmlspecialchars($cat['category']); ?></p>
                    <div style="margin-top: 10px; font-size: 0.8rem; color: #666;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span>Orders:</span>
                            <strong><?php echo number_format($cat['orders']); ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>Revenue:</span>
                            <strong>₹<?php echo number_format($cat['revenue']); ?></strong>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="table-container">
                <h3>Best Selling Products</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Units Sold</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($bestProducts)): ?>
                            <?php foreach ($bestProducts as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo htmlspecialchars($product['category']); ?></td>
                                <td><?php echo number_format($product['units_sold']); ?></td>
                                <td>₹<?php echo number_format($product['revenue']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 20px;">No sales data available yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        // Sidebar Toggle
        const navbarToggle = document.getElementById('navbarToggle');
        const sidebarNav = document.getElementById('sidebarNav');

        navbarToggle.addEventListener('click', () => {
            sidebarNav.classList.toggle('open');
        });

        // Sales Chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($monthlyData, 'month')); ?>,
                datasets: [{
                    label: 'Revenue (₹)',
                    data: <?php echo json_encode(array_column($monthlyData, 'revenue')); ?>,
                    borderColor: '#ff9800',
                    backgroundColor: 'rgba(255, 152, 0, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₹' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // Traffic Chart (Category-wise)
        const trafficCtx = document.getElementById('trafficChart').getContext('2d');
        Chart.register(ChartDataLabels);
        
        const categoryData = <?php echo json_encode($categoryData); ?>;
        const categoryLabels = categoryData.map(item => item.category);
        const categoryRevenues = categoryData.map(item => item.revenue);
        const totalRevenue = categoryRevenues.reduce((a, b) => a + b, 0);
        const categoryPercentages = categoryRevenues.map(rev => totalRevenue > 0 ? Math.round((rev / totalRevenue) * 100) : 0);
        
        // Make sure chart renders after DOM is ready
        setTimeout(() => {
            new Chart(trafficCtx, {
                type: 'doughnut',
                data: {
                    labels: categoryLabels,
                    datasets: [{
                        data: categoryPercentages,
                        backgroundColor: [
                            '#ff9800', // Electronics - Orange
                            '#2874f0', // Clothing - Blue
                            '#4caf50', // Home & Living - Green
                            '#f44336', // Books - Red
                            '#9c27b0'  // Others - Purple
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: {
                        padding: 20
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed;
                                    const revenue = categoryRevenues[context.dataIndex];
                                    return label + ': ' + value + '% (₹' + revenue.toLocaleString() + ')';
                                }
                            }
                        },
                        datalabels: {
                            color: '#fff',
                            font: {
                                weight: 'bold',
                                size: 14
                            },
                            formatter: (value, ctx) => {
                                const label = ctx.chart.data.labels[ctx.dataIndex];
                                const shortLabel = label.split(' ')[0];
                                return [value + '%', shortLabel];
                            },
                            textAlign: 'center'
                        }
                    }
                }
            });
        }, 100);
    </script>
</body>
</html>
