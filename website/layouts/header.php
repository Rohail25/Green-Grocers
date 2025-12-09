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
<nav class="fixed top-0 z-50 w-full bg-white shadow-sm px-6 py-2 flex items-center justify-between">
    <!-- Logo -->
    <div class="flex items-center gap-2">
        <a href="<?php echo BASE_PATH; ?>/">
            <img src="<?php echo imagePath('GGLOGO.png'); ?>" alt="Logo" class="w-28 h-28" />
        </a>
    </div>

    <!-- Nav Links - Desktop -->
    <div class="hidden md:flex gap-6 items-center">
        <a href="<?php echo BASE_PATH; ?>/" class="text-md font-normal text-black">Home</a>
        <a href="<?php echo BASE_PATH; ?>/category?name=Vegetables" class="text-md font-normal text-gray-500 hover:text-black">Categories</a>
        <a href="#" class="text-md font-normal text-gray-500 hover:text-black">Daily Packages</a>
    </div>

    <!-- Right Side -->
    <div class="flex items-center gap-3 md:gap-3">
        <!-- Home Button (Mobile Only) -->
        <a href="<?php echo BASE_PATH; ?>/" class="md:hidden text-gray-700 hover:text-black" title="Home">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
            </svg>
        </a>

        <!-- Search Icon (Desktop) -->
        <button onclick="toggleDesktopSearch()" class="hidden lg:flex items-center justify-center w-8 h-8 text-gray-500 hover:text-black transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
        </button>
        
        <!-- Search Bar (Desktop - Hidden by default, shown on search icon click) -->
        <div class="hidden lg:flex items-center gap-2 ml-0">
            <div class="relative hidden" id="desktop-search-container">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <input type="text" id="header-search-input" placeholder="Search products..." class="pl-10 pr-4 py-2 w-64 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500" />
            </div>
        </div>

        <!-- Search Bar (Mobile/Tablet - Compact) -->
        <div class="md:hidden flex items-center gap-1">
            <div class="relative">
                <svg class="absolute left-2 top-1/2 -translate-y-1/2 text-gray-400 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <input type="text" id="mobile-header-search-input" placeholder="Search..." class="pl-8 pr-2 py-1.5 w-32 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-green-500" />
            </div>
            <button onclick="performHeaderSearch()" class="p-1.5 bg-green-600 text-white rounded-lg hover:bg-green-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </button>
        </div>
        
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
                <span class="cart-badge absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center"><?php echo $cartCount; ?></span>
            <?php else: ?>
                <span class="cart-badge absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center" style="display: none;">0</span>
            <?php endif; ?>
        </button>

        <!-- Auth (Desktop) -->
        <div class="hidden md:flex items-center gap-1">
            <?php if ($isAuthenticated): ?>
                <div class="flex items-center gap-2">
                    <!-- <img src="https://i.pravatar.cc/40" alt="User" class="w-10 h-10 rounded-full" /> -->
                    <?php if ($currentUser['role'] === 'admin' || $currentUser['role'] === 'vendor'): ?>
                        <a href="<?php echo BASE_PATH; ?>/dashboard/pages/dashboard.php" class="font-medium text-gray-800 hover:text-green-600 transition-colors"><?php echo htmlspecialchars($currentUser['firstName'] ?? 'User'); ?></a>
                        <a href="<?php echo BASE_PATH; ?>/dashboard/pages/dashboard.php" class="text-green-600 hover:text-green-400 font-semibold">Admin</a>
                    <?php else: ?>
                        <a href="<?php echo BASE_PATH; ?>/website/pages/dashboard.php" class="font-medium text-gray-800 hover:text-green-600 transition-colors"><?php echo htmlspecialchars($currentUser['firstName'] ?? 'User'); ?></a>
                    <?php endif; ?>
                    <a href="<?php echo BASE_PATH; ?>/includes/logout.php" class="text-red-600 hover:text-red-400 font-semibold ml-2">Logout</a>
                </div>
            <?php else: ?>
                <a href="<?php echo BASE_PATH; ?>/auth/register.php" class="text-green-600 hover:text-green-400 font-normal">Sign Up</a>
                <span class="text-gray-600">or</span>
                <a href="<?php echo BASE_PATH; ?>/auth/login.php" class="text-green-600 hover:text-green-400 font-normal">Login</a>
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
    <div id="mobile-menu" class="hidden absolute top-full left-0 w-full bg-white shadow-md flex flex-col items-center gap-4 py-6 md:hidden z-40">
        <a href="<?php echo BASE_PATH; ?>/" class="text-lg font-bold text-black">Home</a>
        <a href="<?php echo BASE_PATH; ?>/category?name=Vegetables" class="text-lg font-bold text-gray-500 hover:text-black">Categories</a>
        <a href="#" class="text-lg font-bold text-gray-500 hover:text-black">Daily Packages</a>
        
        <?php if ($isAuthenticated): ?>
            <div class="flex flex-col items-center gap-2">
                <img src="https://i.pravatar.cc/40" alt="User" class="w-12 h-12 rounded-full" />
                <?php if ($currentUser['role'] === 'admin' || $currentUser['role'] === 'vendor'): ?>
                    <a href="<?php echo BASE_PATH; ?>/dashboard/pages/dashboard.php" class="font-medium text-gray-800 hover:text-green-600 transition-colors"><?php echo htmlspecialchars($currentUser['firstName'] ?? $currentUser['email']); ?></a>
                <?php else: ?>
                    <a href="<?php echo BASE_PATH; ?>/website/pages/dashboard.php" class="font-medium text-gray-800 hover:text-green-600 transition-colors"><?php echo htmlspecialchars($currentUser['firstName'] ?? $currentUser['email']); ?></a>
                <?php endif; ?>
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

function toggleDesktopSearch() {
    const searchContainer = document.getElementById('desktop-search-container');
    if (searchContainer) {
        searchContainer.classList.toggle('hidden');
        if (!searchContainer.classList.contains('hidden')) {
            const searchInput = document.getElementById('header-search-input');
            if (searchInput) {
                setTimeout(() => searchInput.focus(), 100);
            }
        }
    }
}

function performHeaderSearch() {
    const searchInput = window.innerWidth < 1024 
        ? document.getElementById('mobile-header-search-input')
        : document.getElementById('header-search-input');
    
    const query = searchInput ? searchInput.value.trim() : '';
    if (query) {
        // Redirect to category page with search query
        window.location.href = '<?php echo BASE_PATH; ?>/category?search=' + encodeURIComponent(query);
    }
}

// Allow Enter key to trigger search and preserve search query in input
document.addEventListener('DOMContentLoaded', function() {
    const mobileInput = document.getElementById('mobile-header-search-input');
    const desktopInput = document.getElementById('header-search-input');
    
    // Get search query from URL and populate input fields
    const urlParams = new URLSearchParams(window.location.search);
    const searchQuery = urlParams.get('search') || '';
    
    if (mobileInput && searchQuery) {
        mobileInput.value = decodeURIComponent(searchQuery);
    }
    if (desktopInput && searchQuery) {
        desktopInput.value = decodeURIComponent(searchQuery);
    }
    
    if (mobileInput) {
        mobileInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performHeaderSearch();
            }
        });
    }
    
    if (desktopInput) {
        desktopInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performHeaderSearch();
            }
        });
    }
});
</script>

