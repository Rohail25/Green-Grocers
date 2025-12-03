<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

$email = $_GET['email'] ?? '';
$token = $_GET['key'] ?? $_GET['token'] ?? '';

// Match Node.js: handle email confirmation via token
if (!empty($token)) {
    $result = confirmEmail($token);
    if ($result['success']) {
        $_SESSION['success'] = 'Email confirmed successfully! You can now login.';
        header('Location: ' . BASE_PATH . '/auth/login.php');
        exit;
    } else {
        $error = $result['message'] ?? 'Invalid or expired token';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - Green Grocers</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
<div class="flex min-h-screen">
    <div class="hidden md:flex w-1/2 bg-gray-100 items-center justify-center">
        <img src="<?php echo imagePath('verify.jpg'); ?>" alt="Auth Illustration" class="w-full h-[100vh] object-cover" />
    </div>
    <div class="w-full md:w-1/2 flex items-center justify-center px-6">
        <div class="w-full max-w-md">
            <div class="text-center">
                <?php if (isset($error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><?php echo htmlspecialchars($error); ?></div>
                <?php elseif (isset($_SESSION['success'])): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
                <?php else: ?>
                    <div class="text-blue-600 text-6xl mb-4">📧</div>
                    <h2 class="text-2xl font-bold mb-4">Check Your Email</h2>
                    <p class="text-gray-600 mb-6">We've sent a verification link to <strong><?php echo htmlspecialchars($email); ?></strong>. Please check your email and click the verification link to activate your account.</p>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <p class="text-sm text-blue-800"><strong>Didn't receive the email?</strong><br />Check your spam folder or click the button below to resend.</p>
                    </div>
                <?php endif; ?>
                <div class="space-y-3">
                    <a href="<?php echo BASE_PATH; ?>/auth/login.php" class="block w-full border py-2 rounded-lg hover:bg-gray-50 text-center">Back to Login</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>

