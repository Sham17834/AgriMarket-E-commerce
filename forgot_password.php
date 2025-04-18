<?php
require_once 'db_connect.php';
date_default_timezone_set('Asia/Kuala_Lumpur');
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_SPECIAL_CHARS);
    $phone = preg_replace('/[^0-9-]/', '', $phone); 
    if (!preg_match('/^01[0-9]-[0-9]{7,8}$/', $phone)) {
        $error = "Please enter a valid Malaysian phone number (e.g., 016-1234567).";
    } else {
        $phone_clean = str_replace('-', '', $phone); 
        $phone_international = '+6' . $phone_clean; 

        $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ?");
        $stmt->execute([$phone]); 
        $user = $stmt->fetch();
        if ($user) {
            $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $stmt = $pdo->prepare("UPDATE users SET reset_otp = ?, otp_expiry = NOW() + INTERVAL 10 MINUTE WHERE phone = ?");
            if ($stmt->execute([$otp, $phone])) {
                $message = "Hello " . htmlspecialchars($user['username']) . ",\n";
                $message .= "Your OTP for password reset is: $otp\n";
                $message .= "Valid for 10 minutes. Ignore if not requested.";
                $encoded_message = urlencode($message);
                $whatsapp_link = "https://wa.me/$phone_international?text=$encoded_message";
                $_SESSION['reset_phone'] = $phone;
                $success = "OTP sent via WhatsApp. <a href='$whatsapp_link' target='_blank' class='text-green-600 underline'>Click here to send it</a>.<br>Then verify below.";
            } else {
                $error = "Failed to generate OTP.";
            }
        } else {
            $error = "Phone number not found.";
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
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Open+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
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

    <main class="pt-24 pb-12 flex-grow">
        <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-8">
            <h1 class="text-2xl font-bold text-gray-800 text-center mb-4">Forgot Password</h1>
            <p class="text-gray-600 text-center mb-6">Enter your phone number to receive an OTP via WhatsApp</p>

            <?php if (isset($error)): ?>
                <div class="bg-red-100 text-red-700 p-3 rounded mb-4"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (isset($success)): ?>
                <div class="bg-green-100 text-green-700 p-3 rounded mb-4 text-center">
                    <?php echo $success; ?>
                    <a href="verify_otp.php" class="block mt-4 w-full bg-green-600 text-white py-2 rounded hover:bg-green-700 text-center">
                        Proceed to Verify OTP
                    </a>
                </div>
            <?php else: ?>
                <form method="POST" class="space-y-4">
                    <div>
                        <label for="phone" class="block text-sm text-gray-700">Phone Number (Malaysian Format)</label>
                        <input type="text" id="phone" name="phone" required
                               class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-green-500"
                               placeholder="016-1234567"
                               oninput="formatPhoneNumber(this)">
                    </div>
                    <button type="submit"
                            class="w-full bg-green-600 text-white py-2 rounded hover:bg-green-700">
                        Generate OTP for WhatsApp
                    </button>
                </form>
            <?php endif; ?>

            <p class="text-center text-sm mt-4">
                Remember your password? <a href="login.php" class="text-green-600 hover:underline">Sign in</a>
            </p>
        </div>
    </main>

    <footer class="bg-white py-4 border-t">
        <div class="max-w-7xl mx-auto px-4 text-center text-gray-500 text-sm">
            © 2025 FreshHarvest. All rights reserved.
        </div>
    </footer>

    <script>
        function formatPhoneNumber(input) {
            // Remove all non-digit characters except hyphens
            let value = input.value.replace(/[^0-9-]/g, '');
            
            value = value.replace(/-/g, '');
            
            if (value.length > 3) {
                value = value.slice(0, 3) + '-' + value.slice(3);
            }
            
            value = value.slice(0, 12);
            input.value = value;
        }
    </script>
</body>
</html>