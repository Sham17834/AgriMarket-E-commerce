<?php
require_once 'db_connect.php';
date_default_timezone_set('Asia/Kuala_Lumpur'); // Set to your timezone

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_SPECIAL_CHARS);
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    if (!preg_match('/^\+[1-9][0-9]{7,14}$/', $phone)) {
        $error = "Please enter a valid phone number in international format (e.g., +60123456789).";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        $user = $stmt->fetch();

        if ($user) {
            // Check if a valid token already exists
            $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ? AND reset_token IS NOT NULL AND token_expiry > NOW()");
            $stmt->execute([$phone]);
            $existing_token = $stmt->fetch();

            if ($existing_token) {
                $reset_token = $existing_token['reset_token'];
            } else {
                $reset_token = bin2hex(random_bytes(16)); // 32-character token
                // Debug the raw token
                file_put_contents('token_log.txt', "Raw Token: $reset_token\nRaw Length: " . strlen($reset_token) . "\n", FILE_APPEND);
                $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, token_expiry = NOW() + INTERVAL 1 HOUR WHERE phone = ?");
                if (!$stmt->execute([$reset_token, $phone])) {
                    $error = "Failed to store reset token. Please try again.";
                }
            }

            if (!isset($error)) {
                file_put_contents('token_log.txt', "Token: $reset_token\nLength: " . strlen($reset_token) . "\nPhone: $phone\n", FILE_APPEND);
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $domain = $_SERVER['HTTP_HOST'];
                $reset_link = "$protocol://$domain/reset_password.php?token=$reset_token";
                $message = "Hello " . htmlspecialchars($user['username']) . ",\n";
                $message .= "Click to reset your password: $reset_link\n";
                $message .= "Expires in 1 hour. Ignore if not requested.";
                $encoded_message = urlencode($message);
                $whatsapp_link = "https://wa.me/$phone?text=$encoded_message";
                $success = "A password reset link has been prepared. <a href='$whatsapp_link' target='_blank' class='text-primary underline'>Click here to open WhatsApp and send the message</a>.";
            }
        } else {
            $error = "No account found with that phone number.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | FreshHarvest</title>
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
                    <h1 class="text-3xl font-heading font-bold text-gray-800 mb-2">Forgot Password</h1>
                    <p class="text-gray-600">Enter your phone number to receive a password reset link via WhatsApp</p>
                </div>

                <?php if (isset($error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($success)): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number (International Format)</label>
                        <input type="text" id="phone" name="phone" required
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent"
                            placeholder="+60123456789">
                    </div>

                    <button type="submit"
                        class="w-full bg-primary text-white py-3 px-4 rounded-button font-semibold hover:bg-primary-dark transition-colors flex items-center justify-center">
                        Send Reset Link via WhatsApp
                    </button>
                </form>

                <div class="mt-8 text-center text-sm text-gray-600">
                    Remember your password? <a href="login.php" class="text-primary font-medium hover:underline">Sign in</a>
                </div>
            </div>
        </div>
    </main>

    <footer class="bg-white py-8 border-t border-gray-200">
        <div class="max-w-7xl mx-auto px-4 text-center text-gray-500 text-sm">
            © 2025 FreshHarvest. All rights reserved.
        </div>
    </footer>
</body>
</html>