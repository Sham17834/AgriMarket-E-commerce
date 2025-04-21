<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
require_once 'db_connect.php';

// Redirect to login if not logged in
if (!$isLoggedIn) {
    header("Location: login.php?redirect=checkout.php");
    exit();
}

$customer_id = $_SESSION['user_id'];

// Enable error logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

// Get selected shipping method from session
if (!isset($_SESSION['selected_shipping'])) {
    $_SESSION['selected_shipping'] = 'standard';
}
$selected_shipping = $_SESSION['selected_shipping'];

// Shipping options
$shipping_options = [
    'standard' => ['name' => 'Standard Shipping', 'price' => 5.99, 'days' => '3-5 business days'],
    'express' => ['name' => 'Express Shipping', 'price' => 12.99, 'days' => '1-2 business days'],
    'pickup' => ['name' => 'Store Pickup', 'price' => 0.00, 'days' => 'Ready in 1 hour']
];

// Fetch user information from database
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $customer_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        throw new Exception("User not found.");
    }
} catch (Exception $e) {
    error_log("Error fetching user data: " . $e->getMessage());
    $error = "Error loading user data. Please try again.";
}

// Initialize form values from user data
$first_name = $user['first_name'] ?? '';
$last_name = $user['last_name'] ?? '';
$email = $user['email'] ?? '';
$phone = $user['phone'] ?? '';
$shipping_address = '';
$shipping_city = '';
$shipping_state = '';
$shipping_postal_code = '';

// Fetch cart data
try {
    $stmt = $pdo->prepare("SELECT cart_id, cart_data FROM shopping_carts WHERE customer_id = :customer_id");
    $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
    $stmt->execute();
    $cart = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cart || empty($cart['cart_data'])) {
        header("Location: cart.php?error=Empty+cart");
        exit();
    }

    $cart_data = json_decode($cart['cart_data'], true);
    if (json_last_error() !== JSON_ERROR_NONE || empty($cart_data)) {
        error_log("Invalid cart data JSON: " . json_last_error_msg());
        header("Location: cart.php?error=Invalid+cart+data");
        exit();
    }
} catch (PDOException $e) {
    error_log("Error accessing cart: " . $e->getMessage());
    header("Location: cart.php?error=Cart+access+failed");
    exit();
}

// Process form submission
$show_success_modal = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    // Validate form data
    $validation_errors = [];

    // Required fields validation
    $required_fields = [
        'first_name' => 'First Name',
        'last_name' => 'Last Name',
        'email' => 'Email',
        'phone' => 'Phone',
        'shipping_address' => 'Shipping Address',
        'shipping_city' => 'City',
        'shipping_state' => 'State',
        'shipping_postal_code' => 'Postal Code',
        'payment_method' => 'Payment Method'
    ];

    foreach ($required_fields as $field => $label) {
        if (empty($_POST[$field])) {
            $validation_errors[] = "$label is required.";
        }
    }

    // Email validation
    if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $validation_errors[] = "Please enter a valid email address.";
    }

    // Phone validation
    if (!empty($_POST['phone']) && !preg_match('/^\d{10,12}$/', preg_replace('/[^0-9]/', '', $_POST['phone']))) {
        $validation_errors[] = "Please enter a valid phone number.";
    }

    // Payment method validation
    $valid_payment_methods = ['credit_card', 'bank_transfer', 'mobile_payment', 'cod'];
    if (!in_array($_POST['payment_method'], $valid_payment_methods)) {
        $validation_errors[] = "Please select a valid payment method.";
    }

    // Credit card validation
    if ($_POST['payment_method'] === 'credit_card') {
        $cc_required_fields = [
            'card_number' => 'Card Number',
            'card_expiry' => 'Expiry Date',
            'card_cvv' => 'CVV',
            'card_holder' => 'Card Holder Name'
        ];

        foreach ($cc_required_fields as $field => $label) {
            if (empty($_POST[$field])) {
                $validation_errors[] = "$label is required.";
            }
        }

        if (!empty($_POST['card_number']) && !preg_match('/^\d{16}$/', preg_replace('/\s+/', '', $_POST['card_number']))) {
            $validation_errors[] = "Please enter a valid card number.";
        }

        if (!empty($_POST['card_expiry']) && !preg_match('/^(0[1-9]|1[0-2])\/([0-9]{2})$/', $_POST['card_expiry'])) {
            $validation_errors[] = "Please enter expiry date in MM/YY format.";
        }

        if (!empty($_POST['card_cvv']) && !preg_match('/^\d{3,4}$/', $_POST['card_cvv'])) {
            $validation_errors[] = "Please enter a valid CVV.";
        }
    }

    // Bank transfer validation
    if ($_POST['payment_method'] === 'bank_transfer') {
        if (empty($_POST['bank_reference_number'])) {
            $validation_errors[] = "Bank Reference Number is required.";
        }
    }

    // Mobile payment validation
    if ($_POST['payment_method'] === 'mobile_payment') {
        if (empty($_POST['mobile_payment_phone'])) {
            $validation_errors[] = "Phone Number is required for Mobile Payment.";
        } elseif (!preg_match('/^\d{10,12}$/', preg_replace('/[^0-9]/', '', $_POST['mobile_payment_phone']))) {
            $validation_errors[] = "Please enter a valid phone number for Mobile Payment.";
        }
    }

    // If validation passes, create order
    if (empty($validation_errors)) {
        try {
            // Start transaction
            $pdo->beginTransaction();

            // Validate cart items against products
            $cart_items = [];
            $subtotal = 0;
            $product_ids = array_column($cart_data, 'product_id');
            if (empty($product_ids)) {
                throw new Exception("No products in cart.");
            }

            $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
            $stmt = $pdo->prepare("SELECT product_id, name, price, discounted_price, stock_quantity 
                                   FROM products 
                                   WHERE product_id IN ($placeholders) AND is_active = 1");
            $stmt->execute($product_ids);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $valid_product_ids = array_column($products, 'product_id');
            foreach ($cart_data as $cart_item) {
                if (!in_array($cart_item['product_id'], $valid_product_ids)) {
                    throw new Exception("Invalid or inactive product in cart: {$cart_item['product_id']}");
                }
                foreach ($products as $product) {
                    if ($product['product_id'] == $cart_item['product_id']) {
                        if ($cart_item['quantity'] <= 0) {
                            throw new Exception("Invalid quantity for product: {$product['name']}");
                        }
                        if ($product['stock_quantity'] < $cart_item['quantity']) {
                            throw new Exception("Insufficient stock for product: {$product['name']}");
                        }
                        $price = !is_null($product['discounted_price']) && $product['discounted_price'] < $product['price']
                            ? $product['discounted_price']
                            : $product['price'];
                        $item_subtotal = $price * $cart_item['quantity'];
                        $subtotal += $item_subtotal;

                        $cart_items[] = [
                            'product_id' => $product['product_id'],
                            'product_name' => $product['name'],
                            'quantity' => $cart_item['quantity'],
                            'unit_price' => $price,
                            'discount_amount' => ($product['price'] - $price) * $cart_item['quantity'],
                            'subtotal' => $item_subtotal
                        ];
                        break;
                    }
                }
            }

            if (empty($cart_items)) {
                throw new Exception("No valid items in cart after validation.");
            }

            // Calculate totals
            $shipping_cost = $shipping_options[$selected_shipping]['price'];
            $tax_rate = 0.06;
            $tax_amount = $subtotal * $tax_rate;
            $total_amount = $subtotal + $shipping_cost + $tax_amount;

            // Create shipping address string
            $shipping_address_full = $_POST['shipping_address'] . ', ' . $_POST['shipping_city'] . ', ' .
                $_POST['shipping_state'] . ' ' . $_POST['shipping_postal_code'];
            $billing_address = isset($_POST['same_address']) ? $shipping_address_full : ($_POST['billing_address'] ?? $shipping_address_full);

            // Create order
            $stmt = $pdo->prepare("INSERT INTO orders (
                customer_id, order_date, total_amount, shipping_fee, tax_amount, 
                payment_method, payment_status, order_status, shipping_address, 
                billing_address, customer_notes, product_name
            ) VALUES (
                ?, NOW(), ?, ?, ?, ?, 'pending', 'pending', ?, ?, ?, ?
            )");

            $product_names = array_column($cart_items, 'product_name');
            $first_product_name = count($product_names) > 0 ? $product_names[0] : 'Multiple Products';
            if (count($product_names) > 1) {
                $first_product_name .= ' and ' . (count($product_names) - 1) . ' more';
            }

            $customer_notes = $_POST['order_notes'] ?? '';
            $stmt->execute([
                $customer_id,
                $total_amount,
                $shipping_cost,
                $tax_amount,
                $_POST['payment_method'],
                $shipping_address_full,
                $billing_address,
                $customer_notes,
                $first_product_name
            ]);

            $order_id = $pdo->lastInsertId();

            // Create order items
            $stmt = $pdo->prepare("INSERT INTO order_items (
                order_id, product_id, product_name, quantity, unit_price, 
                discount_amount, subtotal
            ) VALUES (?, ?, ?, ?, ?, ?, ?)");

            foreach ($cart_items as $item) {
                $stmt->execute([
                    $order_id,
                    $item['product_id'],
                    $item['product_name'],
                    $item['quantity'],
                    $item['unit_price'],
                    $item['discount_amount'],
                    $item['subtotal']
                ]);

                // Update product stock
                $new_stock = $products[array_search($item['product_id'], array_column($products, 'product_id'))]['stock_quantity'] - $item['quantity'];
                $update_stmt = $pdo->prepare("UPDATE products SET stock_quantity = ? WHERE product_id = ?");
                $update_stmt->execute([$new_stock, $item['product_id']]);
            }

            // Clear cart
            $stmt = $pdo->prepare("UPDATE shopping_carts SET cart_data = '[]', updated_at = NOW() WHERE cart_id = ?");
            $stmt->execute([$cart['cart_id']]);

            // Sync session cart
            $_SESSION['cart'] = [];

            // Commit transaction
            $pdo->commit();

            // Set flag to show success modal
            $show_success_modal = true;
            $_SESSION['last_order_id'] = $order_id;

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Order creation failed: " . $e->getMessage());
            $error = "Error processing order: " . htmlspecialchars($e->getMessage());
        }
    } else {
        $error = implode("<br>", $validation_errors);
    }
}

// Fetch cart items for order summary
$cart_items = [];
$subtotal = 0;
if (!empty($cart_data)) {
    $product_ids = array_column($cart_data, 'product_id');
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    try {
        $stmt = $pdo->prepare("SELECT product_id, name, price, discounted_price, stock_quantity, minimum_order_quantity, image_url 
                               FROM products 
                               WHERE product_id IN ($placeholders) AND is_active = 1");
        $stmt->execute($product_ids);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($cart_data as $cart_item) {
            foreach ($products as $product) {
                if ($product['product_id'] == $cart_item['product_id']) {
                    $price = !is_null($product['discounted_price']) && $product['discounted_price'] < $product['price']
                        ? $product['discounted_price']
                        : $product['price'];
                    $item_subtotal = $price * $cart_item['quantity'];
                    $subtotal += $item_subtotal;
                    $cart_items[] = [
                        'product_id' => $product['product_id'],
                        'name' => $product['name'],
                        'price' => $price,
                        'quantity' => $cart_item['quantity'],
                        'subtotal' => $item_subtotal,
                        'image_url' => json_decode($product['image_url'], true)[0] ?? 'https://public.readdy.ai/api/placeholder/500/500'
                    ];
                    break;
                }
            }
        }
    } catch (PDOException $e) {
        error_log("Error fetching cart items: " . $e->getMessage());
        $error = "Error fetching cart items. Please try again.";
    }
}

// Calculate totals
$shipping_cost = $shipping_options[$selected_shipping]['price'];
$tax_rate = 0.06;
$tax_amount = $subtotal * $tax_rate;
$total = $subtotal + $shipping_cost + $tax_amount;

// Get cart item count for header
$cart_count = array_sum(array_column($cart_items, 'quantity'));
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FreshHarvest - Checkout</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Open+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css" rel="stylesheet">
    <style>
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            outline: none;
            transition: border-color 0.2s;
        }

        .form-input:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .form-input:disabled {
            background-color: #f3f4f6;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .payment-option {
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 0.75rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .payment-option:hover {
            border-color: #9ca3af;
        }

        .payment-option.selected {
            border-color: #10b981;
            background-color: #ecfdf5;
        }

        .payment-option input[type="radio"] {
            margin-right: 0.75rem;
        }

        .payment-methods-container {
            margin-bottom: 1.5rem;
        }

        .payment-details {
            display: none;
            padding: 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            margin-top: 0.75rem;
            background-color: #f9fafb;
        }

        .payment-details.active {
            display: block;
        }

        .order-item {
            display: flex;
            padding: 1rem 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .order-item img {
            width: 64px;
            height: 64px;
            object-fit: cover;
            border-radius: 0.375rem;
            margin-right: 1rem;
        }

        .error-box {
            background-color: #fee2e2;
            color: #b91c1c;
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1.5rem;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 2rem;
            border-radius: 0.5rem;
            width: 90%;
            max-width: 400px;
            text-align: center;
        }

        .modal.show {
            display: block;
        }
    </style>
</head>

<body class="bg-natural-light min-h-screen font-body">
    <!-- Success Modal -->
    <div id="successModal" class="modal <?php echo $show_success_modal ? 'show' : ''; ?>">
        <div class="modal-content">
            <h2 class="text-2xl font-heading font-bold text-green-600 mb-4">Payment Successful!</h2>
            <p class="text-gray-600 mb-6">Your order
                #<?php echo isset($_SESSION['last_order_id']) ? $_SESSION['last_order_id'] : ''; ?> has been placed
                successfully.</p>
            <button id="closeModal" class="bg-primary text-white px-4 py-2 rounded-full hover:bg-primary-dark">Continue
                Shopping</button>
        </div>
    </div>

    <header class="fixed top-0 left-0 right-0 bg-white shadow-md z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between h-20">
                <div class="flex items-center space-x-8">
                    <a href="index.php" class="text-3xl font-heading font-bold text-primary flex items-center">
                        <i class="ri-leaf-line mr-2 text-primary-light"></i>
                        AgriMarket
                    </a>
                    <nav class="hidden lg:flex space-x-8">
                        <a href="index.php" class="text-gray-700 hover:text-primary font-medium">Home</a>
                        <a href="products.php" class="text-gray-700 hover:text-primary font-medium">Products</a>
                        <a href="categories.php" class="text-gray-700 hover:text-primary font-medium">Categories</a>
                        <a href="farmers.php" class="text-gray-700 hover:text-primary font-medium">Meet Our Farmers</a>
                        <a href="about.php" class="text-gray-700 hover:text-primary font-medium">About Us</a>
                    </nav>
                </div>
                <div class="flex items-center space-x-6">
                    <div class="relative hidden md:block">
                        <form action="search.php" method="GET">
                            <input type="text" name="q" placeholder="Search for fresh produce..."
                                class="w-64 pl-10 pr-4 py-2 rounded-full border border-gray-200 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                            <button type="submit" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                                <i class="ri-search-line"></i>
                            </button>
                        </form>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="search.php" class="md:hidden text-gray-700"><i class="ri-search-line text-xl"></i></a>
                        <a href="cart.php" class="relative cursor-pointer group">
                            <i class="ri-shopping-cart-line text-xl group-hover:text-primary transition-colors"></i>
                            <span
                                class="absolute -top-2 -right-2 bg-accent text-white text-xs rounded-full w-5 h-5 flex items-center justify-center"><?php echo $cart_count; ?></span>
                        </a>
                        <a href="logout.php" class="cursor-pointer hover:text-primary transition-colors"
                            title="Log Out">
                            <i class="ri-logout-box-line text-xl"></i>
                        </a>
                        <a href="profile.php" class="cursor-pointer hover:text-primary transition-colors"
                            title="Profile">
                            <i class="ri-user-line text-xl"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="pt-20">
        <section class="py-16 bg-white">
            <div class="max-w-7xl mx-auto px-4">
                <nav class="flex items-center text-sm mb-6">
                    <a href="index.php" class="text-gray-500 hover:text-primary">Home</a>
                    <i class="ri-arrow-right-s-line mx-2 text-gray-400"></i>
                    <a href="cart.php" class="text-gray-500 hover:text-primary">Shopping Cart</a>
                    <i class="ri-arrow-right-s-line mx-2 text-gray-400"></i>
                    <span class="text-gray-700">Checkout</span>
                </nav>

                <h1 class="text-3xl font-heading font-bold mb-8">Checkout</h1>

                <?php if (isset($error)): ?>
                    <div class="error-box"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST" id="checkout-form">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <div class="lg:col-span-2">
                            <div class="bg-gray-50 p-6 rounded-lg mb-6">
                                <h2 class="text-xl font-heading font-bold mb-4">Customer Information</h2>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="form-group">
                                        <label for="first_name" class="form-label">First Name <span
                                                class="text-red-500">*</span></label>
                                        <input type="text" id="first_name" name="first_name" class="form-input"
                                            value="<?php echo htmlspecialchars($first_name); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="last_name" class="form-label">Last Name <span
                                                class="text-red-500">*</span></label>
                                        <input type="text" id="last_name" name="last_name" class="form-input"
                                            value="<?php echo htmlspecialchars($last_name); ?>" required>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="form-group">
                                        <label for="email" class="form-label">Email <span
                                                class="text-red-500">*</span></label>
                                        <input type="email" id="email" name="email" class="form-input"
                                            value="<?php echo htmlspecialchars($email); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="phone" class="form-label">Phone <span
                                                class="text-red-500">*</span></label>
                                        <input type="tel" id="phone" name="phone" class="form-input"
                                            value="<?php echo htmlspecialchars($phone); ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-gray-50 p-6 rounded-lg mb-6">
                                <h2 class="text-xl font-heading font-bold mb-4">Shipping Information</h2>
                                <div class="form-group">
                                    <label for="shipping_address" class="form-label">Address <span
                                            class="text-red-500">*</span></label>
                                    <input type="text" id="shipping_address" name="shipping_address" class="form-input"
                                        value="<?php echo htmlspecialchars($shipping_address); ?>" required>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="form-group">
                                        <label for="shipping_city" class="form-label">City <span
                                                class="text-red-500">*</span></label>
                                        <input type="text" id="shipping_city" name="shipping_city" class="form-input"
                                            value="<?php echo htmlspecialchars($shipping_city); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="shipping_state" class="form-label">State <span
                                                class="text-red-500">*</span></label>
                                        <input type="text" id="shipping_state" name="shipping_state" class="form-input"
                                            value="<?php echo htmlspecialchars($shipping_state); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="shipping_postal_code" class="form-label">Postal Code <span
                                                class="text-red-500">*</span></label>
                                        <input type="text" id="shipping_postal_code" name="shipping_postal_code"
                                            class="form-input"
                                            value="<?php echo htmlspecialchars($shipping_postal_code); ?>" required>
                                    </div>
                                </div>
                                <div class="flex items-center mb-4">
                                    <input type="checkbox" id="same_address" name="same_address" class="mr-3" checked>
                                    <label for="same_address" class="font-medium">Same as shipping address</label>
                                </div>
                                <div id="billing-address-container" class="hidden">
                                    <h2 class="text-xl font-heading font-bold mb-4">Billing Address</h2>
                                    <div class="form-group">
                                        <label for="billing_address" class="form-label">Address</label>
                                        <input type="text" id="billing_address" name="billing_address"
                                            class="form-input">
                                    </div>
                                </div>
                            </div>
                            <div class="bg-gray-50 p-6 rounded-lg mb-6">
                                <h2 class="text-xl font-heading font-bold mb-4">Payment Method</h2>
                                <div class="payment-methods-container">
                                    <!-- Credit Card -->
                                    <label class="payment-option block selected">
                                        <input type="radio" name="payment_method" value="credit_card"
                                            class="payment-method" checked>
                                        <span class="font-medium">Credit Card</span>
                                        <span class="ml-2 text-gray-500">
                                            <i class="ri-visa-fill text-lg"></i>
                                            <i class="ri-mastercard-fill text-lg ml-1"></i>
                                        </span>
                                    </label>
                                    <div id="credit_card-details" class="payment-details active">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div class="form-group">
                                                <label for="card_number" class="form-label">Card Number <span
                                                        class="text-red-500">*</span></label>
                                                <input type="text" id="card_number" name="card_number"
                                                    class="form-input" placeholder="1234 5678 9012 3456" maxlength="19"
                                                    required>
                                            </div>
                                            <div class="form-group">
                                                <label for="card_holder" class="form-label">Card Holder Name <span
                                                        class="text-red-500">*</span></label>
                                                <input type="text" id="card_holder" name="card_holder"
                                                    class="form-input" placeholder="John Doe" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="card_expiry" class="form-label">Expiry Date <span
                                                        class="text-red-500">*</span></label>
                                                <input type="text" id="card_expiry" name="card_expiry"
                                                    class="form-input" placeholder="MM/YY" maxlength="5" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="card_cvv" class="form-label">CVV <span
                                                        class="text-red-500">*</span></label>
                                                <input type="text" id="card_cvv" name="card_cvv" class="form-input"
                                                    placeholder="123" maxlength="4" required>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Bank Transfer -->
                                    <label class="payment-option block">
                                        <input type="radio" name="payment_method" value="bank_transfer"
                                            class="payment-method">
                                        <span class="font-medium">Bank Transfer</span>
                                    </label>
                                    <div id="bank_transfer-details" class="payment-details">
                                        <div class="form-group">
                                            <label for="bank_reference_number" class="form-label">Bank Account Number
                                                <span class="text-red-500">*</span></label>
                                            <input type="text" id="bank_reference_number" name="bank_reference_number"
                                                class="form-input" placeholder="Enter your bank reference number">
                                        </div>
                                    </div>

                                    <!-- Mobile Payment -->
                                    <label class="payment-option block">
                                        <input type="radio" name="payment_method" value="mobile_payment"
                                            class="payment-method">
                                        <span class="font-medium">Mobile Payment</span>
                                        <span class="ml-2 text-gray-500">
                                            <i class="ri-paypal-fill text-lg"></i>
                                        </span>
                                    </label>
                                    <div id="mobile_payment-details" class="payment-details">
                                        <p class="text-sm text-gray-600 mb-2">Payment will be processed using the phone
                                            number provided in Customer Information.</p>
                                        <div class="form-group">
                                            <label for="mobile_payment_phone" class="form-label">Phone Number <span
                                                    class="text-red-500">*</span></label>
                                            <input type="tel" id="mobile_payment_phone" name="mobile_payment_phone"
                                                class="form-input" readonly required>
                                        </div>
                                    </div>

                                    <!-- Cash on Delivery -->
                                    <label class="payment-option block">
                                        <input type="radio" name="payment_method" value="cod" class="payment-method">
                                        <span class="font-medium">Cash on Delivery</span>
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label for="order_notes" class="form-label">Order Notes (Optional)</label>
                                    <textarea id="order_notes" name="order_notes" class="form-input" rows="4"
                                        placeholder="Any special instructions for your order..."></textarea>
                                </div>
                            </div>
                            <div class="mt-6">
                                <button type="submit" name="place_order"
                                    class="w-full bg-primary text-white px-6 py-3 rounded-full hover:bg-primary-dark transition-colors">
                                    Place Order
                                </button>
                            </div>
                        </div>
                        <div class="lg:col-span-1">
                            <div class="bg-gray-50 p-6 rounded-lg sticky top-24">
                                <h2 class="text-xl font-heading font-bold mb-4">Order Summary</h2>
                                <?php foreach ($cart_items as $item): ?>
                                    <div class="order-item">
                                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>"
                                            alt="<?php echo htmlspecialchars($item['name']); ?>">
                                        <div class="flex-1">
                                            <p class="font-medium"><?php echo htmlspecialchars($item['name']); ?></p>
                                            <p class="text-sm text-gray-600">Quantity: <?php echo $item['quantity']; ?></p>
                                            <p class="text-sm font-medium">
                                                RM<?php echo number_format($item['subtotal'], 2); ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <div class="mt-4 pt-4 border-t">
                                    <div class="flex justify-between mb-2">
                                        <span>Subtotal</span>
                                        <span>RM<?php echo number_format($subtotal, 2); ?></span>
                                    </div>
                                    <div class="flex justify-between mb-2">
                                        <span>Shipping
                                            (<?php echo $shipping_options[$selected_shipping]['name']; ?>)</span>
                                        <span>RM<?php echo number_format($shipping_cost, 2); ?></span>
                                    </div>
                                    <div class="flex justify-between mb-2">
                                        <span>Tax (6%)</span>
                                        <span>RM<?php echo number_format($tax_amount, 2); ?></span>
                                    </div>
                                    <div class="flex justify-between font-bold text-lg border-t pt-3">
                                        <span>Total</span>
                                        <span>RM<?php echo number_format($total, 2); ?></span>
                                    </div>
                                </div>
                                <div class="mt-4 flex justify-center space-x-4">
                                    <i class="ri-visa-fill text-2xl text-gray-600"></i>
                                    <i class="ri-mastercard-fill text-2xl text-gray-600"></i>
                                    <i class="ri-paypal-fill text-2xl text-gray-600"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </section>
    </main>

    <footer class="bg-primary-dark text-white py-12">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <a href="index.php" class="text-2xl font-heading font-bold flex items-center mb-4">
                        <i class="ri-leaf-line mr-2 text-primary-light"></i>
                        FreshHarvest
                    </a>
                    <p class="text-sm opacity-80">AgriMarket Solutions is a fictitious business created for a university
                        course.</p>
                </div>
                <div>
                    <h5 class="font-heading font-bold text-lg mb-4">Quick Links</h5>
                    <ul class="space-y-2">
                        <li><a href="products.php" class="hover:text-primary-light transition-colors">Products</a></li>
                        <li><a href="categories.php" class="hover:text-primary-light transition-colors">Categories</a>
                        </li>
                        <li><a href="farmers.php" class="hover:text-primary-light transition-colors">Our Farmers</a>
                        </li>
                        <li><a href="about.php" class="hover:text-primary-light transition-colors">About Us</a></li>
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
                        <a href="https://web.facebook.com/INTI.edu/?locale=ms_MY&_rdc=1&_rdr#"
                            class="hover:text-primary-light transition-colors"><i
                                class="ri-facebook-fill text-xl"></i></a>
                        <a href="https://www.instagram.com/inti_edu/?hl=en"
                            class="hover:text-primary-light transition-colors"><i
                                class="ri-instagram-fill text-xl"></i></a>
                    </div>
                </div>
            </div>
            <div class="mt-12 pt-8 border-t border-white border-opacity-20 text-center">
                <p class="text-sm">© 2025 FreshHarvest. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Handle billing address toggle
            const sameAddressCheckbox = document.getElementById('same_address');
            const billingAddressContainer = document.getElementById('billing-address-container');
            sameAddressCheckbox.addEventListener('change', function () {
                billingAddressContainer.classList.toggle('hidden', this.checked);
                const billingAddressInput = document.getElementById('billing_address');
                if (this.checked) {
                    billingAddressInput.removeAttribute('required');
                } else {
                    billingAddressInput.setAttribute('required', '');
                }
            });

            // Define payment fields
            const creditCardFields = ['card_number', 'card_holder', 'card_expiry', 'card_cvv'];
            const bankTransferField = ['bank_reference_number'];
            const mobilePaymentField = ['mobile_payment_phone'];
            const allPaymentFields = [...creditCardFields, ...bankTransferField, ...mobilePaymentField];

            // Synchronize mobile payment phone with customer info phone
            const phoneInput = document.getElementById('phone');
            const mobilePaymentPhoneInput = document.getElementById('mobile_payment_phone');
            function syncMobilePaymentPhone() {
                if (mobilePaymentPhoneInput && phoneInput) {
                    mobilePaymentPhoneInput.value = phoneInput.value;
                }
            }
            phoneInput.addEventListener('input', syncMobilePaymentPhone);
            syncMobilePaymentPhone(); // Initial sync

            // Add event listeners to payment method radios
            const paymentMethods = document.querySelectorAll('.payment-method');
            paymentMethods.forEach(method => {
                method.addEventListener('change', function () {
                    // Clear all payment fields except mobile_payment_phone
                    allPaymentFields.forEach(field => {
                        const input = document.getElementById(field);
                        if (input && field !== 'mobile_payment_phone') {
                            input.value = '';
                        }
                    });

                    // Update selected styling
                    document.querySelectorAll('.payment-option').forEach(option => {
                        option.classList.remove('selected');
                    });
                    this.closest('.payment-option').classList.add('selected');

                    // Show/hide payment details
                    document.querySelectorAll('.payment-details').forEach(detail => {
                        detail.classList.remove('active');
                    });
                    const methodId = this.value;
                    const detailsSection = document.getElementById(`${methodId}-details`);
                    if (detailsSection) {
                        detailsSection.classList.add('active');
                    } else {
                        console.error(`Details section not found for method: ${methodId}`);
                    }

                    // Update field states
                    updatePaymentFields(methodId);

                    console.log('Selected payment method:', methodId);
                    console.log('Showing details:', detailsSection ? detailsSection.id : 'None');
                });
            });

            // Function to update field states
            function updatePaymentFields(methodId) {
                console.log('Updating fields for:', methodId);

                // Disable all payment fields
                allPaymentFields.forEach(field => {
                    const input = document.getElementById(field);
                    if (input) {
                        input.setAttribute('disabled', 'disabled');
                        input.removeAttribute('required');
                        console.log(`Disabled ${field}:`, input.hasAttribute('disabled'));
                    }
                });

                // Enable fields for selected method
                if (methodId === 'credit_card') {
                    creditCardFields.forEach(field => {
                        const input = document.getElementById(field);
                        if (input) {
                            input.removeAttribute('disabled');
                            input.setAttribute('required', 'required');
                            input.disabled = false;
                            console.log(`Enabled ${field}:`, !input.hasAttribute('disabled'));
                        }
                    });
                } else if (methodId === 'bank_transfer') {
                    bankTransferField.forEach(field => {
                        const input = document.getElementById(field);
                        if (input) {
                            input.removeAttribute('disabled');
                            input.setAttribute('required', 'required');
                            input.disabled = false;
                            input.style.display = 'none';
                            setTimeout(() => { input.style.display = ''; }, 0);
                            console.log(`Enabled ${field}:`, !input.hasAttribute('disabled'));
                        }
                    });
                } else if (methodId === 'mobile_payment') {
                    mobilePaymentField.forEach(field => {
                        const input = document.getElementById(field);
                        if (input) {
                            input.removeAttribute('disabled');
                            input.setAttribute('required', 'required');
                            input.disabled = false;
                            console.log(`Enabled ${field}:`, !input.hasAttribute('disabled'));
                        }
                    });
                }
            }

            // Initialize by triggering change on checked radio
            const initialMethod = document.querySelector('.payment-method:checked');
            if (initialMethod) {
                initialMethod.dispatchEvent(new Event('change'));
            }

            // Format card number
            const cardNumberInput = document.getElementById('card_number');
            cardNumberInput.addEventListener('input', function (e) {
                if (document.querySelector('input[name="payment_method"]:checked').value !== 'credit_card') return;
                let value = e.target.value.replace(/\D/g, '');
                let formatted = '';
                for (let i = 0; i < value.length; i++) {
                    if (i > 0 && i % 4 === 0) formatted += ' ';
                    formatted += value[i];
                }
                e.target.value = formatted.trim();
            });

            // Format card expiry
            const cardExpiryInput = document.getElementById('card_expiry');
            cardExpiryInput.addEventListener('input', function (e) {
                if (document.querySelector('input[name="payment_method"]:checked').value !== 'credit_card') return;
                let value = e.target.value.replace(/\D/g, '');
                if (value.length >= 3) {
                    value = value.slice(0, 2) + '/' + value.slice(2);
                }
                e.target.value = value;
            });

            // Form validation
            document.getElementById('checkout-form').addEventListener('submit', function (e) {
                const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
                if (paymentMethod === 'credit_card') {
                    const cardNumber = document.getElementById('card_number').value.replace(/\s/g, '');
                    const cardExpiry = document.getElementById('card_expiry').value;
                    const cardCvv = document.getElementById('card_cvv').value;
                    if (!/^\d{16}$/.test(cardNumber)) {
                        e.preventDefault();
                        alert('Please enter a valid 16-digit card number.');
                        return;
                    }
                    if (!/^(0[1-9]|1[0-2])\/([0-9]{2})$/.test(cardExpiry)) {
                        e.preventDefault();
                        alert('Please enter a valid expiry date in MM/YY format.');
                        return;
                    }
                    if (!/^\d{3,4}$/.test(cardCvv)) {
                        e.preventDefault();
                        alert('Please enter a valid CVV.');
                        return;
                    }
                } else if (paymentMethod === 'bank_transfer') {
                    const bankReferenceNumber = document.getElementById('bank_reference_number').value.trim();
                    if (!bankReferenceNumber) {
                        e.preventDefault();
                        alert('Please enter a bank reference number.');
                        return;
                    }
                } else if (paymentMethod === 'mobile_payment') {
                    const mobilePaymentPhone = document.getElementById('mobile_payment_phone').value.trim();
                    if (!/^\d{10,12}$/.test(mobilePaymentPhone.replace(/\D/g, ''))) {
                        e.preventDefault();
                        alert('Please enter a valid phone number in Customer Information.');
                        return;
                    }
                }
            });

            // Modal handling
            const successModal = document.getElementById('successModal');
            const closeModalButton = document.getElementById('closeModal');
            if (successModal.classList.contains('show')) {
                document.body.style.overflow = 'hidden';
            }
            if (closeModalButton) {
                closeModalButton.addEventListener('click', function () {
                    successModal.classList.remove('show');
                    document.body.style.overflow = 'auto';
                    window.location.href = 'index.php';
                });
            }
            successModal.addEventListener('click', function (e) {
                if (e.target === successModal) {
                    successModal.classList.remove('show');
                    document.body.style.overflow = 'auto';
                    window.location.href = 'index.php';
                }
            });
        });
    </script>
</body>

</html>