<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/cart.php';

$cartItems = getCartItems();
$cartTotal = getCartTotal();
$cartCount = getCartCount();
?>
<!-- Cart Modal -->
<div id="cart-modal" class="fixed inset-0 z-50 hidden">
    <!-- Overlay -->
    <div class="absolute inset-0 bg-black/40" onclick="closeCart()"></div>
    
    <!-- Cart Drawer -->
    <div class="relative w-full sm:w-[400px] bg-white h-full shadow-xl flex flex-col ml-auto">
        <!-- Header -->
        <div class="flex justify-between items-center p-4 border-b">
            <h2 class="text-lg font-semibold flex items-center gap-2">
                🛒 My Cart <span class="text-gray-500">(<?php echo $cartCount; ?> items)</span>
            </h2>
            <button onclick="closeCart()" class="text-gray-500 hover:text-red-500">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <!-- Cart Items -->
        <div class="flex-1 overflow-y-auto p-4 space-y-4">
            <?php if (empty($cartItems)): ?>
                <div class="text-center py-12">
                    <p class="text-gray-500">Your cart is empty</p>
                    <button onclick="closeCart()" class="mt-4 px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                        Continue Shopping
                    </button>
                </div>
            <?php else: ?>
                <?php foreach ($cartItems as $item): ?>
                    <?php
                    // Use new schema fields: retailPrice + discount JSON
                    $unitPrice = $item['retailPrice'] ?? 0;
                    $discountPercent = $item['discount']['value'] ?? 0;
                    $discountPrice = $unitPrice - ($unitPrice * $discountPercent / 100);
                    $image = !empty($item['images']) ? $item['images'][0] : imagePath('product.jpg');
                    $itemId = $item['id'];
                    $itemType = $item['cart_type'] ?? 'product';
                    $qty = $item['cart_quantity'] ?? 1;
                    ?>
                    <div class="flex gap-3 p-3 rounded-lg border">
                        <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="w-16 h-16 object-cover rounded" />
                        <div class="flex-1">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="font-semibold"><?php echo htmlspecialchars($item['name']); ?></h4>
                                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($item['itemSize'] ?? ''); ?></p>
                                </div>
                                <span class="font-medium text-green-600">
                                    $<?php echo number_format($discountPrice * $qty, 2); ?>
                                </span>
                            </div>
                            <p class="text-xs text-gray-400"><?php echo htmlspecialchars($item['category_name'] ?? ''); ?></p>
                            
                            <!-- Quantity Controls -->
                            <div class="flex items-center gap-2 mt-2">
                                <button onclick="updateCartQuantity('<?php echo $itemId; ?>', '<?php echo $itemType; ?>', -1)" class="px-2 py-1 border rounded hover:bg-gray-100">-</button>
                                <span><?php echo $qty; ?></span>
                                <button onclick="updateCartQuantity('<?php echo $itemId; ?>', '<?php echo $itemType; ?>', 1)" class="px-2 py-1 border rounded hover:bg-gray-100">+</button>
                                <button onclick="removeFromCart('<?php echo $itemId; ?>', '<?php echo $itemType; ?>')" class="ml-auto text-red-500 hover:text-red-700 text-sm">Remove</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Footer -->
        <?php if (!empty($cartItems)): ?>
            <div class="p-4 border-t bg-white">
                <div class="flex justify-between mb-3 font-semibold">
                    <span>Sub Total:</span>
                    <span class="text-green-600">$<?php echo number_format($cartTotal, 2); ?></span>
                </div>
                <a href="<?php echo BASE_PATH; ?>/cart/checkout.php" class="block w-full py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 text-center">
                    Proceed to Checkout
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function openCart() {
    document.getElementById('cart-modal').classList.remove('hidden');
}

function closeCart() {
    document.getElementById('cart-modal').classList.add('hidden');
}

function updateCartQuantity(id, type, change) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?php echo BASE_PATH; ?>/includes/cart-action.php';
    
    const input1 = document.createElement('input');
    input1.type = 'hidden';
    input1.name = 'action';
    input1.value = 'update';
    
    const input2 = document.createElement('input');
    input2.type = 'hidden';
    input2.name = 'id';
    input2.value = id;
    
    const input3 = document.createElement('input');
    input3.type = 'hidden';
    input3.name = 'type';
    input3.value = type;
    
    const input4 = document.createElement('input');
    input4.type = 'hidden';
    input4.name = 'change';
    input4.value = change;
    
    form.appendChild(input1);
    form.appendChild(input2);
    form.appendChild(input3);
    form.appendChild(input4);
    document.body.appendChild(form);
    form.submit();
}

function removeFromCart(id, type) {
    if (confirm('Remove this item from cart?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?php echo BASE_PATH; ?>/includes/cart-action.php';
        
        const input1 = document.createElement('input');
        input1.type = 'hidden';
        input1.name = 'action';
        input1.value = 'remove';
        
        const input2 = document.createElement('input');
        input2.type = 'hidden';
        input2.name = 'id';
        input2.value = id;
        
        const input3 = document.createElement('input');
        input3.type = 'hidden';
        input3.name = 'type';
        input3.value = type;
        
        form.appendChild(input1);
        form.appendChild(input2);
        form.appendChild(input3);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

