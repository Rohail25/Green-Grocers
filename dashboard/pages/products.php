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
$pageTitle = 'Manage Products';

$products = getAllProducts();
?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="space-y-6">
    <div class="flex flex-col md:flex-row items-center justify-between gap-4">
        <h2 class="text-3xl font-bold text-gray-800">Manage Products</h2>
        <a href="<?php echo BASE_PATH; ?>/dashboard/pages/add-product.php" class="flex items-center gap-2 bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Add Product
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-green-600 text-white">
                <tr>
                    <th class="px-4 py-2">Product Image</th>
                    <th class="px-4 py-2">Name</th>
                    <th class="px-4 py-2">Category</th>
                    <th class="px-4 py-2">Price ($)</th>
                    <th class="px-4 py-2">Stock Quantity</th>
                    <th class="px-4 py-2">Status</th>
                    <th class="px-4 py-2">Action</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y">
                <?php foreach ($products as $product): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2">
                            <img src="<?php echo !empty($product['images']) ? htmlspecialchars($product['images'][0]) : imagePath('product.jpg'); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="w-12 h-12 object-cover rounded-md" />
                        </td>
                        <td class="px-4 py-2"><?php echo htmlspecialchars($product['name']); ?></td>
                        <td class="px-4 py-2"><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></td>
                        <td class="px-4 py-2">$<?php echo number_format($product['retailPrice'] ?? 0, 2); ?></td>
                        <td class="px-4 py-2"><?php echo $product['totalQuantityInStock'] ?? 0; ?></td>
                        <td class="px-4 py-2"><?php echo ($product['totalQuantityInStock'] ?? 0) > 0 ? 'Available' : 'Out of Stock'; ?></td>
                        <td class="px-4 py-2 space-x-2">
                            <a href="<?php echo BASE_PATH; ?>/dashboard/pages/edit-product.php?id=<?php echo $product['id']; ?>" class="text-blue-600 hover:text-blue-800">Edit</a>
                            <form method="POST" action="<?php echo BASE_PATH; ?>/includes/product-action.php" class="inline" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                <button type="submit" class="text-red-600 hover:text-red-800">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

