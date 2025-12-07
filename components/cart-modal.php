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
                🛒 My Cart <span class="text-gray-500" id="cart-item-count">(<?php echo $cartCount; ?> items)</span>
            </h2>
            <button onclick="closeCart()" class="text-gray-500 hover:text-red-500">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <!-- Cart Items -->
        <div id="cart-items-container" class="flex-1 overflow-y-auto p-4 space-y-4">
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
        <div id="cart-footer">
            <?php if (!empty($cartItems)): ?>
                <div class="p-4 border-t bg-white">
                    <div class="flex justify-between mb-3 font-semibold">
                        <span>Sub Total:</span>
                        <span class="text-green-600" id="cart-total">$<?php echo number_format($cartTotal, 2); ?></span>
                    </div>
                    <a href="<?php echo BASE_PATH; ?>/cart/checkout.php" class="block w-full py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 text-center">
                        Proceed to Checkout
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
const BASE_PATH = '<?php echo BASE_PATH; ?>';

function openCart() {
    document.getElementById('cart-modal').classList.remove('hidden');
    refreshCart(); // Refresh cart when opening
}

function closeCart() {
    document.getElementById('cart-modal').classList.add('hidden');
}

function refreshCart() {
    // Get updated cart data
    fetch(BASE_PATH + '/includes/cart-action.php?action=get_count&ajax=1')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.cart) {
                // Update cart items container
                updateCartItems(data.cart.items);
                
                // Update cart total
                const cartTotalEl = document.getElementById('cart-total');
                if (cartTotalEl) {
                    cartTotalEl.textContent = '$' + parseFloat(data.cart.total).toFixed(2);
                }
                
                // Update cart count
                const cartCountEl = document.getElementById('cart-item-count');
                if (cartCountEl) {
                    cartCountEl.textContent = '(' + data.cart.count + ' items)';
                }
                
                // Update footer visibility
                updateCartFooter(data.cart.items.length > 0);
            }
            
            // Update cart count in header
            updateCartCount();
        })
        .catch(error => {
            console.error('Error refreshing cart:', error);
            // Fallback: reload the page section
            location.reload();
        });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function updateCartItems(items) {
    const container = document.getElementById('cart-items-container');
    if (!container) return;
    
    if (items.length === 0) {
        container.innerHTML = `
            <div class="text-center py-12">
                <p class="text-gray-500">Your cart is empty</p>
                <button onclick="closeCart()" class="mt-4 px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                    Continue Shopping
                </button>
            </div>
        `;
        return;
    }
    
    let html = '';
    items.forEach(item => {
        const unitPrice = parseFloat(item.retailPrice) || 0;
        const discountPercent = (item.discount && item.discount.value) ? parseFloat(item.discount.value) : 0;
        const discountPrice = unitPrice - (unitPrice * discountPercent / 100);
        const image = (item.images && item.images.length > 0 && item.images[0]) ? escapeHtml(item.images[0]) : '<?php echo htmlspecialchars(imagePath("product.jpg"), ENT_QUOTES); ?>';
        const itemId = escapeHtml(item.id);
        const itemType = escapeHtml(item.cart_type || 'product');
        const qty = parseInt(item.cart_quantity) || 1;
        const itemSize = escapeHtml(item.itemSize || '');
        const categoryName = escapeHtml(item.category_name || '');
        const itemName = escapeHtml(item.name || '');
        
        html += `
            <div class="flex gap-3 p-3 rounded-lg border">
                <img src="${image}" alt="${itemName}" class="w-16 h-16 object-cover rounded" />
                <div class="flex-1">
                    <div class="flex justify-between items-start">
                        <div>
                            <h4 class="font-semibold">${itemName}</h4>
                            <p class="text-sm text-gray-500">${itemSize}</p>
                        </div>
                        <span class="font-medium text-green-600">
                            $${(discountPrice * qty).toFixed(2)}
                        </span>
                    </div>
                    <p class="text-xs text-gray-400">${categoryName}</p>
                    <div class="flex items-center gap-2 mt-2">
                        <button onclick="updateCartQuantity('${itemId}', '${itemType}', -1)" class="px-2 py-1 border rounded hover:bg-gray-100">-</button>
                        <span>${qty}</span>
                        <button onclick="updateCartQuantity('${itemId}', '${itemType}', 1)" class="px-2 py-1 border rounded hover:bg-gray-100">+</button>
                        <button onclick="removeFromCart('${itemId}', '${itemType}')" class="ml-auto text-red-500 hover:text-red-700 text-sm">Remove</button>
                    </div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

function updateCartFooter(hasItems) {
    const footer = document.getElementById('cart-footer');
    if (!footer) return;
    
    if (hasItems) {
        // Footer will be updated by refreshCart with total
        const cartTotalEl = document.getElementById('cart-total');
        if (!cartTotalEl) {
            // Need to fetch total
            fetch(BASE_PATH + '/includes/cart-action.php?action=get_count&ajax=1')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.cart) {
                        footer.innerHTML = `
                            <div class="p-4 border-t bg-white">
                                <div class="flex justify-between mb-3 font-semibold">
                                    <span>Sub Total:</span>
                                    <span class="text-green-600" id="cart-total">$${parseFloat(data.cart.total).toFixed(2)}</span>
                                </div>
                                <a href="${BASE_PATH}/cart/checkout.php" class="block w-full py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 text-center">
                                    Proceed to Checkout
                                </a>
                            </div>
                        `;
                    }
                });
        }
    } else {
        footer.innerHTML = '';
    }
}

function updateCartCount() {
    // Update cart count badge in header
    fetch(BASE_PATH + '/includes/cart-action.php?action=get_count&ajax=1')
        .then(response => response.json())
        .then(data => {
            const cartBadge = document.querySelector('.cart-badge');
            if (cartBadge) {
                if (data.count > 0) {
                    cartBadge.textContent = data.count;
                    cartBadge.style.display = 'flex';
                } else {
                    cartBadge.style.display = 'none';
                }
            }
        })
        .catch(error => {
            console.error('Error updating cart count:', error);
        });
}

function updateCartQuantity(id, type, change) {
    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('id', id);
    formData.append('type', type);
    formData.append('change', change);
    
    fetch(BASE_PATH + '/includes/cart-action.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update cart items directly from response
            if (data.cart) {
                updateCartItems(data.cart.items);
                
                // Update cart total
                const cartTotalEl = document.getElementById('cart-total');
                if (cartTotalEl) {
                    cartTotalEl.textContent = '$' + parseFloat(data.cart.total).toFixed(2);
                }
                
                // Update cart count
                const cartCountEl = document.getElementById('cart-item-count');
                if (cartCountEl) {
                    cartCountEl.textContent = '(' + data.cart.count + ' items)';
                }
                
                // Update footer
                updateCartFooter(data.cart.items.length > 0);
            }
            
            // Update cart count in header
            updateCartCount();
        } else {
            alert(data.message || 'Failed to update cart');
        }
    })
    .catch(error => {
        console.error('Error updating cart:', error);
        alert('An error occurred while updating the cart');
    });
}

function removeFromCart(id, type) {
    if (confirm('Remove this item from cart?')) {
        const formData = new FormData();
        formData.append('action', 'remove');
        formData.append('id', id);
        formData.append('type', type);
        
        fetch(BASE_PATH + '/includes/cart-action.php', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update cart items directly from response
                if (data.cart) {
                    updateCartItems(data.cart.items);
                    
                    // Update cart total
                    const cartTotalEl = document.getElementById('cart-total');
                    if (cartTotalEl) {
                        cartTotalEl.textContent = '$' + parseFloat(data.cart.total).toFixed(2);
                    }
                    
                    // Update cart count
                    const cartCountEl = document.getElementById('cart-item-count');
                    if (cartCountEl) {
                        cartCountEl.textContent = '(' + data.cart.count + ' items)';
                    }
                    
                    // Update footer
                    updateCartFooter(data.cart.items.length > 0);
                }
                
                // Update cart count in header
                updateCartCount();
            } else {
                alert(data.message || 'Failed to remove item');
            }
        })
        .catch(error => {
            console.error('Error removing from cart:', error);
            alert('An error occurred while removing the item');
        });
    }
}
</script>

