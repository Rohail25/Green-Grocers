<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

// Allow both admin and vendor to access dashboard
requireAuth();
$currentUser = getCurrentUser();
if (!in_array($currentUser['role'], ['admin', 'vendor'])) {
    header('Location: ' . BASE_PATH . '/website/pages/dashboard.php');
    exit;
}
$pageTitle = ($currentUser['role'] === 'admin') ? 'Admin Dashboard' : 'Vendor Dashboard';

$orders = getAllOrders();
$recentOrders = array_slice($orders, 0, 4);

$cardsData = [
    ['title' => 'Total Orders', 'number' => count($orders), 'percentage' => 12],
    ['title' => 'Fulfilled Orders', 'number' => count(array_filter($orders, fn($o) => $o['status'] === 'Delivered')), 'percentage' => 18],
    ['title' => 'Pending Orders', 'number' => count(array_filter($orders, fn($o) => $o['status'] === 'Placed')), 'percentage' => -5]
];
?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="space-y-6">
    <!-- Top Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <?php foreach ($cardsData as $card): ?>
            <div class="bg-white shadow rounded-lg p-5 flex justify-between">
                <div class="flex-1">
                    <h3 class="text-gray-500 font-semibold"><?php echo htmlspecialchars($card['title']); ?></h3>
                    <p class="text-3xl font-bold mt-2"><?php echo $card['number']; ?></p>
                    <div class="flex items-center gap-2 mt-2">
                        <?php if ($card['percentage'] >= 0): ?>
                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                        <?php else: ?>
                            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                        <?php endif; ?>
                        <span class="font-semibold <?php echo $card['percentage'] >= 0 ? 'text-green-500' : 'text-red-500'; ?>"><?php echo abs($card['percentage']); ?>%</span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white shadow rounded-lg p-5">
        <h3 class="text-xl font-bold mb-4">Quick Actions</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="<?php echo BASE_PATH; ?>/dashboard/pages/featured-products.php" class="flex items-center p-4 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition-colors">
                <svg class="w-6 h-6 text-yellow-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path></svg>
                <div>
                    <h4 class="font-semibold text-gray-800">Manage Featured Products</h4>
                    <p class="text-sm text-gray-600">Toggle featured status</p>
                </div>
            </a>
            <a href="<?php echo BASE_PATH; ?>/dashboard/pages/products.php" class="flex items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                <svg class="w-6 h-6 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                <div>
                    <h4 class="font-semibold text-gray-800">Manage Products</h4>
                    <p class="text-sm text-gray-600">Add, edit, delete products</p>
                </div>
            </a>
            <a href="<?php echo BASE_PATH; ?>/dashboard/pages/orders.php" class="flex items-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                <svg class="w-6 h-6 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                <div>
                    <h4 class="font-semibold text-gray-800">Manage Orders</h4>
                    <p class="text-sm text-gray-600">View and process orders</p>
                </div>
            </a>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="bg-white shadow rounded-lg p-5">
        <h3 class="text-xl font-bold mb-4">Recent Orders</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-green-500 text-white">
                    <tr>
                        <th class="text-left py-2 px-3">Order ID</th>
                        <th class="text-left py-2 px-3">Customer</th>
                        <th class="text-left py-2 px-3">Date</th>
                        <th class="text-left py-2 px-3">Status</th>
                        <th class="text-left py-2 px-3">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($recentOrders as $order): ?>
                        <?php
                        $orderId      = $order['id'] ?? '';
                        $customerName = trim(($order['firstName'] ?? '') . ' ' . ($order['lastName'] ?? ''));
                        $totalAmount  = $order['totalAmount'] ?? 0;
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="py-2 px-3"><?php echo htmlspecialchars($orderId); ?></td>
                            <td class="py-2 px-3"><?php echo htmlspecialchars($customerName); ?></td>
                            <td class="py-2 px-3"><?php echo date('Y-m-d', strtotime($order['created_at'])); ?></td>
                            <td class="py-2 px-3 font-semibold <?php echo $order['status'] === 'Delivered' ? 'text-green-600' : 'text-yellow-600'; ?>"><?php echo htmlspecialchars($order['status']); ?></td>
                            <td class="py-2 px-3">$<?php echo number_format($totalAmount, 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

