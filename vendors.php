<?php
session_start();
require_once 'db_connect.php';

$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Vendors | FreshHarvest</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Open+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body class="bg-gray-100 min-h-screen font-body flex flex-col">
    <header class="fixed top-0 left-0 right-0 bg-white shadow-md z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between h-20">
                <div class="flex items-center space-x-8">
                    <a href="index.php" class="text-3xl font-heading font-bold text-primary flex items-center">
                        <i class="fa-solid fa-leaf mr-2 text-primary-light"></i>
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

    <main class="pt-28 pb-16 flex-1">
        <div class="max-w-7xl mx-auto px-4">
            <div class="bg-white rounded-lg shadow-sm p-8">
                <h1 class="text-3xl font-heading font-bold text-gray-800 mb-8">All Vendors</h1>

                <!-- Vendor List -->
                <div class="space-y-4">
                    <?php
                    $vendorAvatars = [
                        'https://images.unsplash.com/photo-1633332755192-727a05c4013d?q=80&w=200&auto=format&fit=crop',
                        'https://images.unsplash.com/photo-1573497019418-b400bb3ab074?q=80&w=200&auto=format&fit=crop',
                        'https://plus.unsplash.com/premium_photo-1689977807477-a579eda91fa2?q=80&w=200&auto=format&fit=crop'
                    ];

                    $stmt = $pdo->query("SELECT v.*, u.username, u.email, u.phone 
                                    FROM vendors v 
                                    JOIN users u ON v.vendor_id = u.user_id 
                                    WHERE u.role = 'vendor'");
                    $vendorIndex = 0;

                    if ($stmt->rowCount() > 0) {
                        while ($row = $stmt->fetch()) {
                            $vendorAvatar = $vendorAvatars[$vendorIndex % count($vendorAvatars)];
                            $vendorIndex++;

                            // Calculate average rating for the vendor
                            $avg_rating_stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as review_count 
                                                         FROM vendor_reviews 
                                                         WHERE vendor_id = ?");
                            $avg_rating_stmt->execute([$row['vendor_id']]);
                            $rating_data = $avg_rating_stmt->fetch();
                            $avg_rating = $rating_data['avg_rating'] ? round($rating_data['avg_rating'], 1) : 0;
                            ?>
                            <div
                                class="bg-white p-4 rounded-lg shadow-sm hover:shadow-md transition-all border border-gray-100">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-4">
                                        <!-- Avatar Image -->
                                        <div class="relative">
                                            <img src="<?php echo htmlspecialchars($vendorAvatar); ?>"
                                                alt="<?php echo htmlspecialchars($row['business_name']); ?>"
                                                class="w-12 h-12 rounded-full object-cover border-2 border-white shadow-sm">
                                            <?php if ($row['verified_status']) { ?>
                                                <div class="absolute -bottom-1 -right-1 bg-white p-0.5 rounded-full">
                                                    <i class="ri-verified-badge-fill text-green-500 text-sm"
                                                        title="Verified Vendor"></i>
                                                </div>
                                            <?php } ?>
                                        </div>

                                        <!-- Vendor Info -->
                                        <div>
                                            <h3 class="text-lg font-semibold">
                                                <?php echo htmlspecialchars($row['business_name']); ?>
                                            </h3>
                                            <p class="text-gray-600 text-sm">
                                                <?php echo htmlspecialchars($row['business_address']); ?>
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Rating and Action -->
                                    <div class="flex items-center space-x-4">
                                        <span class="text-yellow-500 flex items-center text-sm">
                                            <i class="ri-star-fill"></i>
                                            <span class="text-gray-600 ml-1"><?php echo $avg_rating; ?></span>
                                        </span>
                                        <a href="vendor_details.php?id=<?php echo $row['vendor_id']; ?>"
                                            class="text-primary font-medium hover:underline whitespace-nowrap">
                                            View Details <i class="ri-arrow-right-line ml-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php }
                    } else { ?>
                        <div class="text-center py-12 bg-gray-50 rounded-lg">
                            <div class="mx-auto w-16 h-16 flex items-center justify-center bg-gray-200 rounded-full mb-4">
                                <i class="ri-store-2-line text-2xl text-gray-500"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-700 mb-2">No Vendors Found</h3>
                            <p class="text-gray-500">There are no vendors available at the moment.</p>
                        </div>
                    <?php } ?>
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
</body>

</html>