<?php
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
    if (loginUser($email, $password, $platform)) {
        $user = getCurrentUser();
        
        // Redirect based on role
        if ($user['role'] === 'admin' || $user['role'] === 'vendor') {
            // Admin and Vendor → Admin Dashboard
            header('Location: ' . BASE_PATH . '/dashboard/pages/dashboard.php');
        } elseif ($user['role'] === 'logistic') {
            // Logistic → Customer Dashboard (or create separate logistic dashboard)
            header('Location: ' . BASE_PATH . '/website/pages/dashboard.php');
        } else {
            // Customer → Customer Dashboard
            header('Location: ' . BASE_PATH . '/website/pages/dashboard.php');
        }
        exit;
    } else {
        $error = $_SESSION['error'] ?? 'Invalid email or password';
        unset($_SESSION['error']);
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
</head>
<body>
<div class="flex min-h-screen">
    <div class="hidden md:flex w-1/2 bg-gray-100 items-center justify-center">
        <img src="<?php echo imagePath('login.jpg'); ?>" alt="Auth Illustration" class="w-full h-[100vh] object-cover" />
    </div>
    <div class="w-full md:w-1/2 flex items-center justify-center px-6">
        <div class="w-full max-w-md">
            <h2 class="text-3xl font-bold mb-6">Welcome!</h2>
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="POST" class="space-y-3">
                <div>
                    <label class="block text-md mb-1">Email</label>
                    <input type="email" name="email" placeholder="Enter email" class="w-full border px-3 py-2 rounded-lg" required />
                </div>
                <div>
                    <label class="block text-md mb-1">Password</label>
                    <input type="password" name="password" placeholder="Enter password" class="w-full border px-3 py-2 rounded-lg" required />
                </div>
                <div class="flex items-center justify-between text-sm">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="remember" name="rememberMe" class="w-4 h-4 accent-green-700 cursor-pointer" />
                        <label for="remember" class="text-md">Remember me</label>
                    </div>
                    <a href="<?php echo BASE_PATH; ?>/auth/forgot.php" class="text-green-600">Forgot password?</a>
                </div>
                <button type="submit" class="w-full bg-green-600 text-white py-2 rounded-lg hover:bg-green-700">Login</button>
                <div class="flex items-center my-3">
                    <hr class="flex-grow border-t border-gray-500" />
                    <span class="px-2 text-sm">OR</span>
                    <hr class="flex-grow border-t border-gray-500" />
                </div>
                <button type="button" class="w-full border py-2 rounded-lg">Login with <span class="text-blue-600 font-serif text-lg">Google</span></button>
                <p class="text-center text-md mt-3">Don't have an account? <a href="<?php echo BASE_PATH; ?>/auth/register.php" class="text-green-600 font-medium">Sign up</a></p>
            </form>
        </div>
    </div>
</div>
</body>
</html>

