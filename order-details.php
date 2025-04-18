<?php
session_start();

$isLoggedIn = isset($_SESSION['user_id']);
if (!$isLoggedIn) {
    header("Location: /login.php");
    exit;
}

require_once 'db_connect.php';

// Fetch user details
$user_id = $_SESSION['user_id'];
try {
    $stmt = $pdo->prepare("SELECT username, email, role, phone FROM users WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        session_destroy();
        header("Location: /login.php?error=User not found");
        exit;
    }
} catch (PDOException $e) {
    error_log("Error fetching user: " . $e->getMessage());
    header("Location: /login.php?error=Error fetching profile");
    exit;
}

// Validate order ID
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($order_id <= 0) {
    header("Location: /profile.php?error=Invalid order ID");
    exit;
}

// Handle order cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    try {
        // Check current order status
        $stmt = $pdo->prepare("SELECT order_status FROM orders WHERE order_id = :order_id AND customer_id = :customer_id");
        $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
        $stmt->bindParam(':customer_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            $current_status = strtolower($order['order_status']);
            $cancellable_statuses = ['pending', 'processing'];
            if (in_array($current_status, $cancellable_statuses)) {
                // Update order status to cancelled
                $stmt = $pdo->prepare("UPDATE orders SET order_status = 'cancelled' WHERE order_id = :order_id");
                $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
                $stmt->execute();
                
                // Redirect back to same page with success message
                header("Location: " . $_SERVER['PHP_SELF'] . "?id=$order_id&success=1");
                exit;
            } else {
                header("Location: " . $_SERVER['PHP_SELF'] . "?id=$order_id&error=Order cannot be cancelled in its current status");
                exit;
            }
        } else {
            header("Location: /profile.php?error=Order not found");
            exit;
        }
    } catch (PDOException $e) {
        error_log("Error cancelling order: " . $e->getMessage());
        header("Location: " . $_SERVER['PHP_SELF'] . "?id=$order_id&error=Failed to cancel order");
        exit;
    }
}

// Fetch order details
try {
    $stmt = $pdo->prepare("
        SELECT order_id, order_date, total_amount, shipping_fee, payment_method, 
               payment_status, order_status, shipping_address, billing_address, customer_notes 
        FROM orders 
        WHERE order_id = :order_id AND customer_id = :customer_id
    ");
    $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
    $stmt->bindParam(':customer_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        header("Location: /profile.php?error=Order not found");
        exit;
    }
} catch (PDOException $e) {
    error_log("Error fetching order: " . $e->getMessage());
    header("Location: /profile.php?error=Error fetching order");
    exit;
}

// Fetch order items
$order_items = [];
$order_subtotal = 0;
try {
    $stmt = $pdo->prepare("
        SELECT oi.order_item_id, oi.product_id, oi.quantity, oi.unit_price, oi.discount_amount, oi.subtotal, 
               p.name, p.image_url 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.product_id 
        WHERE oi.order_id = :order_id
    ");
    $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
    $stmt->execute();
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($order_items as $item) {
        $order_subtotal += $item['subtotal'];
    }
} catch (PDOException $e) {
    error_log("Error fetching order items: " . $e->getMessage());
}

// Initialize cart count
$cart_count = 0;
try {
    $stmt = $pdo->prepare("SELECT cart_id FROM shopping_carts WHERE customer_id = :customer_id ORDER BY updated_at DESC LIMIT 1");
    $stmt->bindParam(':customer_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $cart = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cart) {
        $cart_id = $cart['cart_id'];
        $stmt = $pdo->prepare("SELECT SUM(quantity) as total_quantity FROM cart_items WHERE cart_id = :cart_id");
        $stmt->bindParam(':cart_id', $cart_id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $cart_count = $result['total_quantity'] ?? 0;
    }
} catch (PDOException $e) {
    error_log("Error fetching cart count: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details #<?php echo $order_id; ?> | FreshHarvest</title>
    <link rel="stylesheet" href="/style.css">
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Open+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* Modal styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        .modal-content {
            background-color: white;
            padding: 2rem;
            border-radius: 0.5rem;
            width: 90%;
            max-width: 400px;
            text-align: center;
            transform: translateY(20px);
            transition: transform 0.3s ease;
        }
        .modal-overlay.active .modal-content {
            transform: translateY(0);
        }
        .animate-spin {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen font-body flex flex-col">
    <!-- Success Modal -->
    <div id="successModal" class="modal-overlay <?php echo isset($_GET['success']) ? 'active' : ''; ?>">
        <div class="modal-content">
            <div class="text-center">
                <i class="ri-checkbox-circle-fill text-5xl text-green-500 mb-4"></i>
                <h3 class="text-xl font-semibold mb-2">Order Cancelled</h3>
                <p class="text-gray-600 mb-6">Your order has been successfully cancelled.</p>
                <button onclick="closeSuccessModal()" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-primary-dark transition-colors">
                    Continue
                </button>
            </div>
        </div>
    </div>

    <!-- Error Modal -->
    <div id="errorModal" class="modal-overlay <?php echo isset($_GET['error']) ? 'active' : ''; ?>">
        <div class="modal-content">
            <div class="text-center">
                <i class="ri-close-circle-fill text-5xl text-red-500 mb-4"></i>
                <h3 class="text-xl font-semibold mb-2">Error</h3>
                <p class="text-gray-600 mb-6"><?php echo isset($_GET['error']) ? htmlspecialchars($_GET['error']) : ''; ?></p>
                <button onclick="closeErrorModal()" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-primary-dark transition-colors">
                    OK
                </button>
            </div>
        </div>
    </div>

    <header class="fixed top-0 left-0 right-0 bg-white shadow-md z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between h-20">
                <div class="flex items-center space-x-8">
                    <a href="/index.php" class="text-3xl font-heading font-bold text-primary flex items-center">
                        <i class="fa-solid fa-leaf mr-2 text-primary-light"></i>
                        AgriMarket
                    </a>
                    <nav class="hidden lg:flex space-x-8">
                        <a href="/index.php" class="text-gray-700 hover:text-primary font-medium">Home</a>
                        <a href="/products.php" class="text-gray-700 hover:text-primary font-medium">Products</a>
                        <a href="/categories.php" class="text-gray-700 hover:text-primary font-medium">Categories</a>
                        <a href="/farmers.php" class="text-gray-700 hover:text-primary font-medium">Meet Our Farmers</a>
                        <a href="/about.php" class="text-gray-700 hover:text-primary font-medium">About Us</a>
                    </nav>
                </div>
                <div class="flex items-center space-x-6">
                    <div class="relative hidden md:block">
                        <form action="/products.php" method="GET" id="search-form">
                            <input type="text" name="search" id="search-input" placeholder="Search for fresh produce..." class="w-64 pl-10 pr-4 py-2 rounded-full border border-gray-200 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                            <button type="submit" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                                <i class="ri-search-line"></i>
                            </button>
                        </form>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="md:hidden flex items-center">
                            <button id="mobile-search-toggle" class="text-gray-700"><i class="ri-search-line text-xl"></i></button>
                            <form action="/products.php" method="GET" id="mobile-search-form" class="hidden absolute top-20 left-0 right-0 bg-white shadow-md p-4 z-50">
                                <div class="relative">
                                    <input type="text" name="search" id="mobile-search-input" placeholder="Search for fresh produce..." class="w-full pl-10 pr-4 py-2 rounded-full border border-gray-200 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                    <button type="submit" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                                        <i class="ri-search-line"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                        <a href="/cart.php" class="relative cursor-pointer group">
                            <i class="ri-shopping-cart-line text-xl group-hover:text-primary transition-colors"></i>
                            <span class="absolute -top-2 -right-2 bg-accent text-white text-xs rounded-full w-5 h-5 flex items-center justify-center"><?php echo $cart_count; ?></span>
                        </a>
                        <a href="/logout.php" class="cursor-pointer hover:text-primary transition-colors" title="Log Out">
                            <i class="ri-logout-box-line text-xl"></i>
                        </a>
                        <a href="/profile.php" class="cursor-pointer hover:text-primary transition-colors" title="Profile">
                            <i class="ri-user-line text-xl"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="pt-28 pb-16 flex-1">
        <div class="max-w-6xl mx-auto px-4">
            <div class="bg-white rounded-xl shadow-sm p-6">
                <!-- Order Summary Header -->
                <div class="mb-8">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                        <div>
                            <h1 class="text-2xl font-heading font-bold text-gray-800">Order #<?php echo htmlspecialchars($order['order_id']); ?></h1>
                            <p class="text-gray-600 mt-1">Placed on <?php echo date('F j, Y, g:i A', strtotime($order['order_date'])); ?></p>
                        </div>
                        <div class="mt-4 md:mt-0 flex items-center space-x-4">
                            <span class="px-4 py-2 rounded-full text-sm font-medium <?php
                            $statusClass = 'bg-gray-100 text-gray-800';
                            switch (strtolower($order['order_status'])) {
                                case 'delivered':
                                    $statusClass = 'bg-green-100 text-green-800';
                                    break;
                                case 'shipped':
                                    $statusClass = 'bg-purple-100 text-purple-800';
                                    break;
                                case 'processing':
                                case 'pending':
                                    $statusClass = 'bg-blue-100 text-blue-800';
                                    break;
                                case 'cancelled':
                                    $statusClass = 'bg-red-100 text-red-800';
                                    break;
                            }
                            echo $statusClass;
                            ?>">
                                <?php echo htmlspecialchars(ucfirst($order['order_status'])); ?>
                            </span>
                            <?php if (in_array(strtolower($order['order_status']), ['pending', 'processing'])): ?>
                                <form method="POST" id="cancelForm" onsubmit="return confirmCancel()">
                                    <input type="hidden" name="cancel_order" value="1">
                                    <button type="submit" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 font-medium transition-colors" id="cancelButton">
                                        Cancel Order
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Main Content Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Left Column: Status Tracker, Items, Summary -->
                    <div class="lg:col-span-2 space-y-8">
                        <!-- Order Status Tracker -->
                        <div>
                            <h2 class="text-lg font-semibold text-gray-700 mb-4">Order Status</h2>
                            <div class="relative">
                                <div class="flex justify-between items-center">
                                    <?php
                                    $statuses = ['pending', 'processing', 'shipped', 'delivered'];
                                    $currentStatus = strtolower($order['order_status']);
                                    if ($currentStatus === 'cancelled') {
                                        $statuses = ['cancelled'];
                                    }
                                    $statusIcons = [
                                        'pending' => 'ri-time-line',
                                        'processing' => 'ri-tools-line',
                                        'shipped' => 'ri-truck-line',
                                        'delivered' => 'ri-check-double-line',
                                        'cancelled' => 'ri-close-circle-line'
                                    ];
                                    $statusLabels = [
                                        'pending' => 'Order Placed',
                                        'processing' => 'Processing',
                                        'shipped' => 'Shipped',
                                        'delivered' => 'Delivered',
                                        'cancelled' => 'Cancelled'
                                    ];

                                    $currentIndex = array_search($currentStatus, $statuses);
                                    foreach ($statuses as $index => $status):
                                        $isActive = $index <= $currentIndex || $currentStatus === 'cancelled';
                                        $isLast = $index === count($statuses) - 1;
                                    ?>
                                        <div class="flex-1 text-center">
                                            <div class="flex flex-col items-center">
                                                <div class="w-12 h-12 rounded-full flex items-center justify-center <?php echo $isActive ? 'bg-primary text-white' : 'bg-gray-200 text-gray-500'; ?>">
                                                    <i class="<?php echo $statusIcons[$status]; ?> text-xl"></i>
                                                </div>
                                                <p class="mt-2 text-sm font-medium <?php echo $isActive ? 'text-gray-800' : 'text-gray-500'; ?>">
                                                    <?php echo $statusLabels[$status]; ?>
                                                </p>
                                            </div>
                                        </div>
                                        <?php if (!$isLast): ?>
                                            <div class="flex-1">
                                                <div class="h-1 bg-gray-200 mx-2 mt-6">
                                                    <div class="h-1 <?php echo $isActive ? 'bg-primary' : 'bg-gray-200'; ?>" style="width: 100%;"></div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Order Items -->
                        <div>
                            <h2 class="text-lg font-semibold text-gray-700 mb-4">Items in Your Order</h2>
                            <?php if (empty($order_items)): ?>
                                <p class="text-gray-500">No items found for this order.</p>
                            <?php else: ?>
                                <div class="space-y-4">
                                    <?php foreach ($order_items as $item):
                                        $image_url = !empty($item['image_url']) ? json_decode($item['image_url'], true)[0] : 'https://via.placeholder.com/100';
                                    ?>
                                        <div class="flex items-center border-b pb-4">
                                            <img src="<?php echo htmlspecialchars($image_url); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="w-16 h-16 object-cover rounded-lg mr-4">
                                            <div class="flex-1">
                                                <h3 class="text-gray-800 font-medium"><?php echo htmlspecialchars($item['name']); ?></h3>
                                                <p class="text-gray-600 text-sm">Quantity: <?php echo $item['quantity']; ?></p>
                                                <p class="text-gray-600 text-sm">Unit Price: RM<?php echo number_format($item['unit_price'], 2); ?></p>
                                                <?php if ($item['discount_amount'] > 0): ?>
                                                    <p class="text-green-600 text-sm">Discount: -RM<?php echo number_format($item['discount_amount'], 2); ?></p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-gray-800 font-medium">
                                                RM<?php echo number_format($item['subtotal'], 2); ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Order Summary (Totals) -->
                        <div>
                            <h2 class="text-lg font-semibold text-gray-700 mb-4">Order Summary</h2>
                            <div class="bg-gray-50 p-4 rounded-lg space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Subtotal</span>
                                    <span class="text-gray-800 font-medium">RM<?php echo number_format($order_subtotal, 2); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Shipping Fee</span>
                                    <span class="text-gray-800 font-medium">RM<?php echo number_format($order['shipping_fee'], 2); ?></span>
                                </div>
                                <div class="flex justify-between border-t pt-2">
                                    <span class="text-gray-700 font-semibold">Total</span>
                                    <span class="text-gray-800 font-semibold">
                                        RM<?php
                                        $calculated_total = $order_subtotal + $order['shipping_fee'];
                                        if (abs($order['total_amount'] - $calculated_total) > 0.01) {
                                            error_log("Total mismatch for order $order_id: DB total = {$order['total_amount']}, Calculated total = $calculated_total");
                                            echo number_format($calculated_total, 2);
                                        } else {
                                            echo number_format($order['total_amount'], 2);
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Payment & Shipping Details -->
                    <div class="lg:col-span-1">
                        <div class="sticky top-28">
                            <h2 class="text-lg font-semibold text-gray-700 mb-4">Payment & Shipping Details</h2>
                            <div class="bg-gray-50 p-4 rounded-lg space-y-4">
                                <div>
                                    <p class="text-gray-600 text-sm">Name</p>
                                    <p class="text-gray-800 font-medium"><?php echo htmlspecialchars($user['username']); ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-600 text-sm">Email</p>
                                    <p class="text-gray-800 font-medium"><?php echo htmlspecialchars($user['email']); ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-600 text-sm">Phone</p>
                                    <p class="text-gray-800 font-medium"><?php echo htmlspecialchars($user['phone'] ?: 'Not provided'); ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-600 text-sm">Payment Method</p>
                                    <p class="text-gray-800 font-medium"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $order['payment_method']))); ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-600 text-sm">Payment Status</p>
                                    <span class="px-3 py-1 rounded-full text-sm font-medium <?php
                                    $paymentStatusClass = 'bg-gray-100 text-gray-800';
                                    switch (strtolower($order['payment_status'])) {
                                        case 'completed':
                                            $paymentStatusClass = 'bg-green-100 text-green-800';
                                            break;
                                        case 'pending':
                                            $paymentStatusClass = 'bg-blue-100 text-blue-800';
                                            break;
                                        case 'failed':
                                        case 'refunded':
                                            $paymentStatusClass = 'bg-red-100 text-red-800';
                                            break;
                                    }
                                    echo $paymentStatusClass;
                                    ?>">
                                        <?php echo htmlspecialchars(ucfirst($order['payment_status'])); ?>
                                    </span>
                                </div>
                                <div>
                                    <p class="text-gray-600 text-sm">Shipping Address</p>
                                    <p class="text-gray-800"><?php
                                        $address = $order['shipping_address'] ?: 'Not provided';
                                        if ($address !== 'Not provided') {
                                            $parts = explode(',', $address, 2);
                                            $address = trim($parts[0]) ?: $address;
                                        }
                                        echo nl2br(htmlspecialchars($address));
                                    ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-600 text-sm">Billing Address</p>
                                    <p class="text-gray-800"><?php
                                        $address = $order['shipping_address'] ?: 'Not provided';
                                        if ($address !== 'Not provided') {
                                            $parts = explode(',', $address, 2);
                                            $address = trim($parts[0]) ?: $address;
                                        }
                                        echo nl2br(htmlspecialchars($address));
                                    ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Back to Profile -->
                <div class="mt-8 text-center">
                    <a href="/profile.php" class="inline-flex items-center text-primary hover:text-primary-dark font-medium">
                        <i class="ri-arrow-left-line mr-2"></i> Back to Profile
                    </a>
                </div>
            </div>
        </div>
    </main>

    <footer class="bg-primary-dark text-white py-12 flex-shrink-0">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <a href="/index.php" class="text-2xl font-heading font-bold flex items-center mb-4">
                        <i class="fa-solid fa-leaf mr-2 text-primary-light"></i>
                        FreshHarvest
                    </a>
                    <p class="text-sm opacity-80">Connecting local farmers with your table since 2020.</p>
                </div>
                <div>
                    <h5 class="font-heading font-bold text-lg mb-4">Quick Links</h5>
                    <ul class="space-y-2">
                        <li><a href="/products.php" class="hover:text-primary-light transition-colors">Products</a></li>
                        <li><a href="/categories.php" class="hover:text-primary-light transition-colors">Categories</a></li>
                        <li><a href="/farmers.php" class="hover:text-primary-light transition-colors">Our Farmers</a></li>
                        <li><a href="/about.php" class="hover:text-primary-light transition-colors">About Us</a></li>
                    </ul>
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
                        <a href="#" class="hover:text-primary-light transition-colors"><i class="ri-facebook-fill text-xl"></i></a>
                        <a href="#" class="hover:text-primary-light transition-colors"><i class="ri-twitter-fill text-xl"></i></a>
                        <a href="#" class="hover:text-primary-light transition-colors"><i class="ri-instagram-fill text-xl"></i></a>
                    </div>
                </div>
            </div>
            <div class="mt-12 pt-8 border-t border-white border-opacity-20 text-center">
                <p class="text-sm">© 2025 FreshHarvest. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Modal handling
        function closeSuccessModal() {
            document.getElementById('successModal').classList.remove('active');
            window.location.href = window.location.pathname + '?id=' + new URLSearchParams(window.location.search).get('id');
        }

        function closeErrorModal() {
            document.getElementById('errorModal').classList.remove('active');
            window.location.href = window.location.pathname + '?id=' + new URLSearchParams(window.location.search).get('id');
        }

        // Cancel order confirmation
        function confirmCancel() {
            return confirm('Are you sure you want to cancel this order?');
        }

        // Loading spinner for cancel button
        document.getElementById('cancelForm')?.addEventListener('submit', function() {
            const button = document.getElementById('cancelButton');
            if (button) {
                button.innerHTML = '<i class="ri-loader-4-line animate-spin mr-2"></i> Processing...';
                button.disabled = true;
            }
        });

        // Mobile search toggle
        document.getElementById('mobile-search-toggle')?.addEventListener('click', function() {
            const form = document.getElementById('mobile-search-form');
            form.classList.toggle('hidden');
        });
    </script>
</body>
</html>