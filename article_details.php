<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in and determine their role
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : null;

// Get the article ID from the URL
$article_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch the article details
$stmt = $pdo->prepare("SELECT kb.*, u.username 
                       FROM knowledge_base kb 
                       JOIN users u ON kb.author_id = u.user_id 
                       WHERE kb.article_id = ?");
$stmt->execute([$article_id]);
$article = $stmt->fetch();

if (!$article) {
    header("Location: insights.php?error=Article not found");
    exit;
}

// Increment view count
$pdo->prepare("UPDATE knowledge_base SET view_count = view_count + 1 WHERE article_id = ?")
    ->execute([$article_id]);

// Function to check if an external URL is accessible
function is_url_accessible($url) {
    $headers = @get_headers($url);
    if ($headers && strpos($headers[0], '200') !== false) {
        return true;
    }
    return false;
}

// Split content into paragraphs (assuming content uses double newlines to separate paragraphs)
$paragraphs = explode("\n\n", $article['content']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($article['title']); ?> | FreshHarvest</title>
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
    <div class="max-w-4xl mx-auto px-4">
        <!-- Breadcrumb -->
        <nav class="mb-6 text-sm text-gray-500">
            <a href="index.php" class="hover:text-primary">Home</a>
            <span class="mx-2">/</span>
            <a href="insights.php" class="hover:text-primary">Insights</a>
            <span class="mx-2">/</span>
            <span class="text-gray-700"><?php echo htmlspecialchars($article['title']); ?></span>
        </nav>

        <!-- Article Header -->
        <div class="mb-8">
            <span class="inline-block px-3 py-1 bg-primary/10 text-primary text-sm rounded-full mb-3">
                <?php echo htmlspecialchars(ucfirst($article['category'])); ?>
            </span>
            <h1 class="text-3xl font-heading font-bold text-gray-800 mb-4"><?php echo htmlspecialchars($article['title']); ?></h1>
            <div class="flex items-center text-gray-500 text-sm">
                <span><i class="ri-user-line mr-1"></i> <?php echo htmlspecialchars($article['username']); ?></span>
                <span class="mx-2">•</span>
                <span><i class="ri-calendar-line mr-1"></i> Published on <?php echo date('F j, Y', strtotime($article['published_date'])); ?></span>
                <span class="mx-2">•</span>
                <span><i class="ri-eye-line mr-1"></i> <?php echo $article['view_count']; ?> views</span>
            </div>
            <?php if ($article['last_updated'] && $article['last_updated'] !== $article['published_date']): ?>
                <div class="text-gray-500 text-sm mt-1">
                    <span><i class="ri-time-line mr-1"></i> Last updated on <?php echo date('F j, Y', strtotime($article['last_updated'])); ?></span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Article Images -->
        <?php if ($article['image_url']): ?>
            <?php
            $images = json_decode($article['image_url'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($images) && !empty($images)):
            ?>
                <div class="mb-8">
                    <div class="grid grid-cols-1 gap-4">
                        <?php foreach ($images as $img_url): ?>
                            <?php
                            $display_url = htmlspecialchars($img_url);
                            if (!is_url_accessible($display_url)) {
                                $display_url = 'assets/images/placeholder.jpg';
                            }
                            ?>
                            <img src="<?php echo $display_url; ?>" alt="Image for <?php echo htmlspecialchars($article['title']); ?>" class="w-full h-96 object-cover rounded-lg">
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Article Content -->
        <div class="text-gray-700 text-lg leading-snug">
            <?php foreach ($paragraphs as $paragraph): ?>
                <p class="mb-4"><?php echo htmlspecialchars(trim($paragraph)); ?></p>
            <?php endforeach; ?>
        </div>

        <!-- Back to Insights Link -->
        <div class="mt-12">
            <a href="insights.php" class="inline-flex items-center text-primary hover:underline">
                <i class="ri-arrow-left-line mr-2"></i> Back to Insights
            </a>
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