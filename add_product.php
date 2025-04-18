<?php
session_start();

// Check if vendor is logged in
if (!isset($_SESSION['vendor_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'vendor') {
    header("Location: login.php");
    exit;
}

require_once 'db_connect.php';

$vendor_id = $_SESSION['vendor_id'];
$product = null;
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$errors = [];

if ($product_id) {
    // Fetch existing product for editing
    $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ? AND vendor_id = ?");
    $stmt->execute([$product_id, $vendor_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        header("Location: vendor_dashboard.php?error=Product not found");
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $stock_quantity = trim($_POST['stock_quantity'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    
    // Validation
    if (empty($name)) {
        $errors[] = "Product name is required.";
    }
    if (!is_numeric($price) || $price < 0) {
        $errors[] = "Price must be a positive number.";
    }
    if (!is_numeric($stock_quantity) || $stock_quantity < 0) {
        $errors[] = "Stock quantity must be a positive number.";
    }
    if ($category_id <= 0) {
        $errors[] = "Please select a valid category.";
    }
    
    if (empty($errors)) {
        try {
            if ($product_id) {
                // Update existing product
                $stmt = $pdo->prepare("UPDATE products 
                                      SET name = ?, price = ?, stock_quantity = ?, category_id = ?, updated_at = NOW() 
                                      WHERE product_id = ? AND vendor_id = ?");
                $stmt->execute([$name, $price, $stock_quantity, $category_id, $product_id, $vendor_id]);
            } else {
                // Insert new product
                $stmt = $pdo->prepare("INSERT INTO products 
                                      (vendor_id, name, price, stock_quantity, category_id, is_active, created_at, updated_at) 
                                      VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())");
                $stmt->execute([$vendor_id, $name, $price, $stock_quantity, $category_id]);
            }
            header("Location: vendor_dashboard.php?success=Product saved successfully");
            exit;
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch categories for dropdown
$categories = $pdo->query("SELECT * FROM product_categories")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $product_id ? 'Edit Product' : 'Add Product'; ?> | FreshHarvest</title>
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
    <div class="max-w-2xl mx-auto px-4">
        <div class="bg-white rounded-lg shadow-sm p-8">
            <h1 class="text-3xl font-heading font-bold text-gray-800 mb-8">
                <?php echo $product_id ? 'Edit Product' : 'Add Product'; ?>
            </h1>

            <?php if (!empty($errors)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Product Name</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($product['name'] ?? ''); ?>" required
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>

                <div>
                    <label for="price" class="block text-sm font-medium text-gray-700 mb-1">Price (RM)</label>
                    <input type="number" step="0.01" id="price" name="price" value="<?php echo htmlspecialchars($product['price'] ?? ''); ?>" required
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>

                <div>
                    <label for="stock_quantity" class="block text-sm font-medium text-gray-700 mb-1">Stock Quantity</label>
                    <input type="number" id="stock_quantity" name="stock_quantity" value="<?php echo htmlspecialchars($product['stock_quantity'] ?? ''); ?>" required
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>

                <div>
                    <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select id="category_id" name="category_id" required
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="">Select a category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['category_id']; ?>" <?php echo ($product && $product['category_id'] == $category['category_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flex space-x-4">
                    <button type="submit" class="bg-primary text-white py-3 px-6 rounded-lg hover:bg-primary-dark font-medium transition-colors">
                        Save Product
                    </button>
                    <a href="vendor_dashboard.php" class="bg-gray-300 text-gray-800 py-3 px-6 rounded-lg hover:bg-gray-400 font-medium transition-colors">
                        Cancel
                    </a>
                </div>
            </form>
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
</body>
</html>