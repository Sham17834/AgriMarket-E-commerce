<?php
require_once 'db_connect.php';
date_default_timezone_set('Asia/Kuala_Lumpur'); // Set to your timezone

$token = isset($_GET['token']) ? $_GET['token'] : null;
$error = null;
$success = null;

// Validate the token
if ($token) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ? AND token_expiry > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        // Debug: Check why the token is invalid
        $stmt = $pdo->prepare("SELECT reset_token, token_expiry, NOW() as current_time FROM users WHERE reset_token = ?");
        $stmt->execute([$token]);
        $debug = $stmt->fetch();
        if ($debug) {
            $error = "Token expired. Expiry: {$debug['token_expiry']}, Current Time: {$debug['current_time']}. Token: $token";
        } else {
            $error = "Invalid reset token. Token: $token";
        }
    }
} else {
    $error = "No reset token provided.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, token_expiry = NULL WHERE reset_token = ?");
        $stmt->execute([$password_hash, $token]);
        $success = "Your password has been reset successfully. You can now sign in.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | FreshHarvest</title>
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
                <a href="index.php" class="text-3xl font-heading font-bold text-primary flex items-center">
                    <i class="ri-leaf-line mr-2 text-primary-light"></i>
                    FreshHarvest
                </a>
                <nav class="hidden md:flex items-center space-x-6">
                    <a href="products.php" class="text-gray-700 hover:text-primary font-medium">Browse Products</a>
                    <a href="login.php" class="text-primary font-medium hover:underline">Sign In</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="pt-32 pb-16">
        <div class="max-w-md mx-auto px-4">
            <div class="bg-white rounded-xl shadow-md overflow-hidden p-8">
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-heading font-bold text-gray-800 mb-2">Reset Password</h1>
                    <p class="text-gray-600">Enter your new password below</p>
                </div>

                <?php if (isset($error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($success)): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                        <?php echo htmlspecialchars($success); ?>
                        <p class="mt-2"><a href="login.php" class="text-primary font-medium hover:underline">Sign in now</a></p>
                    </div>
                <?php else: ?>
                    <form method="POST" class="space-y-6">
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                            <div class="relative">
                                <input type="password" id="password" name="password" required
                                    class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent"
                                    placeholder="••••••••">
                                <button type="button" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600" onclick="togglePassword('password')">
                                    <i class="ri-eye-line" id="password-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                            <div class="relative">
                                <input type="password" id="confirm_password" name="confirm_password" required
                                    class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent"
                                    placeholder="••••••••">
                                <button type="button" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600" onclick="togglePassword('confirm_password')">
                                </button>
                            </div>
                        </div>

                        <button type="submit"
                            class="w-full bg-primary text-white py-3 px-4 rounded-button font-semibold hover:bg-primary-dark transition-colors flex items-center justify-center">
                            Reset Password
                        </button>
                    </form>
                <?php endif; ?>
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
                eyeIcon.classList.remove('ri-eye-line');
                eyeIcon.classList.add('ri-eye-off-line');
            } else {
                field.type = 'password';
                eyeIcon.classList.remove('ri-eye-off-line');
                eyeIcon.classList.add('ri-eye-line');
            }
        }
    </script>
</body>
</html>