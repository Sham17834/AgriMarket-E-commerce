<?php
require_once 'db_connect.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Fetch admin username for display
$stmt = $pdo->prepare("SELECT username FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch();
$adminName = $admin['username'] ?? 'Admin';

// Fetch all users
$users_stmt = $pdo->query("SELECT user_id, username, email, role FROM users");
$users = $users_stmt->fetchAll();

// ===== REAL DATA FROM DATABASE =====
// Most Ordered Products 
$ordered_stmt = $pdo->query("SELECT p.name, SUM(oi.quantity) as total_ordered 
                             FROM order_items oi 
                             JOIN products p ON oi.product_id = p.product_id 
                             GROUP BY p.product_id 
                             ORDER BY total_ordered DESC 
                             LIMIT 5");
$most_ordered_products = $ordered_stmt->fetchAll();

// Sales Reports 
$periods = [
    'weekly' => '%Y-%u',
    'monthly' => '%Y-%m',
    'quarterly' => '%Y-%q',
    'annually' => '%Y'
];
$sales_reports = [];
foreach ($periods as $period => $date_format) {
    $stmt = $pdo->query("SELECT DATE_FORMAT(o.order_date, '$date_format') AS period, 
                                SUM(o.total_amount) AS total_sales, 
                                COUNT(o.order_id) AS order_count 
                         FROM orders o 
                         WHERE o.order_status = 'delivered' 
                         GROUP BY DATE_FORMAT(o.order_date, '$date_format') 
                         ORDER BY MAX(o.order_date) DESC 
                         LIMIT 4");
    $sales_reports[$period] = $stmt->fetchAll();
}


function generateFakeStats($base_value, $variation = 0.3)
{
    return $base_value * (1 + (rand(-100, 100) / 100 * $variation));
}

// Most Searched Products 
$fake_searches = [
    ['name' => 'Organic Apples', 'search_count' => rand(120, 180)],
    ['name' => 'Free Range Eggs', 'search_count' => rand(90, 140)],
    ['name' => 'Fresh Salmon', 'search_count' => rand(80, 120)],
    ['name' => 'Avocados', 'search_count' => rand(70, 110)],
    ['name' => 'Artisan Bread', 'search_count' => rand(60, 100)]
];

// Most Visited Product Pages 
$fake_views = [
    ['name' => 'Organic Apples', 'view_count' => rand(300, 500)],
    ['name' => 'Free Range Eggs', 'view_count' => rand(250, 400)],
    ['name' => 'Fresh Salmon', 'view_count' => rand(200, 350)],
    ['name' => 'Avocados', 'view_count' => rand(180, 300)],
    ['name' => 'Artisan Bread', 'view_count' => rand(150, 250)]
];

// User Activity 
$fake_user_activity = [
    ['day' => 'Mon', 'active' => rand(120, 180), 'new' => rand(15, 30)],
    ['day' => 'Tue', 'active' => rand(130, 190), 'new' => rand(20, 35)],
    ['day' => 'Wed', 'active' => rand(140, 200), 'new' => rand(25, 40)],
    ['day' => 'Thu', 'active' => rand(150, 210), 'new' => rand(30, 45)],
    ['day' => 'Fri', 'active' => rand(180, 240), 'new' => rand(35, 50)],
    ['day' => 'Sat', 'active' => rand(200, 280), 'new' => rand(40, 60)],
    ['day' => 'Sun', 'active' => rand(180, 250), 'new' => rand(30, 50)]
];

// Performance Metrics 
$performance_metrics = [
    'conversion_rate' => rand(25, 40) / 10,
    'avg_order_value' => rand(8500, 12000) / 100,
    'customer_satisfaction' => rand(85, 95)
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | FreshHarvest</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #3b82f6;
            --primary-light: #93c5fd;
            --primary-dark: #1d4ed8;
            --secondary: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #06b6d4;
        }

        /* Improved sidebar styling */
        .sidebar {
            width: 240px;
            transition: transform 0.3s ease;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
        }

        /* Better content area spacing */
        .content-area {
            margin-left: 240px;
            padding: 1.5rem;
            transition: margin 0.3s ease;
        }

        /* Responsive adjustments */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
                z-index: 50;
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .content-area {
                margin-left: 0;
            }
        }

        /* Card improvements */
        .card {
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            transition: all 0.2s ease;
        }

        .card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.07), 0 4px 6px -2px rgba(0, 0, 0, 0.03);
        }

        /* Metric card styling */
        .metric-card {
            padding: 1.25rem;
            display: flex;
            align-items: center;
        }

        .metric-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            margin-right: 1rem;
        }

        /* Better chart containers */
        .chart-container {
            height: 280px;
            position: relative;
        }

        /* Improved table styling */
        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .data-table th {
            text-align: left;
            padding: 0.75rem 1rem;
            background-color: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }

        .data-table td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .data-table tr:hover td {
            background-color: #f8fafc;
        }

        /* Better badge styling */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        /* Typography improvements */
        .dashboard-title {
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1.25;
        }

        .dashboard-subtitle {
            color: #64748b;
            font-size: 0.875rem;
        }

        /* Button improvements */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
        }

        .btn-secondary {
            background-color: white;
            border: 1px solid #e2e8f0;
            color: #334155;
        }

        .btn-secondary:hover {
            background-color: #f8fafc;
        }
    </style>
</head>

<body class="bg-gray-50 font-sans antialiased">
    <!-- Sidebar -->
    <aside class="sidebar fixed top-0 left-0 h-full bg-white">
        <div class="p-4 border-b border-gray-100">
            <div class="flex items-center space-x-2">
                <i class="ri-leaf-line text-2xl text-primary"></i>
                <span class="text-xl font-bold text-gray-800">AgriMarket</span>
            </div>
        </div>
        <nav class="p-4 space-y-1">
            <a href="#" class="flex items-center space-x-3 p-3 rounded-lg bg-blue-50 text-primary font-medium">
                <i class="ri-dashboard-line"></i>
                <span>Dashboard</span>
            </a>
            <a href="admin_analytics.php"
                class="flex items-center space-x-3 p-3 rounded-lg text-gray-600 hover:bg-blue-50 hover:text-primary transition-colors">
                <i class="ri-bar-chart-2-line"></i>
                <span>Analytics</span>
            </a>
            <a href="admin_reviews.php"
                class="flex items-center space-x-3 p-3 rounded-lg text-gray-600 hover:bg-blue-50 hover:text-primary transition-colors">
                <i class="ri-star-line"></i>
                <span>Verify Reviews</span>
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <div class="content-area">
        <!-- Top Navigation -->
        <header class="sticky top-0 z-40 bg-white border-b border-gray-100 py-4 px-6 flex items-center justify-between">
            <button id="menu-toggle" class="lg:hidden text-gray-600 hover:text-primary">
                <i class="ri-menu-line text-2xl"></i>
            </button>
            <div class="flex items-center space-x-2">
                <div class="flex items-center space-x-0">
                    <div
                        class="h-8 w-8 rounded-full bg-primary flex items-center justify-center text-white font-medium">
                        <?php echo strtoupper(substr($adminName, 0, 1)); ?>
                    </div>
                    <span class="font-medium"><?php echo htmlspecialchars($adminName); ?></span>
                </div>
                <a href="logout.php" class="p-2 rounded-full hover:bg-gray-100 text-red-500">
                    <i class="ri-logout-box-r-line text-xl"></i>
                </a>
            </div>
        </header>

        <!-- Dashboard Content -->
        <main class="py-6">
            <div class="max-w-7xl mx-auto">
                <!-- Dashboard Header -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
                    <div>
                        <h1 class="dashboard-title">Dashboard Overview</h1>
                        <p class="dashboard-subtitle">Welcome back, <?php echo htmlspecialchars($adminName); ?>! Here's
                            what's happening with your store today.</p>
                    </div>
                </div>

                <!-- Metrics Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Total Revenue -->
                    <div class="card metric-card">
                        <div class="metric-icon bg-primary">
                            <i class="ri-money-dollar-circle-line text-xl text-white"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Total Revenue</p>
                            <h3 class="text-2xl font-bold">RM
                                <?php echo number_format(rand(15000, 35000) / 100 * 1000, 2); ?>
                            </h3>
                            <p class="text-green-500 text-sm flex items-center">
                                <i class="ri-arrow-up-line mr-1"></i> <?php echo rand(5, 15); ?>% from last month
                            </p>
                        </div>
                    </div>

                    <!-- Total Orders -->
                    <div class="card metric-card">
                        <div class="metric-icon bg-secondary">
                            <i class="ri-shopping-cart-2-line text-xl text-white"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Total Orders</p>
                            <h3 class="text-2xl font-bold"><?php echo number_format(rand(200, 800)); ?></h3>
                            <p class="text-green-500 text-sm flex items-center">
                                <i class="ri-arrow-up-line mr-1"></i> <?php echo rand(3, 10); ?>% from last month
                            </p>
                        </div>
                    </div>

                    <!-- Conversion Rate -->
                    <div class="card metric-card">
                        <div class="metric-icon bg-warning">
                            <i class="ri-exchange-line text-xl text-white"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Conversion Rate</p>
                            <h3 class="text-2xl font-bold"><?php echo rand(25, 40) / 10; ?>%</h3>
                            <p class="text-red-500 text-sm flex items-center">
                                <i class="ri-arrow-down-line mr-1"></i> <?php echo rand(1, 3); ?>% from last month
                            </p>
                        </div>
                    </div>

                    <!-- Customer Satisfaction -->
                    <div class="card metric-card">
                        <div class="metric-icon bg-info">
                            <i class="ri-emotion-happy-line text-xl text-white"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Customer Satisfaction</p>
                            <h3 class="text-2xl font-bold"><?php echo rand(85, 95); ?>%</h3>
                            <p class="text-green-500 text-sm flex items-center">
                                <i class="ri-arrow-up-line mr-1"></i> <?php echo rand(2, 5); ?>% from last month
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Sales Trend -->
                    <div class="card p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-lg font-bold">Sales Trend</h2>
                            <select
                                class="text-sm border border-gray-200 rounded-lg px-3 py-1 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                <option>Last 7 Days</option>
                                <option selected>Last 30 Days</option>
                                <option>Last 90 Days</option>
                            </select>
                        </div>
                        <div class="chart-container">
                            <canvas id="salesTrendChart"></canvas>
                        </div>
                    </div>

                    <!-- User Activity -->
                    <div class="card p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-lg font-bold">User Activity</h2>
                            <select
                                class="text-sm border border-gray-200 rounded-lg px-3 py-1 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                <option>Last 7 Days</option>
                                <option selected>Last 30 Days</option>
                                <option>Last 90 Days</option>
                            </select>
                        </div>
                        <div class="chart-container">
                            <canvas id="userActivityChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Product Insights -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                    <!-- Most Ordered Products -->
                    <div class="card p-6">
                        <h2 class="text-lg font-bold mb-4">Most Ordered Products</h2>
                        <div class="space-y-4">
                            <?php foreach ($most_ordered_products as $product): ?>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div
                                            class="h-10 w-10 rounded-full bg-blue-50 flex items-center justify-center text-primary">
                                            <i class="ri-shopping-basket-line"></i>
                                        </div>
                                        <span class="truncate"><?php echo htmlspecialchars($product['name']); ?></span>
                                    </div>
                                    <span
                                        class="font-medium whitespace-nowrap"><?php echo $product['total_ordered']; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Most Searched Products -->
                    <div class="card p-6">
                        <h2 class="text-lg font-bold mb-4">Most Searched Products</h2>
                        <div class="space-y-4">
                            <?php foreach ($fake_searches as $product): ?>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div
                                            class="h-10 w-10 rounded-full bg-green-50 flex items-center justify-center text-green-500">
                                            <i class="ri-search-line"></i>
                                        </div>
                                        <span class="truncate"><?php echo htmlspecialchars($product['name']); ?></span>
                                    </div>
                                    <span
                                        class="font-medium whitespace-nowrap"><?php echo $product['search_count']; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Most Visited Products -->
                    <div class="card p-6">
                        <h2 class="text-lg font-bold mb-4">Most Visited Products</h2>
                        <div class="space-y-4">
                            <?php foreach ($fake_views as $product): ?>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div
                                            class="h-10 w-10 rounded-full bg-purple-50 flex items-center justify-center text-purple-500">
                                            <i class="ri-eye-line"></i>
                                        </div>
                                        <span class="truncate"><?php echo htmlspecialchars($product['name']); ?></span>
                                    </div>
                                    <span class="font-medium whitespace-nowrap"><?php echo $product['view_count']; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Recent Users Table -->
                <div class="card p-6 mb-8">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-bold">Recent Users</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($users, 0, 6) as $user): ?>
                                    <tr>
                                        <td class="font-medium"><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td class="text-gray-600"><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <span class="badge <?php
                                            echo $user['role'] === 'admin' ? 'bg-blue-100 text-blue-800' :
                                                ($user['role'] === 'vendor' ? 'bg-green-100 text-green-800' :
                                                    ($user['role'] === 'staff' ? 'bg-purple-100 text-purple-800' : 'bg-yellow-100 text-yellow-800'));
                                            ?>">
                                                <?php echo ucfirst(htmlspecialchars($user['role'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-green-100 text-green-800">Active</span>
                                        </td>
                                        <td>
                                            <button class="text-primary hover:text-primary-dark">
                                                <i class="ri-edit-line"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Sidebar toggle
        const menuToggle = document.getElementById('menu-toggle');
        const sidebar = document.querySelector('.sidebar');
        const contentArea = document.querySelector('.content-area');

        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });

        // Sales Trend Chart
        const salesTrendCtx = document.getElementById('salesTrendChart').getContext('2d');
        new Chart(salesTrendCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
                datasets: [{
                    label: 'Sales (RM)',
                    data: [12000, 19000, 15000, 22000, 18000, 25000, 28000],
                    borderColor: 'rgba(59, 130, 246, 1)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true,
                    borderWidth: 2,
                    pointBackgroundColor: 'white',
                    pointBorderColor: 'rgba(59, 130, 246, 1)',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: {
                            size: 14,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 12
                        },
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        grid: {
                            drawBorder: false,
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            callback: function (value) {
                                return 'RM' + value.toLocaleString();
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // User Activity Chart
        const userActivityCtx = document.getElementById('userActivityChart').getContext('2d');
        new Chart(userActivityCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($fake_user_activity, 'day')); ?>,
                datasets: [
                    {
                        label: 'Active Users',
                        data: <?php echo json_encode(array_column($fake_user_activity, 'active')); ?>,
                        backgroundColor: 'rgba(16, 185, 129, 0.7)',
                        borderRadius: 6,
                        borderWidth: 0
                    },
                    {
                        label: 'New Users',
                        data: <?php echo json_encode(array_column($fake_user_activity, 'new')); ?>,
                        backgroundColor: 'rgba(59, 130, 246, 0.7)',
                        borderRadius: 6,
                        borderWidth: 0
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: {
                            size: 14,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 12
                        },
                        padding: 12,
                        cornerRadius: 8
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            drawBorder: false,
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    </script>
</body>

</html>