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
                    // Order number: use orderNumber field if available, otherwise use id (UUID) as fallback
                    $orderNumber = !empty($order['orderNumber']) ? $order['orderNumber'] : (substr($order['id'] ?? '', 0, 8) . '...');
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
                            <div class="flex gap-2">
                                <button onclick="openViewModal('<?php echo $order['id']; ?>')" class="px-3 py-1 bg-blue-600 text-white rounded-md hover:bg-blue-700">View</button>
                                <button onclick="openModal('<?php echo $order['id']; ?>', '<?php echo htmlspecialchars($order['status']); ?>')" class="px-3 py-1 bg-green-600 text-white rounded-md hover:bg-green-700">Update</button>
                            </div>
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

<!-- View Order Details Modal -->
<div id="viewModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 overflow-y-auto py-4" onclick="closeViewModalOnBackdrop(event)">
    <div class="bg-white rounded-lg w-full max-w-4xl max-h-[90vh] flex flex-col relative my-8" onclick="event.stopPropagation()">
        <!-- Header - Fixed -->
        <div class="flex-shrink-0 px-6 pt-6 pb-4 border-b">
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-bold">Order Details</h2>
                <button onclick="closeViewModal()" class="text-gray-500 hover:text-gray-700 text-2xl font-bold w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 transition-colors" title="Close">✕</button>
            </div>
        </div>
        
        <!-- Content - Scrollable -->
        <div id="viewModalContent" class="flex-1 overflow-y-auto px-6 py-4 space-y-6">
            <div class="text-center py-8">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-green-600"></div>
                <p class="mt-2 text-gray-600">Loading order details...</p>
            </div>
        </div>
        
        <!-- Footer - Fixed -->
        <div class="flex-shrink-0 px-6 py-4 border-t bg-gray-50 flex justify-center">
            <button onclick="closeViewModal()" class="px-6 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors">Close</button>
        </div>
    </div>
</div>

<script>
function openViewModal(orderId) {
    document.getElementById('viewModal').classList.remove('hidden');
    
    // Fetch order details via AJAX
    fetch('<?php echo BASE_PATH; ?>/dashboard/pages/get-order-details.php?order_id=' + encodeURIComponent(orderId))
        .then(response => response.json())
        .then(order => {
            if (order.error) {
                document.getElementById('viewModalContent').innerHTML = '<p class="text-red-600 text-center py-8">' + order.error + '</p>';
                return;
            }
            
            // Format shipping address
            const shippingAddress = order.shippingAddress || {};
            const addressText = shippingAddress.street || 'Not provided';
            const phone = shippingAddress.phone || order.email || 'Not provided';
            const notes = shippingAddress.notes || order.notes || 'None';
            
            // Format order items
            const items = order.items || [];
            let itemsHtml = '';
            if (items.length > 0) {
                itemsHtml = '<div class="space-y-2">';
                items.forEach(item => {
                    const itemName = item.productName || item.name || 'Unknown Product';
                    const itemImage = item.productImage || (item.images && item.images[0]) || '';
                    const quantity = item.quantity || item.cart_quantity || 1;
                    const price = item.price || 0;
                    const total = price * quantity;
                    
                    itemsHtml += `
                        <div class="flex items-center gap-4 p-3 border rounded-md">
                            ${itemImage ? `<img src="${itemImage}" alt="${itemName}" class="w-16 h-16 object-cover rounded">` : '<div class="w-16 h-16 bg-gray-200 rounded"></div>'}
                            <div class="flex-1">
                                <h4 class="font-semibold">${itemName}</h4>
                                <p class="text-sm text-gray-600">Quantity: ${quantity}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold">$${parseFloat(price).toFixed(2)}</p>
                                <p class="text-sm text-gray-600">Total: $${parseFloat(total).toFixed(2)}</p>
                            </div>
                        </div>
                    `;
                });
                itemsHtml += '</div>';
            } else {
                itemsHtml = '<p class="text-gray-500">No items found</p>';
            }
            
            // Format payment method
            const paymentMethod = order.paymentMethod || 'Not specified';
            const paymentStatus = order.paymentStatus || 'PENDING';
            const transactionId = order.transactionId || 'N/A';
            
            // Format dates
            const orderDate = order.created_at ? new Date(order.created_at).toLocaleString() : 'N/A';
            const updatedDate = order.updated_at ? new Date(order.updated_at).toLocaleString() : 'N/A';
            
            // Build modal content
            const content = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Customer Information -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="font-bold text-lg mb-3 text-green-600">Customer Information</h3>
                        <div class="space-y-2">
                            <p><span class="font-semibold">Name:</span> ${order.customerName || (order.firstName + ' ' + order.lastName) || 'N/A'}</p>
                            <p><span class="font-semibold">Email:</span> ${order.email || 'N/A'}</p>
                            <p><span class="font-semibold">Phone:</span> ${phone}</p>
                        </div>
                    </div>
                    
                    <!-- Shipping Address -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="font-bold text-lg mb-3 text-green-600">Shipping Address</h3>
                        <div class="space-y-2">
                            <p><span class="font-semibold">Address:</span></p>
                            <p class="text-gray-700 break-words">${addressText}</p>
                            <p><span class="font-semibold">Delivery Notes:</span> ${notes}</p>
                        </div>
                    </div>
                    
                    <!-- Order Information -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="font-bold text-lg mb-3 text-green-600">Order Information</h3>
                        <div class="space-y-2">
                            <p><span class="font-semibold">Order ID:</span> <span class="text-sm break-all">${order.id || 'N/A'}</span></p>
                            <p><span class="font-semibold">Order Date:</span> ${orderDate}</p>
                            <p><span class="font-semibold">Last Updated:</span> ${updatedDate}</p>
                            <p><span class="font-semibold">Status:</span> <span class="px-2 py-1 bg-green-100 text-green-800 rounded">${order.status || 'N/A'}</span></p>
                        </div>
                    </div>
                    
                    <!-- Payment Information -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="font-bold text-lg mb-3 text-green-600">Payment Information</h3>
                        <div class="space-y-2">
                            <p><span class="font-semibold">Payment Method:</span> ${paymentMethod}</p>
                            <p><span class="font-semibold">Payment Status:</span> <span class="px-2 py-1 ${paymentStatus === 'PAID' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'} rounded">${paymentStatus}</span></p>
                            <p><span class="font-semibold">Transaction ID:</span> <span class="text-sm text-gray-600 break-all">${transactionId}</span></p>
                        </div>
                    </div>
                </div>
                
                <!-- Order Items -->
                <div class="mt-6">
                    <h3 class="font-bold text-lg mb-3 text-green-600">Order Items</h3>
                    ${itemsHtml}
                </div>
                
                <!-- Order Summary -->
                <div class="mt-6 bg-green-50 p-4 rounded-lg">
                    <h3 class="font-bold text-lg mb-3 text-green-600">Order Summary</h3>
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div class="space-y-1">
                            <p><span class="font-semibold">Subtotal:</span> $${parseFloat(order.totalAmount - (order.discountAmount || 0)).toFixed(2)}</p>
                            ${order.discountAmount > 0 ? `<p><span class="font-semibold">Discount:</span> -$${parseFloat(order.discountAmount).toFixed(2)}</p>` : ''}
                            ${order.couponCode ? `<p><span class="font-semibold">Coupon Code:</span> ${order.couponCode}</p>` : ''}
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-bold text-green-600">Total: $${parseFloat(order.totalAmount || 0).toFixed(2)}</p>
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('viewModalContent').innerHTML = content;
        })
        .catch(error => {
            document.getElementById('viewModalContent').innerHTML = '<p class="text-red-600 text-center py-8">Error loading order details: ' + error.message + '</p>';
        });
}

function closeViewModal() {
    document.getElementById('viewModal').classList.add('hidden');
    // Clear modal content when closing
    document.getElementById('viewModalContent').innerHTML = `
        <div class="text-center py-8">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-green-600"></div>
            <p class="mt-2 text-gray-600">Loading order details...</p>
        </div>
    `;
}

function closeViewModalOnBackdrop(event) {
    // Close modal if clicking on the backdrop (not on the modal content)
    if (event.target.id === 'viewModal') {
        closeViewModal();
    }
}

// Close modal on Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const viewModal = document.getElementById('viewModal');
        if (!viewModal.classList.contains('hidden')) {
            closeViewModal();
        }
    }
});

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

