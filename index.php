<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FreshHarvest - Farm to Table Marketplace</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Open+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css" rel="stylesheet">
</head>

<body class="bg-natural-light min-h-screen font-body">
    <?php include 'db_connect.php'; ?>
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
        <!-- Hero Section -->
        <section class="hero-section"
            style="background-image: url('https://public.readdy.ai/ai/img_res/376e8ca2ffbd11f6034acd0b36ed888c.jpg')">
            <div class="relative max-w-7xl mx-auto px-4 h-full flex items-center">
                <div class="max-w-2xl text-white">
                    <span class="inline-block bg-accent px-4 py-1 rounded-full text-sm font-semibold mb-4">Fresh &
                        Organic</span>
                    <h1 class="text-5xl font-heading font-bold mb-6 leading-tight">Farm Fresh Products Delivered to Your
                        Door</h1>
                    <p class="text-xl mb-8 opacity-90">Experience the taste of nature with our carefully selected
                        farm-fresh products. Direct from local farmers to your table.</p>
                    <div class="flex flex-wrap gap-4">
                        <a href="products.php"
                            class="bg-primary text-white px-8 py-3 rounded-button text-lg font-semibold hover:bg-primary-dark transition-colors flex items-center">
                            Shop Now <i class="ri-arrow-right-line ml-2"></i>
                        </a>
                        <a href="about.php"
                            class="bg-white text-primary px-8 py-3 rounded-button text-lg font-semibold hover:bg-gray-100 transition-colors">Learn
                            More</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Shop by Category -->
        <section class="py-16 bg-white">
            <div class="max-w-7xl mx-auto px-4">
                <div class="text-center mb-12">
                    <span class="inline-block text-primary font-semibold mb-2">DISCOVER</span>
                    <h2 class="text-3xl font-heading font-bold">Shop by Category</h2>
                </div>
                <div class="category-container">
                    <?php
                    $stmt = $pdo->query("SELECT * FROM product_categories WHERE parent_category_id IS NULL LIMIT 4");
                    if (!$stmt) {
                        die("Query failed: " . $pdo->errorInfo()[2]);
                    }

                    $categoryImages = [
                        1 => 'https://plus.unsplash.com/premium_photo-1661902069934-e7db684d77b3?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D', // Livestock
                        2 => 'https://images.unsplash.com/photo-1554871037-99938249e220?q=80&w=1935&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D', // Crops
                        3 => 'https://images.unsplash.com/photo-1506953823976-52e1fdc0149a?q=80&w=1935&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D', // Forestry
                        8 => 'https://images.unsplash.com/photo-1685853996091-f9b4fe630374?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D', // Fish Farming
                    ];

                    while ($row = $stmt->fetch()) {
                        $categoryId = $row['category_id'];
                        $imageSrc = isset($categoryImages[$categoryId])
                            ? $categoryImages[$categoryId]
                            : 'https://public.readdy.ai/api/placeholder/400/320';
                        ?>
                        <div class="category-block">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-2xl font-heading font-semibold">
                                    <?php echo htmlspecialchars($row['name']); ?>
                                </h3>
                                <a href="products.php?category=<?php echo $categoryId; ?>"
                                    class="text-primary font-medium hover:underline flex items-center">
                                    View All <i class="ri-arrow-right-line ml-1"></i>
                                </a>
                            </div>
                            <div class="relative mb-4">
                                <img src="<?php echo $imageSrc; ?>" alt="<?php echo htmlspecialchars($row['name']); ?>"
                                    class="w-full h-48 object-cover rounded-lg">
                            </div>
                            <p class="category-description text-gray-600 mb-4">
                                <?php echo htmlspecialchars($row['description']); ?>
                            </p>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </section>

        <!-- Agricultural Insights Section -->
        <section class="py-16 bg-white">
            <div class="max-w-7xl mx-auto px-4">
                <div class="text-center mb-12">
                    <span class="inline-block text-primary font-semibold mb-2">LEARN & GROW</span>
                    <h2 class="text-3xl font-heading font-bold">Agricultural Insights</h2>
                    <p class="text-gray-600 mt-2 max-w-2xl mx-auto">Stay informed with the latest farming trends and
                        tips</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php
                    try {
                        $stmt = $pdo->prepare("
                    SELECT k.*, u.username AS author_name 
                    FROM knowledge_base k
                    JOIN users u ON k.author_id = u.user_id
                    WHERE k.is_featured = 1
                    ORDER BY k.published_date DESC 
                    LIMIT 3
                ");
                        $stmt->execute();

                        if ($stmt->rowCount() > 0) {
                            while ($article = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                // Process image URLs
                                $imageUrls = json_decode($article['image_url'] ?? '[]', true);
                                $mainImage = !empty($imageUrls) ? $imageUrls[0] : null;

                                // Format date
                                $publishedDate = date('F j, Y', strtotime($article['published_date']));
                                ?>
                                <div
                                    class="bg-natural-light rounded-lg overflow-hidden hover:shadow-md transition-all h-full flex flex-col">
                                    <div class="relative flex-shrink-0">
                                        <?php if ($mainImage): ?>
                                            <img src="<?php echo htmlspecialchars($mainImage); ?>"
                                                alt="<?php echo htmlspecialchars($article['title']); ?>"
                                                class="w-full h-48 object-cover" loading="lazy">
                                        <?php else: ?>
                                            <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                                                <span class="text-gray-500">No image available</span>
                                            </div>
                                        <?php endif; ?>
                                        <div
                                            class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black to-transparent p-4">
                                            <span class="text-white text-sm font-medium">
                                                <?php echo ucfirst($article['category']); ?>
                                                <?php if ($article['is_featured']): ?>
                                                    <span
                                                        class="ml-2 bg-yellow-500 text-white px-2 py-1 rounded-full text-xs">Featured</span>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="p-6 flex-grow flex flex-col">
                                        <h3 class="text-xl font-heading font-semibold mb-3">
                                            <?php echo htmlspecialchars($article['title']); ?>
                                        </h3>

                                        <p class="text-gray-600 mb-4 flex-grow">
                                            <?php echo substr(strip_tags($article['content']), 0, 100); ?>...
                                        </p>

                                        <div class="mt-auto">
                                            <div class="flex items-center justify-between text-sm text-gray-500 mb-2">
                                                <span>
                                                    <i class="ri-user-line mr-1"></i>
                                                    <?php echo htmlspecialchars($article['author_name']); ?>
                                                </span>
                                                <span>
                                                    <i class="ri-eye-line mr-1"></i>
                                                    <?php echo number_format($article['view_count']); ?> views
                                                </span>
                                            </div>

                                            <div class="flex items-center justify-between border-t pt-3">
                                                <span class="text-sm text-gray-500">
                                                    <?php echo $publishedDate; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            }
                        } else {
                            $fallback_stmt = $pdo->query("
                        SELECT * FROM knowledge_base 
                        ORDER BY published_date DESC 
                        LIMIT 3
                    ");

                            if ($fallback_stmt->rowCount() > 0) {
                                while ($article = $fallback_stmt->fetch(PDO::FETCH_ASSOC)) {
                                }
                            } else {
                                echo '<div class="col-span-3 text-center py-8"><p class="text-gray-500">No articles available at the moment. Please check back later.</p></div>';
                            }
                        }
                    } catch (PDOException $e) {
                        error_log("Database error: " . $e->getMessage());
                        echo '<div class="col-span-3 text-center py-8"><p class="text-red-500">Error loading articles. Please try again later.</p></div>';
                    }
                    ?>
                </div>

                <div class="text-center mt-12">
                    <a href="insights.php"
                        class="inline-block border-2 border-primary text-primary px-8 py-3 rounded-button hover:bg-primary hover:text-white transition-colors">
                        View All Articles <i class="ri-arrow-right-line ml-2"></i>
                    </a>
                </div>
            </div>
        </section>

        <!-- Our Top Vendors -->
        <section class="py-16 bg-white">
            <div class="max-w-7xl mx-auto px-4">
                <div class="text-center mb-12">
                    <span class="inline-block text-primary font-semibold mb-2">TRUSTED SOURCES</span>
                    <h2 class="text-3xl font-heading font-bold">Our Top Vendors</h2>
                    <p class="text-gray-600 mt-2 max-w-2xl mx-auto">Meet the farmers and producers who bring you the
                        freshest products</p>
                </div>

                <!-- Horizontal Vendor Cards with Avatars -->
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
                               WHERE u.role = 'vendor' 
                               LIMIT 3");
                    $vendorIndex = 0;

                    while ($row = $stmt->fetch()) {
                        $vendorAvatar = $vendorAvatars[$vendorIndex % count($vendorAvatars)];
                        $vendorIndex++;
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
                                        <span class="text-gray-600 ml-1">4.7</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>

                <div class="text-center mt-8">
                    <a href="vendors.php"
                        class="inline-block border border-primary text-primary px-6 py-2 rounded-lg hover:bg-primary hover:text-white transition-colors font-medium">
                        View All Vendors
                    </a>
                </div>
            </div>
        </section>

        <!-- Today's Best Deals Section -->
        <section class="py-16 bg-primary-light">
            <div class="max-w-7xl mx-auto px-4">
                <div class="text-center mb-12">
                    <span class="inline-block text-primary-dark font-semibold mb-2">LIMITED TIME</span>
                    <h2 class="text-3xl font-heading font-bold">Today's Best Deals</h2>
                    <p class="text-gray-700 mt-2 max-w-2xl mx-auto">Special offers for our valued customers</p>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <?php
                    try {
                        // Query for deals using PDO
                        $stmt = $pdo->query("SELECT p.*, pc.name AS category_name 
                            FROM products p 
                            JOIN product_categories pc ON p.category_id = pc.category_id 
                            WHERE (p.discounted_price IS NOT NULL OR p.stock_quantity > 20)
                            AND p.is_active = 1
                            LIMIT 3");

                        if ($stmt->rowCount() > 0) {
                            while ($row = $stmt->fetch()) {
                                $discount = $row['discounted_price'] ? round((($row['price'] - $row['discounted_price']) / $row['price']) * 100) : null;

                                // Handle image URLs
                                $mainImage = 'https://via.placeholder.com/400x300?text=No+Image';
                                if (!empty($row['image_url'])) {
                                    $imageUrls = json_decode($row['image_url'], true);
                                    if (json_last_error() === JSON_ERROR_NONE && is_array($imageUrls) && !empty($imageUrls)) {
                                        $mainImage = $imageUrls[0];
                                    }
                                }
                                ?>
                                <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                                    <div class="relative">
                                        <?php if ($discount) { ?>
                                            <div
                                                class="absolute top-2 left-2 bg-red-500 text-white text-xs font-bold px-3 py-1 rounded-full shadow-lg">
                                                -<?php echo $discount; ?>% OFF
                                            </div>
                                        <?php } ?>
                                        <img src="<?php echo htmlspecialchars($mainImage); ?>"
                                            alt="<?php echo htmlspecialchars($row['name']); ?>" class="w-full h-48 object-cover">
                                    </div>
                                    <div class="p-6">
                                        <h3 class="text-xl font-heading font-semibold mb-2">
                                            <?php echo htmlspecialchars($row['name']); ?>
                                        </h3>
                                        <div class="countdown-timer mb-3">
                                            <div class="countdown-item" id="countdown-<?php echo $row['product_id']; ?>">Ends in:
                                                Loading...</div>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <?php if ($row['discounted_price']) { ?>
                                                    <span
                                                        class="text-gray-400 line-through mr-2">RM<?php echo number_format($row['price'], 2); ?></span>
                                                    <span
                                                        class="text-primary-dark font-bold text-xl">RM<?php echo number_format($row['discounted_price'], 2); ?></span>
                                                <?php } else { ?>
                                                    <span
                                                        class="text-primary-dark font-bold text-xl">RM<?php echo number_format($row['price'], 2); ?></span>
                                                <?php } ?>
                                            </div>
                                            <a href="products.php?id=<?php echo $row['product_id']; ?>"
                                                class="text-primary-dark font-medium hover:underline">
                                                Shop Now <i class="ri-arrow-right-line"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            }
                        } else {
                            echo '<div class="col-span-3 text-center py-8"><p class="text-gray-600">No special deals available at the moment. Check back later!</p></div>';
                        }
                    } catch (PDOException $e) {
                        echo '<div class="col-span-3 text-center py-8"><p class="text-red-500">Error loading deals. Please try again later.</p></div>';
                    }
                    ?>
                </div>
            </div>
        </section>


        <section class="py-16 bg-white">
            <div class="max-w-7xl mx-auto px-4">
                <div class="text-center mb-12">
                    <span class="inline-block text-primary font-semibold mb-2">WHY CHOOSE US</span>
                    <h2 class="text-3xl font-heading font-bold">The FreshHarvest Difference</h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="feature-card text-center p-6 rounded-lg hover:bg-natural-light transition-colors">
                        <div class="w-20 h-20 mx-auto mb-5 feature-icon flex items-center justify-center">
                            <i class="ri-leaf-line text-4xl text-primary"></i>
                        </div>
                        <h3 class="text-xl font-heading font-semibold mb-3">100% Organic</h3>
                        <p class="text-gray-600">All our products are certified organic and sustainably sourced from
                            local farmers who care for the environment.</p>
                    </div>
                    <div class="feature-card text-center p-6 rounded-lg hover:bg-natural-light transition-colors">
                        <div class="w-20 h-20 mx-auto mb-5 feature-icon flex items-center justify-center">
                            <i class="ri-truck-line text-4xl text-primary"></i>
                        </div>
                        <h3 class="text-xl font-heading font-semibold mb-3">Fast Delivery</h3>
                        <p class="text-gray-600">Same day delivery for orders placed before 2 PM. Fresh from farm to
                            your doorstep within hours, not days.</p>
                    </div>
                    <div class="feature-card text-center p-6 rounded-lg hover:bg-natural-light transition-colors">
                        <div class="w-20 h-20 mx-auto mb-5 feature-icon flex items-center justify-center">
                            <i class="ri-shield-check-line text-4xl text-primary"></i>
                        </div>
                        <h3 class="text-xl font-heading font-semibold mb-3">Quality Guaranteed</h3>
                        <p class="text-gray-600">We guarantee the quality of all our products. Not satisfied? Get a full
                            refund with our no-questions-asked policy.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Customer Review -->
        <section class="py-16 bg-natural-light">
            <div class="max-w-7xl mx-auto px-4">
                <div class="text-center mb-12">
                    <span class="inline-block text-primary font-semibold mb-2">REVIEWS</span>
                    <h2 class="text-3xl font-heading font-bold">What Our Customers Say</h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <?php
                    $stmt = $pdo->query("
                SELECT pr.*, u.username 
                FROM product_reviews pr
                JOIN users u ON pr.customer_id = u.user_id
                WHERE pr.is_approved = 1
                ORDER BY pr.review_date DESC
                LIMIT 3
            ");

                    if ($stmt->rowCount() > 0) {
                        while ($review = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            // Generate star rating
                            $stars = str_repeat('<i class="ri-star-fill"></i>', $review['rating']);
                            if ($review['rating'] < 5) {
                                $stars .= str_repeat('<i class="ri-star-line"></i>', 5 - $review['rating']);
                            }

                            // Extract first name from username
                            $usernameParts = explode('_', $review['username']);
                            $firstName = ucfirst($usernameParts[0]);
                            ?>
                            <div class="bg-white p-6 rounded-lg shadow-sm hover:shadow-md transition-all">
                                <div class="flex items-center mb-4">
                                    <i class="ri-double-quotes-l text-2xl text-primary mr-2"></i>
                                    <div class="flex text-yellow-400">
                                        <?php echo $stars; ?>
                                    </div>
                                </div>
                                <p class="text-gray-600 mb-4">"<?php echo htmlspecialchars($review['comment']); ?>"</p>
                                <div class="flex items-center">
                                    <div class="w-12 h-12 bg-gray-200 rounded-full mr-3 flex items-center justify-center">
                                        <span
                                            class="text-gray-500 text-lg"><?php echo strtoupper(substr($firstName, 0, 1)); ?></span>
                                    </div>
                                    <div>
                                        <p class="font-semibold"><?php echo $firstName; ?></p>
                                        <p class="text-sm text-gray-500">
                                            <?php echo date('F Y', strtotime($review['review_date'])); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php }
                    } else { ?>
                        <!-- Fallback if no reviews exist -->
                        <div class="bg-white p-6 rounded-lg shadow-sm col-span-3 text-center py-8">
                            <p class="text-gray-500">No customer testimonials available yet.</p>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </section>

        <!-- Footer Section -->
        <footer class="bg-primary-dark text-white py-12">
            <div class="max-w-7xl mx-auto px-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <div>
                        <a href="index.php" class="text-2xl font-heading font-bold flex items-center mb-4">
                            <i class="ri-leaf-line mr-2 text-primary-light"></i>
                            FreshHarvest
                        </a>
                        <p class="text-sm opacity-80">Connecting local farmers with your table since 2020.</p>
                    </div>
                    <div>
                        <h5 class="font-heading font-bold text-lg mb-4">Quick Links</h5>
                        <ul class="space-y-2">
                            <li><a href="products.php" class="hover:text-primary-light transition-colors">Products</a>
                            </li>
                            <li><a href="categories.php"
                                    class="hover:text-primary-light transition-colors">Categories</a>
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
                            <a href="https://web.facebook.com/INTI.edu/?locale=ms_MY&_rdc=1&_rdr#"
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