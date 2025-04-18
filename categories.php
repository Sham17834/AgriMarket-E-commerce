<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
require_once 'db_connect.php';

// Category class
class Category {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getCategories() {
        try {
            $stmt = $this->pdo->query("SELECT category_id, name, description, parent_category_id 
                                       FROM product_categories 
                                       ORDER BY name");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching categories: " . $e->getMessage());
            return [];
        }
    }
}

$category = new Category($pdo);
$categories = $category->getCategories();

// Category images (updated to include all categories from the table)
$categoryImages = [
    1 => 'https://plus.unsplash.com/premium_photo-1661902069934-e7db684d77b3?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D', // Livestock
    2 => 'https://images.unsplash.com/photo-1554871037-99938249e220?q=80&w=1935&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D', // Crops
    3 => 'https://images.unsplash.com/photo-1723209249231-01b6bfe4125f?q=80&w=1974&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D', // Forestry
    4 => 'https://plus.unsplash.com/premium_photo-1695166780662-b9b168dc65af?q=80&w=1976&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D', // Dairy
    5 => 'https://images.unsplash.com/photo-1441122456239-401e92b73c65?q=80&w=2071&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D', // Poultry
    6 => 'https://images.unsplash.com/photo-1537200275355-4f0c0714f777?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D', // Grains
    7 => 'https://plus.unsplash.com/premium_photo-1675237626334-5cf9d9d8b30c?q=80&w=1974&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D', // Nuts
    8 => 'https://images.unsplash.com/photo-1685853996091-f9b4fe630374?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D', // Fish Farming
    9 => 'https://images.unsplash.com/photo-1729260551386-0b6ba0a662d3?q=80&w=1974&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D', // Honey Products
    10 => 'https://images.unsplash.com/photo-1557844352-761f2565b576?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D', // Vegetables
    11 => 'https://plus.unsplash.com/premium_photo-1676642611795-9f1de2b99f83?q=80&w=1974&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D', // Fruits
    12 => 'https://plus.unsplash.com/premium_photo-1661775035918-b8fad529d319?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D', // Tubers
    13 => 'https://images.unsplash.com/photo-1525904097878-94fb15835963?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D' // Organic Products
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FreshHarvest - Categories</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Open+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css" rel="stylesheet">
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
                                class="absolute -top-2 -right-2 bg-accent text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">3</span>
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

    <main class="pt-20">
        <section class="py-16 bg-white">
            <div class="max-w-7xl mx-auto px-4">
                <div class="text-center mb-12">
                    <span class="inline-block text-primary font-semibold mb-2">EXPLORE</span>
                    <h2 class="text-3xl font-heading font-bold">Product Categories</h2>
                    <p class="text-gray-600 mt-2 max-w-2xl mx-auto">Browse our wide range of agricultural products by category</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $row): ?>
                            <div class="category-block">
                                <div class="flex items-center justify-between mb-6">
                                    <h3 class="text-2xl font-heading font-semibold">
                                        <?php echo htmlspecialchars($row['name']); ?>
                                    </h3>
                                    <a href="products.php?category=<?php echo $row['category_id']; ?>" 
                                       class="text-primary font-medium hover:underline flex items-center">
                                        View Products <i class="ri-arrow-right-line ml-1"></i>
                                    </a>
                                </div>
                                <div class="relative mb-4">
                                    <img src="<?php echo htmlspecialchars($categoryImages[$row['category_id']] ?? 'https://public.readdy.ai/api/placeholder/400/320'); ?>" 
                                         alt="<?php echo htmlspecialchars($row['name']); ?>" 
                                         class="w-full h-48 object-cover rounded-lg">
                                </div>
                                <p class="category-description text-gray-600 mb-4">
                                    <?php echo htmlspecialchars($row['description'] ?? 'Explore our selection of ' . strtolower($row['name'])); ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-span-3 text-center py-8">
                            <p class="text-gray-500">No categories available.</p>
                        </div>
                    <?php endif; ?>
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
</body>
</html>