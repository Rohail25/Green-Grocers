<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

// Allow both admin and vendor to access
requireAuth();
$currentUser = getCurrentUser();
if (!in_array($currentUser['role'], ['admin', 'vendor'])) {
    header('Location: ' . BASE_PATH . '/website/pages/dashboard.php');
    exit;
}
$pageTitle = 'Dashboard Pages';
?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="space-y-6">
    <h1 class="text-4xl font-bold text-gray-800 mb-8">Dashboard Pages</h1>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <a href="<?php echo BASE_PATH; ?>/dashboard/pages/dashboard.php" class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition">
            <h3 class="text-xl font-bold text-green-600 mb-2">Dashboard</h3>
            <p class="text-gray-600">Main admin dashboard with statistics</p>
        </a>
        
        <a href="<?php echo BASE_PATH; ?>/dashboard/pages/products.php" class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition">
            <h3 class="text-xl font-bold text-green-600 mb-2">Products</h3>
            <p class="text-gray-600">Manage products (view, add, edit, delete)</p>
        </a>
        
        <a href="<?php echo BASE_PATH; ?>/dashboard/pages/packages.php" class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition">
            <h3 class="text-xl font-bold text-green-600 mb-2">Packages</h3>
            <p class="text-gray-600">Manage daily packages</p>
        </a>
        
        <a href="<?php echo BASE_PATH; ?>/dashboard/pages/orders.php" class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition">
            <h3 class="text-xl font-bold text-green-600 mb-2">Orders</h3>
            <p class="text-gray-600">View and manage customer orders</p>
        </a>
        
        <a href="<?php echo BASE_PATH; ?>/dashboard/pages/featured-products.php" class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition">
            <h3 class="text-xl font-bold text-green-600 mb-2">Featured Products</h3>
            <p class="text-gray-600">Manage featured products</p>
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>


