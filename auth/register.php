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
        <img src="<?php echo imagePath('signup.jpg'); ?>" alt="Auth Illustration" class="w-full h-full object-cover" />
    </div>
    <div class="w-full md:w-1/2 flex items-center justify-center px-6 py-8">
        <div class="w-full max-w-md">
            <h2 class="text-3xl font-bold mb-8 text-gray-800" style="font-family: 'Arial', sans-serif;">Create an account</h2>
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <label class="block text-sm font-medium mb-3 text-gray-700" style="font-family: 'Arial', sans-serif;">Role</label>
            <div class="flex mb-6 border rounded-lg overflow-hidden">
                <a href="?role=customer" class="w-1/2 py-3 font-medium text-center transition <?php echo $role === 'customer' ? 'bg-orange-500 text-white' : 'bg-transparent text-gray-800'; ?>" style="font-family: 'Arial', sans-serif;">Customer</a>
                <a href="?role=admin" class="w-1/2 py-3 font-medium text-center transition <?php echo $role === 'admin' ? 'bg-orange-500 text-white' : 'bg-transparent text-gray-800'; ?>" style="font-family: 'Arial', sans-serif;">Admin</a>
            </div>
            <form method="POST" class="space-y-5">
                <input type="hidden" name="role" value="<?php echo htmlspecialchars($role); ?>" />
                <div>
                    <label class="block text-sm font-medium mb-2 text-gray-700" style="font-family: 'Arial', sans-serif;">Name<span class="text-red-500">*</span></label>
                    <input type="text" name="firstName" placeholder="Enter your name" class="w-full border border-gray-300 px-4 py-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" style="font-family: 'Arial', sans-serif;" required />
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2 text-gray-700" style="font-family: 'Arial', sans-serif;">Email<span class="text-red-500">*</span></label>
                    <input type="email" name="email" placeholder="Enter your email" class="w-full border border-gray-300 px-4 py-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" style="font-family: 'Arial', sans-serif;" required />
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2 text-gray-700" style="font-family: 'Arial', sans-serif;">Password<span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="password" name="password" id="password" placeholder="Create a password" class="w-full border border-gray-300 px-4 py-3 pr-10 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" style="font-family: 'Arial', sans-serif;" required minlength="6" />
                        <button type="button" onclick="togglePassword('password')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" id="eye-password">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            <svg class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" id="eye-off-password">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2 text-gray-700" style="font-family: 'Arial', sans-serif;">Confirm Password<span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="password" name="confirmPassword" id="confirmPassword" placeholder="Re-enter your password" class="w-full border border-gray-300 px-4 py-3 pr-10 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" style="font-family: 'Arial', sans-serif;" required minlength="6" />
                        <button type="button" onclick="togglePassword('confirmPassword')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" id="eye-confirmPassword">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            <svg class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" id="eye-off-confirmPassword">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <input type="checkbox" id="terms" name="agreeToTerms" class="w-5 h-5 mt-0.5 cursor-pointer" style="accent-color: #f97316;" required />
                    <label for="terms" class="text-sm text-gray-700" style="font-family: 'Arial', sans-serif;">I agree with the <a href="#" class="text-orange-500 font-bold hover:underline">Terms of services</a> and <a href="#" class="text-orange-500 font-bold hover:underline">Privacy Policy</a></label>
                </div>
                <button type="submit" class="w-full bg-green-600 text-white py-3 rounded-lg hover:bg-green-700 font-medium" style="font-family: 'Arial', sans-serif;">Create Account</button>
                
                <p class="text-center text-sm mt-6 text-gray-700" style="font-family: 'Arial', sans-serif;">Already have an Account? <a href="<?php echo BASE_PATH; ?>/auth/login.php" class="text-green-600 font-medium hover:underline">Log in</a></p>
            </form>
        </div>
    </div>
</div>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const eyeIcon = document.getElementById('eye-' + fieldId);
    const eyeOffIcon = document.getElementById('eye-off-' + fieldId);
    
    if (field.type === 'password') {
        field.type = 'text';
        eyeIcon.classList.add('hidden');
        eyeOffIcon.classList.remove('hidden');
    } else {
        field.type = 'password';
        eyeIcon.classList.remove('hidden');
        eyeOffIcon.classList.add('hidden');
    }
}
</script>
</body>
</html>

