<?php
ob_start();
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
require_once 'db_connect.php';

// Redirect to login if not logged in
if (!$isLoggedIn) {
    header("Location: login.php?redirect=checkout.php");
    exit();
}

$customer_id = $_SESSION['user_id'];

// Shipping options
$shipping_options = [
    'standard' => [
        'name' => 'Standard Shipping',
        'price' => 5.99,
        'days' => '3-5 business days'
    ],
    'express' => [
        'name' => 'Express Shipping',
        'price' => 12.99,
        'days' => '1-2 business days'
    ],
    'pickup' => [
        'name' => 'Store Pickup',
        'price' => 0.00,
        'days' => 'Ready in 1 hour'
    ]
];

// Get the selected shipping method from session
$selected_shipping = $_SESSION['selected_shipping'] ?? 'standard';
$shipping_cost = $shipping_options[$selected_shipping]['price'];

// Fetch cart data
try {
    $stmt = $pdo->prepare("SELECT cart_id, cart_data FROM shopping_carts WHERE customer_id = :customer_id");
    $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
    $stmt->execute();
    $cart = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cart || empty($cart['cart_data'])) {
        $cart_data = [];
        $error = "Your cart is empty. Please add items to your cart before checking out.";
    } else {
        $cart_data = json_decode($cart['cart_data'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $cart_data = [];
            $error = "Error decoding cart data.";
        }
        $cart_id = $cart['cart_id'];
    }
} catch (PDOException $e) {
    $error = "Error accessing cart: " . $e->getMessage();
    $cart_data = [];
}

// Fetch cart items with product details
$cart_items = [];
$subtotal = 0;
if (!empty($cart_data)) {
    $product_ids = array_column($cart_data, 'product_id');
    if (empty($product_ids)) {
        $error = "Cart contains no valid product IDs.";
        $cart_items = [];
        $subtotal = 0;
    } else {
        $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
        try {
            $stmt = $pdo->prepare("SELECT product_id, name, price, discounted_price, stock_quantity, minimum_order_quantity, image_url 
                                   FROM products 
                                   WHERE product_id IN ($placeholders) AND is_active = 1");
            $stmt->execute($product_ids);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($cart_data as $cart_item) {
                $found = false;
                foreach ($products as $product) {
                    if ($product['product_id'] == $cart_item['product_id']) {
                        $price = !is_null($product['discounted_price']) && $product['discounted_price'] > 0 && $product['discounted_price'] < $product['price']
                            ? $product['discounted_price']
                            : $product['price'];
                        if ($price <= 0) {
                            error_log("Skipping product ID {$product['product_id']}: Invalid price (price={$product['price']}, discounted_price={$product['discounted_price']})");
                            continue; // Skip invalid product
                        }
                        $quantity = max(1, intval($cart_item['quantity']));
                        $item_subtotal = $price * $quantity;
                        $subtotal += $item_subtotal;
                        $cart_items[] = [
                            'product_id' => $product['product_id'],
                            'name' => $product['name'],
                            'price' => $price,
                            'quantity' => $quantity,
                            'subtotal' => $item_subtotal,
                            'stock_quantity' => $product['stock_quantity'],
                            'minimum_order_quantity' => $product['minimum_order_quantity'],
                            'image_url' => json_decode($product['image_url'], true)[0] ?? 'https://public.readdy.ai/api/placeholder/500/500'
                        ];
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    error_log("Product ID {$cart_item['product_id']} not found or inactive for customer_id=$customer_id");
                }
            }
            if (empty($cart_items)) {
                $error = "No valid products found in cart. Please add available items.";
            }
        } catch (PDOException $e) {
            $error = "Error fetching cart items: " . $e->getMessage();
            error_log("Cart fetch error: " . $e->getMessage());
            $cart_items = [];
            $subtotal = 0;
        }
    }
} else {
    $error = "Your cart is empty. Please add items to proceed.";
}
$total = $subtotal + $shipping_cost;

// Debug total
if ($total <= 0) {
    $error = isset($error) ? $error . "<br>Total is invalid: $total (Subtotal: $subtotal, Shipping: $shipping_cost)" : "Total is invalid: $total (Subtotal: $subtotal, Shipping: $shipping_cost)";
}

// Get cart item count
$cart_count = array_sum(array_column($cart_items, 'quantity'));

// Function to create an order
function createOrder($pdo, $customer_id, $total, $shipping_cost, $shipping_address, $cart_items, $cart_id, $payment_method) {
    if (empty($cart_items)) {
        throw new Exception("Cannot place order: No valid items in cart.");
    }
    if ($total <= 0 || is_null($total)) {
        throw new Exception("Cannot place order: Total must be greater than zero (got $total).");
    }

    try {
        $pdo->beginTransaction();

        // Map payment method to ENUM values
        $mapped_payment_method = $payment_method;
        if ($payment_method === 'card') {
            $mapped_payment_method = 'credit_card';
        } elseif ($payment_method === 'mobile') {
            $mapped_payment_method = 'mobile_payment';
        } elseif ($payment_method === 'bank_transfer') {
            $mapped_payment_method = 'bank_transfer';
        } elseif ($payment_method === 'cod') {
            $mapped_payment_method = 'cod';
        }

        // Default values for orders table
        $tax_amount = 0.00;
        $payment_status = 'pending';
        $order_status = 'pending';
        $billing_address = $shipping_address;
        $customer_notes = '';
        $total = floatval($total);

        // Insert into orders table
        $stmt = $pdo->prepare("
            INSERT INTO orders (
                customer_id, order_date, total_amount, shipping_fee, tax_amount, 
                payment_method, payment_status, order_status, shipping_address, 
                billing_address, customer_notes
            ) VALUES (
                :customer_id, NOW(), :total_amount, :shipping_fee, :tax_amount, 
                :payment_method, :payment_status, :order_status, :shipping_address, 
                :billing_address, :customer_notes
            )
        ");
        $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
        $stmt->bindParam(':total_amount', $total);
        $stmt->bindParam(':shipping_fee', $shipping_cost);
        $stmt->bindParam(':tax_amount', $tax_amount);
        $stmt->bindParam(':payment_method', $mapped_payment_method, PDO::PARAM_STR);
        $stmt->bindParam(':payment_status', $payment_status, PDO::PARAM_STR);
        $stmt->bindParam(':order_status', $order_status, PDO::PARAM_STR);
        $stmt->bindParam(':shipping_address', $shipping_address, PDO::PARAM_STR);
        $stmt->bindParam(':billing_address', $billing_address, PDO::PARAM_STR);
        $stmt->bindParam(':customer_notes', $customer_notes, PDO::PARAM_STR);
        $stmt->execute();
        $order_id = $pdo->lastInsertId();

        // Insert into order_items table (exclude subtotal)
        $stmt = $pdo->prepare("
            INSERT INTO order_items (
                order_id, product_id, quantity, unit_price, discount_amount, product_name
            ) VALUES (
                :order_id, :product_id, :quantity, :unit_price, :discount_amount, :product_name
            )
        ");
        foreach ($cart_items as $item) {
            $discount_amount = 0.00; // Adjust if you have discount logic
            $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
            $stmt->bindParam(':product_id', $item['product_id'], PDO::PARAM_INT);
            $stmt->bindParam(':quantity', $item['quantity'], PDO::PARAM_INT);
            $stmt->bindParam(':unit_price', $item['price']);
            $stmt->bindParam(':discount_amount', $discount_amount);
            $stmt->bindParam(':product_name', $item['name'], PDO::PARAM_STR);
            $stmt->execute();

            // Update product stock
            $stmt = $pdo->prepare("
                UPDATE products 
                SET stock_quantity = stock_quantity - :quantity 
                WHERE product_id = :product_id
            ");
            $stmt->bindParam(':quantity', $item['quantity'], PDO::PARAM_INT);
            $stmt->bindParam(':product_id', $item['product_id'], PDO::PARAM_INT);
            $stmt->execute();
        }

        // Clear the cart
        $empty_cart_data = '[]';
        $stmt = $pdo->prepare("
            UPDATE shopping_carts 
            SET cart_data = :cart_data, updated_at = NOW()
            WHERE cart_id = :cart_id
        ");
        $stmt->bindParam(':cart_data', $empty_cart_data, PDO::PARAM_STR);
        $stmt->bindParam(':cart_id', $cart_id, PDO::PARAM_INT);
        $stmt->execute();

        // Fetch order date for success message
        $stmt = $pdo->prepare("SELECT order_date FROM orders WHERE order_id = :order_id");
        $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
        $stmt->execute();
        $order_date = $stmt->fetchColumn();

        $pdo->commit();
        return ['order_id' => $order_id, 'order_date' => $order_date, 'total' => $total];
    } catch (PDOException $e) {
        $pdo->rollBack();
        throw new Exception("Error placing order: " . $e->getMessage());
    }
}

// Handle form submission
$show_success = false;
$order_details = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    // Collect and sanitize form data
    $full_name = filter_var(trim($_POST['full_name'] ?? ''), FILTER_SANITIZE_STRING);
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $phone = filter_var(trim($_POST['phone'] ?? ''), FILTER_SANITIZE_STRING);
    $address = filter_var(trim($_POST['address'] ?? ''), FILTER_SANITIZE_STRING);
    $city = filter_var(trim($_POST['city'] ?? ''), FILTER_SANITIZE_STRING);
    $state = filter_var(trim($_POST['state'] ?? ''), FILTER_SANITIZE_STRING);
    $postcode = filter_var(trim($_POST['postcode'] ?? ''), FILTER_SANITIZE_STRING);
    $payment_method = filter_var($_POST['payment_method'] ?? 'card', FILTER_SANITIZE_STRING);

    // Basic validation for shipping information
    $errors = [];
    if (isset($error)) {
        $errors[] = $error; // Propagate cart errors
    }
    if (empty($full_name) || strlen($full_name) < 2 || strlen($full_name) > 100) {
        $errors[] = "Full name is required and must be between 2 and 100 characters.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "A valid email address is required.";
    }
    if (!preg_match("/^\d{3}-\d{7}$/", $phone)) {
        $errors[] = "Phone number must be in the format xxx-xxxxxxx (e.g., 012-3456789).";
    }
    if (empty($address) || strlen($address) < 5 || strlen($address) > 255) {
        $errors[] = "Address is required and must be between 5 and 255 characters.";
    }
    if (empty($city) || strlen($city) < 2 || strlen($city) > 100) {
        $errors[] = "City is required and must be between 2 and 100 characters.";
    }
    $valid_states = ['Johor', 'Kedah', 'Kelantan', 'Malacca', 'Negeri Sembilan', 'Pahang', 'Penang', 'Perak', 'Perlis', 'Sabah', 'Sarawak', 'Selangor', 'Terengganu', 'Kuala Lumpur', 'Labuan', 'Putrajaya'];
    if (empty($state) || !in_array($state, $valid_states)) {
        $errors[] = "A valid state is required.";
    }
    if (!preg_match("/^[0-9]{5}$/", $postcode)) {
        $errors[] = "A valid 5-digit postcode is required.";
    }

    // Recompute cart to ensure consistency
    $cart_items = [];
    $subtotal = 0;
    if (!empty($cart_data)) {
        $product_ids = array_column($cart_data, 'product_id');
        if (empty($product_ids)) {
            $errors[] = "Cart contains no valid product IDs.";
        } else {
            $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
            try {
                $stmt = $pdo->prepare("SELECT product_id, name, price, discounted_price, stock_quantity, minimum_order_quantity, image_url 
                                       FROM products 
                                       WHERE product_id IN ($placeholders) AND is_active = 1");
                $stmt->execute($product_ids);
                $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($cart_data as $cart_item) {
                    $found = false;
                    foreach ($products as $product) {
                        if ($product['product_id'] == $cart_item['product_id']) {
                            $price = !is_null($product['discounted_price']) && $product['discounted_price'] > 0 && $product['discounted_price'] < $product['price']
                                ? $product['discounted_price']
                                : $product['price'];
                            if ($price <= 0) {
                                error_log("Skipping product ID {$product['product_id']}: Invalid price (price={$product['price']}, discounted_price={$product['discounted_price']})");
                                continue;
                            }
                            $quantity = max(1, intval($cart_item['quantity']));
                            $item_subtotal = $price * $quantity;
                            $subtotal += $item_subtotal;
                            $cart_items[] = [
                                'product_id' => $product['product_id'],
                                'name' => $product['name'],
                                'price' => $price,
                                'quantity' => $quantity,
                                'subtotal' => $item_subtotal,
                                'stock_quantity' => $product['stock_quantity'],
                                'minimum_order_quantity' => $product['minimum_order_quantity'],
                                'image_url' => json_decode($product['image_url'], true)[0] ?? 'https://public.readdy.ai/api/placeholder/500/500'
                            ];
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        error_log("Product ID {$cart_item['product_id']} not found or inactive for customer_id=$customer_id");
                    }
                }
                if (empty($cart_items)) {
                    $errors[] = "No valid products found in cart. Please add available items.";
                }
            } catch (PDOException $e) {
                $errors[] = "Error fetching cart items: " . $e->getMessage();
                error_log("Cart fetch error: " . $e->getMessage());
            }
        }
    } else {
        $errors[] = "Your cart is empty. Please add items to proceed.";
    }
    $total = $subtotal + $shipping_cost;

    // Validate stock availability and minimum order quantity
    foreach ($cart_items as $item) {
        if ($item['quantity'] > $item['stock_quantity']) {
            $errors[] = "Insufficient stock for {$item['name']}. Available: {$item['stock_quantity']}, Requested: {$item['quantity']}.";
        }
        if ($item['quantity'] < $item['minimum_order_quantity']) {
            $errors[] = "Quantity for {$item['name']} must be at least {$item['minimum_order_quantity']}.";
        }
    }

    // Validate cart and total
    if (empty($cart_items)) {
        $errors[] = "Your cart is empty. Please add items to proceed.";
    }
    if ($total <= 0) {
        $errors[] = "Order total is invalid ($total). Please check your cart.";
    }

    // Validate payment method fields
    if ($payment_method === 'card') {
        $card_number = preg_replace('/\D/', '', trim($_POST['card_number'] ?? ''));
        $expiry_date = trim($_POST['expiry_date'] ?? '');
        $cvv = trim($_POST['cvv'] ?? '');

        if (!preg_match("/^[0-9]{15,16}$/", $card_number) || !luhn_check($card_number)) {
            $errors[] = "Valid card number is required (15-16 digits, must pass Luhn check).";
        }
        if (!preg_match("/^(0[1-9]|1[0-2])\/[0-9]{2}$/", $expiry_date) || !is_valid_expiry($expiry_date)) {
            $errors[] = "Valid expiry date (MM/YY) is required and must be in the future.";
        }
        if (!preg_match("/^[0-9]{3,4}$/", $cvv)) {
            $errors[] = "Valid CVV is required (3-4 digits).";
        }
    } elseif ($payment_method === 'mobile') {
        $mobile_payment_number = preg_replace('/\D/', '', trim($_POST['mobile_payment_number'] ?? ''));
        if (!preg_match("/^[0-9]{10,15}$/", $mobile_payment_number)) {
            $errors[] = "Valid mobile payment number is required (10-15 digits).";
        }
    } elseif ($payment_method === 'bank_transfer') {
        $bank_account_number = preg_replace('/\D/', '', trim($_POST['bank_account_number'] ?? ''));
        if (!preg_match("/^[0-9]{10,20}$/", $bank_account_number)) {
            $errors[] = "Valid bank account number is required (10-20 digits).";
        }
    } elseif ($payment_method !== 'cod') {
        $errors[] = "Invalid payment method selected.";
    }

    // Format shipping address
    $shipping_address = "$full_name\n$address\n$city, $state $postcode\nPhone: $phone\nEmail: $email";

    if (empty($errors)) {
        error_log("Checkout: customer_id=$customer_id, subtotal=$subtotal, shipping_cost=$shipping_cost, total=$total, cart_items=" . json_encode($cart_items));
        try {
            $order_details = createOrder($pdo, $customer_id, $total, $shipping_cost, $shipping_address, $cart_items, $cart_id, $payment_method);
            $show_success = true;
        } catch (Exception $e) {
            $error = $e->getMessage();
            error_log("Error placing order: " . $e->getMessage());
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

// Luhn Algorithm for card number validation
function luhn_check($number) {
    $sum = 0;
    $isEven = false;
    for ($i = strlen($number) - 1; $i >= 0; $i--) {
        $digit = (int)$number[$i];
        if ($isEven) {
            $digit *= 2;
            if ($digit > 9) {
                $digit -= 9;
            }
        }
        $sum += $digit;
        $isEven = !$isEven;
    }
    return $sum % 10 === 0;
}

// Validate expiry date is in the future
function is_valid_expiry($expiry_date) {
    if (!preg_match("/^(\d{2})\/(\d{2})$/", $expiry_date, $matches)) {
        return false;
    }
    $month = (int)$matches[1];
    $year = (int)$matches[2] + 2000;
    $current_year = (int)date('Y');
    $current_month = (int)date('n');
    if ($year < $current_year || ($year === $current_year && $month < $current_month)) {
        return false;
    }
    return true;
}
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
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Open+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css" rel="stylesheet">
    <style>
        .input-field {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            outline: none;
            transition: border-color 0.2s;
        }
        .input-field:focus {
            border-color: #10b981;
        }
        .input-field.border-red-500 {
            border-color: #dc2626;
        }
        .cart-table th, .cart-table td {
            padding: 1rem;
            text-align: left;
        }
        .cart-table img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 0.375rem;
        }
        .summary-box {
            background-color: #f9fafb;
            padding: 1.5rem;
            border-radius: 0.5rem;
        }
        .error-text {
            color: #dc2626;
            font-size: 0.875rem;
            margin-top: 0.25rem;
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
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            width: 90%;
            max-width: 450px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .modal-details {
            text-align: left;
        }
        .modal-details .total {
            display: block;
            font-size: 1.25rem;
            font-weight: bold;
            color: #3b82f6;
            margin-bottom: 0.25rem;
        }
        .modal-details .date {
            display: block;
            font-size: 0.875rem;
            color: #6b7280;
        }
        .modal-icon {
            width: 40px;
            height: 40px;
            background: #34c759;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .modal-icon .checkmark {
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
        }
        .modal-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 1.5rem;
        }
        .modal-button {
            display: inline-block;
            background: #3b82f6;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-size: 1rem;
            font-weight: 500;
            transition: background 0.2s;
        }
        .modal-button:hover {
            background: #2563eb;
        }
    </style>
</head>
<body class="bg-natural-light min-h-screen font-body">
    <!-- Header -->
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
                            <span class="absolute -top-2 -right-2 bg-accent text-white text-xs rounded-full w-5 h-5 flex items-center justify-center"><?php echo $cart_count; ?></span>
                        </a>
                        <?php if ($isLoggedIn): ?>
                            <a href="logout.php" class="cursor-pointer hover:text-primary transition-colors" title="Log Out">
                                <i class="ri-logout-box-line text-xl"></i>
                            </a>
                            <a href="profile.php" class="cursor-pointer hover:text-primary transition-colors" title="Profile">
                                <i class="ri-user-line text-xl"></i>
                            </a>
                        <?php else: ?>
                            <a href="login.php" class="cursor-pointer hover:text-primary transition-colors" title="Log In">
                                <i class="ri-user-line text-xl"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="pt-20">
        <section class="py-16 bg-white">
            <div class="max-w-7xl mx-auto px-4">
                <!-- Breadcrumb -->
                <nav class="flex items-center text-sm mb-6">
                    <a href="index.php" class="text-gray-500 hover:text-primary">Home</a>
                    <i class="ri-arrow-right-s-line mx-2 text-gray-400"></i>
                    <a href="cart.php" class="text-gray-500 hover:text-primary">Cart</a>
                    <i class="ri-arrow-right-s-line mx-2 text-gray-400"></i>
                    <span class="text-gray-700">Checkout</span>
                </nav>

                <h1 class="text-3xl font-heading font-bold mb-8">Checkout</h1>

                <!-- Error Messages -->
                <?php if (isset($error)): ?>
                    <div class="bg-red-100 text-red-800 p-4 rounded-lg mb-6">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($cart_items)): ?>
                    <div class="bg-gray-50 p-6 rounded-lg text-center">
                        <p class="text-gray-600">Your cart is empty. <a href="products.php" class="text-primary hover:underline">Start shopping now!</a></p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Checkout Form -->
                        <div class="lg:col-span-2 lg:order-first">
                            <form method="POST" id="checkout-form" class="space-y-8">
                                <!-- Shipping Information -->
                                <div class="bg-gray-50 p-6 rounded-lg">
                                    <h2 class="text-xl font-heading font-bold mb-4">Shipping Information</h2>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="md:col-span-2">
                                            <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                                            <input type="text" name="full_name" id="full_name" class="input-field" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
                                        </div>
                                        <div>
                                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                                            <input type="email" name="email" id="email" class="input-field" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                                        </div>
                                        <div>
                                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                            <input type="tel" name="phone" id="phone" class="input-field" placeholder="012-3456789" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required>
                                        </div>
                                        <div class="md:col-span-2">
                                            <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                                            <input type="text" name="address" id="address" class="input-field" value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>" required>
                                        </div>
                                        <div>
                                            <label for="city" class="block text-sm font-medium text-gray-700 mb-1">City</label>
                                            <input type="text" name="city" id="city" class="input-field" value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>" required>
                                        </div>
                                        <div>
                                            <label for="state" class="block text-sm font-medium text-gray-700 mb-1">State</label>
                                            <select name="state" id="state" class="input-field" required>
                                                <option value="">Select a state</option>
                                                <?php foreach (['Johor', 'Kedah', 'Kelantan', 'Malacca', 'Negeri Sembilan', 'Pahang', 'Penang', 'Perak', 'Perlis', 'Sabah', 'Sarawak', 'Selangor', 'Terengganu', 'Kuala Lumpur', 'Labuan', 'Putrajaya'] as $state_option): ?>
                                                    <option value="<?php echo htmlspecialchars($state_option); ?>" <?php echo (isset($_POST['state']) && $_POST['state'] === $state_option) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($state_option); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div>
                                            <label for="postcode" class="block text-sm font-medium text-gray-700 mb-1">Postcode</label>
                                            <input type="text" name="postcode" id="postcode" class="input-field" value="<?php echo htmlspecialchars($_POST['postcode'] ?? ''); ?>" required>
                                        </div>
                                    </div>
                                </div>

                                <!-- Payment Method Selection -->
                                <div class="bg-gray-50 p-6 rounded-lg">
                                    <h2 class="text-xl font-heading font-bold mb-4">Payment Method</h2>
                                    <div class="payment-methods">
                                        <!-- Credit/Debit Card -->
                                        <label class="payment-option block selected">
                                            <input type="radio" name="payment_method" value="card" class="payment-method" checked>
                                            <span class="font-medium">Credit/Debit Card</span>
                                            <div class="mt-4 card-details">
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                    <div class="md:col-span-2">
                                                        <label for="card_number" class="block text-sm font-medium text-gray-700 mb-1">Card Number</label>
                                                        <input type="text" name="card_number" id="card_number" class="input-field" placeholder="1234 5678 9012 3456" value="<?php echo htmlspecialchars($_POST['card_number'] ?? ''); ?>">
                                                    </div>
                                                    <div>
                                                        <label for="expiry_date" class="block text-sm font-medium text-gray-700 mb-1">Expiry Date (MM/YY)</label>
                                                        <input type="text" name="expiry_date" id="expiry_date" class="input-field" placeholder="MM/YY" value="<?php echo htmlspecialchars($_POST['expiry_date'] ?? ''); ?>">
                                                    </div>
                                                    <div>
                                                        <label for="cvv" class="block text-sm font-medium text-gray-700 mb-1">CVV</label>
                                                        <input type="text" name="cvv" id="cvv" class="input-field" placeholder="123" value="<?php echo htmlspecialchars($_POST['cvv'] ?? ''); ?>">
                                                    </div>
                                                </div>
                                                <div class="mt-4 flex justify-center space-x-4">
                                                    <i class="ri-visa-fill text-2xl text-gray-600"></i>
                                                    <i class="ri-mastercard-fill text-2xl text-gray-600"></i>
                                                </div>
                                            </div>
                                        </label>
                                        <!-- Mobile Payments -->
                                        <label class="payment-option block">
                                            <input type="radio" name="payment_method" value="mobile" class="payment-method">
                                            <span class="font-medium">Mobile Payments</span>
                                            <div class="mt-4 mobile-details hidden">
                                                <div class="grid grid-cols-1 gap-4">
                                                    <div>
                                                        <label for="mobile_payment_number" class="block text-sm font-medium text-gray-700 mb-1">Mobile Payment Number</label>
                                                        <input type="text" name="mobile_payment_number" id="mobile_payment_number" class="input-field" placeholder="Enter mobile payment number" value="<?php echo htmlspecialchars($_POST['mobile_payment_number'] ?? ''); ?>">
                                                    </div>
                                                </div>
                                                <div class="mt-4 flex justify-center">
                                                    <i class="ri-smartphone-line text-2xl text-gray-600"></i>
                                                </div>
                                            </div>
                                        </label>
                                        <!-- Bank Transfer -->
                                        <label class="payment-option block">
                                            <input type="radio" name="payment_method" value="bank_transfer" class="payment-method">
                                            <span class="font-medium">Bank Transfer</span>
                                            <div class="mt-4 bank-transfer-details hidden">
                                                <div class="grid grid-cols-1 gap-4">
                                                    <div>
                                                        <label for="bank_account_number" class="block text-sm font-medium text-gray-700 mb-1">Bank Account Number</label>
                                                        <input type="text" name="bank_account_number" id="bank_account_number" class="input-field" placeholder="Enter bank account number" value="<?php echo htmlspecialchars($_POST['bank_account_number'] ?? ''); ?>">
                                                    </div>
                                                </div>
                                                <div class="mt-4 flex justify-center">
                                                    <i class="ri-bank-line text-2xl text-gray-600"></i>
                                                </div>
                                            </div>
                                        </label>
                                        <!-- Cash on Delivery -->
                                        <label class="payment-option block">
                                            <input type="radio" name="payment_method" value="cod" class="payment-method">
                                            <span class="font-medium">Cash on Delivery</span>
                                            <div class="mt-4 cod-details hidden">
                                                <div class="mt-4 flex justify-center">
                                                    <i class="ri-truck-line text-2xl text-gray-600"></i>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <!-- Place Order Button -->
                                <button type="submit" name="place_order" id="place-order-btn" class="w-full bg-green-600 text-white px-6 py-3 rounded-full hover:bg-green-700 transition-colors">
                                    Place Order (RM<?php echo number_format($total, 2); ?>)
                                </button>
                            </form>
                        </div>

                        <!-- Order Summary -->
                        <div class="lg:col-span-1 lg:order-last">
                            <div class="summary-box">
                                <h2 class="text-xl font-heading font-bold mb-4">Order Summary</h2>
                                <!-- Cart Items -->
                                <div class="mb-4">
                                    <table class="cart-table w-full">
                                        <tbody>
                                            <?php foreach ($cart_items as $item): ?>
                                                <tr class="border-b">
                                                    <td class="flex items-center space-x-3">
                                                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                                        <div>
                                                            <p class="font-medium"><?php echo htmlspecialchars($item['name']); ?></p>
                                                            <p class="text-sm text-gray-600">Qty: <?php echo $item['quantity']; ?></p>
                                                        </div>
                                                    </td>
                                                    <td class="text-right">RM<?php echo number_format($item['subtotal'], 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <!-- Subtotal -->
                                <div class="flex justify-between mb-2">
                                    <span>Subtotal</span>
                                    <span>RM<?php echo number_format($subtotal, 2); ?></span>
                                </div>
                                <!-- Shipping Cost -->
                                <div class="flex justify-between border-t pt-3 mb-2">
                                    <span>Shipping (<?php echo $shipping_options[$selected_shipping]['name']; ?>)</span>
                                    <span>RM<?php echo number_format($shipping_cost, 2); ?></span>
                                </div>
                                <!-- Total -->
                                <div class="flex justify-between font-bold text-lg border-t pt-3">
                                    <span>Total</span>
                                    <span>RM<?php echo number_format($total, 2); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <!-- Success Modal -->
    <?php if ($show_success): ?>
        <div class="modal-overlay" id="success-modal">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="modal-details">
                        <span class="total">RM<?php echo number_format($order_details['total'], 2); ?></span>
                        <span class="date"><?php echo date('jS M, Y', strtotime($order_details['order_date'])); ?></span>
                    </div>
                    <div class="modal-icon">
                        <span class="checkmark">✔</span>
                    </div>
                </div>
                <h4 class="modal-title">Payment Successful</h4>
                <a href="index.php" class="modal-button">OK</a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="bg-primary-dark text-white py-12">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <a href="index.php" class="text-2xl font-heading font-bold flex items-center mb-4">
                        <i class="ri-leaf-line mr-2 text-primary-light"></i>
                        FreshHarvest
                    </a>
                    <p class="text-sm opacity-80">AgriMarket Solutions is a fictitious business created for a university course.</p>
                </div>
                <div>
                    <h5 class="font-heading font-bold text-lg mb-4">Quick Links</h5>
                    <ul class="space-y-2">
                        <li><a href="products.php" class="hover:text-primary-light transition-colors">Products</a></li>
                        <li><a href="categories.php" class="hover:text-primary-light transition-colors">Categories</a></li>
                        <li><a href="farmers.php" class="hover:text-primary-light transition-colors">Our Farmers</a></li>
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

    <!-- JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const paymentMethods = document.querySelectorAll('.payment-method');
            const cardDetails = document.querySelector('.card-details');
            const mobileDetails = document.querySelector('.mobile-details');
            const bankTransferDetails = document.querySelector('.bank-transfer-details');
            const codDetails = document.querySelector('.cod-details');
            const form = document.getElementById('checkout-form');
            const placeOrderBtn = document.getElementById('place-order-btn');

            function showError(input, message) {
                const parent = input.closest('div');
                let error = parent.querySelector('.error-text');
                if (!error) {
                    error = document.createElement('div');
                    error.className = 'error-text';
                    parent.appendChild(error);
                }
                error.textContent = message;
                input.classList.add('border-red-500');
            }

            function clearError(input) {
                const parent = input.closest('div');
                const error = parent.querySelector('.error-text');
                if (error) {
                    error.remove();
                }
                input.classList.remove('border-red-500');
            }

            function validateInput(input, validateFn, errorMessage) {
                input.addEventListener('blur', function () {
                    clearError(input);
                    if (!validateFn(input.value)) {
                        showError(input, errorMessage);
                    }
                });
            }

            const validators = {
                full_name: value => value.length >= 2 && value.length <= 100,
                email: value => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value),
                phone: value => /^\d{3}-\d{7}$/.test(value),
                address: value => value.length >= 5 && value.length <= 255,
                city: value => value.length >= 2 && value.length <= 100,
                state: value => ['Johor', 'Kedah', 'Kelantan', 'Malacca', 'Negeri Sembilan', 'Pahang', 'Penang', 'Perak', 'Perlis', 'Sabah', 'Sarawak', 'Selangor', 'Terengganu', 'Kuala Lumpur', 'Labuan', 'Putrajaya'].includes(value),
                postcode: value => /^[0-9]{5}$/.test(value),
                card_number: value => /^[0-9]{15,16}$/.test(value.replace(/\D/g, '')),
                expiry_date: value => /^(0[1-9]|1[0-2])\/[0-9]{2}$/.test(value) && isValidExpiry(value),
                cvv: value => /^[0-9]{3,4}$/.test(value),
                mobile_payment_number: value => /^[0-9]{10,15}$/.test(value.replace(/\D/g, '')),
                bank_account_number: value => /^[0-9]{10,20}$/.test(value.replace(/\D/g, '')),
            };

            function isValidExpiry(value) {
                const [month, year] = value.split('/').map(Number);
                const fullYear = 2000 + year;
                const now = new Date();
                const currentYear = now.getFullYear();
                const currentMonth = now.getMonth() + 1;
                return fullYear > currentYear || (fullYear === currentYear && month >= currentMonth);
            }

            const inputs = {
                full_name: document.getElementById('full_name'),
                email: document.getElementById('email'),
                phone: document.getElementById('phone'),
                address: document.getElementById('address'),
                city: document.getElementById('city'),
                state: document.getElementById('state'),
                postcode: document.getElementById('postcode'),
                card_number: document.getElementById('card_number'),
                expiry_date: document.getElementById('expiry_date'),
                cvv: document.getElementById('cvv'),
                mobile_payment_number: document.getElementById('mobile_payment_number'),
                bank_account_number: document.getElementById('bank_account_number'),
            };

            validateInput(inputs.full_name, validators.full_name, 'Full name must be 2-100 characters.');
            validateInput(inputs.email, validators.email, 'Enter a valid email address.');
            validateInput(inputs.phone, validators.phone, 'Phone number must be in format xxx-xxxxxxx (e.g., 012-3456789).');
            validateInput(inputs.address, validators.address, 'Address must be 5-255 characters.');
            validateInput(inputs.city, validators.city, 'City must be 2-100 characters.');
            validateInput(inputs.state, validators.state, 'Select a valid state.');
            validateInput(inputs.postcode, validators.postcode, 'Enter a valid 5-digit postcode.');
            validateInput(inputs.card_number, validators.card_number, 'Enter a valid card number (15-16 digits).');
            validateInput(inputs.expiry_date, validators.expiry_date, 'Enter a valid expiry date (MM/YY, future date).');
            validateInput(inputs.cvv, validators.cvv, 'Enter a valid CVV (3-4 digits).');
            validateInput(inputs.mobile_payment_number, validators.mobile_payment_number, 'Enter a valid mobile number (10-15 digits).');
            validateInput(inputs.bank_account_number, validators.bank_account_number, 'Enter a valid bank account number (10-20 digits).');

            paymentMethods.forEach(method => {
                method.addEventListener('change', function () {
                    document.querySelectorAll('.payment-option').forEach(option => {
                        option.classList.remove('selected');
                    });
                    this.closest('.payment-option').classList.add('selected');
                    cardDetails.classList.add('hidden');
                    mobileDetails.classList.add('hidden');
                    bankTransferDetails.classList.add('hidden');
                    codDetails.classList.add('hidden');
                    if (this.value === 'card') {
                        cardDetails.classList.remove('hidden');
                    } else if (this.value === 'mobile') {
                        mobileDetails.classList.remove('hidden');
                    } else if (this.value === 'bank_transfer') {
                        bankTransferDetails.classList.remove('hidden');
                    } else if (this.value === 'cod') {
                        codDetails.classList.remove('hidden');
                    }
                });
            });

            form.addEventListener('submit', function (e) {
                let errors = [];
                Object.entries(inputs).forEach(([key, input]) => {
                    if (!input) return;
                    clearError(input);
                    const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
                    if (
                        (key === 'card_number' || key === 'expiry_date' || key === 'cvv') && paymentMethod !== 'card' ||
                        key === 'mobile_payment_number' && paymentMethod !== 'mobile' ||
                        key === 'bank_account_number' && paymentMethod !== 'bank_transfer'
                    ) {
                        return;
                    }
                    if (!validators[key](input.value)) {
                        errors.push({ input, message: input.dataset.error || `Invalid ${key.replace('_', ' ')}.` });
                    }
                });
                if (errors.length > 0) {
                    e.preventDefault();
                    errors.forEach(({ input, message }) => showError(input, message));
                    placeOrderBtn.disabled = false;
                }
            });

            function restrictToDigits(input, maxLength) {
                input.addEventListener('input', function () {
                    const start = this.selectionStart;
                    const end = this.selectionEnd;
                    let value = this.value.replace(/\D/g, '').substring(0, maxLength);
                    this.value = value;
                    let newPos = start;
                    if (this.value.length < start) {
                        newPos = this.value.length;
                    }
                    this.setSelectionRange(newPos, newPos);
                });
            }

            const phoneInput = document.getElementById('phone');
            if (phoneInput) {
                phoneInput.addEventListener('input', function () {
                    const start = this.selectionStart;
                    let value = this.value.replace(/\D/g, '').substring(0, 10);
                    let formatted = '';
                    if (value.length > 3) {
                        formatted = value.substring(0, 3) + '-' + value.substring(3);
                    } else {
                        formatted = value;
                    }
                    this.value = formatted;
                    let newPos = start;
                    if (value.length < start) {
                        newPos = this.value.length;
                    } else if (this.value[start - 1] === '-') {
                        newPos++;
                    }
                    this.setSelectionRange(newPos, newPos);
                });
            }

            const cardNumber = document.getElementById('card_number');
            if (cardNumber) {
                cardNumber.addEventListener('input', function () {
                    const start = this.selectionStart;
                    let value = this.value.replace(/\D/g, '').substring(0, 16);
                    let formatted = value.match(/.{1,4}/g);
                    this.value = formatted ? formatted.join(' ') : value;
                    let newPos = start;
                    if (value.length < start) {
                        newPos = this.value.length;
                    } else if (this.value[start - 1] === ' ') {
                        newPos++;
                    }
                    this.setSelectionRange(newPos, newPos);
                });
            }

            const expiryDate = document.getElementById('expiry_date');
            if (expiryDate) {
                expiryDate.addEventListener('input', function () {
                    const start = this.selectionStart;
                    let value = this.value.replace(/\D/g, '').substring(0, 4);
                    if (value.length >= 2) {
                        this.value = value.substring(0, 2) + (value.length > 2 ? '/' + value.substring(2) : '');
                    } else {
                        this.value = value;
                    }
                    let newPos = start;
                    if (this.value.length < start) {
                        newPos = this.value.length;
                    } else if (this.value[start - 1] === '/') {
                        newPos++;
                    }
                    this.setSelectionRange(newPos, newPos);
                });
            }

            if (inputs.cvv) restrictToDigits(inputs.cvv, 4);
            if (inputs.mobile_payment_number) restrictToDigits(inputs.mobile_payment_number, 15);
            if (inputs.bank_account_number) restrictToDigits(inputs.bank_account_number, 20);
        });
    </script>
</body>
</html>
<?php
ob_end_flush();
?>