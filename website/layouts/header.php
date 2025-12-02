<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
$currentUser = getCurrentUser();
$isAuthenticated = isAuthenticated();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Green Grocers'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
</head>
<body>
<nav class="fixed top-0 z-50 w-full bg-white shadow-sm px-6 py-3 flex items-center justify-between">
    <!-- Logo -->
    <div class="flex items-center gap-2">
        <a href="<?php echo BASE_PATH; ?>/">
            <img src="<?php echo imagePath('GGLOGO.png'); ?>" alt="Logo" class="w-20 h-20" />
        </a>
    </div>

    <!-- Nav Links - Desktop -->
    <div class="hidden md:flex gap-6">
        <a href="<?php echo BASE_PATH; ?>/" class="text-md font-bold text-black">Home</a>
        <a href="<?php echo BASE_PATH; ?>/category?name=Vegetables" class="text-md font-bold text-gray-500 hover:text-black">Categories</a>
        <a href="#" class="text-md font-bold text-gray-500 hover:text-black">Daily Packages</a>
    </div>

    <!-- Right Side -->
    <div class="flex items-center gap-4">
        <!-- Cart -->
        <div class="relative cursor-pointer">
            <svg class="w-6 h-6 text-gray-700 hover:text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            <span class="absolute -top-1 -right-1 w-3 h-3 bg-orange-500 rounded-full"></span>
        </div>

        <!-- Search -->
        <svg class="w-6 h-6 text-gray-500 cursor-pointer hover:text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
        </svg>
        
        <!-- Cart Icon -->
        <button onclick="openCart()" class="relative">
            <svg class="w-6 h-6 text-gray-500 cursor-pointer hover:text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            <?php
            require_once __DIR__ . '/../../includes/cart.php';
            $cartCount = getCartCount();
            if ($cartCount > 0):
            ?>
                <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center"><?php echo $cartCount; ?></span>
            <?php endif; ?>
        </button>

        <!-- Auth (Desktop) -->
        <div class="hidden md:flex items-center gap-4">
            <?php if ($isAuthenticated): ?>
                <div class="flex items-center gap-2">
                    <img src="https://i.pravatar.cc/40" alt="User" class="w-10 h-10 rounded-full" />
                    <span class="font-medium"><?php echo htmlspecialchars($currentUser['firstName'] ?? 'User'); ?></span>
                    <?php if ($currentUser['role'] === 'admin'): ?>
                        <a href="<?php echo BASE_PATH; ?>/dashboard/pages/dashboard.php" class="text-green-600 hover:text-green-400 font-semibold">Admin</a>
                    <?php endif; ?>
                    <a href="<?php echo BASE_PATH; ?>/includes/logout.php" class="text-red-600 hover:text-red-400 font-semibold ml-2">Logout</a>
                </div>
            <?php else: ?>
                <a href="<?php echo BASE_PATH; ?>/auth/register.php" class="text-green-600 hover:text-green-400 font-semibold">Sign Up</a>
                <span>or</span>
                <a href="<?php echo BASE_PATH; ?>/auth/login.php" class="text-green-600 hover:text-green-400 font-semibold">Login</a>
            <?php endif; ?>
        </div>

        <!-- Mobile Menu Button -->
        <button class="md:hidden text-gray-700" onclick="toggleMobileMenu()">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" id="menu-icon">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
            <svg class="w-7 h-7 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" id="close-icon">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>

    <!-- Mobile Menu Panel -->
    <div id="mobile-menu" class="hidden absolute top-full left-0 w-full bg-white shadow-md flex flex-col items-center gap-4 py-6 md:hidden">
        <a href="<?php echo BASE_PATH; ?>/" class="text-lg font-bold text-black">Home</a>
        <a href="<?php echo BASE_PATH; ?>/category?name=Vegetables" class="text-lg font-bold text-gray-500 hover:text-black">Categories</a>
        <a href="#" class="text-lg font-bold text-gray-500 hover:text-black">Daily Packages</a>
        
        <?php if ($isAuthenticated): ?>
            <div class="flex flex-col items-center gap-2">
                <img src="https://i.pravatar.cc/40" alt="User" class="w-12 h-12 rounded-full" />
                <span class="font-medium"><?php echo htmlspecialchars($currentUser['firstName'] ?? $currentUser['email']); ?></span>
                <a href="<?php echo BASE_PATH; ?>/includes/logout.php" class="text-red-600 hover:text-red-400 font-semibold">Logout</a>
            </div>
        <?php else: ?>
            <div class="flex flex-col items-center gap-3">
                <a href="<?php echo BASE_PATH; ?>/auth/register.php" class="text-green-600 hover:text-green-400 font-semibold">Sign Up</a>
                <span>or</span>
                <a href="<?php echo BASE_PATH; ?>/auth/login.php" class="text-green-600 hover:text-green-400 font-semibold">Login</a>
            </div>
        <?php endif; ?>
    </div>
</nav>

<script>
function toggleMobileMenu() {
    const menu = document.getElementById('mobile-menu');
    const menuIcon = document.getElementById('menu-icon');
    const closeIcon = document.getElementById('close-icon');
    
    menu.classList.toggle('hidden');
    menuIcon.classList.toggle('hidden');
    closeIcon.classList.toggle('hidden');
}
</script>

