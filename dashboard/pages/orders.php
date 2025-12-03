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
$pageTitle = 'Manage Orders';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    updateOrderStatus($_POST['order_id'], $_POST['status']);
    header('Location: ' . BASE_PATH . '/dashboard/pages/orders.php');
    exit;
}

$orders = getAllOrders();
?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-3xl font-bold text-gray-800">Manage Orders</h2>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-green-600 text-white">
                <tr>
                    <th class="px-4 py-2">Order Number</th>
                    <th class="px-4 py-2">Customer Name</th>
                    <th class="px-4 py-2">Date / Time</th>
                    <th class="px-4 py-2">Total Amount ($)</th>
                    <th class="px-4 py-2">Status</th>
                    <th class="px-4 py-2">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y">
                <?php foreach ($orders as $order): ?>
                    <?php
                    // Order number: use id (UUID) as display reference
                    $orderNumber = $order['id'] ?? '';
                    $customerName = trim(($order['firstName'] ?? '') . ' ' . ($order['lastName'] ?? ''));
                    $totalAmount = $order['totalAmount'] ?? 0;
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2"><?php echo htmlspecialchars($orderNumber); ?></td>
                        <td class="px-4 py-2"><?php echo htmlspecialchars($customerName); ?></td>
                        <td class="px-4 py-2"><?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></td>
                        <td class="px-4 py-2">$<?php echo number_format($totalAmount, 2); ?></td>
                        <td class="px-4 py-2"><?php echo htmlspecialchars($order['status']); ?></td>
                        <td class="px-4 py-2">
                            <button onclick="openModal('<?php echo $order['id']; ?>', '<?php echo htmlspecialchars($order['status']); ?>')" class="px-3 py-1 bg-green-600 text-white rounded-md hover:bg-green-700">Update</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Update Status Modal -->
<div id="statusModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg w-full max-w-md p-6 relative">
        <button onclick="closeModal()" class="absolute top-3 right-3 text-gray-500 hover:text-gray-700">✕</button>
        <h2 class="text-2xl font-bold text-center mb-6">Update Order Status</h2>
        <form method="POST">
            <input type="hidden" id="order_id" name="order_id" />
            <input type="hidden" name="update_status" value="1" />
            <div class="flex flex-col gap-3 mb-6">
                <?php foreach (['Placed', 'Processed', 'Shipped', 'Delivered'] as $status): ?>
                    <label class="flex items-center gap-3 w-full border p-2 rounded-md cursor-pointer hover:bg-gray-100">
                        <input type="radio" name="status" value="<?php echo $status; ?>" class="accent-green-600" />
                        <?php echo $status; ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <div class="flex justify-center gap-4">
                <button type="button" onclick="closeModal()" class="px-4 py-2 rounded-md border border-gray-300 hover:bg-gray-100">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-md bg-green-600 text-white hover:bg-green-700">Update</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(orderId, currentStatus) {
    document.getElementById('order_id').value = orderId;
    document.querySelectorAll('input[name="status"]').forEach(radio => {
        if (radio.value === currentStatus) radio.checked = true;
    });
    document.getElementById('statusModal').classList.remove('hidden');
}
function closeModal() {
    document.getElementById('statusModal').classList.add('hidden');
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

