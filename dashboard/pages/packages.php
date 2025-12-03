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
$pageTitle = 'Manage Packages';

$packages = getAllPackages();
?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="space-y-6">
    <div class="flex flex-col md:flex-row items-center justify-between gap-4">
        <h2 class="text-3xl font-bold text-gray-800">Manage Packages</h2>
        <a href="<?php echo BASE_PATH; ?>/dashboard/pages/add-package.php" class="flex items-center gap-2 bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Create Package
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-green-600 text-white">
                <tr>
                    <th class="px-4 py-2">Package Name</th>
                    <th class="px-4 py-2">Assigned Day</th>
                    <th class="px-4 py-2">Price ($)</th>
                    <th class="px-4 py-2">Status</th>
                    <th class="px-4 py-2">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y">
                <?php foreach ($packages as $pkg): ?>
                    <?php
                        // Handle new schema field names and defaults
                        $price = $pkg['retailPrice'] ?? 0;
                        $statusLabel = (isset($pkg['status']) && $pkg['status'] === 'active') ? 'Active' : 'Inactive';
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2"><?php echo htmlspecialchars($pkg['name']); ?></td>
                        <td class="px-4 py-2"><?php echo htmlspecialchars($pkg['packageDay'] ?? 'N/A'); ?></td>
                        <td class="px-4 py-2">$<?php echo number_format($price, 2); ?></td>
                        <td class="px-4 py-2"><?php echo $statusLabel; ?></td>
                        <td class="px-4 py-2 space-x-2">
                            <a href="<?php echo BASE_PATH; ?>/dashboard/pages/edit-package.php?id=<?php echo $pkg['id']; ?>" class="text-blue-600 hover:underline">Edit</a>
                            <form method="POST" action="<?php echo BASE_PATH; ?>/includes/package-action.php" class="inline" onsubmit="return confirm('Are you sure you want to delete this package?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $pkg['id']; ?>">
                                <button type="submit" class="text-red-600 hover:underline">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

