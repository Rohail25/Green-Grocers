<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

$error = '';
$role = $_GET['role'] ?? 'customer';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userData = [
        'email' => $_POST['email'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'password' => $_POST['password'] ?? '',
        'confirmPassword' => $_POST['confirmPassword'] ?? '',
        'firstName' => $_POST['firstName'] ?? '',
        'lastName' => $_POST['lastName'] ?? '',
        'role' => $_POST['role'] ?? 'customer',
        'platform' => 'trivemart' // Match Node.js: platform required
    ];
    
    if ($userData['password'] !== $userData['confirmPassword']) {
        $error = 'Passwords do not match';
    } elseif (strlen($userData['password']) < 6) {
        $error = 'Password must be at least 6 characters long';
    } elseif (!isset($_POST['agreeToTerms'])) {
        $error = 'Please agree to the terms and conditions';
    } else {
        $result = registerUser($userData);
        if ($result['success']) {
            header('Location: ' . BASE_PATH . '/auth/verify.php?email=' . urlencode($userData['email']));
            exit;
        } else {
            $error = $result['message'] ?? 'Registration failed';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Green Grocers</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
<div class="flex min-h-screen">
    <div class="hidden md:flex w-1/2 bg-gray-100 items-center justify-center">
        <img src="<?php echo imagePath('signup.jpg'); ?>" alt="Auth Illustration" class="w-full h-[100vh] object-cover" />
    </div>
    <div class="w-full md:w-1/2 flex items-center justify-center px-6">
        <div class="w-full max-w-md">
            <h2 class="text-3xl font-bold mb-4">Create an Account</h2>
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <label class="block text-md mb-1">Role</label>
            <div class="flex mb-4 border rounded-lg overflow-hidden">
                <a href="?role=customer" class="w-1/2 py-2 font-medium text-center <?php echo $role === 'customer' ? 'bg-orange-500 text-white' : 'bg-gray-100'; ?>">Customer</a>
                <a href="?role=admin" class="w-1/2 py-2 font-medium text-center <?php echo $role === 'admin' ? 'bg-orange-500 text-white' : 'bg-gray-100'; ?>">Admin</a>
            </div>
            <form method="POST" class="space-y-3">
                <input type="hidden" name="role" value="<?php echo htmlspecialchars($role); ?>" />
                <div>
                    <label class="block text-md mb-1">Name</label>
                    <input type="text" name="firstName" placeholder="Enter first name" class="w-full border px-3 py-2 rounded-lg" required />
                </div>
                <div>
                    <label class="block text-md mb-1">Email</label>
                    <input type="email" name="email" placeholder="Enter email" class="w-full border px-3 py-2 rounded-lg" required />
                </div>
                <div>
                    <label class="block text-md mb-1">Password</label>
                    <input type="password" name="password" placeholder="Enter password" class="w-full border px-3 py-2 rounded-lg" required minlength="6" />
                </div>
                <div>
                    <label class="block text-md mb-1">Confirm Password</label>
                    <input type="password" name="confirmPassword" placeholder="Confirm password" class="w-full border px-3 py-2 rounded-lg" required minlength="6" />
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" id="terms" name="agreeToTerms" class="w-4 h-4 accent-green-700 cursor-pointer" required />
                    <label for="terms" class="text-md">I agree with the <a href="#" class="text-orange-500 hover:underline">Terms of Service</a> and <a href="#" class="text-orange-600 hover:underline">Privacy Policy</a></label>
                </div>
                <button type="submit" class="w-full bg-green-600 text-white py-2 rounded-lg hover:bg-green-700">Create Account</button>
                <p class="text-center text-md mt-3">Already have an account? <a href="<?php echo BASE_PATH; ?>/auth/login.php" class="text-green-600 font-medium">Log in</a></p>
            </form>
        </div>
    </div>
</div>
</body>
</html>

