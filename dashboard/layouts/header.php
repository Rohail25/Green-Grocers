<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
$currentUser = getCurrentUser();
$currentPath = $_SERVER['REQUEST_URI'];
$active = 'Dashboard';
if (strpos($currentPath, '/products') !== false) $active = 'Products';
elseif (strpos($currentPath, '/packages') !== false) $active = 'Daily Packages';
elseif (strpos($currentPath, '/orders') !== false) $active = 'Orders';
elseif (strpos($currentPath, '/featured-products') !== false) $active = 'Featured Products';
elseif (strpos($currentPath, '/users') !== false) $active = 'Users';
elseif (strpos($currentPath, '/change-password') !== false) $active = 'Change Password';
elseif (strpos($currentPath, '/dynamic-texts') !== false) $active = 'Dynamic Texts';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Admin Dashboard - Green Grocers'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
<div class="flex h-screen bg-gray-50">
    <!-- Mobile Overlay (when sidebar is open) -->
    <div id="mobile-overlay" class="hidden fixed inset-0 bg-black bg-opacity-50 z-40 md:hidden" onclick="toggleSidebar()"></div>
    
    <!-- Sidebar -->
    <aside id="sidebar" class="fixed md:relative w-64 h-full bg-white border-r shadow-sm flex flex-col z-50 transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out">
        <!-- Logo -->
        <div class="flex items-center justify-between p-4 border-b">
            <div class="flex items-center gap-2">
                <img src="<?php echo imagePath('GGLOGO.png'); ?>" alt="Green Grocers Logo" class="w-16 h-16" />
                <div>
                    <h1 class="font-bold text-lg">Green Grocers</h1>
                    <p class="text-md text-gray-500">Company</p>
                </div>
            </div>
            <!-- Close button for mobile -->
            <button onclick="toggleSidebar()" class="md:hidden text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Nav Links -->
        <nav class="flex-1 px-2 mt-4 space-y-1">
            <a href="<?php echo BASE_PATH; ?>/dashboard/pages/dashboard.php" class="flex items-center gap-3 w-full px-3 py-2 rounded-lg text-lg font-semibold transition <?php echo $active === 'Dashboard' ? 'text-green-600 border border-gray-200 bg-green-50' : 'text-gray-700 hover:bg-gray-100'; ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                <span>Dashboard</span>
            </a>
            <a href="<?php echo BASE_PATH; ?>/dashboard/pages/products.php" class="flex items-center gap-3 w-full px-3 py-2 rounded-lg text-lg font-semibold transition <?php echo $active === 'Products' ? 'text-green-600 border border-gray-200 bg-green-50' : 'text-gray-700 hover:bg-gray-100'; ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                <span>Products</span>
            </a>
            <a href="<?php echo BASE_PATH; ?>/dashboard/pages/packages.php" class="flex items-center gap-3 w-full px-3 py-2 rounded-lg text-lg font-semibold transition <?php echo $active === 'Daily Packages' ? 'text-green-600 border border-gray-200 bg-green-50' : 'text-gray-700 hover:bg-gray-100'; ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                <span>Daily Packages</span>
            </a>
            <a href="<?php echo BASE_PATH; ?>/dashboard/pages/orders.php" class="flex items-center gap-3 w-full px-3 py-2 rounded-lg text-lg font-semibold transition <?php echo $active === 'Orders' ? 'text-green-600 border border-gray-200 bg-green-50' : 'text-gray-700 hover:bg-gray-100'; ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                <span>Orders</span>
            </a>
            <a href="<?php echo BASE_PATH; ?>/dashboard/pages/featured-products.php" class="flex items-center gap-3 w-full px-3 py-2 rounded-lg text-lg font-semibold transition <?php echo $active === 'Featured Products' ? 'text-green-600 border border-gray-200 bg-green-50' : 'text-gray-700 hover:bg-gray-100'; ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path></svg>
                <span>Featured Products</span>
            </a>
            <?php if ($currentUser['role'] === 'admin'): ?>
            <a href="<?php echo BASE_PATH; ?>/dashboard/pages/users.php" class="flex items-center gap-3 w-full px-3 py-2 rounded-lg text-lg font-semibold transition <?php echo $active === 'Users' ? 'text-green-600 border border-gray-200 bg-green-50' : 'text-gray-700 hover:bg-gray-100'; ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                <span>Users</span>
            </a>
            <a href="<?php echo BASE_PATH; ?>/dashboard/pages/change-password.php" class="flex items-center gap-3 w-full px-3 py-2 rounded-lg text-lg font-semibold transition <?php echo $active === 'Change Password' ? 'text-green-600 border border-gray-200 bg-green-50' : 'text-gray-700 hover:bg-gray-100'; ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                <span>Change Password</span>
            </a>
            <a href="<?php echo BASE_PATH; ?>/dashboard/pages/dynamic-texts.php" class="flex items-center gap-3 w-full px-3 py-2 rounded-lg text-lg font-semibold transition <?php echo $active === 'Dynamic Texts' ? 'text-green-600 border border-gray-200 bg-green-50' : 'text-gray-700 hover:bg-gray-100'; ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path></svg>
                <span>Dynamic Texts</span>
            </a>
            <?php endif; ?>
        </nav>
    </aside>

    <!-- Main Section -->
    <div class="flex flex-col flex-1 w-full md:w-auto">
        <!-- Header -->
        <header class="flex items-center justify-between bg-white border-b px-4 py-6">
            <div class="flex items-center gap-4">
                <!-- Hamburger Menu Button (Mobile Only) -->
                <button onclick="toggleSidebar()" class="md:hidden text-gray-700 hover:text-gray-900">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                <h2 class="text-2xl md:text-4xl font-bold text-black"><?php echo htmlspecialchars($active); ?></h2>
            </div>
            <div class="flex items-center gap-4 flex-1 ml-2 md:ml-6">
                <!-- Search bar -->
                <div class="relative flex-1">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-green-600 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    <input type="text" placeholder="Search here..." class="w-full pl-10 pr-4 py-2 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-green-500" />
                </div>

                <!-- Profile & Logout -->
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <!-- <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="Admin" class="w-16 h-16 rounded-md" /> -->
                        <div class="hidden md:block">
                            <p class="text-md font-semibold"><?php echo htmlspecialchars(getCurrentUser()['firstName'] ?? 'Admin'); ?></p>
                            <p class="text-dm text-gray-500"><?php echo htmlspecialchars(getCurrentUser()['role'] ?? 'Admin'); ?></p>
                        </div>
                    </div>
                    <!-- Logout Button -->
                    <a href="<?php echo BASE_PATH; ?>/includes/logout.php" class="flex items-center gap-2 px-2 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-semibold" title="Logout">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        <!-- <span class="hidden md:inline">Logout</span> -->
                    </a>
                </div>
            </div>
        </header>

        <!-- Content -->
        <main class="p-4 md:p-6 overflow-y-auto w-full">

