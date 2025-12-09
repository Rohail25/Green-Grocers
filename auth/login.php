<?php
// Enable error display for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Auto-detect platform from email domain or find in database
    $platform = 'trivemart'; // Default
    
    // Auto-detect platform from email domain
    if (strpos($email, '@trivestore.com') !== false) {
        $platform = 'trivestore';
    } elseif (strpos($email, '@trivemart.com') !== false) {
        $platform = 'trivemart';
    } else {
        // If email doesn't match known domains, try to find user in database
        // by checking both platforms (try trivemart first, then trivestore)
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT platform FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => strtolower(trim($email))]);
        $userPlatform = $stmt->fetchColumn();
        if ($userPlatform) {
            $platform = $userPlatform;
        }
    }
    
    // Match Node.js flow: login with detected platform
    try {
        if (loginUser($email, $password, $platform)) {
            $user = getCurrentUser();
            
            // Check if there's a return URL (e.g., from checkout page)
            $returnUrl = $_SESSION['return_url'] ?? null;
            if ($returnUrl) {
                unset($_SESSION['return_url']);
                header('Location: ' . $returnUrl);
                exit;
            }
            
            // Redirect based on role
            if ($user['role'] === 'admin' || $user['role'] === 'vendor') {
                // Admin and Vendor → Admin Dashboard
                header('Location: ' . BASE_PATH . '/dashboard/pages/dashboard.php');
            } elseif ($user['role'] === 'logistic') {
                // Logistic → Customer Dashboard (or create separate logistic dashboard)
                header('Location: ' . BASE_PATH . '/website/pages/dashboard.php');
            } else {
                // Customer → Website Home Page
                header('Location: ' . BASE_PATH . '/');
            }
            exit;
        } else {
            $error = $_SESSION['error'] ?? 'Invalid email or password';
            unset($_SESSION['error']);
        }
    } catch (Exception $e) {
        $error = 'Login error: ' . $e->getMessage();
        error_log("Login error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    } catch (Error $e) {
        $error = 'Login error: ' . $e->getMessage();
        error_log("Login fatal error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Green Grocers</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        input::placeholder {
            color: #9ca3af;
        }
        input[type="checkbox"] {
            accent-color: #f97316;
        }
    </style>
</head>
<body>
<div class="flex min-h-screen">
    <div class="hidden md:flex w-1/2 bg-gray-100 items-center justify-center overflow-hidden">
        <img src="<?php echo imagePath('login.jpg'); ?>" alt="Auth Illustration" class="w-full h-[100vh] object-fit" />
    </div>
    <div class="w-full md:w-1/2 flex items-center justify-center ">
        <div class="w-full max-w-md">
            <h2 class="text-3xl font-bold mb-6 text-gray-800" style="font-family: 'Arial', sans-serif;">Welcome!</h2>
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($error); ?>
                    <?php if (ini_get('display_errors')): ?>
                        <div class="mt-2 text-xs text-red-600">
                            <strong>Debug Info:</strong> Check PHP error logs or enable error display in php.ini
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-2 text-gray-700" style="font-family: 'Arial', sans-serif;">Email</label>
                    <input type="email" name="email" placeholder="Enter your email" class="w-full border border-gray-300 px-4 py-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" style="font-family: 'Arial', sans-serif;" required />
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2 text-gray-700" style="font-family: 'Arial', sans-serif;">Password</label>
                    <input type="password" name="password" placeholder="Enter your password" class="w-full border border-gray-300 px-4 py-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" style="font-family: 'Arial', sans-serif;" required />
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="remember" name="rememberMe" class="w-5 h-5 cursor-pointer" style="accent-color: #f97316;" />
                        <label for="remember" class="text-sm text-gray-700" style="font-family: 'Arial', sans-serif;">Remember me</label>
                    </div>
                    <a href="<?php echo BASE_PATH; ?>/auth/forgot.php" class="text-sm text-orange-500 hover:underline" style="font-family: 'Arial', sans-serif;">Forget Password?</a>
                </div>
                <button type="submit" class="w-full bg-green-600 text-white py-3 rounded-lg hover:bg-green-700 font-medium" style="font-family: 'Arial', sans-serif;">Login</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>

