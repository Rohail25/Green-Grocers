<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (loginUser($email, $password)) {
        $user = getCurrentUser();
        if ($user['role'] === 'admin') {
            header('Location: ' . BASE_PATH . '/dashboard/pages/dashboard.php');
            exit;
        } else {
            $error = 'Access denied. Admin access required.';
        }
    } else {
        $error = 'Invalid email or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Green Grocers</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
<div class="flex min-h-screen">
    <div class="hidden md:flex w-1/2 bg-gray-100 items-center justify-center">
        <img src="<?php echo imagePath('login.jpg'); ?>" alt="Auth Illustration" class="w-full h-[100vh] object-cover" />
    </div>
    <div class="w-full md:w-1/2 flex items-center justify-center px-6">
        <div class="w-full max-w-md">
            <h2 class="text-3xl font-bold mb-6">Welcome to Admin Portal!</h2>
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
                <button type="submit" class="w-full bg-green-600 text-white py-2 rounded-lg hover:bg-green-700">Login</button>
                <p class="text-center text-md mt-3">Don't have an account? <a href="<?php echo BASE_PATH; ?>/auth/register.php" class="text-green-600 font-medium">Sign up</a></p>
            </form>
        </div>
    </div>
</div>
</body>
</html>

