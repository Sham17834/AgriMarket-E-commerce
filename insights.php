<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in and determine their role
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : null;

// Get the selected category filter (if any)
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$valid_categories = ['crops', 'livestock', 'technology', 'market_trends'];
if (!empty($category_filter) && !in_array($category_filter, $valid_categories)) {
    $category_filter = ''; // Reset to no filter if invalid
}

// Pagination settings
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 6; // Articles per page
$offset = ($page - 1) * $per_page;

// Fetch featured articles
$featured_stmt = $pdo->prepare("SELECT kb.*, u.username 
                                FROM knowledge_base kb 
                                JOIN users u ON kb.author_id = u.user_id 
                                WHERE kb.is_featured = 1 
                                ORDER BY kb.published_date DESC 
                                LIMIT 3");
$featured_stmt->execute();
$featured_articles = $featured_stmt->fetchAll();

// Fetch all articles (with optional category filter)
$query = "SELECT kb.*, u.username 
          FROM knowledge_base kb 
          JOIN users u ON kb.author_id = u.user_id";
$params = [];
$param_types = [];

if (!empty($category_filter)) {
    $query .= " WHERE kb.category = ?";
    $params[] = $category_filter;
    $param_types[] = PDO::PARAM_STR;
}
$query .= " ORDER BY kb.published_date DESC LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$param_types[] = PDO::PARAM_INT;
$param_types[] = PDO::PARAM_INT;

$articles_stmt = $pdo->prepare($query);
foreach ($params as $index => $value) {
    $articles_stmt->bindValue($index + 1, $value, $param_types[$index]);
}
$articles_stmt->execute();
$articles = $articles_stmt->fetchAll();

// Get total number of articles for pagination
$total_query = "SELECT COUNT(*) FROM knowledge_base";
$total_params = [];
$total_param_types = [];
if (!empty($category_filter)) {
    $total_query .= " WHERE category = ?";
    $total_params[] = $category_filter;
    $total_param_types[] = PDO::PARAM_STR;
}
$total_stmt = $pdo->prepare($total_query);
foreach ($total_params as $index => $value) {
    $total_stmt->bindValue($index + 1, $value, $total_param_types[$index]);
}
$total_stmt->execute();
$total_articles = $total_stmt->fetchColumn();
$total_pages = ceil($total_articles / $per_page);

// Function to check if an external URL is accessible
function is_url_accessible($url) {
    $headers = @get_headers($url);
    if ($headers && strpos($headers[0], '200') !== false) {
        return true;
    }
    return false;
}

// Function to truncate content for preview
function truncate_content($content, $length = 100) {
    if (strlen($content) <= $length) {
        return $content;
    }
    return substr($content, 0, $length) . '...';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insights | FreshHarvest</title>
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
                    <a href="insights.php" class="text-primary font-medium">Insights</a>
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
        <!-- Page Title -->
        <div class="mb-8">
            <h1 class="text-3xl font-heading font-bold text-gray-800">Insights & Knowledge Base</h1>
            <p class="text-gray-600 mt-2">Explore articles on farming, technology, market trends, and more.</p>
        </div>

        <!-- Featured Articles Section -->
        <?php if ($featured_articles): ?>
            <div class="mb-12">
                <h2 class="text-2xl font-heading font-bold text-gray-800 mb-6">Featured Articles</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <?php foreach ($featured_articles as $article): ?>
                        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                            <?php
                            $image_url = '';
                            if ($article['image_url']) {
                                $images = json_decode($article['image_url'], true);
                                if (json_last_error() === JSON_ERROR_NONE && is_array($images) && !empty($images)) {
                                    $image_url = htmlspecialchars($images[0]);
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
                            <img src="<?php echo $image_url; ?>" alt="<?php echo htmlspecialchars($article['title']); ?>" class="w-full h-48 object-cover">
                            <div class="p-6">
                                <span class="inline-block px-3 py-1 bg-primary/10 text-primary text-sm rounded-full mb-3">
                                    <?php echo htmlspecialchars(ucfirst($article['category'])); ?>
                                </span>
                                <h3 class="text-xl font-heading font-semibold text-gray-800 mb-2">
                                    <a href="article_details.php?id=<?php echo $article['article_id']; ?>" class="hover:text-primary transition-colors">
                                        <?php echo htmlspecialchars($article['title']); ?>
                                    </a>
                                </h3>
                                <p class="text-gray-600 text-sm mb-4"><?php echo htmlspecialchars(truncate_content(strip_tags($article['content']))); ?></p>
                                <div class="flex items-center text-gray-500 text-sm">
                                    <span><i class="ri-user-line mr-1"></i> <?php echo htmlspecialchars($article['username']); ?></span>
                                    <span class="mx-2">•</span>
                                    <span><i class="ri-calendar-line mr-1"></i> <?php echo date('F j, Y', strtotime($article['published_date'])); ?></span>
                                    <span class="mx-2">•</span>
                                    <span><i class="ri-eye-line mr-1"></i> <?php echo $article['view_count']; ?> views</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Category Filter -->
        <div class="mb-8">
            <h3 class="text-lg font-heading font-semibold text-gray-800 mb-4">Filter by Category</h3>
            <div class="flex flex-wrap gap-3">
                <a href="insights.php" class="px-4 py-2 rounded-lg text-sm font-medium <?php echo empty($category_filter) ? 'bg-primary text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> transition-colors">
                    All
                </a>
                <?php foreach ($valid_categories as $category): ?>
                    <a href="insights.php?category=<?php echo $category; ?>" class="px-4 py-2 rounded-lg text-sm font-medium <?php echo $category_filter === $category ? 'bg-primary text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> transition-colors">
                        <?php echo htmlspecialchars(ucfirst($category)); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- All Articles Section -->
        <div>
            <h2 class="text-2xl font-heading font-bold text-gray-800 mb-6"><?php echo empty($category_filter) ? 'All Articles' : htmlspecialchars(ucfirst($category_filter)) . ' Articles'; ?></h2>
            <?php if ($articles): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($articles as $article): ?>
                        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                            <?php
                            $image_url = '';
                            if ($article['image_url']) {
                                $images = json_decode($article['image_url'], true);
                                if (json_last_error() === JSON_ERROR_NONE && is_array($images) && !empty($images)) {
                                    $image_url = htmlspecialchars($images[0]);
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
                            <img src="<?php echo $image_url; ?>" alt="<?php echo htmlspecialchars($article['title']); ?>" class="w-full h-40 object-cover">
                            <div class="p-4">
                                <span class="inline-block px-3 py-1 bg-primary/10 text-primary text-sm rounded-full mb-3">
                                    <?php echo htmlspecialchars(ucfirst($article['category'])); ?>
                                </span>
                                <h3 class="text-lg font-heading font-semibold text-gray-800 mb-2">
                                    <a href="article_details.php?id=<?php echo $article['article_id']; ?>" class="hover:text-primary transition-colors">
                                        <?php echo htmlspecialchars($article['title']); ?>
                                    </a>
                                </h3>
                                <p class="text-gray-600 text-sm mb-4"><?php echo htmlspecialchars(truncate_content(strip_tags($article['content']))); ?></p>
                                <div class="flex items-center text-gray-500 text-sm">
                                    <span><i class="ri-user-line mr-1"></i> <?php echo htmlspecialchars($article['username']); ?></span>
                                    <span class="mx-2">•</span>
                                    <span><i class="ri-calendar-line mr-1"></i> <?php echo date('F j, Y', strtotime($article['published_date'])); ?></span>
                                    <span class="mx-2">•</span>
                                    <span><i class="ri-eye-line mr-1"></i> <?php echo $article['view_count']; ?> views</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="mt-8 flex justify-center space-x-2">
                        <?php if ($page > 1): ?>
                            <a href="insights.php?<?php echo !empty($category_filter) ? "category=$category_filter&" : ''; ?>page=<?php echo $page - 1; ?>" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                                Previous
                            </a>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="insights.php?<?php echo !empty($category_filter) ? "category=$category_filter&" : ''; ?>page=<?php echo $i; ?>" class="px-4 py-2 rounded-lg <?php echo $i === $page ? 'bg-primary text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> transition-colors">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        <?php if ($page < $total_pages): ?>
                            <a href="insights.php?<?php echo !empty($category_filter) ? "category=$category_filter&" : ''; ?>page=<?php echo $page + 1; ?>" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                                Next
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="text-center py-12 bg-gray-50 rounded-lg">
                    <div class="mx-auto w-16 h-16 flex items-center justify-center bg-gray-200 rounded-full mb-4">
                        <i class="ri-article-line text-2xl text-gray-500"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-700 mb-2">No Articles Found</h3>
                    <p class="text-gray-500">There are no articles available<?php echo !empty($category_filter) ? ' in the ' . htmlspecialchars(ucfirst($category_filter)) . ' category' : ''; ?>. Check back later!</p>
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
<script src="script.js"></script>
</body>
</html>