<?php
session_start(); // Move session_start to the top

require_once 'db_connect.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $password = $_POST['password'];
    $role = trim($_POST['role']);
    
    error_log("Login attempt: email=$email, role=$role"); 
    
    // Fetch user and vendor_id (if applicable)
    $stmt = $pdo->prepare("SELECT u.*, v.vendor_id 
                           FROM users u 
                           LEFT JOIN vendors v ON u.user_id = v.vendor_id 
                           WHERE u.email = ? AND u.role = ?");
    $stmt->execute([$email, $role]);
    $user = $stmt->fetch();
    
    if ($user) {
        error_log("User found: " . print_r($user, true)); 
        if (password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // Set vendor_id in session if the role is vendor
            if ($user['role'] === 'vendor' && $user['vendor_id']) {
                $_SESSION['vendor_id'] = $user['vendor_id'];
            } else if ($user['role'] === 'vendor' && !$user['vendor_id']) {
                // If role is vendor but no vendor_id, redirect with error
                header("Location: login.php?error=Vendor profile not found");
                exit;
            }
            
            error_log("Session set: user_id=" . $_SESSION['user_id'] . ", role=" . $_SESSION['role']);
            
            // Role-based redirection
            if ($_SESSION['role'] === 'admin') {
                header("Location: admin_analytics.php");
                exit;
            } elseif ($_SESSION['role'] === 'staff') {
                header("Location: staff_manage.php");
                exit;
            } elseif ($_SESSION['role'] === 'vendor') {
                header("Location: vendor_dashboard.php");
                exit;
            } else {
                header("Location: index.php");
                exit;
            }
        } else {
            error_log("Password verification failed for email=$email");
            header("Location: login.php?error=Invalid password and Credentials");
            exit;
        }
    } else {
        error_log("No user found for email=$email, role=$role");
        header("Location: login.php?error=Invalid email or role");
        exit;
    }
}

// Get available roles for dropdown
$roles_stmt = $pdo->query("SELECT DISTINCT role FROM users");
$roles = $roles_stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | FreshHarvest</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Open+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body class="bg-natural-light min-h-screen font-body">
    <header class="fixed top-0 left-0 right-0 bg-white shadow-md z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between h-20">
                <a href="index.php" class="text-3xl font-heading font-bold text-primary flex items-center">
                    <i class="fa-solid fa-leaf mr-2 text-primary-light"></i>
                    AgriMarket
                </a>
                <nav class="hidden md:flex items-center space-x-6">
                    <a href="products.php" class="text-gray-700 hover:text-primary font-medium">Browse Products</a>
                    <a href="register.php" class="text-primary font-medium hover:underline">Create Account</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="pt-32 pb-16">
        <div class="max-w-md mx-auto px-4">
            <div class="bg-white rounded-xl shadow-md overflow-hidden p-8">
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-heading font-bold text-gray-800 mb-2">Welcome Back</h1>
                    <p class="text-gray-600">Sign in to your account</p>
                </div>

                <?php if (isset($_GET['error'])): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                        <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                        <input type="email" id="email" name="email" required
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent"
                            placeholder="your@email.com">
                    </div>
                    
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <div class="relative">
                            <input type="password" id="password" name="password" required
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent"
                                placeholder="••••••••">
                            <button type="button" id="password-eye" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600" onclick="togglePassword('password')">
                            </button>
                        </div>
                    </div>
                    
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Login as</label>
                        <select id="role" name="role" required
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="">Select your role</option>
                            <?php foreach ($roles as $role_option): ?>
                                <option value="<?php echo htmlspecialchars($role_option); ?>">
                                    <?php echo ucfirst(htmlspecialchars($role_option)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="flex items-center justify-end">
                        <a href="forgot_password.php" class="text-sm text-primary hover:underline">Forgot password?</a>
                    </div>
                    
                    <button type="submit"
                        class="w-full bg-primary text-white py-3 px-4 rounded-button font-semibold hover:bg-primary-dark transition-colors flex items-center justify-center">
                        Sign In
                    </button>
                </form>

                <div class="mt-8 text-center text-sm text-gray-600">
                    Don't have an account? <a href="register.php" class="text-primary font-medium hover:underline">Sign up</a>
                </div>
            </div>
        </div>
    </main>

    <footer class="bg-white py-8 border-t border-gray-200">
        <div class="max-w-7xl mx-auto px-4 text-center text-gray-500 text-sm">
            © 2025 FreshHarvest. All rights reserved.
        </div>
    </footer>

    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const eyeIcon = document.getElementById(fieldId + '-eye');
            
            if (field.type === 'password') {
                field.type = 'text';
                eyeIcon.innerHTML = '<i class="ri-eye-off-line"></i>';
            } else {
                field.type = 'password';
                eyeIcon.innerHTML = '<i class="ri-eye-line"></i>';
            }
        }
    </script>
</body>
</html>