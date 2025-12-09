<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

// Allow both admin and vendor to access
requireAuth();
$currentUser = getCurrentUser();
if (!in_array($currentUser['role'], ['admin', 'vendor'])) {
    header('Location: ' . BASE_PATH . '/website/pages/dashboard.php');
    exit;
}
$pageTitle = 'Featured Products';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_featured'])) {
    toggleProductFeatured($_POST['product_id']);
    header('Location: ' . BASE_PATH . '/dashboard/pages/featured-products.php');
    exit;
}

$products = getAllProducts();
?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="space-y-6">
    <h2 class="text-3xl font-bold text-gray-800">Manage Featured Products</h2>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($products as $product): ?>
            <?php
                // Match new schema: retailPrice, isFeatured, images JSON already decoded in getAllProducts()
                $price = $product['retailPrice'] ?? 0;
                $isFeatured = !empty($product['isFeatured']);
            ?>
            <div class="bg-white rounded-lg shadow p-4">
                <img src="<?php echo htmlspecialchars(getProductImage($product)); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="w-full h-48 object-cover rounded mb-4" />
                <h3 class="font-semibold text-lg mb-2"><?php echo htmlspecialchars($product['name']); ?></h3>
                <p class="text-gray-600 mb-4">$<?php echo number_format($price, 2); ?></p>
                <form method="POST" class="mt-4">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>" />
                    <input type="hidden" name="toggle_featured" value="1" />
                    <button type="submit" class="w-full px-4 py-2 rounded-md <?php echo $isFeatured ? 'bg-yellow-500 text-white hover:bg-yellow-600' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                        <?php echo $isFeatured ? 'Remove from Featured' : 'Add to Featured'; ?>
                    </button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

