<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAuth();
$currentUser = getCurrentUser();
$pageTitle = 'My Orders';

// Get all orders for the current customer
$conn = getDBConnection();
$stmt = $conn->prepare("
    SELECT o.*, u.firstName, u.lastName, u.email 
    FROM orders o 
    LEFT JOIN users u ON o.userId = u.id 
    WHERE o.userId = :userId
    ORDER BY o.created_at DESC
");
$stmt->execute([':userId' => $currentUser['id']]);
$orders = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Decode JSON fields
    $row['items'] = !empty($row['items']) ? json_decode($row['items'], true) : [];
    $row['shippingAddress'] = !empty($row['shippingAddress']) ? json_decode($row['shippingAddress'], true) : null;
    $row['statusHistory'] = !empty($row['statusHistory']) ? json_decode($row['statusHistory'], true) : [];
    $orders[] = $row;
}
?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="min-h-screen bg-gray-100 p-6 md:p-8 mt-24">
    <div class="max-w-6xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold text-gray-800" style="font-family: 'Arial', sans-serif;">My Orders</h1>
            <a href="<?php echo BASE_PATH; ?>/website/pages/dashboard.php" class="text-green-600 hover:text-green-700 font-medium" style="font-family: 'Arial', sans-serif;">← Back to Dashboard</a>
        </div>
        
        <?php if (empty($orders)): ?>
            <div class="bg-white rounded-lg shadow-md p-8 text-center">
                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <p class="text-gray-600 text-lg mb-4" style="font-family: 'Arial', sans-serif;">You haven't placed any orders yet.</p>
                <a href="<?php echo BASE_PATH; ?>/" class="inline-block bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 font-medium" style="font-family: 'Arial', sans-serif;">Start Shopping</a>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($orders as $order): ?>
                    <?php
                    $orderNumber = !empty($order['orderNumber']) ? $order['orderNumber'] : (substr($order['id'] ?? '', 0, 8) . '...');
                    $totalAmount = $order['totalAmount'] ?? 0;
                    $status = $order['status'] ?? 'Pending';
                    $createdAt = $order['created_at'] ?? date('Y-m-d H:i:s');
                    $items = $order['items'] ?? [];
                    $itemCount = count($items);
                    ?>
                    <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <h3 class="text-lg font-bold text-gray-800" style="font-family: 'Arial', sans-serif;">Order #<?php echo htmlspecialchars($orderNumber); ?></h3>
                                    <span class="px-3 py-1 rounded-full text-xs font-medium <?php 
                                        echo $status === 'delivered' ? 'bg-green-100 text-green-800' : 
                                            ($status === 'canceled' ? 'bg-red-100 text-red-800' : 
                                            ($status === 'assigned' || $status === 'dispatched' ? 'bg-blue-100 text-blue-800' : 
                                            'bg-yellow-100 text-yellow-800')); 
                                    ?>" style="font-family: 'Arial', sans-serif;">
                                        <?php echo htmlspecialchars(ucfirst($status)); ?>
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600 mb-1" style="font-family: 'Arial', sans-serif;">
                                    Placed on <?php echo date('F j, Y g:i A', strtotime($createdAt)); ?>
                                </p>
                                <p class="text-sm text-gray-600" style="font-family: 'Arial', sans-serif;">
                                    <?php echo $itemCount; ?> item<?php echo $itemCount !== 1 ? 's' : ''; ?> • Total: <span class="font-semibold text-gray-800">$<?php echo number_format($totalAmount, 2); ?></span>
                                </p>
                            </div>
                            <div class="flex gap-2">
                                <button onclick="viewOrderDetails('<?php echo htmlspecialchars($order['id']); ?>')" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium text-sm" style="font-family: 'Arial', sans-serif;">View Details</button>
                            </div>
                        </div>
                        
                        <!-- Order Items Preview -->
                        <?php if (!empty($items) && count($items) > 0): ?>
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                    <?php foreach (array_slice($items, 0, 3) as $item): ?>
                                        <?php
                                        $itemName = $item['productName'] ?? $item['name'] ?? 'Unknown Product';
                                        $itemImage = $item['productImage'] ?? ($item['images'] && is_array($item['images']) ? $item['images'][0] : '');
                                        $quantity = $item['quantity'] ?? $item['cart_quantity'] ?? 1;
                                        ?>
                                        <div class="flex items-center gap-2">
                                            <?php if ($itemImage): ?>
                                                <img src="<?php echo htmlspecialchars($itemImage); ?>" alt="<?php echo htmlspecialchars($itemName); ?>" class="w-12 h-12 object-cover rounded">
                                            <?php else: ?>
                                                <div class="w-12 h-12 bg-gray-200 rounded flex items-center justify-center">
                                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                                    </svg>
                                                </div>
                                            <?php endif; ?>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-800 truncate" style="font-family: 'Arial', sans-serif;"><?php echo htmlspecialchars($itemName); ?></p>
                                                <p class="text-xs text-gray-500" style="font-family: 'Arial', sans-serif;">Qty: <?php echo $quantity; ?></p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if (count($items) > 3): ?>
                                        <div class="flex items-center text-sm text-gray-600" style="font-family: 'Arial', sans-serif;">
                                            +<?php echo count($items) - 3; ?> more item<?php echo (count($items) - 3) !== 1 ? 's' : ''; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Order Details Modal -->
<div id="orderDetailsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-3xl w-full max-h-[90vh] overflow-y-auto p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-800" style="font-family: 'Arial', sans-serif;">Order Details</h2>
            <button onclick="closeOrderDetailsModal()" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div id="orderDetailsContent"></div>
    </div>
</div>

<script>
const orders = <?php echo json_encode($orders); ?>;

function viewOrderDetails(orderId) {
    const order = orders.find(o => o.id === orderId);
    if (!order) {
        alert('Order not found');
        return;
    }
    
    const orderNumber = order.orderNumber || (order.id.substring(0, 8) + '...');
    const status = order.status || 'Pending';
    const totalAmount = parseFloat(order.totalAmount || 0);
    const items = order.items || [];
    const shippingAddress = order.shippingAddress || {};
    const paymentMethod = order.paymentMethod || 'Not specified';
    const paymentStatus = order.paymentStatus || 'PENDING';
    const createdAt = order.created_at ? new Date(order.created_at).toLocaleString() : 'N/A';
    
    let itemsHtml = '';
    if (items.length > 0) {
        itemsHtml = '<div class="space-y-3 mb-6">';
        items.forEach(item => {
            const itemName = item.productName || item.name || 'Unknown Product';
            const itemImage = item.productImage || (item.images && item.images[0]) || '';
            const quantity = item.quantity || item.cart_quantity || 1;
            const price = parseFloat(item.price || item.retailPrice || 0);
            const discount = item.discount && item.discount.value ? parseFloat(item.discount.value) : 0;
            const finalPrice = price - (price * discount / 100);
            const total = finalPrice * quantity;
            
            itemsHtml += `
                <div class="flex items-center gap-4 p-3 border border-gray-200 rounded-lg">
                    ${itemImage ? `<img src="${itemImage}" alt="${itemName}" class="w-16 h-16 object-cover rounded">` : '<div class="w-16 h-16 bg-gray-200 rounded"></div>'}
                    <div class="flex-1">
                        <h4 class="font-semibold text-gray-800" style="font-family: 'Arial', sans-serif;">${itemName}</h4>
                        <p class="text-sm text-gray-600" style="font-family: 'Arial', sans-serif;">Quantity: ${quantity}</p>
                        ${item.itemSize ? `<p class="text-xs text-gray-500" style="font-family: 'Arial', sans-serif;">Size: ${item.itemSize}</p>` : ''}
                    </div>
                    <div class="text-right">
                        ${discount > 0 ? `<p class="text-xs text-gray-500 line-through" style="font-family: 'Arial', sans-serif;">$${price.toFixed(2)}</p>` : ''}
                        <p class="font-semibold text-gray-800" style="font-family: 'Arial', sans-serif;">$${finalPrice.toFixed(2)}</p>
                        <p class="text-sm text-gray-600" style="font-family: 'Arial', sans-serif;">Total: $${total.toFixed(2)}</p>
                    </div>
                </div>
            `;
        });
        itemsHtml += '</div>';
    } else {
        itemsHtml = '<p class="text-gray-500 mb-6" style="font-family: 'Arial', sans-serif;">No items found</p>';
    }
    
    const addressText = shippingAddress.street || 'N/A';
    
    const content = `
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-600 mb-1" style="font-family: 'Arial', sans-serif;">Order Number</p>
                    <p class="font-semibold text-gray-800" style="font-family: 'Arial', sans-serif;">${orderNumber}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 mb-1" style="font-family: 'Arial', sans-serif;">Order Date</p>
                    <p class="font-semibold text-gray-800" style="font-family: 'Arial', sans-serif;">${createdAt}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 mb-1" style="font-family: 'Arial', sans-serif;">Status</p>
                    <span class="inline-block px-3 py-1 rounded-full text-xs font-medium ${status === 'delivered' ? 'bg-green-100 text-green-800' : (status === 'canceled' ? 'bg-red-100 text-red-800' : (status === 'assigned' || status === 'dispatched' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800'))}" style="font-family: 'Arial', sans-serif;">
                        ${status.charAt(0).toUpperCase() + status.slice(1)}
                    </span>
                </div>
                <div>
                    <p class="text-sm text-gray-600 mb-1" style="font-family: 'Arial', sans-serif;">Payment Method</p>
                    <p class="font-semibold text-gray-800" style="font-family: 'Arial', sans-serif;">${paymentMethod}</p>
                </div>
            </div>
            
            <div class="border-t border-gray-200 pt-4">
                <h3 class="font-semibold text-gray-800 mb-3" style="font-family: 'Arial', sans-serif;">Shipping Address</h3>
                <p class="text-gray-700" style="font-family: 'Arial', sans-serif;">${addressText}</p>
                ${shippingAddress.phone ? `<p class="text-gray-700 mt-1" style="font-family: 'Arial', sans-serif;">Phone: ${shippingAddress.phone}</p>` : ''}
            </div>
            
            <div class="border-t border-gray-200 pt-4">
                <h3 class="font-semibold text-gray-800 mb-3" style="font-family: 'Arial', sans-serif;">Order Items</h3>
                ${itemsHtml}
            </div>
            
            <div class="border-t border-gray-200 pt-4">
                <div class="flex justify-between items-center">
                    <span class="text-lg font-bold text-gray-800" style="font-family: 'Arial', sans-serif;">Total Amount:</span>
                    <span class="text-lg font-bold text-green-600" style="font-family: 'Arial', sans-serif;">$${totalAmount.toFixed(2)}</span>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('orderDetailsContent').innerHTML = content;
    document.getElementById('orderDetailsModal').classList.remove('hidden');
}

function closeOrderDetailsModal() {
    document.getElementById('orderDetailsModal').classList.add('hidden');
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

