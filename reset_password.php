<?php
require_once 'db_connect.php';
date_default_timezone_set('Asia/Kuala_Lumpur');
session_start();

if (!isset($_SESSION['reset_phone']) || !isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
    header("Location: forgot_password.php");
    exit();
}

$phone = $_SESSION['reset_phone'];
$error = $success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, reset_otp = NULL, otp_expiry = NULL WHERE phone = ?");
        if ($stmt->execute([$password_hash, $phone])) {
            $success = "Password reset successfully.";
            session_destroy();
        } else {
            $error = "Failed to reset password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | FreshHarvest</title>
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Open+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen">
    <header class="fixed top-0 left-0 right-0 bg-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <a href="index.php" class="text-2xl font-bold text-green-600 flex items-center">
                <i class="ri-leaf-line mr-2"></i>AgriMarket
            </a>
            <nav class="space-x-4">
                <a href="products.php" class="text-gray-600 hover:text-green-600">Products</a>
                <a href="login.php" class="text-green-600 hover:underline">Sign In</a>
            </nav>
        </div>
    </header>

    <main class="pt-24 pb-12">
        <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-8">
            <h1 class="text-2xl font-bold text-gray-800 text-center mb-4">Reset Password</h1>
            <p class="text-gray-600 text-center mb-6">Enter your new password</p>

            <?php if (isset($error)): ?>
                <div class="bg-red-100 text-red-700 p-3 rounded mb-4"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if (isset($success)): ?>
                <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
                    <?php echo htmlspecialchars($success); ?>
                    <p class="mt-2"><a href="login.php" class="text-green-600 hover:underline">Sign in</a></p>
                </div>
            <?php else: ?>
                <form method="POST" class="space-y-4">
                    <div>
                        <label for="password" class="block text-sm text-gray-700">New Password</label>
                        <input type="password" id="password" name="password" required
                               class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-green-500"
                               onclick="togglePassword('password')" placeholder="********">
                    </div>
                    <div>
                        <label for="confirm_password" class="block text-sm text-gray-700">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required
                               class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-green-500"
                               onclick="togglePassword('confirm_password')" placeholder="********">
                    </div>
                    <button type="submit"
                            class="w-full bg-green-600 text-white py-2 rounded hover:bg-green-700" >
                        Reset Password
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </main>

    <footer class="bg-white py-4 border-t">
        <div class="max-w-7xl mx-auto px-4 text-center text-gray-500 text-sm">
        © 2025 AgriMarket Solutions. All rights reserved.
        </div>
    </footer>
</body>
</html>