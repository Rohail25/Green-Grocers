<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAuth();
require_once __DIR__ . '/../../includes/cart.php';

$pageTitle = 'Checkout';
$step = $_GET['step'] ?? 1;
$currentUser = getCurrentUser();
// Normalize name fields to support both new (firstName/lastName) and legacy (first_name/last_name)
$userFirstName = $currentUser['firstName'] ?? $currentUser['first_name'] ?? '';
$userLastName  = $currentUser['lastName'] ?? $currentUser['last_name'] ?? '';

// Get actual cart items
$cartItems = getCartItems();

// Calculate totals using new schema (retailPrice + discount JSON)
$subtotal = 0;
foreach ($cartItems as $item) {
    $basePrice = $item['retailPrice'] ?? 0;
    $discountPercent = $item['discount']['value'] ?? 0;
    $finalPrice = $basePrice - ($basePrice * $discountPercent / 100);
    $subtotal += $finalPrice * ($item['cart_quantity'] ?? 1);
}
$vat = round($subtotal * 0.05, 2);
$total = $subtotal + $vat;
?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="min-h-screen bg-gray-50 mt-24">
    <div class="max-w-6xl mx-auto px-4 py-8">
        <div class="flex items-center gap-4 mb-8">
            <a href="<?php echo BASE_PATH; ?>/" class="text-gray-600 hover:text-black font-semibold">← Continue browsing</a>
        </div>
        <div class="flex items-center justify-center mb-8">
            <div class="flex items-center gap-3">
                <?php 
                $steps = [['id' => 1, 'label' => 'Review Order'], ['id' => 2, 'label' => 'Address & Contact'], ['id' => 3, 'label' => 'Payment']];
                foreach ($steps as $idx => $s): 
                    $active = $s['id'] == $step;
                    $done = $s['id'] < $step;
                ?>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center font-semibold <?php echo $active ? 'bg-green-600 text-white' : ($done ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-700'); ?>"><?php echo $s['id']; ?></div>
                        <div class="<?php echo $active ? 'text-green-600 font-semibold' : 'text-gray-600'; ?>"><?php echo htmlspecialchars($s['label']); ?></div>
                    </div>
                    <?php if ($idx < count($steps) - 1): ?><div class="text-gray-400 mx-2">➜</div><?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <div class="lg:col-span-8 space-y-6">
                <?php if ($step == 1): ?>
                    <section class="bg-white rounded-lg p-6 shadow-sm">
                        <h3 class="font-semibold mb-3">Your Order</h3>
                        <div class="space-y-3">
                            <?php if (empty($cartItems)): ?>
                                <p class="text-gray-500 text-center py-8">Your cart is empty. <a href="<?php echo BASE_PATH; ?>/" class="text-green-600 hover:underline">Continue shopping</a></p>
                            <?php else: ?>
                                <?php foreach ($cartItems as $item): ?>
                                    <?php
                                    $basePrice = $item['retailPrice'] ?? 0;
                                    $discountPercent = $item['discount']['value'] ?? 0;
                                    $discountPrice = $basePrice - ($basePrice * $discountPercent / 100);
                                    $image = !empty($item['images']) ? $item['images'][0] : imagePath('product.jpg');
                                    $quantity = $item['cart_quantity'] ?? 1;
                                    $itemTotal = $discountPrice * $quantity;
                                    ?>
                                    <div class="flex gap-3 p-3 bg-white rounded-lg shadow-sm items-start">
                                        <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="w-20 h-20 object-cover rounded" />
                                        <div class="flex-1">
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <div class="font-semibold"><?php echo htmlspecialchars($item['name']); ?> <span class="text-gray-500">(<?php echo htmlspecialchars($item['itemSize'] ?? ''); ?>)</span></div>
                                                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($item['category_name'] ?? ($item['cart_type'] ?? 'Product')); ?></div>
                                                    <div class="text-xs text-gray-400 mt-1">Quantity: <?php echo $quantity; ?></div>
                                                </div>
                                                <div class="font-semibold text-green-600">$<?php echo number_format($itemTotal, 2); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </section>
                <?php elseif ($step == 2): ?>
                    <form method="POST" action="?step=3">
                        <section class="bg-white rounded-lg p-6 shadow-sm">
                            <div class="flex justify-between items-center mb-6">
                                <h2 class="text-2xl font-semibold">Address & Contact</h2>
                            </div>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block mb-2 font-semibold">Full Name <span class="text-red-500">*</span></label>
                                    <input type="text" name="full_name" required class="w-full border px-4 py-3 rounded-md" value="<?php echo htmlspecialchars(trim($userFirstName . ' ' . $userLastName)); ?>">
                                </div>
                                
                                <div>
                                    <label class="block mb-2 font-semibold">Phone Number <span class="text-red-500">*</span></label>
                                    <input type="tel" name="phone" required class="w-full border px-4 py-3 rounded-md" value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>">
                                </div>
                                
                                <div>
                                    <label class="block mb-2 font-semibold">Delivery Address <span class="text-red-500">*</span></label>
                                    <textarea name="delivery_address" required rows="4" class="w-full border px-4 py-3 rounded-md" placeholder="Enter your complete delivery address"><?php echo htmlspecialchars($_POST['delivery_address'] ?? ''); ?></textarea>
                                </div>
                                
                                <div>
                                    <label class="block mb-2 font-semibold">Notes (Optional)</label>
                                    <textarea name="notes" rows="3" class="w-full border px-4 py-3 rounded-md" placeholder="Any special instructions?"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            
                            <div class="mt-6 flex justify-end gap-4">
                                <a href="?step=1" class="px-6 py-2 rounded-lg font-medium border border-gray-300 hover:bg-gray-50">Back</a>
                                <button type="submit" class="px-6 py-2 rounded-lg font-medium bg-green-600 text-white hover:bg-green-700">Continue</button>
                            </div>
                        </section>
                    </form>
                <?php elseif ($step == 3): ?>
                    <form method="POST" action="<?php echo BASE_PATH; ?>/includes/create-order.php">
                        <section class="bg-white rounded-lg p-6 shadow-sm">
                            <div class="mb-3 font-medium">Choose Payment Method</div>
                            <div class="space-y-3 mb-6">
                                <?php foreach (['card' => 'Credit/Debit Card', 'bank' => 'Bank Transfer', 'transfer' => 'Bank Transfer', 'ussd' => 'USSD'] as $method => $label): ?>
                                    <label class="w-full block p-3 border rounded cursor-pointer hover:bg-gray-50">
                                        <input type="radio" name="payment_method" value="<?php echo $method; ?>" required class="mr-3 accent-green-600" <?php echo $method === 'card' ? 'checked' : ''; ?> />
                                        Pay via <?php echo $label; ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Hidden fields for order data -->
                            <input type="hidden" name="delivery_address" value="<?php echo htmlspecialchars($_POST['delivery_address'] ?? ''); ?>">
                            <input type="hidden" name="full_name" value="<?php echo htmlspecialchars(trim($userFirstName . ' ' . $userLastName)); ?>">
                            <input type="hidden" name="phone" value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>">
                            
                            <div class="flex justify-end gap-4">
                                <a href="?step=2" class="px-6 py-2 rounded-lg font-medium border border-gray-300 hover:bg-gray-50">Back</a>
                                <button type="submit" class="px-6 py-2 rounded-lg font-medium bg-green-600 text-white hover:bg-green-700">
                                    Place Order
                                </button>
                            </div>
                        </section>
                    </form>
                <?php endif; ?>
            </div>
            <aside class="lg:col-span-4">
                <div class="lg:top-20 lg:sticky space-y-4">
                    <div class="bg-green-600 text-white rounded-lg p-4">
                        <h4 class="font-semibold text-2xl">Order Summary</h4>
                        <div class="mt-4 space-y-6 text-lg">
                            <div class="flex justify-between"><div>Sub Total</div><div>$<?php echo number_format($subtotal, 2); ?></div></div>
                            <div class="flex justify-between"><div>VAT (5%)</div><div>$<?php echo number_format($vat, 2); ?></div></div>
                            <div class="border-t border-green-800 mt-3 pt-3 flex justify-between font-semibold"><div>Total</div><div>$<?php echo number_format($total, 2); ?></div></div>
                            <?php if ($step < 3): ?>
                                <div class="mt-4">
                                    <a href="?step=<?php echo $step + 1; ?>" class="block w-full px-4 py-2 bg-white text-green-600 rounded font-semibold text-center hover:bg-gray-100">Continue</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

