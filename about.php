<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FreshHarvest - About Us</title>
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
                        <form action="products.php" method="GET" id="search-form">
                            <input type="text" name="search" id="search-input" placeholder="Search for fresh produce..."
                                class="w-64 pl-10 pr-4 py-2 rounded-full border border-gray-200 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                            <button type="submit" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                                <i class="ri-search-line"></i>
                            </button>
                        </form>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="md:hidden flex items-center">
                            <button id="mobile-search-toggle" class="text-gray-700"><i
                                    class="ri-search-line text-xl"></i></button>
                            <form action="products.php" method="GET" id="mobile-search-form"
                                class="hidden absolute top-20 left-0 right-0 bg-white shadow-md p-4 z-50">
                                <div class="relative">
                                    <input type="text" name="search" id="mobile-search-input"
                                        placeholder="Search for fresh produce..."
                                        class="w-full pl-10 pr-4 py-2 rounded-full border border-gray-200 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                    <button type="submit"
                                        class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                                        <i class="ri-search-line"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
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
            style="background-image: url('https://images.unsplash.com/photo-1500595046743-cd271d694d30?q=80&w=2074&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D')">
            <div class="relative max-w-7xl mx-auto px-4 h-full flex items-center">
                <div class="max-w-2xl text-white">
                    <span class="inline-block bg-accent px-4 py-1 rounded-full text-sm font-semibold mb-4">About
                        FreshHarvest</span>
                    <h1 class="text-5xl font-heading font-bold mb-6 leading-tight">Connecting You to Local Farmers</h1>
                    <p class="text-xl mb-8 opacity-90">Learn about our mission to bring fresh, organic produce directly
                        from local farms to your table.</p>
                    <a href="#mission"
                        class="bg-primary text-white px-8 py-3 rounded-button text-lg font-semibold hover:bg-primary-dark transition-colors flex items-center w-fit">
                        Discover More <i class="ri-arrow-right-line ml-2"></i>
                    </a>
                </div>
            </div>
        </section>

        <!-- Our Mission Section -->
        <section id="mission" class="py-16 bg-white">
            <div class="max-w-7xl mx-auto px-4">
                <div class="text-center mb-12">
                    <span class="inline-block text-primary font-semibold mb-2">OUR MISSION</span>
                    <h2 class="text-3xl font-heading font-bold">Bridging the Gap Between Farmers and Consumers</h2>
                    <p class="text-gray-600 mt-2 max-w-2xl mx-auto">We’re committed to supporting local farmers and
                        providing you with the freshest, most sustainable produce.</p>
                </div>
                <div class="flex flex-wrap items-center justify-center gap-8">
                    <div class="max-w-lg">
                        <p class="text-gray-600 mb-4">FreshHarvest was launched in 2020 by AgriMarket Solutions with a
                            focused mission: to build a digital marketplace that empowers Malaysian farmers and
                            multi-vendors by enabling them to efficiently market their diverse agricultural products.
                            This includes livestock (cattle, poultry, hogs, etc.), crops (corn, soybeans, hay, etc.),
                            edible forestry products (almonds, walnuts, etc.), dairy (milk products), fish farming, and
                            miscellaneous items (honey, etc.). We are dedicated to the role of fresh, organic produce in
                            strengthening Malaysian communities and advancing sustainable farming practices.</p>
                        <p class="text-gray-600 mb-4">Through close partnerships with farmers across Malaysia, including
                            key agricultural regions like the Cameron Highlands and Johor, we ensure that every product
                            meets our stringent standards for quality and freshness. Beyond a marketplace, our platform
                            serves as an agricultural knowledge hub, providing insights into modern farming techniques,
                            comparative market pricing, and streamlined workflows, making it seamless for you to
                            discover, purchase, and enjoy farm-fresh goods while directly supporting Malaysia’s
                            hardworking farmers.</p>
                    </div>
                    <img src="https://images.unsplash.com/photo-1600585154340-be6161a56a0c?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D"
                        alt="Farmers working in a field" class="w-full max-w-md h-64 object-cover rounded-lg">
                </div>
            </div>
        </section>

        <!-- Our Team Section -->
        <section class="py-16 bg-natural-light">
            <div class="max-w-7xl mx-auto px-4">
                <div class="text-center mb-12">
                    <span class="inline-block text-primary font-semibold mb-2">MEET THE TEAM</span>
                    <h2 class="text-3xl font-heading font-bold">The People Behind FreshHarvest</h2>
                    <p class="text-gray-600 mt-2 max-w-2xl mx-auto">Our dedicated team works tirelessly to bring you the
                        best farm-fresh experience.</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="bg-white p-6 rounded-lg shadow-sm text-center">
                        <img src="https://images.unsplash.com/photo-1544005313-94ddf0286df2?q=80&w=200&auto=format&fit=crop"
                            alt="Anna Green" class="w-24 h-24 rounded-full object-cover mx-auto mb-4">
                        <h3 class="text-xl font-heading font-semibold mb-2">Anna Green</h3>
                        <p class="text-primary font-medium mb-2">Founder & CEO</p>
                        <p class="text-gray-600 text-sm">Anna started FreshHarvest to support local farmers and promote
                            sustainable agriculture.</p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow-sm text-center">
                        <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?q=80&w=200&auto=format&fit=crop"
                            alt="Mark Fields" class="w-24 h-24 rounded-full object-cover mx-auto mb-4">
                        <h3 class="text-xl font-heading font-semibold mb-2">Mark Fields</h3>
                        <p class="text-primary font-medium mb-2">Head of Operations</p>
                        <p class="text-gray-600 text-sm">Mark ensures smooth logistics, getting produce from farms to
                            your door quickly.</p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow-sm text-center">
                        <img src="https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?q=80&w=200&auto=format&fit=crop"
                            alt="Sarah Bloom" class="w-24 h-24 rounded-full object-cover mx-auto mb-4">
                        <h3 class="text-xl font-heading font-semibold mb-2">Johson Sam</h3>
                        <p class="text-primary font-medium mb-2">Community Manager</p>
                        <p class="text-gray-600 text-sm">Sarah builds strong relationships with our farmers and
                            customers alike.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Our Values Section -->
        <section class="py-16 bg-white">
            <div class="max-w-7xl mx-auto px-4">
                <div class="text-center mb-12">
                    <span class="inline-block text-primary font-semibold mb-2">OUR VALUES</span>
                    <h2 class="text-3xl font-heading font-bold">What We Stand For</h2>
                    <p class="text-gray-600 mt-2 max-w-2xl mx-auto">Our core values guide everything we do at
                        FreshHarvest.</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="text-center p-6 rounded-lg hover:bg-natural-light transition-colors">
                        <div class="w-20 h-20 mx-auto mb-5 flex items-center justify-center">
                            <i class="ri-leaf-line text-4xl text-primary"></i>
                        </div>
                        <h3 class="text-xl font-heading font-semibold mb-3">Sustainability</h3>
                        <p class="text-gray-600">We promote eco-friendly farming practices to protect our planet for
                            future generations.</p>
                    </div>
                    <div class="text-center p-6 rounded-lg hover:bg-natural-light transition-colors">
                        <div class="w-20 h-20 mx-auto mb-5 flex items-center justify-center">
                            <i class="ri-heart-line text-4xl text-primary"></i>
                        </div>
                        <h3 class="text-xl font-heading font-semibold mb-3">Community</h3>
                        <p class="text-gray-600">We foster strong connections between farmers and consumers to build a
                            thriving community.</p>
                    </div>
                    <div class="text-center p-6 rounded-lg hover:bg-natural-light transition-colors">
                        <div class="w-20 h-20 mx-auto mb-5 flex items-center justify-center">
                            <i class="ri-shield-check-line text-4xl text-primary"></i>
                        </div>
                        <h3 class="text-xl font-heading font-semibold mb-3">Quality</h3>
                        <p class="text-gray-600">We ensure every product meets the highest standards of freshness and
                            quality.</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer Section -->
    <footer class="bg-primary-dark text-white py-12">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <a href="index.php" class="text-2xl font-heading font-bold flex items-center mb-4">
                        <i class="fa-solid fa-leaf mr-2 text-primary-light"></i>
                        FreshHarvest
                    </a>
                    <p class="text-sm opacity-80">Connecting local farmers with your table since 2020.</p>
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
                <p class="text-sm">© 2025 AgriMarket Solutions. All rights reserved.</p>
            </div>
        </div>
    </footer>
    <script src="script.js"></script>
</body>

</html>