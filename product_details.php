<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
require_once 'db_connect.php';

// Initialize cart if not already set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle Add to Cart action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = (int) $_POST['product_id'];
    $quantity = (int) $_POST['quantity'];

    if (!$isLoggedIn) {
        $error = "Please log in to add items to your cart.";
    } else {
        $customer_id = $_SESSION['user_id']; 

        try {
            // Validate Product Stock/Min Order 
            $stmt = $pdo->prepare("SELECT stock_quantity, minimum_order_quantity FROM products WHERE product_id = :id AND is_active = 1");
            $stmt->bindParam(':id', $product_id, PDO::PARAM_INT);
            $stmt->execute();
            $product_validation = $stmt->fetch(PDO::FETCH_ASSOC); 

            if (!$product_validation) {
                $error = "Product not found or unavailable.";
            } elseif ($quantity < $product_validation['minimum_order_quantity']) {
                $error = "Quantity must be at least " . $product_validation['minimum_order_quantity'] . ".";
            } elseif ($quantity > $product_validation['stock_quantity']) {
                $error = "Requested quantity ($quantity) exceeds available stock (" . $product_validation['stock_quantity'] . ").";
            } else {
                // Validation Passed - Proceed with Database Cart Update
                $cart_stmt = $pdo->prepare("SELECT cart_id, cart_data FROM shopping_carts WHERE customer_id = :customer_id");
                $cart_stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
                $cart_stmt->execute();
                $existing_cart_row = $cart_stmt->fetch(PDO::FETCH_ASSOC);

                $cart_data = [];
                if ($existing_cart_row && !empty($existing_cart_row['cart_data'])) {
                    $cart_data = json_decode($existing_cart_row['cart_data'], true);
                    if (json_last_error() !== JSON_ERROR_NONE || !is_array($cart_data)) {
                         error_log("Error decoding cart data for customer ID: $customer_id. JSON Error: " . json_last_error_msg());
                         $cart_data = [];
                    }
                }

                $found = false;
                foreach ($cart_data as &$item) { 
                    if (isset($item['product_id']) && $item['product_id'] === $product_id) {
                        if (!isset($item['quantity'])) $item['quantity'] = 0;
                        $item['quantity'] += $quantity;
                        $found = true;
                        break;
                    }
                }
                unset($item); 

                if (!$found) {
                    $cart_data[] = ['product_id' => $product_id, 'quantity' => $quantity]; 
                }

                $updated_cart_json = json_encode($cart_data);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception("Failed to encode cart data to JSON. Error: " . json_last_error_msg());
                }

                $save_stmt = $pdo->prepare("
                    INSERT INTO shopping_carts (customer_id, cart_data, created_at, updated_at)
                    VALUES (:customer_id, :cart_data, NOW(), NOW())
                    ON DUPLICATE KEY UPDATE
                        cart_data = VALUES(cart_data),
                        updated_at = NOW()
                ");
                $save_stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
                $save_stmt->bindParam(':cart_data', $updated_cart_json, PDO::PARAM_STR);
                $save_stmt->execute();

                $_SESSION['cart'] = $cart_data;
                $cart_count = array_sum(array_column($_SESSION['cart'], 'quantity'));

                $success = "Product added to cart successfully!";
            }
        } catch (PDOException $e) {
            $error = "Database error adding to cart: " . $e->getMessage();
            error_log("PDOException in cart add: " . $e->getMessage()); 
        } catch (Exception $e) {
            $error = "Error processing cart: " . $e->getMessage();
            error_log("Exception in cart add: " . $e->getMessage()); 
        }
    } 
}

// Handle Add to Wishlist action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_wishlist'])) {
    $product_id = (int) $_POST['product_id'];

    if (!$isLoggedIn) {
        $error = "Please log in to add items to your wishlist.";
    } else {
        $customer_id = $_SESSION['user_id'];

        try {
            // Check if product exists and is active
            $stmt = $pdo->prepare("SELECT product_id FROM products WHERE product_id = :id AND is_active = 1");
            $stmt->bindParam(':id', $product_id, PDO::PARAM_INT);
            $stmt->execute();
            if (!$stmt->fetch()) {
                $error = "Product not found or unavailable.";
            } else {
                // Check if already in wishlist
                $check_stmt = $pdo->prepare("SELECT wishlist_id FROM wishlists WHERE customer_id = :customer_id AND product_id = :product_id");
                $check_stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
                $check_stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
                $check_stmt->execute();
                if ($check_stmt->fetch()) {
                    $error = "This product is already in your wishlist.";
                } else {
                    // Add to wishlist
                    $insert_stmt = $pdo->prepare("INSERT INTO wishlists (customer_id, product_id, added_at) VALUES (:customer_id, :product_id, NOW())");
                    $insert_stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
                    $insert_stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
                    $insert_stmt->execute();
                    $success = "Product added to wishlist successfully!";
                }
            }
        } catch (PDOException $e) {
            $error = "Database error adding to wishlist: " . $e->getMessage();
            error_log("PDOException in wishlist add: " . $e->getMessage());
        }
    }
}

// Fetch product details
$product_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($product_id <= 0) {
    header("HTTP/1.0 404 Not Found");
    echo "<h1>404 Not Found</h1><p>Product not found.</p>";
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT p.*, pc.name AS category_name, v.business_name, v.verified_status
                           FROM products p
                           JOIN product_categories pc ON p.category_id = pc.category_id
                           JOIN vendors v ON p.vendor_id = v.vendor_id
                           WHERE p.product_id = :id AND p.is_active = 1");
    $stmt->bindParam(':id', $product_id, PDO::PARAM_INT);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        header("HTTP/1.0 404 Not Found");
        echo "<h1>404 Not Found</h1><p>Product not found.</p>";
        exit;
    }

    // Fetch product reviews with user_name from users table
    $review_stmt = $pdo->prepare("SELECT pr.*, u.username 
                                 FROM product_reviews pr
                                 JOIN users u ON pr.customer_id = u.user_id
                                 WHERE pr.product_id = :id AND pr.is_approved = 1
                                 ORDER BY pr.review_date DESC");
    $review_stmt->bindParam(':id', $product_id, PDO::PARAM_INT);
    $review_stmt->execute();
    $reviews = $review_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate average rating
    $total_rating = 0;
    $review_count = count($reviews);
    foreach ($reviews as $review) {
        $total_rating += $review['rating'];
    }
    $average_rating = $review_count > 0 ? round($total_rating / $review_count, 1) : 0;

} catch (PDOException $e) {
    error_log("Error fetching product: " . $e->getMessage());
    header("HTTP/1.0 500 Internal Server Error");
    echo "<h1>500 Internal Server Error</h1><p>Error loading product details: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Get cart item count
$cart_count = array_sum(array_column($_SESSION['cart'], 'quantity'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FreshHarvest - <?php echo htmlspecialchars($product['name']); ?></title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Open+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css" rel="stylesheet">
    <style>
        .product-image {
            transition: transform 0.3s ease;
        }
        .product-image:hover {
            transform: scale(1.05);
        }
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
        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .organic-badge {
            background-color: #ecfdf5;
            color: #047857;
        }
        .stock-low {
            color: #dc2626;
            font-weight: 500;
        }
        .review-date {
            color: #6b7280;
            font-size: 0.875rem;
        }
        .review-card {
            transition: box-shadow 0.3s ease;
        }
        .review-card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
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
                    <a href="products.php" class="text-gray-500 hover:text-primary">Products</a>
                    <i class="ri-arrow-right-s-line mx-2 text-gray-400"></i>
                    <span class="text-gray-700"><?php echo htmlspecialchars($product['name']); ?></span>
                </nav>

                <!-- Product Details -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                    <!-- Product Image -->
                    <div class="relative">
                        <?php if ($product['is_organic']): ?>
                            <div class="badge organic-badge absolute top-4 left-4">Organic</div>
                        <?php endif; ?>
                        <?php
                        $image_url = json_decode($product['image_url'], true)[0] ?? 'https://public.readdy.ai/api/placeholder/500/500';
                        ?>
                        <img src="<?php echo htmlspecialchars($image_url); ?>"
                            alt="<?php echo htmlspecialchars($product['name']); ?>"
                            class="w-full h-96 object-cover rounded-lg product-image">
                    </div>

                    <!-- Product Information -->
                    <div>
                        <h1 class="text-3xl font-heading font-bold mb-2"><?php echo htmlspecialchars($product['name']); ?></h1>
                        <div class="flex items-center mb-4">
                            <span class="text-sm text-gray-500 mr-2"><?php echo htmlspecialchars($product['category_name']); ?></span>
                            <span class="text-sm text-primary flex items-center">
                                <i class="ri-user-line mr-1"></i>
                                <?php echo htmlspecialchars($product['business_name']); ?>
                                <?php if ($product['verified_status']): ?>
                                    <i class="ri-verified-badge-fill text-green-500 ml-1" title="Verified Vendor"></i>
                                <?php endif; ?>
                            </span>
                        </div>

                        <!-- Price -->
                        <div class="flex items-center mb-4">
                            <?php if (!is_null($product['discounted_price']) && $product['discounted_price'] < $product['price']): ?>
                                <span class="text-gray-500 line-through text-lg mr-2">RM<?php echo number_format($product['price'], 2); ?></span>
                                <span class="text-primary font-heading font-bold text-2xl">
                                    RM<?php echo number_format($product['discounted_price'], 2); ?>
                                </span>
                            <?php else: ?>
                                <span class="text-primary font-heading font-bold text-2xl">
                                    RM<?php echo number_format($product['price'], 2); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Stock and Minimum Order -->
                        <div class="mb-4">
                            <p class="text-sm text-gray-600">Stock:
                                <span class="<?php echo $product['stock_quantity'] <= 10 ? 'stock-low' : 'text-gray-800'; ?>">
                                    <?php echo $product['stock_quantity']; ?> available
                                </span>
                            </p>
                            <p class="text-sm text-gray-600">Minimum Order: <?php echo $product['minimum_order_quantity']; ?></p>
                        </div>

                        <!-- Description -->
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold mb-2">Description</h3>
                            <p class="text-gray-600"><?php echo htmlspecialchars($product['description']); ?></p>
                        </div>

                        <!-- Additional Details -->
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold mb-2">Details</h3>
                            <ul class="text-sm text-gray-600 space-y-1">
                                <li><strong>Packaging:</strong> <?php echo htmlspecialchars($product['packaging_type']); ?></li>
                                <li><strong>Weight:</strong> <?php echo number_format($product['weight_kg'], 2); ?> kg</li>
                                <li><strong>Harvest Date:</strong>
                                    <?php echo $product['harvest_date'] ? htmlspecialchars($product['harvest_date']) : 'N/A'; ?>
                                </li>
                            </ul>
                        </div>

                        <!-- Add to Cart and Wishlist Form -->
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">

                            <!-- Quantity Selector -->
                            <div class="flex items-center space-x-4">
                                <label for="quantity" class="text-sm font-semibold">Quantity:</label>
                                <div class="quantity-selector">
                                    <span class="quantity-btn quantity-minus">-</span>
                                    <input type="number" name="quantity" id="quantity" class="quantity-input"
                                        value="<?php echo $product['minimum_order_quantity']; ?>"
                                        min="<?php echo $product['minimum_order_quantity']; ?>"
                                        max="<?php echo $product['stock_quantity']; ?>" readonly>
                                    <span class="quantity-btn quantity-plus">+</span>
                                </div>
                            </div>

                            <!-- Success/Error Messages -->
                            <?php if (isset($success)): ?>
                                <p class="text-green-600 text-sm"><?php echo $success; ?></p>
                            <?php endif; ?>
                            <?php if (isset($error)): ?>
                                <p class="text-red-600 text-sm"><?php echo $error; ?></p>
                            <?php endif; ?>

                            <!-- Buttons -->
                            <div class="flex space-x-4">
                                <button type="submit" name="add_to_cart"
                                    class="flex-1 bg-primary text-white px-6 py-3 rounded-full hover:bg-primary-dark transition-colors flex items-center justify-center space-x-2">
                                    <i class="ri-shopping-cart-line"></i>
                                    <span>Add to Cart</span>
                                </button>
                                <button type="submit" name="add_to_wishlist"
                                    class="flex-none bg-gray-100 text-gray-700 px-4 py-3 rounded-full hover:bg-gray-200 transition-colors flex items-center justify-center"
                                    title="Add to Wishlist">
                                    <i class="ri-heart-line text-xl"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>

        <!-- Reviews Section -->
        <section class="py-12 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4">
                <!-- Success/Error Messages for Review Submission -->
                <?php if (isset($_GET['success'])): ?>
                    <div class="bg-green-100 text-green-800 p-4 rounded-lg mb-6">
                        <?php echo htmlspecialchars($_GET['success']); ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['error'])): ?>
                    <div class="bg-red-100 text-red-800 p-4 rounded-lg mb-6">
                        <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                <?php endif; ?>
                <div class="mb-8">
                    <h2 class="text-2xl font-heading font-bold mb-2">Customer Reviews</h2>
                    <div class="flex items-center">
                        <!-- Average Rating -->
                        <div class="flex items-center mr-4">
                            <div class="text-3xl font-bold mr-2"><?php echo $average_rating; ?></div>
                            <div class="flex items-center">
                                <?php
                                $full_stars = floor($average_rating);
                                $has_half_star = ($average_rating - $full_stars) >= 0.5;
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $full_stars) {
                                        echo '<i class="ri-star-fill text-yellow-400 text-xl"></i>';
                                    } elseif ($has_half_star && $i == $full_stars + 1) {
                                        echo '<i class="ri-star-half-fill text-yellow-400 text-xl"></i>';
                                    } else {
                                        echo '<i class="ri-star-line text-yellow-400 text-xl"></i>';
                                    }
                                }
                                ?>
                            </div>
                        </div>
                        <div class="text-gray-600">
                            Based on <?php echo $review_count; ?> review<?php echo $review_count != 1 ? 's' : ''; ?>
                        </div>
                    </div>
                </div>

                <?php if ($review_count > 0): ?>
                    <div class="space-y-6">
                        <?php foreach ($reviews as $review): ?>
                            <div class="bg-white p-6 rounded-lg shadow-sm review-card">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <h4 class="font-semibold"><?php echo htmlspecialchars($review['username']); ?></h4>
                                        <div class="flex items-center mt-1">
                                            <?php
                                            for ($i = 1; $i <= 5; $i++) {
                                                if ($i <= $review['rating']) {
                                                    echo '<i class="ri-star-fill text-yellow-400"></i>';
                                                } else {
                                                    echo '<i class="ri-star-line text-yellow-400"></i>';
                                                }
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <span class="review-date"><?php echo date('F j, Y', strtotime($review['review_date'])); ?></span>
                                </div>
                                <h5 class="font-medium text-lg mb-2"><?php echo htmlspecialchars($review['title']); ?></h5>
                                <p class="text-gray-600"><?php echo htmlspecialchars($review['comment']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="bg-white p-6 rounded-lg shadow-sm text-center">
                        <p class="text-gray-600">No reviews yet. Be the first to review this product!</p>
                    </div>
                <?php endif; ?>

                <!-- Add Review Form (only for logged in users) -->
                <?php if ($isLoggedIn): ?>
                    <div class="mt-12 bg-white p-6 rounded-lg shadow-sm">
                        <h3 class="text-xl font-heading font-bold mb-4">Write a Review</h3>
                        <form method="POST" action="submit_review.php">
                            <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                            <div class="mb-4">
                                <label class="block text-gray-700 mb-2">Rating</label>
                                <div class="flex items-center space-x-1">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <button type="button" class="star-rating text-2xl" data-rating="<?php echo $i; ?>">
                                            <i class="ri-star-line text-yellow-400"></i>
                                        </button>
                                    <?php endfor; ?>
                                    <input type="hidden" name="rating" id="rating-value" value="5">
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="review-title" class="block text-gray-700 mb-2">Title</label>
                                <input type="text" id="review-title" name="title"
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-primary focus:border-primary" required>
                            </div>
                            <div class="mb-4">
                                <label for="review-comment" class="block text-gray-700 mb-2">Your Review</label>
                                <textarea id="review-comment" name="comment" rows="4"
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-primary focus:border-primary" required></textarea>
                            </div>
                            <button type="submit"
                                class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-primary-dark transition-colors">
                                Submit Review
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="mt-8 text-center">
                        <p class="text-gray-600">Want to leave a review? <a href="login.php" class="text-primary hover:underline">Log in</a> to share your experience.</p>
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
                <p class="text-sm">© 2025 FreshHarvest. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Quantity Selector
            const quantitySelector = document.querySelector('.quantity-selector');
            if (quantitySelector) {
                const minusBtn = quantitySelector.querySelector('.quantity-minus');
                const plusBtn = quantitySelector.querySelector('.quantity-plus');
                const input = quantitySelector.querySelector('.quantity-input');
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
            }

            // Star Rating for Review Form
            const starButtons = document.querySelectorAll('.star-rating');
            if (starButtons) {
                starButtons.forEach(button => {
                    button.addEventListener('click', function () {
                        const rating = parseInt(this.getAttribute('data-rating'));
                        document.getElementById('rating-value').value = rating;
                        starButtons.forEach((star, index) => {
                            if (index < rating) {
                                star.innerHTML = '<i class="ri-star-fill text-yellow-400"></i>';
                            } else {
                                star.innerHTML = '<i class="ri-star-line text-yellow-400"></i>';
                            }
                        });
                    });
                });
            }
        });
    </script>
</body>
</html>