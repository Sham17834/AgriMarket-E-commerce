<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in and determine their role
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : null;

// Get vendor ID from URL
$vendor_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch vendor details
$stmt = $pdo->prepare("SELECT v.*, u.username, u.email 
                       FROM vendors v 
                       JOIN users u ON v.vendor_id = u.user_id 
                       WHERE v.vendor_id = ?");
$stmt->execute([$vendor_id]);
$vendor = $stmt->fetch();

if (!$vendor) {
    // Redirect or display an error if the vendor doesn't exist
    header("Location: vendors.php?error=Vendor not found");
    exit;
}

// Fetch vendor's products
$products_stmt = $pdo->prepare("SELECT p.*, c.name AS category_name 
                               FROM products p 
                               JOIN product_categories c ON p.category_id = c.category_id 
                               WHERE p.vendor_id = ? AND p.is_active = 1");
$products_stmt->execute([$vendor_id]);
$products = $products_stmt->fetchAll();

// Fetch vendor reviews
$reviews_stmt = $pdo->prepare("SELECT vr.*, u.username 
                              FROM vendor_reviews vr 
                              JOIN users u ON vr.customer_id = u.user_id 
                              WHERE vr.vendor_id = ? 
                              ORDER BY vr.review_date DESC");
$reviews_stmt->execute([$vendor_id]);
$reviews = $reviews_stmt->fetchAll();

// Calculate average rating
$avg_rating_stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as review_count 
                                 FROM vendor_reviews 
                                 WHERE vendor_id = ?");
$avg_rating_stmt->execute([$vendor_id]);
$rating_data = $avg_rating_stmt->fetch();
$avg_rating = $rating_data['avg_rating'] ? round($rating_data['avg_rating'], 1) : 0;
$review_count = $rating_data['review_count'];

// Function to check if an external URL is accessible
function is_url_accessible($url) {
    $headers = @get_headers($url);
    if ($headers && strpos($headers[0], '200') !== false) {
        return true;
    }
    return false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($vendor['business_name']); ?> | FreshHarvest</title>
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
                        <span class="absolute -top-2 -right-2 bg-accent text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">3</span>
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

<main class="pt-28 pb-16 flex-1">
    <div class="max-w-7xl mx-auto px-4">
        <div class="bg-white rounded-lg shadow-sm p-8">
            <h1 class="text-3xl font-heading font-bold text-gray-800 mb-8"><?php echo htmlspecialchars($vendor['business_name']); ?></h1>

            <!-- Vendor Details -->
            <div class="mb-8">
                <p class="text-gray-600"><?php echo htmlspecialchars($vendor['business_address']); ?></p>
                <div class="flex items-center mt-2">
                    <span class="text-yellow-400">
                        <?php
                        $full_stars = floor($avg_rating);
                        $half_star = $avg_rating - $full_stars >= 0.5 ? 1 : 0;
                        for ($i = 0; $i < $full_stars; $i++) echo '<i class="ri-star-fill"></i>';
                        if ($half_star) echo '<i class="ri-star-half-fill"></i>';
                        for ($i = 0; $i < 5 - $full_stars - $half_star; $i++) echo '<i class="ri-star-line"></i>';
                        ?>
                    </span>
                    <span class="ml-2 text-gray-600"><?php echo $avg_rating; ?> (<?php echo $review_count; ?> reviews)</span>
                </div>
                <!-- Vendor Description -->
                <div class="mt-4">
                    <h3 class="text-lg font-heading font-semibold text-gray-800 mb-2">About <?php echo htmlspecialchars($vendor['business_name']); ?></h3>
                    <?php if (!empty($vendor['description'])): ?>
                        <p class="text-gray-600"><?php echo htmlspecialchars($vendor['description']); ?></p>
                    <?php else: ?>
                        <p class="text-gray-500 italic">No description available for this vendor.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Vendor Products Section -->
            <div class="mt-8">
                <h2 class="text-2xl font-heading font-bold text-gray-800 mb-4">Products by <?php echo htmlspecialchars($vendor['business_name']); ?></h2>
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
                                    <p class="text-gray-800 font-bold">
                                        <?php if ($product['discounted_price']): ?>
                                            <span class="text-gray-400 line-through mr-2">RM<?php echo number_format($product['price'], 2); ?></span>
                                            <span class="text-primary-dark">RM<?php echo number_format($product['discounted_price'], 2); ?></span>
                                        <?php else: ?>
                                            RM<?php echo number_format($product['price'], 2); ?>
                                        <?php endif; ?>
                                    </p>
                                    <p class="text-gray-600">Stock: <span class="quantity-badge"><?php echo $product['stock_quantity']; ?></span></p>
                                    <div class="flex gap-2">
                                        <a href="product_details.php?id=<?php echo $product['product_id']; ?>" class="block mt-2 bg-primary text-white text-center py-2 px-4 rounded-lg hover:bg-primary-dark font-medium transition-colors flex-1">View Details</a>
                                        <?php if ($product['stock_quantity'] > 0): ?>
                                            <a href="add_to_cart.php?product_id=<?php echo $product['product_id']; ?>" class="block mt-2 bg-accent text-white text-center py-2 px-4 rounded-lg hover:bg-accent-dark font-medium transition-colors flex-1">Add to Cart</a>
                                        <?php else: ?>
                                            <button disabled class="block mt-2 bg-gray-400 text-white text-center py-2 px-4 rounded-lg font-medium flex-1">Out of Stock</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12 bg-gray-50 rounded-lg">
                        <div class="mx-auto w-16 h-16 flex items-center justify-center bg-gray-200 rounded-full mb-4">
                            <i class="ri-box-3-line text-2xl text-gray-500"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-700 mb-2">No Products Available</h3>
                        <p class="text-gray-500">This vendor has no products listed at the moment.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Vendor Reviews Section -->
            <div class="mt-8">
                <h2 class="text-2xl font-heading font-bold text-gray-800 mb-4">Vendor Reviews</h2>
                <?php if ($reviews): ?>
                    <div class="space-y-4">
                        <?php foreach ($reviews as $review): ?>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="flex items-center mb-2">
                                    <span class="text-yellow-400">
                                        <?php
                                        for ($i = 0; $i < $review['rating']; $i++) echo '<i class="ri-star-fill"></i>';
                                        for ($i = 0; $i < 5 - $review['rating']; $i++) echo '<i class="ri-star-line"></i>';
                                        ?>
                                    </span>
                                    <span class="ml-2 font-semibold text-gray-800"><?php echo htmlspecialchars($review['username']); ?></span>
                                </div>
                                <p class="text-gray-600"><?php echo htmlspecialchars($review['comment']); ?></p>
                                <p class="text-sm text-gray-500 mt-2"><?php echo date('F j, Y', strtotime($review['review_date'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12 bg-gray-50 rounded-lg">
                        <div class="mx-auto w-16 h-16 flex items-center justify-center bg-gray-200 rounded-full mb-4">
                            <i class="ri-star-line text-2xl text-gray-500"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-700 mb-2">No Reviews Yet</h3>
                        <p class="text-gray-500">Be the first to review this vendor!</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Vendor Review Submission Form -->
            <?php if ($isLoggedIn && $userRole === 'customer'): ?>
                <?php
                // Check if user has purchased from this vendor
                $order_check = $pdo->prepare("SELECT * FROM order_items oi 
                                             JOIN orders o ON oi.order_id = o.order_id 
                                             JOIN products p ON oi.product_id = p.product_id 
                                             WHERE p.vendor_id = ? AND o.customer_id = ?");
                $order_check->execute([$vendor_id, $_SESSION['user_id']]);
                if ($order_check->rowCount() > 0):
                ?>
                    <div class="mt-8">
                        <h3 class="text-xl font-heading font-bold text-gray-800 mb-4">Leave a Review</h3>
                        <form method="POST" action="submit_vendor_review.php" class="space-y-4">
                            <input type="hidden" name="vendor_id" value="<?php echo $vendor_id; ?>">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Your Rating</label>
                                <select name="rating" required class="w-full px-4 py-2 rounded-lg text-sm border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent">
                                    <option value="">Select rating</option>
                                    <option value="5">5 Stars</option>
                                    <option value="4">4 Stars</option>
                                    <option value="3">3 Stars</option>
                                    <option value="2">2 Stars</option>
                                    <option value="1">1 Star</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Your Review</label>
                                <textarea name="comment" required class="w-full px-4 py-2 rounded-lg text-sm border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent" rows="4" placeholder="Share your experience..."></textarea>
                            </div>
                            <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark font-medium transition-colors">
                                Submit Review
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="mt-8 text-gray-600">
                        <p>You can leave a review after purchasing from this vendor.</p>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="mt-8 text-gray-600">
                    <p>Please <a href="login.php" class="text-primary hover:underline">log in</a> to leave a review.</p>
                </div>
            <?php endif; ?>
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
<script>
document.addEventListener('DOMContentLoaded', function() {
    const reviewForm = document.querySelector('form[action="submit_vendor_review.php"]');
    
    if (reviewForm) {
        reviewForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('submit_vendor_review.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Create a new review element and prepend it to the reviews section
                    const reviewsContainer = document.querySelector('.space-y-4');
                    const newReview = document.createElement('div');
                    newReview.className = 'bg-gray-50 p-4 rounded-lg';
                    newReview.innerHTML = `
                        <div class="flex items-center mb-2">
                            <span class="text-yellow-400">
                                ${'<i class="ri-star-fill"></i>'.repeat(data.rating)}
                                ${'<i class="ri-star-line"></i>'.repeat(5 - data.rating)}
                            </span>
                            <span class="ml-2 font-semibold text-gray-800">${data.username}</span>
                        </div>
                        <p class="text-gray-600">${data.comment}</p>
                        <p class="text-sm text-gray-500 mt-2">${new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
                    `;
                    
                    if (reviewsContainer) {
                        reviewsContainer.prepend(newReview);
                    } else {
                        // If no reviews existed before, create the container
                        const reviewsSection = document.querySelector('.space-y-4') || 
                            document.querySelector('div[class*="vendor-reviews"]');
                        const newContainer = document.createElement('div');
                        newContainer.className = 'space-y-4';
                        newContainer.appendChild(newReview);
                        reviewsSection.appendChild(newContainer);
                        
                        // Remove the "no reviews" message if it exists
                        const noReviewsDiv = document.querySelector('div.text-center.py-12');
                        if (noReviewsDiv) {
                            noReviewsDiv.remove();
                        }
                    }
                    
                    // Reset the form
                    reviewForm.reset();
                    
                    // Show success message
                    alert('Thank you for your review!');
                    
                    // Update the average rating display
                    if (document.querySelector('.text-yellow-400')) {
                        // This is a simplified update - in a real app you'd want to recalculate the average
                        const avgRatingElement = document.querySelector('.text-yellow-400');
                        // You might want to make another AJAX call to get the updated average
                    }
                } else {
                    alert('Error: ' + (data.message || 'Failed to submit review'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while submitting your review.');
            });
        });
    }
});
</script>
</body>
</html>