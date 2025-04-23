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

// Most Ordered Products 
$ordered_stmt = $pdo->query("SELECT p.name, SUM(oi.quantity) as total_ordered 
                             FROM order_items oi 
                             JOIN products p ON oi.product_id = p.product_id 
                             GROUP BY p.product_id 
                             ORDER BY total_ordered DESC 
                             LIMIT 5");
$most_ordered_products = $ordered_stmt->fetchAll();

// Fake data for searches and views
$fake_searches = [
    ['name' => 'Organic Apples', 'search_count' => rand(120, 180)],
    ['name' => 'Free Range Eggs', 'search_count' => rand(90, 140)],
    ['name' => 'Fresh Salmon', 'search_count' => rand(80, 120)],
    ['name' => 'Avocados', 'search_count' => rand(70, 110)],
    ['name' => 'Artisan Bread', 'search_count' => rand(60, 100)]
];
$fake_views = [
    ['name' => 'Organic Apples', 'view_count' => rand(300, 500)],
    ['name' => 'Free Range Eggs', 'view_count' => rand(250, 400)],
    ['name' => 'Fresh Salmon', 'view_count' => rand(200, 350)],
    ['name' => 'Avocados', 'view_count' => rand(180, 300)],
    ['name' => 'Artisan Bread', 'view_count' => rand(150, 250)]
];

// Generate daily data for 2024
function generateDailyData($startDate = '2024-01-01', $days = 365) {
    $data = ['dates' => [], 'sales' => [], 'active_users' => [], 'new_users' => []];
    $currentDate = new DateTime($startDate);
    
    for ($i = 0; $i < $days; $i++) {
        $data['dates'][] = $currentDate->format('Y-m-d');
        
        // Sales: Base RM500, ±30%, weekend/holiday peaks
        $baseSales = 500;
        $variation = rand(-30, 30) / 100;
        $isWeekend = in_array($currentDate->format('N'), [6, 7]) ? 1.5 : 1;
        $isHoliday = in_array($currentDate->format('m-d'), ['01-01', '12-25']) ? 2 : 1;
        $data['sales'][] = round($baseSales * (1 + $variation) * $isWeekend * $isHoliday);
        
        // Active Users: Base 100, ±20%, weekend boost
        $baseActive = 100;
        $activeVariation = rand(-20, 20) / 100;
        $data['active_users'][] = round($baseActive * (1 + $activeVariation) * $isWeekend);
        
        // New Users: Base 10, ±50%, occasional spikes
        $baseNew = 10;
        $newVariation = rand(-50, 50) / 100;
        $spike = rand(1, 100) < 10 ? 3 : 1;
        $data['new_users'][] = round($baseNew * (1 + $newVariation) * $spike);
        
        $currentDate->modify('+1 day');
    }
    
    return $data;
}

$dailyData = generateDailyData();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | AgriMarket</title>
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
        .sidebar {
            width: 240px;
            transition: transform 0.3s ease;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
        }
        .content-area {
            margin-left: 240px;
            padding: 1.5rem;
            transition: margin 0.3s ease;
        }
        @media (max-width: 1024px) {
            .sidebar { transform: translateX(-100%); z-index: 50; }
            .sidebar.open { transform: translateX(0); }
            .content-area { margin-left: 0; }
        }
        .card {
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            transition: all 0.2s ease;
        }
        .card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.07), 0 4px 6px -2px rgba(0, 0, 0, 0.03);
        }
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
        .chart-container {
            height: 280px;
            position: relative;
            overflow-x: auto;
        }
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
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .dashboard-title {
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1.25;
        }
        .dashboard-subtitle {
            color: #64748b;
            font-size: 0.875rem;
        }
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
    <aside class="sidebar fixed top-0 left-0 h-full bg-white">
        <div class="p-4 border-b border-gray-100">
            <div class="flex items-center space-x-2">
                <i class="ri-leaf-line text-2xl text-primary"></i>
                <span class="text-xl font-bold{text-gray-800">AgriMarket</span>
            </div>
        </div>
        <nav class="p-4 space-y-1">
            <a href="#" class="flex items-center space-x-3 p-3 rounded-lg bg-blue-50 text-primary font-medium">
                <i class="ri-dashboard-line"></i>
                <span>Dashboard</span>
            </a>
            <a href="admin_reviews.php" class="flex items-center space-x-3 p-3 rounded-lg text-gray-600 hover:bg-blue-50 hover:text-primary transition-colors">
                <i class="ri-star-line"></i>
                <span>Verify Reviews</span>
            </a>
        </nav>
    </aside>

    <div class="content-area">
        <header class="sticky top-0 z-40 bg-white border-b border-gray-100 py-4 px-6 flex items-center justify-between">
            <button id="menu-toggle" class="lg:hidden text-gray-600 hover:text-primary">
                <i class="ri-menu-line text-2xl"></i>
            </button>
            <div class="flex items-center space-x-2">
                <div class="flex items-center space-x-0">
                    <div class="h-8 w-8 rounded-full bg-primary flex items-center justify-center text-white font-medium">
                        <?php echo strtoupper(substr($adminName, 0, 1)); ?>
                    </div>
                    <span class="font-medium"><?php echo htmlspecialchars($adminName); ?></span>
                </div>
                <a href="logout.php" class="p-2 rounded-full hover:bg-gray-100 text-red-500">
                    <i class="ri-logout-box-r-line text-xl"></i>
                </a>
            </div>
        </header>

        <main class="py-6">
            <div class="max-w-7xl mx-auto">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
                    <div>
                        <h1 class="dashboard-title">Dashboard & Analytics</h1>
                        <p class="dashboard-subtitle">Welcome back, <?php echo htmlspecialchars($adminName); ?>! Monitor your store's performance.</p>
                    </div>
                </div>

                <!-- Metrics Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="card metric-card">
                        <div class="metric-icon bg-primary">
                            <i class="ri-money-dollar-circle-line text-xl text-white"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Total Revenue</p>
                            <h3 class="text-2xl font-bold">RM <?php echo number_format(array_sum($dailyData['sales']), 2); ?></h3>
                            <p class="text-green-500 text-sm flex items-center">
                                <i class="ri-arrow-up-line mr-1"></i> <?php echo rand(5, 15); ?>% from last year
                            </p>
                        </div>
                    </div>
                    <div class="card metric-card">
                        <div class="metric-icon bg-secondary">
                            <i class="ri-shopping-cart-2-line text-xl text-white"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Total Orders</p>
                            <h3 class="text-2xl font-bold"><?php echo number_format(rand(200, 800)); ?></h3>
                            <p class="text-green-500 text-sm flex items-center">
                                <i class="ri-arrow-up-line mr-1"></i> <?php echo rand(3, 10); ?>% from last year
                            </p>
                        </div>
                    </div>
                    <div class="card metric-card">
                        <div class="metric-icon bg-warning">
                            <i class="ri-exchange-line text-xl text-white"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Conversion Rate</p>
                            <h3 class="text-2xl font-bold"><?php echo rand(25, 40) / 10; ?>%</h3>
                            <p class="text-red-500 text-sm flex items-center">
                                <i class="ri-arrow-down-line mr-1"></i> <?php echo rand(1, 3); ?>% from last year
                            </p>
                        </div>
                    </div>
                    <div class="card metric-card">
                        <div class="metric-icon bg-info">
                            <i class="ri-emotion-happy-line text-xl text-white"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Customer Satisfaction</p>
                            <h3 class="text-2xl font-bold"><?php echo rand(85, 95); ?>%</h3>
                            <p class="text-green-500 text-sm flex items-center">
                                <i class="ri-arrow-up-line mr-1"></i> <?php echo rand(2, 5); ?>% from last year
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <div class="card p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-lg font-bold">Sales Trend</h2>
                            <select id="salesTrendFilter" class="text-sm border border-gray-200 rounded-lg px-3 py-1 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                <option value="30">Last 30 Days</option>
                                <option value="90">Last 90 Days</option>
                                <option value="365" selected>Full Year</option>
                            </select>
                        </div>
                        <div class="chart-container">
                            <canvas id="salesTrendChart"></canvas>
                        </div>
                    </div>
                    <div class="card p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-lg font-bold">User Activity</h2>
                            <select id="userActivityFilter" class="text-sm border border-gray-200 rounded-lg px-3 py-1 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                <option value="30">Last 30 Days</option>
                                <option value="90">Last 90 Days</option>
                                <option value="365" selected>Full Year</option>
                            </select>
                        </div>
                        <div class="chart-container">
                            <canvas id="userActivityChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Product Insights -->
                <div id="analytics" class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                    <div class="card p-6">
                        <h2 class="text-lg font-bold mb-4">Most Ordered Products</h2>
                        <div class="space-y-4">
                            <?php foreach ($most_ordered_products as $product): ?>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="h-10 w-10 rounded-full bg-blue-50 flex items-center justify-center text-primary">
                                            <i class="ri-shopping-basket-line"></i>
                                        </div>
                                        <span class="truncate"><?php echo htmlspecialchars($product['name']); ?></span>
                                    </div>
                                    <span class="font-medium whitespace-nowrap"><?php echo $product['total_ordered']; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="card p-6">
                        <h2 class="text-lg font-bold mb-4">Most Searched Products</h2>
                        <div class="space-y-4">
                            <?php foreach ($fake_searches as $product): ?>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="h-10 w-10 rounded-full bg-green-50 flex items-center justify-center text-green-500">
                                            <i class="ri-search-line"></i>
                                        </div>
                                        <span class="truncate"><?php echo htmlspecialchars($product['name']); ?></span>
                                    </div>
                                    <span class="font-medium whitespace-nowrap"><?php echo $product['search_count']; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="card p-6">
                        <h2 class="text-lg font-bold mb-4">Most Visited Products</h2>
                        <div class="space-y-4">
                            <?php foreach ($fake_views as $product): ?>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="h-10 w-10 rounded-full bg-purple-50 flex items-center justify-center text-purple-500">
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

        <footer class="bg-primary-dark text-white py-12">
            <div class="max-w-7xl mx-auto px-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div>
                        <span class="text-2xl font-bold flex items-center mb-4">
                            <i class="fa-solid fa-leaf mr-2 text-primary-light"></i>
                            AgriMarket
                        </span>
                        <p class="text-sm opacity-80">Connecting local farmers with your table since 2020.</p>
                    </div>
                    <div>
                        <h5 class="font-bold text-lg mb-4">Contact Us</h5>
                        <ul class="space-y-2 text-sm">
                            <li><i class="ri-mail-line mr-2"></i> support@agrimarket.com</li>
                            <li><i class="ri-phone-line mr-2"></i> 016-1234567</li>
                            <li><i class="ri-map-pin-line mr-2"></i> 123 Farm Road, Green Valley</li>
                        </ul>
                    </div>
                    <div>
                        <h5 class="font-bold text-lg mb-4">Follow Us</h5>
                        <div class="flex space-x-4">
                            <a href="https://web.facebook.com/INTI.edu" class="hover:text-primary-light transition-colors"><i class="ri-facebook-fill text-xl"></i></a>
                            <a href="https://www.instagram.com/inti_edu" class="hover:text-primary-light transition-colors"><i class="ri-instagram-fill text-xl"></i></a>
                        </div>
                    </div>
                </div>
                <div class="mt-12 pt-8 border-t border-white border-opacity-20 text-center">
                    <p class="text-sm">© 2025 AgriMarket Solutions. All rights reserved.</p>
                </div>
            </div>
        </footer>
    </div>

    <script>
        // Sidebar toggle
        const menuToggle = document.getElementById('menu-toggle');
        const sidebar = document.querySelector('.sidebar');
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });

        // Full dataset
        const fullData = {
            dates: <?php echo json_encode($dailyData['dates']); ?>,
            sales: <?php echo json_encode($dailyData['sales']); ?>,
            activeUsers: <?php echo json_encode($dailyData['active_users']); ?>,
            newUsers: <?php echo json_encode($dailyData['new_users']); ?>
        };

        // Filter function
        function filterData(days) {
            const endIndex = fullData.dates.length - 1;
            const startIndex = Math.max(0, endIndex - days + 1);
            return {
                dates: fullData.dates.slice(startIndex, endIndex + 1),
                sales: fullData.sales.slice(startIndex, endIndex + 1),
                activeUsers: fullData.activeUsers.slice(startIndex, endIndex + 1),
                newUsers: fullData.newUsers.slice(startIndex, endIndex + 1)
            };
        }

        // Initialize charts
        let salesTrendChart, userActivityChart;

        function updateCharts(days) {
            const filteredData = filterData(days);

            // Sales Trend Chart
            if (salesTrendChart) salesTrendChart.destroy();
            const salesTrendCtx = document.getElementById('salesTrendChart').getContext('2d');
            salesTrendChart = new Chart(salesTrendCtx, {
                type: 'line',
                data: {
                    labels: filteredData.dates,
                    datasets: [{
                        label: 'Sales (RM)',
                        data: filteredData.sales,
                        borderColor: 'rgba(59, 130, 246, 1)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true,
                        borderWidth: 2,
                        pointBackgroundColor: 'white',
                        pointBorderColor: 'rgba(59, 130, 246, 1)',
                        pointBorderWidth: 2,
                        pointRadius: days <= 30 ? 4 : 0,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleFont: { size: 14, weight: 'bold' },
                            bodyFont: { size: 12 },
                            padding: 12,
                            cornerRadius: 8,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return `RM${context.parsed.y.toLocaleString()}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            grid: { drawBorder: false, color: 'rgba(0, 0, 0, 0.05)' },
                            ticks: {
                                callback: function(value) {
                                    return 'RM' + value.toLocaleString();
                                }
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: {
                                maxTicksLimit: days <= 30 ? 30 : days <= 90 ? 15 : 12,
                                callback: function(value, index, values) {
                                    return filteredData.dates[index];
                                }
                            }
                        }
                    }
                }
            });

            // User Activity Chart
            if (userActivityChart) userActivityChart.destroy();
            const userActivityCtx = document.getElementById('userActivityChart').getContext('2d');
            userActivityChart = new Chart(userActivityCtx, {
                type: 'bar',
                data: {
                    labels: filteredData.dates,
                    datasets: [
                        {
                            label: 'Active Users',
                            data: filteredData.activeUsers,
                            backgroundColor: 'rgba(16, 185, 129, 0.7)',
                            borderRadius: 6,
                            borderWidth: 0
                        },
                        {
                            label: 'New Users',
                            data: filteredData.newUsers,
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
                            labels: { usePointStyle: true, padding: 20 }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleFont: { size: 14, weight: 'bold' },
                            bodyFont: { size: 12 },
                            padding: 12,
                            cornerRadius: 8
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { drawBorder: false, color: 'rgba(0, 0, 0, 0.05)' }
                        },
                        x: {
                            grid: { display: false },
                            ticks: {
                                maxTicksLimit: days <= 30 ? 30 : days <= 90 ? 15 : 12,
                                callback: function(value, index, values) {
                                    return filteredData.dates[index];
                                }
                            }
                        }
                    }
                }
            });
        }

        // Initial render
        updateCharts(365);

        // Filter listeners
        document.getElementById('salesTrendFilter').addEventListener('change', function() {
            updateCharts(parseInt(this.value));
        });
        document.getElementById('userActivityFilter').addEventListener('change', function() {
            updateCharts(parseInt(this.value));
        });
    </script>
</body>
</html>