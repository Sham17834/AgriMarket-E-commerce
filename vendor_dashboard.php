<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if vendor is logged in and has the correct role
if (!isset($_SESSION['vendor_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'vendor') {
    header("Location: login.php");
    exit;
}

try {
    $pdo = require_once 'db_connect.php';

    $vendor_id = $_SESSION['vendor_id'];
    $stmt = $pdo->prepare("SELECT v.*, u.username, u.email, u.phone 
                           FROM vendors v 
                           JOIN users u ON u.user_id = v.vendor_id 
                           WHERE v.vendor_id = ?");
    $stmt->execute([$vendor_id]);
    $vendor = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verify that the vendor_id exists in the database
    if (!$vendor) {
        session_unset();
        session_destroy();
        header("Location: login.php?error=Invalid vendor ID");
        exit;
    }

    $stmt = $pdo->prepare("SELECT p.*, c.name AS category_name 
                           FROM products p 
                           JOIN product_categories c ON p.category_id = c.category_id 
                           WHERE p.vendor_id = ? AND p.is_active = 1");
    $stmt->execute([$vendor_id]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Function to check if an external URL is accessible
    function is_url_accessible($url) {
        $headers = @get_headers($url);
        if ($headers && strpos($headers[0], '200') !== false) {
            return true;
        }
        return false;
    }

    // Retrieve and clear bulk upload feedback from session
    $bulk_upload_success = $_SESSION['bulk_upload_success'] ?? '';
    $bulk_upload_errors = $_SESSION['bulk_upload_errors'] ?? [];
    unset($_SESSION['bulk_upload_success']);
    unset($_SESSION['bulk_upload_errors']);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Dashboard | FreshHarvest</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Open+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #f0f9e8 0%, #e6f4e1 100%);
        }
        .upload-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .upload-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }
        .upload-button {
            transition: transform 0.2s ease;
        }
        .upload-button:hover {
            transform: scale(1.05);
        }
        .tooltip {
            position: relative;
            display: inline-block;
            cursor: pointer;
        }
        .tooltip .tooltiptext {
            visibility: hidden;
            width: 300px;
            background-color: #333;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 8px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            opacity: 0;
            transition: opacity 0.3s;
        }
        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }
    </style>
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
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex flex-col md:flex-row gap-6">
            <!-- Sidebar (Vendor Details) -->
            <div class="w-full md:w-64">
                <div class="bg-white rounded-lg shadow-sm overflow-hidden sticky top-28 p-6">
                    <div class="text-center">
                        <div class="w-20 h-20 rounded-full bg-primary/10 mx-auto flex items-center justify-center">
                            <i class="ri-store-2-line text-4xl text-primary"></i>
                        </div>
                        <h2 class="mt-4 font-bold text-lg text-gray-800">
                            <?php echo htmlspecialchars($vendor['business_name']); ?>
                        </h2>
                        <p class="text-sm text-gray-500">Vendor</p>
                        <div class="mt-4 text-sm text-gray-600 space-y-2">
                            <p><strong>Username:</strong> <?php echo htmlspecialchars($vendor['username']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($vendor['email']); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($vendor['phone'] ?? 'N/A'); ?></p>
                            <p><strong>Address:</strong> <?php echo htmlspecialchars($vendor['business_address']); ?></p>
                            <p><strong>Subscription:</strong> <?php echo htmlspecialchars($vendor['subscription_level'] ?? 'N/A'); ?></p>
                            <p><strong>Verified:</strong> <?php echo $vendor['verified_status'] ? 'Yes' : 'No'; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="flex-1">
                <div class="bg-white rounded-lg shadow-sm p-8">
                    <h1 class="text-3xl font-heading font-bold text-gray-800 mb-8">Vendor Dashboard</h1>

                    <!-- Navigation Buttons -->
                    <div class="flex flex-col sm:flex-row gap-4 mb-8">
                        <a href="vendor_profile.php" class="bg-primary text-white py-3 px-6 rounded-lg hover:bg-primary-dark text-center font-medium transition-colors">Vendor Profile</a>
                        <a href="logout.php" class="bg-primary text-white py-3 px-6 rounded-lg hover:bg-primary-dark text-center font-medium transition-colors">Logout</a>
                    </div>

                    <!-- Bulk Upload Section -->
                    <h3 class="text-2xl font-heading font-bold text-gray-800 mb-4">Bulk Upload Products</h3>
                    <div class="upload-card bg-gray-50 p-6 rounded-lg shadow-sm mb-8 gradient-bg">
                        <?php if ($bulk_upload_success): ?>
                            <p class="text-green-600 mb-4 text-center"><?php echo htmlspecialchars($bulk_upload_success); ?></p>
                        <?php endif; ?>
                        <?php if ($bulk_upload_errors): ?>
                            <ul class="text-red-600 mb-4 list-disc pl-5">
                                <?php foreach ($bulk_upload_errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <form method="POST" enctype="multipart/form-data" action="bulk_upload.php" class="space-y-6">
                            <div>
                                <label class="block text-gray-700 font-medium mb-2">Select CSV File to Upload</label>
                                <div class="flex items-center space-x-2">
                                    <input type="file" name="csv_file" accept=".csv" class="w-full px-4 py-3 border rounded-button focus:outline-none focus:ring-2 focus:ring-primary" required>
                                    <div class="tooltip">
                                        <i class="ri-question-line text-gray-500"></i>
                                        <span class="tooltiptext">CSV Format: name, category_id, description, price, discounted_price, stock_quantity, minimum_order_quantity, packaging_type, weight_kg, is_organic, harvest_date, image_url<br>Example: "Organic Chicken",1,"Free-range, 1.5kg",25.00,20.00,100,10,"Vacuum-sealed",1.5,1,"2025-04-10","https://example.com/chicken.jpg"</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-center gap-4">
                                <button type="submit" class="upload-button bg-primary text-white py-3 px-6 w-full sm:w-auto rounded-button hover:bg-primary-dark">Upload Products</button>
                                <a href="vendor_dashboard.php" class="bg-gray-600 text-white py-3 px-6 w-full sm:w-auto rounded-button hover:bg-gray-700 text-center">Cancel</a>
                            </div>
                        </form>
                    </div>

                    <!-- Products Grid -->
                    <h3 class="text-2xl font-heading font-bold text-gray-800 mb-4">Your Products</h3>
                    <?php if ($products): ?>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php foreach ($products as $product): ?>
                                <div class="bg-white p-4 rounded-lg shadow-sm">
                                    <?php
                                    // Handle image_url as a JSON-encoded array
                                    $image_url = '';
                                    if ($product['image_url']) {
                                        $images = json_decode($product['image_url'], true);
                                        if (json_last_error() === JSON_ERROR_NONE && is_array($images) && !empty($images)) {
                                            $image_url = htmlspecialchars($images[0]);
                                            // Check if the external URL is accessible
                                            if (!is_url_accessible($image_url)) {
                                                $image_url = 'assets/images/placeholder.jpg';
                                            }
                                        } else {
                                            $image_url = 'assets/images/placeholder.jpg';
                                        }
                                    } else {
                                        $image_url = 'assets/images/placeholder.jpg';
                                    }
                                    ?>
                                    <img class="product-image w-full h-48 object-cover rounded-t-lg" src="<?php echo $image_url; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    <div class="p-4 space-y-2">
                                        <h4 class="font-heading font-bold text-lg text-gray-800"><?php echo htmlspecialchars($product['name']); ?></h4>
                                        <p class="text-gray-600">Category: <?php echo htmlspecialchars($product['category_name']); ?></p>
                                        <p class="text-gray-800 font-bold">Price: RM<?php echo number_format($product['discounted_price'] ?? $product['price'], 2); ?></p>
                                        <p class="text-gray-600">Stock: <span class="quantity-badge"><?php echo $product['stock_quantity']; ?></span></p>
                                        <a href="add_product.php?id=<?php echo $product['product_id']; ?>" class="block mt-2 bg-primary text-white text-center py-2 rounded-lg hover:bg-primary-dark font-medium transition-colors">Edit</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-12 bg-gray-50 rounded-lg">
                            <div class="mx-auto w-16 h-16 flex items-center justify-center bg-gray-200 rounded-full mb-4">
                                <i class="ri-box-3-line text-2xl text-gray-500"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-700 mb-2">No Products Listed</h3>
                            <p class="text-gray-500">You have not listed any products yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
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
            <p class="text-sm">© 2025 AgriMarket Solutions. All rights reserved.</p>
        </div>
    </div>
</footer>
<script src="scripts.js"></script>
</body>
</html>