<?php
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'db_connect.php';

    // Validate and sanitize inputs
    $first_name = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING);
    $last_name = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = 'customer'; 

    // Validate password match
    if ($password !== $confirm_password) {
        header("Location: register.php?error=Passwords do not match");
        exit;
    }

    // Validate password strength
    if (strlen($password) < 8 || !preg_match('/[0-9]/', $password)) {
        header("Location: register.php?error=Password must be at least 8 characters with at least one number");
        exit;
    }

    // Validate phone number for Malaysia (01X-XXXXXXX)
    if (!preg_match('/^01[0-9]-[0-9]{7}$/', $phone)) {
        header("Location: register.php?error=Invalid phone number format. Use format: 016-1234567");
        exit;
    }

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        header("Location: register.php?error=Email already registered");
        exit;
    }

    // Generate username (firstname_lastname)
    $username = strtolower($first_name . '_' . $last_name);
    $counter = 1;
    while (true) {
        $check_stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $check_stmt->execute([$username]);
        if ($check_stmt->rowCount() === 0)
            break;
        $username = strtolower($first_name . '_' . $last_name) . $counter;
        $counter++;
    }

    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Insert into database
    try {
        $stmt = $pdo->prepare("
            INSERT INTO users (username, password_hash, email, role, phone) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$username, $password_hash, $email, $role, $phone]);

        // Redirect to login page with success message
        header("Location: login.php?success=Registration successful. Please log in.");
        exit;
    } catch (PDOException $e) {
        header("Location: register.php?error=Registration failed. Please try again.");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | AgriMarket</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Open+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css" rel="stylesheet">
    <style>
        .password-strength {
            height: 4px;
            transition: all 0.3s ease;
        }
        .strength-weak {
            background-color: #ef4444;
            width: 25%;
        }
        .strength-medium {
            background-color: #f59e0b;
            width: 50%;
        }
        .strength-strong {
            background-color: #10b981;
            width: 75%;
        }
        .strength-very-strong {
            background-color: #3b82f6;
            width: 100%;
        }
        .phone-input-container {
            display: block;
        }
        .phone-input-container input {
            width: 100%;
            padding: 0.75rem 1rem;
            border-radius: 0.375rem;
        }
    </style>
</head>
<body class="bg-natural-light min-h-screen font-body">
    <header class="fixed top-0 left-0 right-0 bg-white shadow-md z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between h-20">
                <a href="index.php" class="text-3xl font-heading font-bold text-primary flex items-center">
                    <i class="ri-leaf-line mr-2 text-primary-light"></i>
                    AgriMarket
                </a>
                <nav class="hidden md:flex items-center space-x-6">
                    <a href="products.php" class="text-gray-700 hover:text-primary font-medium">Browse Products</a>
                    <a href="login.php" class="text-primary font-medium hover:underline">Already have an account?</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="pt-32 pb-16">
        <div class="max-w-md mx-auto px-4">
            <div class="bg-white rounded-xl shadow-md overflow-hidden p-8">
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-heading font-bold text-gray-800 mb-2">Join AgriMarket</h1>
                    <p class="text-gray-600">Create your account to start shopping</p>
                </div>

                <?php if (isset($_GET['error'])): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                        <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name*</label>
                            <input type="text" id="first_name" name="first_name" required
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent"
                                placeholder="John">
                        </div>
                        <div>
                            <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name*</label>
                            <input type="text" id="last_name" name="last_name" required
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent"
                                placeholder="Doe">
                        </div>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address*</label>
                        <input type="email" id="email" name="email" required
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent"
                            placeholder="your@email.com">
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number*</label>
                        <div class="phone-input-container">
                            <input type="tel" id="phone" name="phone" required
                                class="rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent"
                                placeholder="016-1234567" oninput="formatPhoneNumber(this)">
                        </div>
                        <p id="phone_format_hint" class="mt-1 text-xs text-gray-500">Format: 016-1234567</p>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password*</label>
                        <div class="relative">
                            <input type="password" id="password" name="password" required
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent"
                                placeholder="••••••••" oninput="checkPasswordStrength(this.value)">
                            <button type="button"
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                onclick="togglePassword('password')">
                                <i id="password-eye" class="ri-eye-line"></i>
                            </button>
                        </div>
                        <div class="flex items-center mt-2 space-x-2">
                            <div class="password-strength strength-weak"></div>
                            <span id="password-strength-text" class="text-xs text-gray-500">Weak</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Minimum 8 characters with at least one number</p>
                    </div>

                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password*</label>
                        <div class="relative">
                            <input type="password" id="confirm_password" name="confirm_password" required
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent"
                                placeholder="••••••••">
                            <button type="button"
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                onclick="togglePassword('confirm_password')">
                                <i id="confirm_password-eye" class="ri-eye-line"></i>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input type="checkbox" id="terms" name="terms" required
                                class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                        </div>
                        <label for="terms" class="ml-3 block text-sm text-gray-700">
                            I agree to the <a href="#" class="text-primary hover:underline">Terms of Service</a> and <a
                                href="#" class="text-primary hover:underline">Privacy Policy</a>*
                        </label>
                    </div>

                    <button type="submit"
                        class="w-full bg-primary text-white py-3 px-4 rounded-button font-semibold hover:bg-primary-dark transition-colors flex items-center justify-center">
                        Create Account
                    </button>
                </form>

                <div class="mt-8 text-center text-sm text-gray-600">
                    Already have an account? <a href="login.php" class="text-primary font-medium hover:underline">Sign in</a>
                </div>
            </div>
        </div>
    </main>

    <footer class="bg-white py-8 border-t border-gray-200">
        <div class="max-w-7xl mx-auto px-4 text-center text-gray-500 text-sm">
            © 2025 AgriMarket. All rights reserved.
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

        function checkPasswordStrength(password) {
            const strengthBar = document.querySelector('.password-strength');
            const strengthText = document.getElementById('password-strength-text');
            strengthBar.className = 'password-strength';
            let strength = 0;
            if (password.length >= 8) strength += 1;
            if (password.match(/[a-z]/)) strength += 1;
            if (password.match(/[A-Z]/)) strength += 1;
            if (password.match(/[0-9]/)) strength += 1;
            if (password.match(/[^a-zA-Z0-9]/)) strength += 1;
            if (password.length === 0) {
                strengthBar.classList.add('strength-weak');
                strengthText.textContent = '';
            } else if (strength <= 2) {
                strengthBar.classList.add('strength-weak');
                strengthText.textContent = 'Weak';
            } else if (strength === 3) {
                strengthBar.classList.add('strength-medium');
                strengthTextContent = 'Medium';
            } else if (strength === 4) {
                strengthBar.classList.add('strength-strong');
                strengthText.textContent = 'Strong';
            } else {
                strengthBar.classList.add('strength-very-strong');
                strengthText.textContent = 'Very Strong';
            }
        }

        function formatPhoneNumber(input) {
            let value = input.value.replace(/[^0-9]/g, '');
            if (value.length > 10) {
                value = value.slice(0, 10);
            }
            if (!value.startsWith('01')) {
                value = '01' + value;
            }
            if (value.length > 3) {
                input.value = value.slice(0, 3) + '-' + value.slice(3, 10);
            } else {
                input.value = value;
            }
        }
    </script>
</body>
</html>