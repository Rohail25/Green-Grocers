<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
$currentPath = $_SERVER['REQUEST_URI'];
$active = 'Dashboard';
if (strpos($currentPath, '/products') !== false) $active = 'Products';
elseif (strpos($currentPath, '/packages') !== false) $active = 'Daily Packages';
elseif (strpos($currentPath, '/orders') !== false) $active = 'Orders';
elseif (strpos($currentPath, '/featured-products') !== false) $active = 'Featured Products';
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
    <!-- Sidebar -->
    <aside class="w-64 bg-white border-r shadow-sm flex flex-col">
        <!-- Logo -->
        <div class="flex items-center justify-between p-4 border-b">
            <div class="flex items-center gap-2">
                <img src="<?php echo imagePath('GGLOGO.png'); ?>" alt="Green Grocers Logo" class="w-16 h-16" />
                <div>
                    <h1 class="font-bold text-lg">Green Grocers</h1>
                    <p class="text-md text-gray-500">Company</p>
                </div>
            </div>
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
        </nav>
    </aside>

    <!-- Main Section -->
    <div class="flex flex-col flex-1">
        <!-- Header -->
        <header class="flex items-center justify-between bg-white border-b px-4 py-6">
            <h2 class="text-4xl font-bold text-black"><?php echo htmlspecialchars($active); ?></h2>
            <div class="flex items-center gap-4 flex-1 ml-6">
                <!-- Search bar -->
                <div class="relative flex-1">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-green-600 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    <input type="text" placeholder="Search here..." class="w-full pl-10 pr-4 py-2 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-green-500" />
                </div>

                <!-- Profile -->
                <div class="flex items-center gap-2">
                    <!-- <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="Admin" class="w-16 h-16 rounded-md" /> -->
                    <div class="hidden md:block">
                        <p class="text-md font-semibold"><?php echo htmlspecialchars(getCurrentUser()['firstName'] ?? 'Admin'); ?></p>
                        <p class="text-dm text-gray-500">Admin</p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Content -->
        <main class="p-6 overflow-y-auto">

