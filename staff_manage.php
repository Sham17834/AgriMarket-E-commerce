<?php
session_start();

$isLoggedIn = isset($_SESSION['user_id']);
$isStaff = isset($_SESSION['role']) && $_SESSION['role'] === 'staff';
if (!$isLoggedIn || !$isStaff) {
    header("Location: login.php?error=Unauthorized access");
    exit;
}

require_once 'db_connect.php';

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = trim($_POST['order_status']);
    
    // Validate status
    $valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    if (in_array($new_status, $valid_statuses)) {
        try {
            $stmt = $pdo->prepare("UPDATE orders SET order_status = :status WHERE order_id = :order_id");
            $stmt->bindParam(':status', $new_status, PDO::PARAM_STR);
            $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
            $stmt->execute();
            header("Location: staff_manage.php?success=Order status updated");
            exit;
        } catch (PDOException $e) {
            error_log("Error updating order status: " . $e->getMessage());
            header("Location: staff_manage.php?error=Failed to update order status");
            exit;
        }
    } else {
        header("Location: staff_manage.php?error=Invalid order status");
        exit;
    }
}

// Fetch all customer orders with customer names
$orders = [];
try {
    $stmt = $pdo->prepare("
        SELECT o.order_id, o.order_date, o.total_amount, o.order_status, 
               o.shipping_fee, o.tax_amount, u.username AS customer_name
        FROM orders o
        JOIN users u ON o.customer_id = u.user_id
        ORDER BY o.order_date DESC
    ");
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate and validate totals for each order
    foreach ($orders as &$order) {
        $order_id = $order['order_id'];
        $stmt = $pdo->prepare("
            SELECT SUM(subtotal) as order_subtotal
            FROM order_items
            WHERE order_id = :order_id
        ");
        $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $order_subtotal = $result['order_subtotal'] ?? 0;
        $calculated_total = $order_subtotal + $order['shipping_fee'] + $order['tax_amount'];
        
        // Flag mismatches
        if (abs($order['total_amount'] - $calculated_total) > 0.01) {
            error_log("Total mismatch for order $order_id: DB total = {$order['total_amount']}, Calculated total = $calculated_total");
            $order['total_amount'] = $calculated_total; 
            $order['has_mismatch'] = true;
        } else {
            $order['has_mismatch'] = false;
        }
        
        // Store breakdown for tooltip
        $order['total_breakdown'] = "Subtotal: RM" . number_format($order_subtotal, 2) . 
                                  "\nShipping: RM" . number_format($order['shipping_fee'], 2) . 
                                  "\nTax: RM" . number_format($order['tax_amount'], 2);
    }
    unset($order); 
} catch (PDOException $e) {
    error_log("Error fetching orders: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard | FreshHarvest</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Open+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body class="bg-gray-100 min-h-screen font-body flex flex-col">
<header class="fixed top-0 left-0 right-0 bg-white shadow-md z-50">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex items-center justify-between h-20">
            <div class="flex items-center space-x-8">
                <span class="text-3xl font-heading font-bold text-primary flex items-center">
                    <i class="fa-solid fa-leaf mr-2 text-primary-light"></i>
                    AgriMarket
                </span>
            </div>
            <div class="flex items-center space-x-4">
                <a href="logout.php" class="cursor-pointer hover:text-primary transition-colors" title="Log Out">
                    <i class="ri-logout-box-line text-xl"></i>
                </a>
            </div>
        </div>
    </div>
</header>

<main class="pt-28 pb-16 flex-1">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex flex-col md:flex-row gap-6">
            <!-- Sidebar -->
            <div class="w-full md:w-64">
                <div class="bg-white rounded-lg shadow-sm overflow-hidden sticky top-28 p-6">
                    <div class="text-center">
                        <div class="w-20 h-20 rounded-full bg-primary/10 mx-auto flex items-center justify-center">
                            <i class="ri-user-3-line text-4xl text-primary"></i>
                        </div>
                        <h2 class="mt-4 font-bold text-lg text-gray-800"><?php echo htmlspecialchars($_SESSION['username']); ?></h2>
                        <p class="text-sm text-gray-500">Staff</p>
                        <div class="mt-6">
                            <a href="logout.php" class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                                <i class="ri-logout-box-line mr-2"></i>
                                Log Out
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="flex-1">
                <div class="bg-white rounded-lg shadow-sm p-8">
                    <h1 class="text-3xl font-heading font-bold text-gray-800 mb-8">Staff Order Management</h1>

                    <!-- Notification -->
                    <?php if (isset($_GET['success'])): ?>
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-8 rounded-r-lg">
                            <?php echo htmlspecialchars($_GET['success']); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_GET['error'])): ?>
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-8 rounded-r-lg">
                            <?php echo htmlspecialchars($_GET['error']); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Orders Table -->
                    <?php if (empty($orders)): ?>
                        <div class="text-center py-12 bg-gray-50 rounded-lg">
                            <div class="mx-auto w-16 h-16 flex items-center justify-center bg-gray-200 rounded-full mb-4">
                                <i class="ri-shopping-bag-line text-2xl text-gray-500"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-700 mb-2">No Orders Found</h3>
                            <p class="text-gray-500">There are currently no customer orders to manage.</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse bg-white rounded-lg shadow-sm">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="py-4 px-6 font-semibold text-gray-700 border-b">Order ID</th>
                                        <th class="py-4 px-6 font-semibold text-gray-700 border-b">Customer</th>
                                        <th class="py-4 px-6 font-semibold text-gray-700 border-b">Date</th>
                                        <th class="py-4 px-6 font-semibold text-gray-700 border-b">Amount</th>
                                        <th class="py-4 px-6 font-semibold text-gray-700 border-b">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php foreach ($orders as $order): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="py-4 px-6 text-gray-800">#<?php echo htmlspecialchars($order['order_id']); ?></td>
                                            <td class="py-4 px-6 text-gray-800"><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                            <td class="py-4 px-6 text-gray-800">
                                                <?php echo date('M j, Y', strtotime($order['order_date'])); ?>
                                            </td>
                                            <td class="py-4 px-6 font-medium text-gray-800">
                                                RM<?php echo number_format($order['total_amount'], 2); ?>
                                            </td>
                                            <td class="py-4 px-6">
                                                <form method="POST" class="flex items-center space-x-3">
                                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                    <select name="order_status" class="px-3 py-2 rounded-lg text-sm font-medium border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent">
                                                        <option value="pending" <?php echo $order['order_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                        <option value="processing" <?php echo $order['order_status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                                        <option value="shipped" <?php echo $order['order_status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                                        <option value="delivered" <?php echo $order['order_status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                                        <option value="cancelled" <?php echo $order['order_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                    </select>
                                                    <button type="submit" name="update_order_status" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark font-medium transition-colors">
                                                        Update
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<footer class="bg-primary-dark text-white py-12 flex-shrink-0">
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div>
                <span class="text-2xl font-heading font-bold flex items-center mb-4">
                    <i class="fa-solid fa-leaf mr-2 text-primary-light"></i>
                    FreshHarvest
                </span>
                <p class="text-sm opacity-80">Connecting local farmers with your table since 2020.</p>
            </div>
            <div>
                <h5 class="font-heading font-bold text-lg mb-4">Contact Us</h5>
                <ul class="space-y-2 text-sm">
                    <li><i class="ri-mail-line mr-2"></i> support@freshharvest.com</li>
                    <li><i class="ri-phone-line mr-2"></i> (555) 123-4567</li>
                    <li><i class="ri-map-pin-line mr-2"></i> 123 Farm Road, Green Valley</li>
                </ul>
            </div>
            <div>
                <h5 class="font-heading font-bold text-lg mb-4">Follow Us</h5>
                <div class="flex space-x-4">
                    <a href="https://web.facebook.com/INTI.edu/?locale=ms_MY&_rdc=1&_rdr#" class="hover:text-primary-light transition-colors"><i class="ri-facebook-fill text-xl"></i></a>
                    <a href="https://www.instagram.com/inti_edu/?hl=en" class="hover:text-primary-light transition-colors"><i class="ri-instagram-fill text-xl"></i></a>
                </div>
            </div>
        </div>
        <div class="mt-12 pt-8 border-t border-white border-opacity-20 text-center">
            <p class="text-sm">© 2025 FreshHarvest. All rights reserved.</p>
        </div>
    </div>
</footer>
<script src="script.js"></script>
</body>
</html>