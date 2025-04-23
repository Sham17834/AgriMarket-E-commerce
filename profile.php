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
    $stmt = $pdo->prepare("SELECT username, email, role, phone, password_hash FROM users WHERE user_id = :user_id");
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

// Fetch order history and create notifications for delivered orders
$orders = [];
try {
    $stmt = $pdo->prepare("
        SELECT o.order_id, o.order_date, o.order_status AS status 
        FROM orders o 
        WHERE o.customer_id = :customer_id 
        ORDER BY o.order_date DESC
    ");
    $stmt->bindParam(':customer_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($orders as &$order) {
        $stmt = $pdo->prepare("
            SELECT SUM(subtotal) as order_total 
            FROM order_items 
            WHERE order_id = :order_id
        ");
        $stmt->bindParam(':order_id', $order['order_id'], PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $order['total_amount'] = $result['order_total'] ?? 0;

        // Create notification for delivered orders
        if (strtolower($order['status']) === 'delivered') {
            // Check if notification already exists
            $stmt = $pdo->prepare("
                SELECT notification_id 
                FROM notifications 
                WHERE user_id = :user_id AND order_id = :order_id AND notification_type = 'order'
            ");
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':order_id', $order['order_id'], PDO::PARAM_INT);
            $stmt->execute();
            if (!$stmt->fetch()) {
                // Insert new notification
                $title = "Order Delivered";
                $message = "Your order #" . $order['order_id'] . " has been delivered.";
                $stmt = $pdo->prepare("
                    INSERT INTO notifications (user_id, order_id, title, message, notification_type, is_read, created_at) 
                    VALUES (:user_id, :order_id, :title, :message, 'order', 0, NOW())
                ");
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindParam(':order_id', $order['order_id'], PDO::PARAM_INT);
                $stmt->bindParam(':title', $title, PDO::PARAM_STR);
                $stmt->bindParam(':message', $message, PDO::PARAM_STR);
                $stmt->execute();
            }
        }
    }
    unset($order);
} catch (PDOException $e) {
    error_log("Error fetching orders or creating notifications: " . $e->getMessage());
}

// Create promotional notifications for discounted or new products
try {
    $stmt = $pdo->prepare("
        SELECT product_id, name, price, discounted_price, created_at 
        FROM products 
        WHERE is_active = 1 
        AND (
            (discounted_price > 0 AND discounted_price <= price * 0.8) 
            OR created_at >= NOW() - INTERVAL 7 DAY
        )
    ");
    $stmt->execute();
    $promo_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($promo_products as $product) {
        $is_new = strtotime($product['created_at']) >= strtotime('-7 days');
        $is_discounted = $product['discounted_price'] > 0 && $product['discounted_price'] <= $product['price'] * 0.8;

        // Check if notification already exists
        $stmt = $pdo->prepare("
            SELECT notification_id 
            FROM notifications 
            WHERE user_id = :user_id AND product_id = :product_id AND notification_type = 'promotion'
        ");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':product_id', $product['product_id'], PDO::PARAM_INT);
        $stmt->execute();
        if (!$stmt->fetch()) {
            // Insert new notification
            $title = $is_new ? "New Product Added" : "Special Offer";
            $message = $is_new 
                ? "New product " . htmlspecialchars($product['name']) . " is now available!"
                : "Check out " . htmlspecialchars($product['name']) . " at a discounted price of RM" . number_format($product['discounted_price'], 2) . "!";
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, product_id, title, message, notification_type, is_read, created_at) 
                VALUES (:user_id, :product_id, :title, :message, 'promotion', 0, NOW())
            ");
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':product_id', $product['product_id'], PDO::PARAM_INT);
            $stmt->bindParam(':title', $title, PDO::PARAM_STR);
            $stmt->bindParam(':message', $message, PDO::PARAM_STR);
            $stmt->execute();
        }
    }
} catch (PDOException $e) {
    error_log("Error creating promotional notifications: " . $e->getMessage());
}

// Fetch notifications
$notifications = [];
try {
    $stmt = $pdo->prepare("
        SELECT notification_id, title, message, notification_type, is_read, created_at 
        FROM notifications 
        WHERE user_id = :user_id 
        ORDER BY created_at DESC
    ");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching notifications: " . $e->getMessage());
}

// Handle marking notification as read via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_notification_read') {
    try {
        $notification_id = $_POST['notification_id'] ?? 0;
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = :notification_id AND user_id = :user_id");
        $stmt->bindParam(':notification_id', $notification_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        error_log("Error marking notification as read: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Failed to mark notification as read']);
    }
    exit;
}

// Handle form submission
$success_message = '';
$error_message = '';
$form_data = [
    'username' => $user['username'],
    'email' => $user['email'],
    'phone' => $user['phone'] ?? '',
    'current_password' => '',
    'new_password' => '',
    'confirm_password' => '',
    'notifications' => ['order_updates', 'promotions']
];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    try {
        // Capture form inputs
        $form_data['username'] = trim($_POST['username'] ?? '');
        $form_data['email'] = trim($_POST['email'] ?? '');
        $form_data['phone'] = trim($_POST['phone'] ?? '');
        $form_data['current_password'] = $_POST['current_password'] ?? '';
        $form_data['new_password'] = $_POST['new_password'] ?? '';
        $form_data['confirm_password'] = $_POST['confirm_password'] ?? '';
        $form_data['notifications'] = $_POST['notifications'] ?? [];

        // Validate inputs
        if (empty($form_data['username'])) {
            throw new Exception("Username is required.");
        }
        if (strlen($form_data['username']) > 50) {
            throw new Exception("Username must be 50 characters or less.");
        }
        if (empty($form_data['email']) || !filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("A valid email address is required.");
        }
        if (strlen($form_data['email']) > 255) {
            throw new Exception("Email must be 255 characters or less.");
        }
        if ($form_data['phone'] && (strlen($form_data['phone']) > 15 || !preg_match('/^[\+\d\s-]{0,15}$/', $form_data['phone']))) {
            throw new Exception("Invalid phone number format (max 15 characters).");
        }

        // Check for unique username and email
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE (username = :username OR email = :email) AND user_id != :user_id");
        $stmt->bindParam(':username', $form_data['username'], PDO::PARAM_STR);
        $stmt->bindParam(':email', $form_data['email'], PDO::PARAM_STR);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->fetch()) {
            throw new Exception("Username or email is already taken.");
        }

        // Prepare update
        $sql = "UPDATE users SET username = :username, email = :email, phone = :phone";
        $params = [
            ':username' => $form_data['username'],
            ':email' => $form_data['email'],
            ':phone' => $form_data['phone'] ?: null,
            ':user_id' => $user_id
        ];

        // Handle password change
        $password_fields = [$form_data['current_password'], $form_data['new_password'], $form_data['confirm_password']];
        if (array_filter($password_fields)) { 
            if (!$form_data['new_password'] || !$form_data['confirm_password']) {
                throw new Exception("Please provide both new password and confirmation.");
            }
            if ($form_data['new_password'] !== $form_data['confirm_password']) {
                throw new Exception("New passwords do not match.");
            }
            if (strlen($form_data['new_password']) < 8) {
                throw new Exception("New password must be at least 8 characters.");
            }
            $hashed_password = password_hash($form_data['new_password'], PASSWORD_DEFAULT);
            $sql .= ", password_hash = :password_hash";
            $params[':password_hash'] = $hashed_password;
        }

        // Execute update
        $sql .= " WHERE user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_null($value) ? PDO::PARAM_NULL : (is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR));
        }
        $stmt->execute();

        // Update user data for UI
        $user['username'] = $form_data['username'];
        $user['email'] = $form_data['email'];
        $user['phone'] = $form_data['phone'];

        // Log notifications
        error_log("User $user_id notification preferences: " . implode(', ', $form_data['notifications']));

        $success_message = "Profile updated successfully.";
        // Clear password fields after success
        $form_data['current_password'] = '';
        $form_data['new_password'] = '';
        $form_data['confirm_password'] = '';
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account | AgriMarket</title>
    <link rel="stylesheet" href="/style.css">
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Open+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script>
        function switchTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.add('hidden');
            });
            document.getElementById(tabName).classList.remove('hidden');
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('bg-primary', 'text-white');
                button.classList.add('bg-gray-100', 'text-gray-700');
            });
            document.getElementById(tabName + '-btn').classList.remove('bg-gray-100', 'text-gray-700');
            document.getElementById(tabName + '-btn').classList.add('bg-primary', 'text-white');
        }

        function markNotificationAsRead(notificationId) {
            fetch('/profile.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=mark_notification_read&notification_id=${notificationId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const notificationElement = document.getElementById(`notification-${notificationId}`);
                    notificationElement.classList.remove('bg-blue-50');
                    notificationElement.querySelector('.mark-read-btn').classList.add('hidden');
                    notificationElement.querySelector('.read-status').innerHTML = '<span class="text-green-600">Read</span>';
                } else {
                    alert('Failed to mark notification as read: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while marking the notification as read.');
            });
        }
    </script>
</head>
<body class="bg-gray-50 min-h-screen font-body flex flex-col">
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
                        <input type="text" name="search" id="search-input" placeholder="Search for fresh produce..."
                            class="w-64 pl-10 pr-4 py-2 rounded-full border border-gray-200 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
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
                                <input type="text" name="search" id="mobile-search-input" placeholder="Search for fresh produce..."
                                    class="w-full pl-10 pr-4 py-2 rounded-full border border-gray-200 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
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
        <div class="flex flex-col md:flex-row gap-6">
            <!-- Sidebar -->
            <div class="w-full md:w-64">
                <div class="bg-white rounded-xl shadow-sm overflow-hidden sticky top-28">
                    <div class="p-6 text-center border-b">
                        <div class="w-20 h-20 rounded-full bg-primary/10 mx-auto flex items-center justify-center">
                            <i class="ri-user-3-line text-4xl text-primary"></i>
                        </div>
                        <h2 class="mt-4 font-bold text-lg"><?php echo htmlspecialchars($user['username']); ?></h2>
                        <p class="text-sm text-gray-500"><?php echo ucfirst(htmlspecialchars($user['role'])); ?></p>
                    </div>
                    <div class="p-4">
                        <ul class="space-y-2">
                            <li>
                                <button id="profile-btn" onclick="switchTab('profile')" class="tab-button w-full p-3 rounded-lg flex items-center bg-gray-100 text-gray-700 hover:bg-gray-200 transition">
                                    <i class="ri-user-settings-line mr-3"></i>
                                    <span>My Profile</span>
                                </button>
                            </li>
                            <li>
                                <button id="orders-btn" onclick="switchTab('orders')" class="tab-button w-full p-3 rounded-lg flex items-center bg-gray-100 text-gray-700 hover:bg-gray-200 transition">
                                    <i class="ri-shopping-bag-line mr-3"></i>
                                    <span>Order History</span>
                                </button>
                            </li>
                            <li>
                                <button id="notifications-btn" onclick="switchTab('notifications')" class="tab-button w-full p-3 rounded-lg flex items-center bg-gray-100 text-gray-700 hover:bg-gray-200 transition">
                                    <i class="ri-notification-3-line mr-3"></i>
                                    <span>Notifications</span>
                                </button>
                            </li>
                            <li>
                                <button id="settings-btn" onclick="switchTab('settings')" class="tab-button w-full p-3 rounded-lg flex items-center bg-primary text-white hover:bg-primary-dark transition">
                                    <i class="ri-settings-3-line mr-3"></i>
                                    <span>Account Settings</span>
                                </button>
                            </li>
                            <li>
                                <a href="/cart.php" class="w-full p-3 rounded-lg flex items-center bg-gray-100 text-gray-700 hover:bg-gray-200 transition">
                                    <i class="ri-shopping-cart-line mr-3"></i>
                                    <span>View Cart</span>
                                </a>
                            </li>
                            <li>
                                <a href="/wishlist.php" class="w-full p-3 rounded-lg flex items-center bg-gray-100 text-gray-700 hover:bg-gray-200 transition">
                                    <i class="ri-heart-line mr-3"></i>
                                    <span>Wishlist</span>
                                </a>
                            </li>
                            <li>
                                <a href="/logout.php" class="w-full p-3 rounded-lg flex items-center bg-gray-100 text-gray-700 hover:bg-gray-200 transition">
                                    <i class="ri-logout-box-line mr-3"></i>
                                    <span>Log Out</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="flex-1">
                <!-- Profile Tab -->
                <section id="profile" class="tab-content bg-white rounded-xl shadow-sm p-6 hidden">
                    <h1 class="text-2xl font-heading font-bold text-gray-800 mb-6">My Profile</h1>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="font-semibold text-gray-700 mb-4">Account Information</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">Username</label>
                                    <div class="text-gray-800 font-medium">
                                        <?php echo htmlspecialchars($user['username']); ?>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">Email Address</label>
                                    <div class="text-gray-800 font-medium">
                                        <?php echo htmlspecialchars($user['email']); ?>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">Phone Number</label>
                                    <div class="text-gray-800 font-medium">
                                        <?php echo !empty($user['phone']) ? htmlspecialchars($user['phone']) : 'Not provided'; ?>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">Account Type</label>
                                    <div class="text-gray-800 font-medium">
                                        <?php echo ucfirst(htmlspecialchars($user['role'])); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-700 mb-4">Account Status</h3>
                            <div class="p-4 bg-green-50 border border-green-100 rounded-lg">
                                <div class="flex items-center">
                                    <div class="p-3 bg-green-100 rounded-full mr-4">
                                        <i class="ri-check-line text-xl text-green-600"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-medium text-green-800">Active Account</h4>
                                        <p class="text-sm text-green-600">Your account is in good standing</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Orders Tab -->
                <section id="orders" class="tab-content bg-white rounded-xl shadow-sm p-6 hidden">
                    <h1 class="text-2xl font-heading font-bold text-gray-800 mb-6">Order History</h1>
                    <?php if (empty($orders)): ?>
                        <div class="text-center py-8">
                            <div class="mx-auto w-16 h-16 flex items-center justify-center bg-gray-100 rounded-full mb-4">
                                <i class="ri-shopping-bag-line text-2xl text-gray-400"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-700 mb-2">No orders yet</h3>
                            <p class="text-gray-500 mb-4">You haven't placed any orders with us yet.</p>
                            <a href="/products.php" class="inline-block bg-primary text-white py-2 px-4 rounded-lg hover:bg-primary-dark transition">
                                Start Shopping
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto max-h-96 overflow-y-auto">
                            <table class="w-full text-left border-collapse">
                                <thead class="sticky top-0 bg-gray-50">
                                    <tr>
                                        <th class="py-4 px-6 font-semibold text-gray-700">Order ID</th>
                                        <th class="py-4 px-6 font-semibold text-gray-700">Date</th>
                                        <th class="py-4 px-6 font-semibold text-gray-700">Amount</th>
                                        <th class="py-4 px-6 font-semibold text-gray-700">Status</th>
                                        <th class="py-4 px-6 font-semibold text-gray-700">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <?php foreach ($orders as $order): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="py-4 px-6 text-gray-800">#<?php echo htmlspecialchars($order['order_id']); ?></td>
                                            <td class="py-4 px-6 text-gray-800">
                                                <?php echo date('M j, Y', strtotime($order['order_date'])); ?>
                                            </td>
                                            <td class="py-4 px-6 font-medium text-gray-800">
                                                RM<?php echo number_format($order['total_amount'], 2); ?>
                                            </td>
                                            <td class="py-4 px-6">
                                                <?php
                                                $statusClass = 'bg-gray-100 text-gray-800';
                                                switch(strtolower($order['status'])) {
                                                    case 'completed':
                                                    case 'delivered':
                                                        $statusClass = 'bg-green-100 text-green-800';
                                                        break;
                                                    case 'processing':
                                                    case 'pending':
                                                        $statusClass = 'bg-blue-100 text-blue-800';
                                                        break;
                                                    case 'shipped':
                                                        $statusClass = 'bg-purple-100 text-purple-800';
                                                        break;
                                                    case 'cancelled':
                                                        $statusClass = 'bg-red-100 text-red-800';
                                                        break;
                                                }
                                                ?>
                                                <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo $statusClass; ?>">
                                                    <?php echo htmlspecialchars(ucfirst($order['status'])); ?>
                                                </span>
                                            </td>
                                            <td class="py-4 px-6">
                                                <a href="/order-details.php?id=<?php echo $order['order_id']; ?>" class="text-primary hover:text-primary-dark font-medium">
                                                    View Details
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>

                <!-- Notifications Tab -->
                <section id="notifications" class="tab-content bg-white rounded-xl shadow-sm p-6 hidden">
                    <h1 class="text-2xl font-heading font-bold text-gray-800 mb-6">Notifications</h1>
                    <?php if (empty($notifications)): ?>
                        <div class="text-center py-8">
                            <div class="mx-auto w-16 h-16 flex items-center justify-center bg-gray-100 rounded-full mb-4">
                                <i class="ri-notification-3-line text-2xl text-gray-400"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-700 mb-2">No notifications</h3>
                            <p class="text-gray-500">You don't have any notifications at the moment.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4 max-h-96 overflow-y-auto">
                            <?php foreach ($notifications as $notification): ?>
                                <div id="notification-<?php echo $notification['notification_id']; ?>" 
                                     class="p-4 rounded-lg border <?php echo $notification['is_read'] ? 'bg-gray-50' : 'bg-blue-50'; ?>">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h4 class="font-medium text-gray-800">
                                                <?php echo htmlspecialchars($notification['title']); ?>
                                                <span class="ml-2 text-xs px-2 py-1 rounded-full 
                                                    <?php
                                                    switch ($notification['notification_type']) {
                                                        case 'order':
                                                            echo 'bg-blue-100 text-blue-800';
                                                            break;
                                                        case 'promotion':
                                                            echo 'bg-yellow-100 text-yellow-800';
                                                            break;
                                                        case 'system':
                                                            echo 'bg-gray-100 text-gray-800';
                                                            break;
                                                        case 'stock':
                                                            echo 'bg-red-100 text-red-800';
                                                            break;
                                                    }
                                                    ?>">
                                                    <?php echo ucfirst(htmlspecialchars($notification['notification_type'])); ?>
                                                </span>
                                            </h4>
                                            <p class="text-sm text-gray-600 mt-1">
                                                <?php echo htmlspecialchars($notification['message']); ?>
                                            </p>
                                            <p class="text-xs text-gray-500 mt-2">
                                                <?php echo date('M j, Y, h:i A', strtotime($notification['created_at'])); ?>
                                            </p>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <span class="read-status text-sm">
                                                <?php echo $notification['is_read'] ? '<span class="text-green-600">Read</span>' : '<span class="text-red-600">Unread</span>'; ?>
                                            </span>
                                            <?php if (!$notification['is_read']): ?>
                                                <button onclick="markNotificationAsRead(<?php echo $notification['notification_id']; ?>)"
                                                        class="mark-read-btn text-primary hover:text-primary-dark text-sm font-medium">
                                                    Mark as Read
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <!-- Settings Tab -->
                <section id="settings" class="tab-content bg-white rounded-xl shadow-sm p-6">
                    <h1 class="text-2xl font-heading font-bold text-gray-800 mb-6">Account Settings</h1>
                    <?php if ($success_message): ?>
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-r-lg">
                            <?php echo htmlspecialchars($success_message); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($error_message): ?>
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r-lg">
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>
                    <form action="/profile.php" method="POST" class="space-y-6">
                        <div>
                            <h3 class="font-semibold text-gray-700 mb-4">Personal Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($form_data['username']); ?>" 
                                        class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                </div>
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($form_data['email']); ?>" 
                                        class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                </div>
                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($form_data['phone']); ?>" 
                                        class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                        placeholder="Enter your phone number">
                                </div>
                            </div>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-700 mb-4">Change Password</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                                    <input type="password" id="new_password" name="new_password" value="<?php echo htmlspecialchars($form_data['new_password']); ?>" 
                                        class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                </div>
                                <div>
                                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                                    <input type="password" id="confirm_password" name="confirm_password" value="<?php echo htmlspecialchars($form_data['confirm_password']); ?>" 
                                        class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                </div>
                            </div>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-700 mb-4">Notification Preferences</h3>
                            <div class="space-y-3">
                                <div class="flex items-center">
                                    <input type="checkbox" id="order_updates" name="notifications[]" value="order_updates" 
                                        class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded" 
                                        <?php echo in_array('order_updates', $form_data['notifications']) ? 'checked' : ''; ?>>
                                    <label for="order_updates" class="ml-2 block text-sm text-gray-700">Order updates and delivery notifications</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" id="promotions" name="notifications[]" value="promotions" 
                                        class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded" 
                                        <?php echo in_array('promotions', $form_data['notifications']) ? 'checked' : ''; ?>>
                                    <label for="promotions" class="ml-2 block text-sm text-gray-700">Promotions and special offers</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" id="newsletters" name="notifications[]" value="newsletters" 
                                        class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded" 
                                        <?php echo in_array('newsletters', $form_data['notifications']) ? 'checked' : ''; ?>>
                                    <label for="newsletters" class="ml-2 block text-sm text-gray-700">Newsletters and farming updates</label>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center justify-end pt-4">
                            <button type="button" onclick="this.form.reset();" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 mr-4 hover:bg-gray-50 transition">
                                Cancel
                            </button>
                            <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </section>
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
                    AgriMarket
                </a>
                <p class="text-sm opacity-80">AgriMarket Solutions is a fictitious business created for a university course.</p>
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
                    <li><i class="ri-mail-line mr-2"></i> support@agrimarket.com</li>
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
            <p class="text-sm">© 2025 AgriMarket Solutions. All rights reserved.</p>
        </div>
    </div>
</footer>
</body>
</html>