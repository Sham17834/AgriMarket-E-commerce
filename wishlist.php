<?php
session_start();

$isLoggedIn = isset($_SESSION['user_id']);
if (!$isLoggedIn) {
    header("Location: login.php?redirect=wishlist.php");
    exit;
}

require_once 'db_connect.php';

// Fetch user details
$user_id = $_SESSION['user_id'];
try {
    $stmt = $pdo->prepare("SELECT username FROM users WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        session_destroy();
        header("Location: login.php?error=User not found");
        exit;
    }
} catch (PDOException $e) {
    error_log("Error fetching user: " . $e->getMessage());
    header("Location: login.php?error=Error fetching profile");
    exit;
}


$cart_count = 0;
try {
    $stmt = $pdo->prepare("SELECT cart_data FROM shopping_carts WHERE customer_id = :customer_id");
    $stmt->bindParam(':customer_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $cart = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cart && !empty($cart['cart_data'])) {
        $cart_data = json_decode($cart['cart_data'], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($cart_data)) {
            $cart_count = array_sum(array_column($cart_data, 'quantity'));
        }
    }
} catch (PDOException $e) {
    error_log("Error fetching cart count: " . $e->getMessage());
}

// Fetch wishlist items
$wishlist_items = [];
try {
    $stmt = $pdo->prepare("
        SELECT w.wishlist_id, p.product_id, p.name, p.price, p.discounted_price, p.image_url, w.added_at
        FROM wishlists w
        JOIN products p ON w.product_id = p.product_id
        WHERE w.customer_id = :customer_id AND p.is_active = 1
        ORDER BY w.added_at DESC
    ");
    $stmt->bindParam(':customer_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $wishlist_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching wishlist: " . $e->getMessage());
}

// Handle wishlist item removal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_wishlist_item'])) {
    $wishlist_id = (int)$_POST['wishlist_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM wishlists WHERE wishlist_id = :wishlist_id AND customer_id = :customer_id");
        $stmt->bindParam(':wishlist_id', $wishlist_id, PDO::PARAM_INT);
        $stmt->bindParam(':customer_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        // Redirect to avoid form resubmission
        header("Location: wishlist.php");
        exit;
    } catch (PDOException $e) {
        error_log("Error removing wishlist item: " . $e->getMessage());
        $error = "Failed to remove item from wishlist.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist | FreshHarvest</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Open+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen font-body flex flex-col">
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

<main class="pt-28 pb-16 flex-1">
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-12">
                <span class="inline-block text-primary font-semibold mb-2">YOUR FAVORITES</span>
                <h2 class="text-3xl font-heading font-bold">My Wishlist</h2>
                <p class="text-gray-600 mt-2">Your saved products</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (empty($wishlist_items)): ?>
                <div class="text-center py-8 bg-gray-50 rounded-lg">
                    <div class="mx-auto w-16 h-16 flex items-center justify-center bg-gray-100 rounded-full mb-4">
                        <i class="ri-heart-line text-2xl text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-700 mb-2">Your wishlist is empty</h3>
                    <p class="text-gray-500 mb-4">Add some products to your wishlist!</p>
                    <a href="products.php" class="inline-block bg-primary text-white py-2 px-4 rounded-lg hover:bg-primary-dark transition">
                        Browse Products
                    </a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($wishlist_items as $item): ?>
                        <div class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-all">
                            <div class="relative">
                                <?php $image_url = json_decode($item['image_url'], true)[0] ?? 'https://public.readdy.ai/api/placeholder/400/320'; ?>
                                <a href="product_details.php?id=<?php echo $item['product_id']; ?>">
                                    <img src="<?php echo htmlspecialchars($image_url); ?>"
                                         alt="<?php echo htmlspecialchars($item['name']); ?>"
                                         class="w-full h-48 object-cover">
                                </a>
                            </div>
                            <div class="p-4">
                                <h3 class="text-lg font-heading font-semibold">
                                    <?php echo htmlspecialchars($item['name']); ?>
                                </h3>
                                <div class="flex items-center mt-2">
                                    <?php if (!is_null($item['discounted_price']) && $item['discounted_price'] < $item['price']): ?>
                                        <span class="text-gray-500 line-through text-sm mr-2">
                                            RM<?php echo number_format($item['price'], 2); ?>
                                        </span>
                                        <span class="text-primary font-heading font-bold text-lg">
                                            RM<?php echo number_format($item['discounted_price'], 2); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-primary font-heading font-bold text-lg">
                                            RM<?php echo number_format($item['price'], 2); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="flex items-center justify-between mt-4">
                                    <a href="product_details.php?id=<?php echo $item['product_id']; ?>" 
                                       class="text-primary hover:text-primary-dark font-medium">
                                        View Product
                                    </a>
                                    <form method="POST" action="">
                                        <input type="hidden" name="wishlist_id" value="<?php echo $item['wishlist_id']; ?>">
                                        <button type="submit" name="remove_wishlist_item" 
                                                class="text-red-500 hover:text-red-700 transition"
                                                title="Remove from Wishlist">
                                            <i class="ri-delete-bin-line text-xl"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<footer class="bg-primary-dark text-white py-12 flex-shrink-0">
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
            <p class="text-sm">© AgriMarket Solutions is a fictitious business created for a university course.</p>
        </div>
    </div>
</footer>
</body>
</html>
