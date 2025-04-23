<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
require_once 'db_connect.php';

// Redirect to login if not logged in
if (!$isLoggedIn) {
    header("Location: login.php?redirect=cart.php");
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

// Initialize selected shipping method in session if not set
if (!isset($_SESSION['selected_shipping'])) {
    $_SESSION['selected_shipping'] = 'standard';
}

// Initialize cart variables
$cart_id = null;
$cart_data = [];
// Fetch cart if it exists (do NOT create a cart yet)
try {
    $stmt = $pdo->prepare("SELECT cart_id, cart_data FROM shopping_carts WHERE customer_id = :customer_id");
    $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
    $stmt->execute();
    $cart = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cart) {
        $cart_id = $cart['cart_id'];
        $cart_data = json_decode($cart['cart_data'], true);
        
        // If JSON is invalid, log error and reset cart_data to empty array
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Cart JSON decode error for user $customer_id: " . json_last_error_msg());
            $cart_data = [];
            // Update the database to fix the invalid JSON
            $encoded_cart_data = json_encode($cart_data);
            $stmt = $pdo->prepare("UPDATE shopping_carts SET cart_data = :cart_data, updated_at = NOW() WHERE cart_id = :cart_id");
            $stmt->bindParam(':cart_data', $encoded_cart_data, PDO::PARAM_STR);
            $stmt->bindParam(':cart_id', $cart_id, PDO::PARAM_INT);
            $stmt->execute();
        }
    }
} catch (PDOException $e) {
    error_log("Database error in cart: " . $e->getMessage());
    $error = "Error accessing your cart. Please try again.";
}

// Helper function to create a new cart
function createNewCart($pdo, $customer_id) {
    $cart_data = [];
    $encoded_cart_data = json_encode($cart_data);
    $stmt = $pdo->prepare("INSERT INTO shopping_carts (customer_id, cart_data) VALUES (:customer_id, :cart_data)");
    $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
    $stmt->bindParam(':cart_data', $encoded_cart_data, PDO::PARAM_STR);
    $stmt->execute();
    return $pdo->lastInsertId();
}

// Handle Add to Cart action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    try {
        // Check if product exists and is active
        $stmt = $pdo->prepare("SELECT stock_quantity, minimum_order_quantity, is_active FROM products WHERE product_id = :id");
        $stmt->bindParam(':id', $product_id, PDO::PARAM_INT);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product && $product['is_active']) {
            $stock_quantity = $product['stock_quantity'];
            $minimum_order_quantity = $product['minimum_order_quantity'];
            
            // Validate quantity
            if ($quantity < $minimum_order_quantity) {
                $error = "Minimum order quantity is $minimum_order_quantity for this product.";
            } elseif ($quantity > $stock_quantity) {
                $error = "Only $stock_quantity items available in stock.";
            } else {
                // If no cart exists, create one
                if (!$cart_id) {
                    $cart_data = []; // Initialize empty cart
                    $encoded_cart_data = json_encode($cart_data);
                    $stmt = $pdo->prepare("INSERT INTO shopping_carts (customer_id, cart_data) VALUES (:customer_id, :cart_data)");
                    $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
                    $stmt->bindParam(':cart_data', $encoded_cart_data, PDO::PARAM_STR);
                    $stmt->execute();
                    $cart_id = $pdo->lastInsertId();
                }

                // Update cart_data
                $found = false;
                foreach ($cart_data as &$item) {
                    if (isset($item['product_id']) && $item['product_id'] === $product_id) {
                        $new_quantity = $item['quantity'] + $quantity;
                        if ($new_quantity > $stock_quantity) {
                            $error = "Cannot add more than available stock ($stock_quantity).";
                            break;
                        }
                        $item['quantity'] = $new_quantity;
                        $found = true;
                        break;
                    }
                }

                if (!$found && !isset($error)) {
                    $cart_data[] = ['product_id' => $product_id, 'quantity' => $quantity];
                }
                
                if (!isset($error)) {
                    $encoded_cart_data = json_encode($cart_data);
                    $stmt = $pdo->prepare("UPDATE shopping_carts SET cart_data = :cart_data, updated_at = NOW() WHERE cart_id = :cart_id");
                    $stmt->bindParam(':cart_data', $encoded_cart_data, PDO::PARAM_STR);
                    $stmt->bindParam(':cart_id', $cart_id, PDO::PARAM_INT);
                    $stmt->execute();
                    
                    $_SESSION['success'] = "Product added to cart successfully!";
                    header("Location: cart.php");
                    exit();
                }
            }
        } else {
            $error = "Product not available.";
        }
    } catch (PDOException $e) {
        $error = "Error adding to cart: " . $e->getMessage();
    }
}

// Handle Clear Cart action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_cart'])) {
    if ($cart_id) { // Only attempt to clear if a cart exists
        try {
            $empty_cart_data = '[]';
            $stmt = $pdo->prepare("UPDATE shopping_carts SET cart_data = :cart_data, updated_at = NOW() WHERE cart_id = :cart_id");
            $stmt->bindParam(':cart_data', $empty_cart_data, PDO::PARAM_STR);
            $stmt->bindParam(':cart_id', $cart_id, PDO::PARAM_INT);
            $stmt->execute();

            $success = "Cart cleared successfully!";
            $cart_data = [];
        } catch (PDOException $e) {
            $error = "Error clearing cart: " . $e->getMessage();
        }
    }
}

// Handle Update Quantity action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_quantity'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];

    if (!$cart_id) {
        $error = "No cart exists to update.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT stock_quantity, minimum_order_quantity, is_active FROM products WHERE product_id = :id");
            $stmt->bindParam(':id', $product_id, PDO::PARAM_INT);
            $stmt->execute();
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($product && $product['is_active']) {
                $stock_quantity = $product['stock_quantity'];
                $minimum_order_quantity = $product['minimum_order_quantity'];

                if ($quantity < $minimum_order_quantity) {
                    $error = "Quantity for product ID $product_id must be at least $minimum_order_quantity.";
                } elseif ($quantity > $stock_quantity) {
                    $error = "Quantity for product ID $product_id exceeds available stock ($stock_quantity).";
                } elseif ($quantity <= 0) {
                    $error = "Quantity must be greater than 0.";
                } else {
                    // Update quantity in cart_data
                    $found = false;
                    foreach ($cart_data as &$item) {
                        if (isset($item['product_id']) && $item['product_id'] === $product_id) {
                            $item['quantity'] = $quantity;
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $error = "Product ID $product_id not found in cart.";
                    } else {
                        // Save updated cart_data
                        $encoded_cart_data = json_encode($cart_data);
                        $stmt = $pdo->prepare("UPDATE shopping_carts SET cart_data = :cart_data, updated_at = NOW() WHERE cart_id = :cart_id");
                        $stmt->bindParam(':cart_data', $encoded_cart_data, PDO::PARAM_STR);
                        $stmt->bindParam(':cart_id', $cart_id, PDO::PARAM_INT);
                        $stmt->execute();

                        $success = "Quantity updated successfully!";
                    }
                }
            } else {
                $error = "Product ID $product_id is not available.";
            }
        } catch (PDOException $e) {
            $error = "Error updating quantity: " . $e->getMessage();
        }
    }
}

// Handle Remove Item action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item'])) {
    $product_id = (int)$_POST['product_id'];

    if (!$cart_id) {
        $error = "No cart exists to remove items from.";
    } else {
        try {
            $cart_data = array_filter($cart_data, function($item) use ($product_id) {
                return !isset($item['product_id']) || $item['product_id'] !== $product_id;
            });
            $cart_data = array_values($cart_data);

            $encoded_cart_data = json_encode($cart_data);
            $stmt = $pdo->prepare("UPDATE shopping_carts SET cart_data = :cart_data, updated_at = NOW() WHERE cart_id = :cart_id");
            $stmt->bindParam(':cart_data', $encoded_cart_data, PDO::PARAM_STR);
            $stmt->bindParam(':cart_id', $cart_id, PDO::PARAM_INT);
            $stmt->execute();

            $success = "Item removed from cart successfully!";
        } catch (PDOException $e) {
            $error = "Error removing item: " . $e->getMessage();
        }
    }
}

// Handle Shipping Method Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['shipping_method'])) {
    $new_shipping_method = $_POST['shipping_method'] ?? 'standard';
    if (array_key_exists($new_shipping_method, $shipping_options)) {
        $_SESSION['selected_shipping'] = $new_shipping_method;
    } else {
        error_log("Invalid shipping method selected: " . htmlspecialchars($new_shipping_method));
    }
}

// Get the selected shipping method from session
$selected_shipping = $_SESSION['selected_shipping'];

// Fetch cart items with product details
$cart_items = [];
$subtotal = 0;
if (!empty($cart_data)) {
    $product_ids = [];
    foreach ($cart_data as $item) {
        if (isset($item['product_id'])) {
            $product_ids[] = $item['product_id'];
        }
    }
    if (!empty($product_ids)) {
        $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
        try {
            $stmt = $pdo->prepare("SELECT product_id, name, price, discounted_price, stock_quantity, minimum_order_quantity, image_url 
                                   FROM products 
                                   WHERE product_id IN ($placeholders) AND is_active = 1");
            $stmt->execute($product_ids);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($cart_data as $cart_item) {
                if (!isset($cart_item['product_id'])) {
                    continue; // Skip invalid cart items
                }
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
                            'stock_quantity' => $product['stock_quantity'],
                            'minimum_order_quantity' => $product['minimum_order_quantity'],
                            'image_url' => json_decode($product['image_url'], true)[0] ?? 'https://public.readdy.ai/api/placeholder/500/500'
                        ];
                        break;
                    }
                }
            }
        } catch (PDOException $e) {
            $error = "Error fetching cart items: " . $e->getMessage();
        }
    }
}

// Calculate totals
$shipping_cost = $shipping_options[$selected_shipping]['price'];
$total = $subtotal + $shipping_cost;

// Get cart item count
$cart_count = array_sum(array_column($cart_items, 'quantity'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FreshHarvest - Shopping Cart</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Open+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css" rel="stylesheet">
    <style>
        .quantity-selector {
            display: flex;
            align-items: center;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            overflow: hidden;
            flex-shrink: 0;
        }

        .quantity-btn {
            background-color: #f3f4f6;
            padding: 0.5rem 1rem;
            cursor: pointer;
            user-select: none;
        }

        .quantity-btn:hover {
            background-color: #e5e7eb;
        }

        .quantity-input {
            width: 60px;
            text-align: center;
            border: none;
            outline: none;
            background-color: transparent;
        }

        .cart-table th,
        .cart-table td {
            padding: 1rem;
            text-align: left;
        }

        .cart-table img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 0.375rem;
        }

        .remove-btn:hover {
            color: #dc2626;
        }
        
        .shipping-option {
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 0.75rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .shipping-option:hover {
            border-color: #9ca3af;
        }
        
        .shipping-option.selected {
            border-color: #10b981;
            background-color: #ecfdf5;
        }
        
        .shipping-option input[type="radio"] {
            margin-right: 0.75rem;
        }

        .clear-cart-btn {
            color: #dc2626;
            transition: color 0.2s;
        }

        .clear-cart-btn:hover {
            color: #b91c1c;
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
                        <a href="categories.php" class "text-gray-700 hover:text-primary font-medium">Categories</a>
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
                        <?php if ($isLoggedIn): ?>
                            <a href="logout.php" class="cursor-pointer hover:text-primary transition-colors"
                                title="Log Out">
                                <i class="ri-logout-box-line text-xl"></i>
                            </a>
                            <a href="profile.php" class="cursor-pointer hover:text-primary transition-colors"
                                title="Profile">
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
                    <span class="text-gray-700">Shopping Cart</span>
                </nav>

                <h1 class="text-3xl font-heading font-bold mb-8">Shopping Cart</h1>

                <!-- Success/Error Messages -->
                <?php if (isset($success)): ?>
                    <div class="bg-green-100 text-green-800 p-4 rounded-lg mb-6">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
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
                        <!-- Cart Items -->
                        <div class="lg:col-span-2">
                            <!-- Cart Items Table -->
                            <div class="overflow-x-auto">
                                <table class="cart-table w-full bg-gray-50 rounded-lg">
                                    <thead>
                                        <tr class="border-b">
                                            <th>Product</th>
                                            <th>Price</th>
                                            <th>Quantity</th>
                                            <th>Subtotal</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cart_items as $item): ?>
                                            <tr class="border-b">
                                                <td class="flex items-center space-x-4">
                                                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                                         alt="<?php echo htmlspecialchars($item['name']); ?>">
                                                    <span><?php echo htmlspecialchars($item['name']); ?></span>
                                                </td>
                                                <td>RM<?php echo number_format($item['price'], 2); ?></td>
                                                <td>
                                                    <form method="POST" class="inline-flex">
                                                        <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                                        <div class="quantity-selector">
                                                            <span class="quantity-btn quantity-minus">-</span>
                                                            <input type="number" name="quantity" class="quantity-input"
                                                                   value="<?php echo $item['quantity']; ?>"
                                                                   min="<?php echo $item['minimum_order_quantity']; ?>"
                                                                   max="<?php echo $item['stock_quantity']; ?>">
                                                            <span class="quantity-btn quantity-plus">+</span>
                                                        </div>
                                                        <button type="submit" name="update_quantity" 
                                                                class="ml-2 text-primary hover:underline">Update</button>
                                                    </form>
                                                </td>
                                                <td>RM<?php echo number_format($item['subtotal'], 2); ?></td>
                                                <td>
                                                    <form method="POST" class="inline">
                                                        <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                                        <button type="submit" name="remove_item" 
                                                                class="remove-btn text-gray-600 hover:text-red-600">
                                                            <i class="ri-delete-bin-line"></i> Remove
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Continue Shopping and Clear Cart Buttons -->
                            <div class="mt-6 flex justify-between">
                                <a href="products.php" class="inline-flex items-center text-primary hover:underline">
                                    <i class="ri-arrow-left-line mr-2"></i> Continue Shopping
                                </a>
                                <form method="POST" class="inline">
                                    <button type="submit" name="clear_cart" 
                                            class="clear-cart-btn inline-flex items-center hover:underline">
                                        <i class="ri-delete-bin-line mr-2"></i> Clear Cart
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Order Summary -->
                        <div class="lg:col-span-1">
                            <div class="bg-gray-50 p-6 rounded-lg sticky top-24">
                                <h2 class="text-xl font-heading font-bold mb-4">Order Summary</h2>
                                
                                <!-- Subtotal -->
                                <div class="flex justify-between mb-2">
                                    <span>Subtotal</span>
                                    <span>RM<?php echo number_format($subtotal, 2); ?></span>
                                </div>
                                
                                <!-- Shipping Options -->
                                <div class="mb-4">
                                    <h3 class="font-medium mb-2">Shipping Method</h3>
                                    <form method="POST" id="shipping-form">
                                        <?php foreach ($shipping_options as $key => $option): ?>
                                            <label class="shipping-option block <?php echo $selected_shipping === $key ? 'selected' : ''; ?>">
                                                <input type="radio" name="shipping_method" value="<?php echo $key; ?>" 
                                                       <?php echo $selected_shipping === $key ? 'checked' : ''; ?>
                                                       class="shipping-method">
                                                <span class="font-medium"><?php echo $option['name']; ?></span>
                                                <span class="float-right">RM<?php echo number_format($option['price'], 2); ?></span>
                                                <div class="text-sm text-gray-600 mt-1"><?php echo $option['days']; ?></div>
                                            </label>
                                        <?php endforeach; ?>
                                        <button type="submit" name="update_shipping" class="hidden">Update Shipping</button>
                                    </form>
                                </div>
                                
                                <!-- Shipping Cost -->
                                <div class="flex justify-between border-t pt-3 mb-2">
                                    <span>Shipping</span>
                                    <span>RM<?php echo number_format($shipping_cost, 2); ?></span>
                                </div>
                                
                                <!-- Total -->
                                <div class="flex justify-between font-bold text-lg border-t pt-3 mb-6">
                                    <span>Total</span>
                                    <span>RM<?php echo number_format($total, 2); ?></span>
                                </div>
                                
                                <!-- Checkout Button -->
                                <a href="checkout.php" 
                                   class="block w-full bg-primary text-white px-6 py-3 rounded-full hover:bg-primary-dark transition-colors text-center">
                                    Proceed to Checkout
                                </a>
                                
                                <!-- Payment Icons -->
                                <div class="mt-4 flex justify-center space-x-4">
                                    <i class="ri-visa-fill text-2xl text-gray-600"></i>
                                    <i class="ri-mastercard-fill text-2xl text-gray-600"></i>
                                    <i class="ri-paypal-fill text-2xl text-gray-600"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

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
                <p class="text-sm">© 2025 AgriMarket Solutions. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Quantity Selector for each cart item
            const quantitySelectors = document.querySelectorAll('.quantity-selector');
            quantitySelectors.forEach(selector => {
                const minusBtn = selector.querySelector('.quantity-minus');
                const plusBtn = selector.querySelector('.quantity-plus');
                const input = selector.querySelector('.quantity-input');
                const min = parseInt(input.getAttribute('min'));
                const max = parseInt(input.getAttribute('max'));

                minusBtn.addEventListener('click', () => {
                    let value = parseInt(input.value);
                    if (value > min) {
                        input.value = value - 1;
                    }
                });

                plusBtn.addEventListener('click', () => {
                    let value = parseInt(input.value);
                    if (value < max) {
                        input.value = value + 1;
                    }
                });

                input.addEventListener('change', () => {
                    let value = parseInt(input.value);
                    if (isNaN(value)) {
                        input.value = min;
                    } else if (value < min) {
                        input.value = min;
                    } else if (value > max) {
                        input.value = max;
                    }
                });

                input.addEventListener('keydown', (e) => {
                    if ([46, 8, 9, 27, 13, 37, 38, 39, 40].includes(e.keyCode) || 
                        ((e.ctrlKey || e.metaKey) && [65, 67, 86, 88].includes(e.keyCode))) {
                        return;
                    }
                    if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                        e.preventDefault();
                    }
                });
            });

            // Auto-submit shipping form when selection changes
            const shippingMethods = document.querySelectorAll('.shipping-method');
            const shippingForm = document.getElementById('shipping-form');
            
            shippingMethods.forEach(method => {
                method.addEventListener('change', function() {
                    // Update selected styling
                    document.querySelectorAll('.shipping-option').forEach(option => {
                        option.classList.remove('selected');
                    });
                    this.closest('.shipping-option').classList.add('selected');
                    
                    // Submit form
                    try {
                        shippingForm.submit();
                    } catch (error) {
                        console.error('Error submitting shipping form:', error);
                    }
                });
            });
        });
    </script>
</body>
</html>