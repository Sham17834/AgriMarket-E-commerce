<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Fetch pending reviews
$stmt = $pdo->prepare("SELECT pr.*, p.name AS product_name, u.username 
                       FROM product_reviews pr 
                       JOIN products p ON pr.product_id = p.product_id 
                       JOIN users u ON pr.customer_id = u.user_id 
                       WHERE pr.is_approved = 0 
                       ORDER BY pr.review_date DESC");
$stmt->execute();
$reviews = $stmt->fetchAll();

// Handle review approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $review_id = (int)$_POST['review_id'];
    $action = $_POST['action'];

    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE product_reviews SET is_approved = 1 WHERE review_id = ?");
        $stmt->execute([$review_id]);
    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare("DELETE FROM product_reviews WHERE review_id = ?");
        $stmt->execute([$review_id]);
    }

    header("Location: admin_reviews.php?success=Review processed");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Moderation | FreshHarvest</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css" rel="stylesheet">
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
        
        .card {
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
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
        
        .review-card {
            transition: box-shadow 0.3s ease;
        }
        
        .review-card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
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
            <a href="admin_analytics.php"
                class="flex items-center space-x-3 p-3 rounded-lg text-gray-600 hover:bg-blue-50 hover:text-primary transition-colors">
                <i class="ri-dashboard-line"></i>
                <span>Dashboard</span>
            </a>
            <a href="admin_reviews.php"
                class="flex items-center space-x-3 p-3 rounded-lg bg-blue-50 text-primary font-medium">
                <i class="ri-star-line"></i>
                <span>Reviews</span>
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
                    <div class="h-8 w-8 rounded-full bg-primary flex items-center justify-center text-white font-medium">
                        <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                    </div>
                    <span class="font-medium"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </div>
                <a href="logout.php" class="p-2 rounded-full hover:bg-gray-100 text-red-500">
                    <i class="ri-logout-box-r-line text-xl"></i>
                </a>
            </div>
        </header>

        <!-- Reviews Content -->
        <main class="py-6">
            <div class="max-w-7xl mx-auto">
                <!-- Page Header -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
                    <div>
                        <h1 class="text-2xl font-bold">Review Moderation</h1>
                        <p class="text-gray-600">Approve or reject customer reviews</p>
                    </div>
                </div>

                <?php if (isset($_GET['success'])): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                        <?php echo htmlspecialchars($_GET['success']); ?>
                    </div>
                <?php endif; ?>

                <div class="card p-6">
                    <?php if ($reviews): ?>
                        <div class="overflow-x-auto">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Customer</th>
                                        <th>Rating</th>
                                        <th>Title</th>
                                        <th>Comment</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reviews as $review): ?>
                                        <tr class="review-card">
                                            <td class="font-medium"><?php echo htmlspecialchars($review['product_name']); ?></td>
                                            <td><?php echo htmlspecialchars($review['username']); ?></td>
                                            <td>
                                                <div class="flex items-center">
                                                    <?php 
                                                    $rating = $review['rating'];
                                                    for ($i = 1; $i <= 5; $i++) {
                                                        echo $i <= $rating 
                                                            ? '<i class="ri-star-fill text-yellow-400"></i>'
                                                            : '<i class="ri-star-line text-yellow-400"></i>';
                                                    }
                                                    ?>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($review['title']); ?></td>
                                            <td><?php echo htmlspecialchars($review['comment']); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($review['review_date'])); ?></td>
                                            <td>
                                                <form method="POST" class="flex space-x-2">
                                                    <input type="hidden" name="review_id" value="<?php echo $review['review_id']; ?>">
                                                    <button type="submit" name="action" value="approve" 
                                                        class="text-green-600 hover:text-green-800 px-3 py-1 rounded bg-green-50">
                                                        <i class="ri-check-line"></i> Approve
                                                    </button>
                                                    <button type="submit" name="action" value="reject" 
                                                        class="text-red-600 hover:text-red-800 px-3 py-1 rounded bg-red-50">
                                                        <i class="ri-close-line"></i> Reject
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-600 text-center py-8">No reviews pending moderation.</p>
                    <?php endif; ?>
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
    </script>
</body>
</html>