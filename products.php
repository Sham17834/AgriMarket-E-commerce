<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
require_once 'db_connect.php';

// Initialize cart if not already set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Product class
class Product
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getProducts($search = '', $category = 0, $organic = null, $min_price = 0, $max_price = 1000, $sort_by = 'name', $sort_order = 'ASC')
    {
        $query = "SELECT p.product_id, p.name, p.description, p.price, p.discounted_price, p.image_url, p.is_organic, 
                         pc.name AS category_name, v.business_name, v.verified_status
                  FROM products p
                  JOIN product_categories pc ON p.category_id = pc.category_id
                  JOIN vendors v ON p.vendor_id = v.vendor_id
                  WHERE p.is_active = 1";
        $params = [];
        $types = [];

        if ($search) {
            $query .= " AND MATCH(p.name, p.description) AGAINST(:search IN BOOLEAN MODE)";
            $params['search'] = $search;
        }
        if ($category > 0) {
            $query .= " AND p.category_id = :category";
            $params['category'] = $category;
            $types[] = PDO::PARAM_INT;
        }
        if ($organic !== null) {
            $query .= " AND p.is_organic = :organic";
            $params['organic'] = $organic;
            $types[] = PDO::PARAM_INT;
        }
        $query .= " AND p.price BETWEEN :min_price AND :max_price";
        $params['min_price'] = $min_price;
        $params['max_price'] = $max_price;
        $types[] = PDO::PARAM_STR;
        $types[] = PDO::PARAM_STR;

        // Add sorting
        $valid_sort_fields = ['name', 'price', 'discounted_price', 'business_name'];
        $valid_sort_orders = ['ASC', 'DESC'];

        if (!in_array($sort_by, $valid_sort_fields)) {
            $sort_by = 'name';
        }
        if (!in_array($sort_order, $valid_sort_orders)) {
            $sort_order = 'ASC';
        }

        $sort_prefix = 'p.';
        if ($sort_by === 'business_name') {
            $sort_prefix = 'v.';
        }

        $query .= " ORDER BY " . $sort_prefix . $sort_by . " " . $sort_order;

        try {
            $stmt = $this->pdo->prepare($query);
            foreach ($params as $key => $value) {
                $paramKey = ':' . $key;
                if ($key === 'search') {
                    $stmt->bindValue($paramKey, $value, PDO::PARAM_STR);
                } elseif ($key === 'category' || $key === 'organic') {
                    $stmt->bindValue($paramKey, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($paramKey, $value, PDO::PARAM_STR);
                }
            }
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching products: " . $e->getMessage());
            return [];
        }
    }

    public function getCategories()
    {
        try {
            $stmt = $this->pdo->query("SELECT category_id, name FROM product_categories ORDER BY name");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching categories: " . $e->getMessage());
            return [];
        }
    }

    public function getProductCount()
    {
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 1");
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error counting products: " . $e->getMessage());
            return 0;
        }
    }

    public function getPriceRange()
    {
        try {
            $stmt = $this->pdo->query("SELECT MIN(price) as min_price, MAX(price) as max_price FROM products WHERE is_active = 1");
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error getting price range: " . $e->getMessage());
            return ['min_price' => 0, 'max_price' => 1000];
        }
    }
}

$product = new Product($pdo);

// Handle search and filter inputs
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? (int) $_GET['category'] : 0;
$organic = isset($_GET['organic']) && $_GET['organic'] !== '' ? (int) $_GET['organic'] : null;
$min_price = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (float) $_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float) $_GET['max_price'] : 1000;
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'name';
$sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'ASC';

// Get price range from database for slider
$price_range = $product->getPriceRange();
$db_min_price = floor($price_range['min_price']);
$db_max_price = ceil($price_range['max_price']);

// If no specific prices are set in the filter, use the database values
if (!isset($_GET['min_price']) || $_GET['min_price'] === '') {
    $min_price = $db_min_price;
}
if (!isset($_GET['max_price']) || $_GET['max_price'] === '') {
    $max_price = $db_max_price;
}

$products = $product->getProducts($search, $category, $organic, $min_price, $max_price, $sort_by, $sort_order);
$categories = $product->getCategories();
$product_count = count($products);

// Get cart item count
$cart_count = array_sum(array_column($_SESSION['cart'], 'quantity'));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!$isLoggedIn) {
        header("Location: login.php?redirect=products.php");
        exit();
    }

    $product_id = (int) $_POST['product_id'];
    $quantity = max(1, (int) ($_POST['quantity'] ?? 1)); // Default to 1 if not specified

    try {
        // Verify product exists and is active
        $stmt = $pdo->prepare("SELECT stock_quantity, minimum_order_quantity, is_active FROM products WHERE product_id = :id");
        $stmt->bindParam(':id', $product_id, PDO::PARAM_INT);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product || !$product['is_active']) {
            $error = "Product is not available.";
        } else {
            $stock_quantity = $product['stock_quantity'];
            $minimum_order_quantity = $product['minimum_order_quantity'];

            // Validate quantity
            if ($quantity < $minimum_order_quantity) {
                $quantity = $minimum_order_quantity;
            }
            if ($quantity > $stock_quantity) {
                $error = "Requested quantity exceeds available stock.";
            } else {
                // Check if user has a cart
                $stmt = $pdo->prepare("SELECT cart_id, cart_data FROM shopping_carts WHERE customer_id = :customer_id");
                $stmt->bindParam(':customer_id', $_SESSION['user_id'], PDO::PARAM_INT);
                $stmt->execute();
                $cart = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$cart) {
                    // Create a new cart
                    $cart_data = [
                        [
                            'product_id' => $product_id,
                            'quantity' => $quantity
                        ]
                    ];

                    $stmt = $pdo->prepare("INSERT INTO shopping_carts (customer_id, cart_data, created_at, updated_at) 
                                        VALUES (:customer_id, :cart_data, NOW(), NOW())");
                    $stmt->bindParam(':customer_id', $_SESSION['user_id'], PDO::PARAM_INT);
                    $cart_data_json = json_encode($cart_data);
                    $stmt->bindParam(':cart_data', $cart_data_json, PDO::PARAM_STR);
                    $stmt->execute();
                } else {
                    // Update existing cart
                    $cart_data = json_decode($cart['cart_data'] ?? '[]', true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $cart_data = [];
                    }

                    // Check if product already in cart
                    $product_found = false;
                    foreach ($cart_data as &$item) {
                        if ($item['product_id'] === $product_id) {
                            $item['quantity'] += $quantity;
                            // Ensure not exceeding stock
                            if ($item['quantity'] > $stock_quantity) {
                                $item['quantity'] = $stock_quantity;
                            }
                            $product_found = true;
                            break;
                        }
                    }

                    // If product not in cart, add it
                    if (!$product_found) {
                        $cart_data[] = [
                            'product_id' => $product_id,
                            'quantity' => $quantity
                        ];
                    }

                    // Update cart in database
                    $stmt = $pdo->prepare("UPDATE shopping_carts SET cart_data = :cart_data, updated_at = NOW() 
                                        WHERE cart_id = :cart_id");
                    $cart_data_json = json_encode($cart_data);
                    $stmt->bindParam(':cart_data', $cart_data_json, PDO::PARAM_STR);
                    $stmt->bindParam(':cart_id', $cart['cart_id'], PDO::PARAM_INT);
                    $stmt->execute();
                }

                $success = "Product added to cart successfully!";

                // Optional: Redirect to cart page
                // header("Location: cart.php");
                // exit();
            }
        }
    } catch (PDOException $e) {
        $error = "Error adding to cart: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FreshHarvest - Products</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Open+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.1/nouislider.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.1/nouislider.min.js" defer></script>
    <style>
        .sidebar-filter {
            transition: all 0.3s ease;
        }

        .filter-section {
            margin-bottom: 1.5rem;
        }

        .filter-title {
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .active-filter {
            color: var(--color-primary);
            font-weight: 600;
        }

        .product-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .product-card:hover {
            transform: translateY(-5px);
        }

        .badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            z-index: 10;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .organic-badge {
            background-color: #ecfdf5;
            color: #047857;
        }

        .discount-badge {
            background-color: #fef2f2;
            color: #dc2626;
        }

        .price-range-slider {
            height: 5px;
            margin: 1.5rem 0;
        }

        .noUi-connect {
            background: var(--color-primary);
        }

        .noUi-handle {
            border-radius: 50%;
            box-shadow: 0 0 0 3px var(--color-primary);
            cursor: pointer;
        }

        .mobile-filter-toggle {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 50;
            height: 3.5rem;
            width: 3.5rem;
            background-color: var(--color-primary);
            color: white;
            border-radius: 9999px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        /* Mobile sidebar overlay */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 40;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .sidebar-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        /* Hide scrollbar but allow scrolling */
        .scrollbar-hide {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }

        /* Footer fixes */
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        main {
            flex: 1;
        }

        footer {
            width: 100%;
            margin-top: auto;
        }

        @media (max-width: 1023px) {
            .sidebar-filter {
                position: fixed;
                top: 0;
                left: -100%;
                height: 100vh;
                width: 80%;
                max-width: 300px;
                z-index: 50;
                overflow-y: auto;
            }

            .sidebar-filter.active {
                left: 0;
            }
        }
    </style>
</head>

<body class="bg-natural-light min-h-screen font-body">
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

    <main class="pt-20 pb-12">
        <section class="py-16 bg-white">
            <div class="max-w-7xl mx-auto px-4">
                <div class="text-center mb-12">
                    <span class="inline-block text-primary font-semibold mb-2">BROWSE</span>
                    <h2 class="text-3xl font-heading font-bold">Our Products</h2>
                    <p class="text-gray-600 mt-2">Farm-fresh produce delivered directly to you</p>
                </div>

                <!-- Mobile filter toggle button -->
                <button class="mobile-filter-toggle lg:hidden" id="mobileFilterToggle">
                    <i class="ri-filter-3-line text-xl"></i>
                </button>

                <!-- Sidebar overlay for mobile -->
                <div class="sidebar-overlay lg:hidden" id="sidebarOverlay"></div>

                <div class="flex flex-wrap lg:flex-nowrap">
                    <!-- Sidebar filters -->
                    <div class="sidebar-filter w-full lg:w-64 bg-white p-6 rounded-lg shadow-sm mr-0 lg:mr-8"
                        id="sidebarFilter">
                        <div class="flex justify-between items-center lg:hidden mb-6">
                            <h3 class="font-heading font-bold text-lg">Filters</h3>
                            <button class="text-gray-500" id="closeSidebar">
                                <i class="ri-close-line text-xl"></i>
                            </button>
                        </div>

                        <form method="GET" id="filterForm">
                            <!-- Keep search term hidden if present -->
                            <?php if ($search): ?>
                                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                            <?php endif; ?>

                            <!-- Filter by category -->
                            <div class="filter-section">
                                <div class="filter-title">
                                    <span>Category</span>
                                </div>
                                <div>
                                    <div class="space-y-2 mt-3">
                                        <div class="flex items-center">
                                            <input type="radio" id="cat-all" name="category" value="0" class="mr-2"
                                                <?php if ($category == 0)
                                                    echo 'checked'; ?>>
                                            <label for="cat-all" class="<?php if ($category == 0)
                                                echo 'active-filter'; ?>">All
                                                Categories</label>
                                        </div>
                                        <?php foreach ($categories as $cat): ?>
                                            <div class="flex items-center">
                                                <input type="radio" id="cat-<?php echo $cat['category_id']; ?>"
                                                    name="category" value="<?php echo $cat['category_id']; ?>" class="mr-2"
                                                    <?php if ($cat['category_id'] == $category)
                                                        echo 'checked'; ?>>
                                                <label for="cat-<?php echo $cat['category_id']; ?>" class="<?php if ($cat['category_id'] == $category)
                                                       echo 'active-filter'; ?>">
                                                    <?php echo htmlspecialchars($cat['name']); ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Filter by organic status -->
                            <div class="filter-section">
                                <div class="filter-title">
                                    <span>Product Type</span>
                                </div>
                                <div>
                                    <div class="space-y-2 mt-3">
                                        <div class="flex items-center">
                                            <input type="radio" id="organic-all" name="organic" value="" class="mr-2"
                                                <?php if ($organic === null)
                                                    echo 'checked'; ?>>
                                            <label for="organic-all" class="<?php if ($organic === null)
                                                echo 'active-filter'; ?>">All
                                                Products</label>
                                        </div>
                                        <div class="flex items-center">
                                            <input type="radio" id="organic-yes" name="organic" value="1" class="mr-2"
                                                <?php if ($organic === 1)
                                                    echo 'checked'; ?>>
                                            <label for="organic-yes" class="<?php if ($organic === 1)
                                                echo 'active-filter'; ?>">Organic
                                                Only</label>
                                        </div>
                                        <div class="flex items-center">
                                            <input type="radio" id="organic-no" name="organic" value="0" class="mr-2"
                                                <?php if ($organic === 0)
                                                    echo 'checked'; ?>>
                                            <label for="organic-no" class="<?php if ($organic === 0)
                                                echo 'active-filter'; ?>">Standard
                                                Only</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Filter by price range -->
                            <div class="filter-section">
                                <div class="filter-title">
                                    <span>Price Range</span>
                                </div>
                                <div>
                                    <div id="priceRangeSlider" class="price-range-slider"></div>
                                    <div class="flex justify-between mt-2">
                                        <div>
                                            <span>RM</span>
                                            <input type="number" name="min_price" id="min_price"
                                                value="<?php echo $min_price; ?>"
                                                class="border border-gray-200 rounded w-16 text-sm px-2 py-1">
                                        </div>
                                        <div>
                                            <span>RM</span>
                                            <input type="number" name="max_price" id="max_price"
                                                value="<?php echo $max_price; ?>"
                                                class="border border-gray-200 rounded w-16 text-sm px-2 py-1">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sort by -->
                            <div class="filter-section">
                                <div class="filter-title">
                                    <span>Sort By</span>
                                </div>
                                <div>
                                    <select name="sort_by" class="w-full border border-gray-200 rounded p-2 text-sm">
                                        <option value="name" <?php if ($sort_by == 'name')
                                            echo 'selected'; ?>>Name
                                        </option>
                                        <option value="price" <?php if ($sort_by == 'price')
                                            echo 'selected'; ?>>Price
                                        </option>
                                        <option value="business_name" <?php if ($sort_by == 'business_name')
                                            echo 'selected'; ?>>Vendor</option>
                                    </select>
                                    <div class="flex mt-3">
                                        <div class="flex items-center mr-4">
                                            <input type="radio" id="sort-asc" name="sort_order" value="ASC" class="mr-2"
                                                <?php if ($sort_order == 'ASC')
                                                    echo 'checked'; ?>>
                                            <label for="sort-asc">Ascending</label>
                                        </div>
                                        <div class="flex items-center">
                                            <input type="radio" id="sort-desc" name="sort_order" value="DESC"
                                                class="mr-2" <?php if ($sort_order == 'DESC')
                                                    echo 'checked'; ?>>
                                            <label for="sort-desc">Descending</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Filter buttons -->
                            <div class="mt-6 space-y-3">
                                <button type="submit"
                                    class="w-full bg-primary text-white px-4 py-2 rounded-full hover:bg-primary-dark transition-colors">
                                    Apply Filters
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Products container -->
                    <div class="w-full">
                        <!-- Result summary -->
                        <div class="flex flex-wrap items-center justify-between mb-6 p-4 bg-gray-50 rounded-lg">
                            <div>
                                <p class="text-sm text-gray-600">Showing <span
                                        class="font-semibold"><?php echo $product_count; ?></span> products</p>
                                <?php if ($search): ?>
                                    <p class="text-sm">Search results for: <span
                                            class="font-semibold">"<?php echo htmlspecialchars($search); ?>"</span></p>
                                <?php endif; ?>
                            </div>               
                        </div>

                        <div class="product-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php if (!empty($products)): ?>
                                <?php foreach ($products as $row): ?>
                                    <div
                                        class="product-card bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-all">
                                        <div class="relative overflow-hidden group">
                                            <?php if ($row['is_organic']): ?>
                                                <div class="badge organic-badge">Organic</div>
                                            <?php endif; ?>
                                            <?php $image_url = json_decode($row['image_url'], true)[0] ?? 'https://public.readdy.ai/api/placeholder/400/320'; ?>
                                            <a href="product_details.php?id=<?php echo $row['product_id']; ?>">
                                                <img src="<?php echo htmlspecialchars($image_url); ?>"
                                                    alt="<?php echo htmlspecialchars($row['name']); ?>"
                                                    class="w-full h-48 object-cover transition-transform duration-500 group-hover:scale-110">
                                            </a>
                                            <div
                                                class="absolute inset-0 bg-black bg-opacity-20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center pointer-events-none">
                                            </div>
                                        </div>
                                        <div class="p-4">
                                            <div class="flex items-center justify-between mb-2">
                                                <span
                                                    class="text-sm text-gray-500"><?php echo htmlspecialchars($row['category_name']); ?></span>
                                            </div>
                                            <h3 class="text-lg font-heading font-semibold">
                                                <?php echo htmlspecialchars($row['name']); ?>
                                            </h3>
                                            <div class="flex items-center mt-2">
                                                <?php if (!is_null($row['discounted_price']) && $row['discounted_price'] < $row['price']): ?>
                                                    <span
                                                        class="text-gray-500 line-through text-sm mr-2">RM<?php echo number_format($row['price'], 2); ?></span>
                                                    <span class="text-primary font-heading font-bold text-lg">
                                                        RM<?php echo number_format($row['discounted_price'], 2); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-primary font-heading font-bold text-lg">
                                                        RM<?php echo number_format($row['price'], 2); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex items-center justify-between mt-4">
                                                <span class="text-sm text-primary flex items-center">
                                                    <i class="ri-user-line mr-1"></i>
                                                    <?php echo htmlspecialchars($row['business_name']); ?>
                                                    <?php if ($row['verified_status']): ?>
                                                        <i class="ri-verified-badge-fill text-green-500 ml-1"
                                                            title="Verified Vendor"></i>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="col-span-3 text-center py-16 bg-gray-50 rounded-lg">
                                    <i class="ri-shopping-basket-line text-5xl text-gray-300 mb-4"></i>
                                    <h3 class="text-xl font-semibold text-gray-700 mb-2">No products found</h3>
                                    <p class="text-gray-500">Try adjusting your filters or search terms.</p>
                                    <a href="products.php" class="inline-block mt-4 text-primary hover:underline">View all
                                        products</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
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
                        <a href="https://web.facebook.com/INTI.edu/?locale=ms_MY&_rdc=1&_rdr#" class="hover:text-primary-light transition-colors"><i
                                class="ri-facebook-fill text-xl"></i></a>
                        <a href="https://www.instagram.com/inti_edu/?hl=en" class="hover:text-primary-light transition-colors"><i
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
            // Price range slider initialization
            const priceSlider = document.getElementById('priceRangeSlider');
            const minPriceInput = document.getElementById('min_price');
            const maxPriceInput = document.getElementById('max_price');

            if (priceSlider) {
                noUiSlider.create(priceSlider, {
                    start: [<?php echo $min_price; ?>, <?php echo $max_price; ?>],
                    connect: true,
                    step: 1,
                    range: {
                        'min': <?php echo $db_min_price; ?>,
                        'max': <?php echo $db_max_price; ?>
                    }
                });

                // Update input values when slider changes
                priceSlider.noUiSlider.on('update', function (values, handle) {
                    const value = Math.floor(values[handle]);
                    if (handle === 0) {
                        minPriceInput.value = value;
                    } else {
                        maxPriceInput.value = value;
                    }
                });

                // Update slider when inputs change
                minPriceInput.addEventListener('change', function () {
                    priceSlider.noUiSlider.set([this.value, null]);
                });

                maxPriceInput.addEventListener('change', function () {
                    priceSlider.noUiSlider.set([null, this.value]);
                });
            }

            // Reset filters button
            const resetFiltersBtn = document.getElementById('resetFilters');
            if (resetFiltersBtn) {
                resetFiltersBtn.addEventListener('click', function () {
                    window.location.href = 'products.php';
                });
            }

            // Client-side validation for price range
            document.querySelector('#filterForm').addEventListener('submit', function (e) {
                const minPrice = parseFloat(minPriceInput.value) || 0;
                const maxPrice = parseFloat(maxPriceInput.value) || 1000;
                if (minPrice < 0 || maxPrice < 0 || minPrice > maxPrice) {
                    e.preventDefault();
                    alert('Please enter a valid price range.');
                }
            });

            // Highlight active filter options
            const filterInputs = document.querySelectorAll('input[type="radio"]');
            filterInputs.forEach(input => {
                input.addEventListener('change', function () {
                    // Remove active class from all labels in the same group
                    const name = this.getAttribute('name');
                    document.querySelectorAll(`input[name="${name}"] + label`).forEach(label => {
                        label.classList.remove('active-filter');
                    });

                    // Add active class to selected label
                    this.nextElementSibling.classList.add('active-filter');
                });
            });

            // ========== CART FUNCTIONALITY ==========
            const cartBtn = document.getElementById('cartBtn');
            if (cartBtn) {
                cartBtn.addEventListener('click', function () {
                    window.location.href = 'cart.php';
                });
            }

            // ========== COUNTDOWN TIMERS ==========
            function initializeCountdowns() {
                const countdownElements = document.querySelectorAll('[id^="countdown"]');

                if (countdownElements.length > 0) {
                    // Set the date we're counting down to (24 hours from now)
                    const countDownDate = new Date();
                    countDownDate.setHours(countDownDate.getHours() + 24);

                    // Update all countdown elements
                    const countdownInterval = setInterval(function () {
                        const now = new Date().getTime();
                        const distance = countDownDate - now;

                        // Time calculations
                        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                        // Update each countdown element
                        countdownElements.forEach(element => {
                            element.innerHTML = "Ends in: " + hours + "h " + minutes + "m " + seconds + "s";
                        });

                        // If the countdown is finished
                        if (distance < 0) {
                            clearInterval(countdownInterval);
                            countdownElements.forEach(element => {
                                element.innerHTML = "EXPIRED";
                            });
                        }
                    }, 1000);
                }
            }
            initializeCountdowns();

            // ========== PRODUCT QUANTITY SELECTORS ==========
            document.querySelectorAll('.quantity-selector').forEach(selector => {
                const minusBtn = selector.querySelector('.quantity-minus');
                const plusBtn = selector.querySelector('.quantity-plus');
                const input = selector.querySelector('.quantity-input');

                minusBtn.addEventListener('click', () => {
                    let value = parseInt(input.value);
                    if (value > 1) {
                        input.value = value - 1;
                    }
                });

                plusBtn.addEventListener('click', () => {
                    let value = parseInt(input.value);
                    input.value = value + 1;
                });
            });

            // ========== LAZY LOAD IMAGES ==========
            if ('IntersectionObserver' in window) {
                const lazyImages = document.querySelectorAll('img.lazy');

                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src;
                            img.classList.remove('lazy');
                            observer.unobserve(img);
                        }
                    });
                });

                lazyImages.forEach(img => imageObserver.observe(img));
            }

            // ========== SMOOTH SCROLLING ==========
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    document.querySelector(this.getAttribute('href')).scrollIntoView({
                        behavior: 'smooth'
                    });
                });
            });
        });
    </script>
</body>

</html>