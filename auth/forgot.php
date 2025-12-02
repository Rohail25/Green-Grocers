<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
$step = $_GET['step'] ?? 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Green Grocers</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
<div class="flex min-h-screen">
    <div class="hidden md:flex w-1/2 bg-gray-100 items-center justify-center">
        <img src="<?php echo imagePath('forgot.jpg'); ?>" alt="Auth Illustration" class="w-full h-[100vh] object-cover" />
    </div>
    <div class="w-full md:w-1/2 flex items-center justify-center px-6">
        <div class="w-full max-w-md">
            <?php if ($step == 1): ?>
                <div>
                    <h2 class="text-3xl font-bold mb-2">Forgot Password?</h2>
                    <p class="text-md text-gray-600 mb-4">Please enter your email to reset your password</p>
                    <form method="GET" action="<?php echo BASE_PATH; ?>/auth/forgot.php" class="space-y-4">
                        <input type="hidden" name="step" value="2" />
                        <div>
                            <label class="block text-md mb-1">Email</label>
                            <input type="email" name="email" placeholder="Enter email" class="w-full border px-3 py-2 rounded-lg" required />
                        </div>
                        <button type="submit" class="w-full bg-green-600 text-white py-2 rounded-lg hover:bg-green-700">Confirm</button>
                        <a href="<?php echo BASE_PATH; ?>/auth/login.php" class="block w-full border py-2 rounded-lg text-center">Cancel</a>
                    </form>
                </div>
            <?php elseif ($step == 2): ?>
                <div>
                    <h2 class="text-3xl font-bold mb-2">Check your email</h2>
                    <p class="text-md text-gray-600 mb-4">We sent a reset link to "<?php echo htmlspecialchars($_GET['email'] ?? 'your@mail.com'); ?>", enter 5 digit code mentioned.</p>
                    <div class="flex justify-center gap-3 mb-6">
                        <?php for ($i = 0; $i < 5; $i++): ?>
                            <input type="text" maxlength="1" class="w-12 h-12 text-center border-2 border-gray-300 rounded-lg focus:border-green-600 outline-none" />
                        <?php endfor; ?>
                    </div>
                    <form method="GET" action="<?php echo BASE_PATH; ?>/auth/forgot.php">
                        <input type="hidden" name="step" value="3" />
                        <input type="hidden" name="email" value="<?php echo htmlspecialchars($_GET['email'] ?? ''); ?>" />
                        <button type="submit" class="w-full bg-green-600 text-white py-2 rounded-lg hover:bg-green-700">Confirm</button>
                        <a href="<?php echo BASE_PATH; ?>/auth/login.php" class="block w-full border py-2 rounded-lg text-center mt-2">Cancel</a>
                    </form>
                </div>
            <?php elseif ($step == 3): ?>
                <div>
                    <h2 class="text-3xl font-bold mb-2">Set a new password</h2>
                    <p class="text-md text-gray-600 mb-4">Create a new password</p>
                    <form class="space-y-4">
                        <div>
                            <label class="block text-md mb-1">Password</label>
                            <input type="password" placeholder="New password" class="w-full border px-3 py-2 rounded-lg" required />
                        </div>
                        <div>
                            <label class="block text-md mb-1">Confirm Password</label>
                            <input type="password" placeholder="Confirm password" class="w-full border px-3 py-2 rounded-lg" required />
                        </div>
                        <button type="submit" class="w-full bg-green-600 text-white py-2 rounded-lg hover:bg-green-700">Confirm</button>
                        <a href="<?php echo BASE_PATH; ?>/auth/login.php" class="block w-full border py-2 rounded-lg text-center">Cancel</a>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>

