<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
$email = $_GET['email'] ?? '';
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
                <div class="text-blue-600 text-6xl mb-4">📧</div>
                <h2 class="text-2xl font-bold mb-4">Check Your Email</h2>
                <p class="text-gray-600 mb-6">We've sent a verification link to <strong><?php echo htmlspecialchars($email); ?></strong>. Please check your email and click the verification link to activate your account.</p>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <p class="text-sm text-blue-800"><strong>Didn't receive the email?</strong><br />Check your spam folder or click the button below to resend.</p>
                </div>
                <div class="space-y-3">
                    <button class="w-full bg-green-600 text-white py-2 rounded-lg hover:bg-green-700">Resend Verification Email</button>
                    <a href="<?php echo BASE_PATH; ?>/auth/login.php" class="block w-full border py-2 rounded-lg hover:bg-gray-50 text-center">Back to Login</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>

