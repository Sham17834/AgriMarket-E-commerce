<?php
require_once 'db_connect.php';
date_default_timezone_set('Asia/Kuala_Lumpur');
session_start();

if (!isset($_SESSION['reset_phone'])) {
    header("Location: forgot_password.php");
    exit();
}

$phone = $_SESSION['reset_phone'];
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = filter_input(INPUT_POST, 'otp', FILTER_SANITIZE_STRING);
    $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ? AND reset_otp = ? AND otp_expiry > NOW()");
    $stmt->execute([$phone, $otp]);
    $user = $stmt->fetch();
    if ($user) {
        $_SESSION['otp_verified'] = true;
        header("Location: reset_password.php");
        exit();
    } else {
        $error = "Invalid or expired OTP.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP | FreshHarvest</title>
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
            <h1 class="text-2xl font-bold text-gray-800 text-center mb-4">Verify OTP</h1>
            <p class="text-gray-600 text-center mb-6">Enter the OTP sent to your phone via WhatsApp</p>

            <?php if (isset($error)): ?>
                <div class="bg-red-100 text-red-700 p-3 rounded mb-4"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div>
                    <label for="otp" class="block text-sm text-gray-700">OTP Number</label>
                    <input type="text" id="otp" name="otp" required
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-green-500"
                           placeholder="123456">
                </div>
                <button type="submit"
                        class="w-full bg-green-600 text-white py-2 rounded hover:bg-green-700">
                    Verify OTP
                </button>
            </form>
        </div>
    </main>

    <footer class="bg-white py-4 border-t">
        <div class="max-w-7xl mx-auto px-4 text-center text-gray-500 text-sm">
        © 2025 AgriMarket Solutions. All rights reserved.
        </div>
    </footer>
</body>
</html>