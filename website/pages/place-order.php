<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAuth();
require_once __DIR__ . '/../../includes/cart.php';

$pageTitle = 'Checkout';
$step = (int)($_GET['step'] ?? $_POST['step'] ?? 1);
$currentUser = getCurrentUser();

// Preserve form data from previous steps in session
if ($step == 2 && isset($_POST['delivery_address'])) {
    $_SESSION['checkout_delivery_address'] = $_POST['delivery_address'];
    $_SESSION['checkout_full_name'] = $_POST['full_name'] ?? '';
    $_SESSION['checkout_phone'] = $_POST['phone'] ?? '';
    $_SESSION['checkout_notes'] = $_POST['notes'] ?? '';
}
if ($step == 3 && isset($_POST['delivery_address'])) {
    $_SESSION['checkout_delivery_address'] = $_POST['delivery_address'];
    $_SESSION['checkout_full_name'] = $_POST['full_name'] ?? '';
    $_SESSION['checkout_phone'] = $_POST['phone'] ?? '';
    $_SESSION['checkout_notes'] = $_POST['notes'] ?? '';
}

// Use session data if POST data is not available
$deliveryAddress = $_POST['delivery_address'] ?? $_SESSION['checkout_delivery_address'] ?? '';
$checkoutFullName = $_POST['full_name'] ?? $_SESSION['checkout_full_name'] ?? trim(($currentUser['firstName'] ?? '') . ' ' . ($currentUser['lastName'] ?? ''));
$checkoutPhone = $_POST['phone'] ?? $_SESSION['checkout_phone'] ?? ($currentUser['phone'] ?? '');
$checkoutNotes = $_POST['notes'] ?? $_SESSION['checkout_notes'] ?? '';
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
                                    <input type="text" name="full_name" required class="w-full border px-4 py-3 rounded-md" value="<?php echo htmlspecialchars($checkoutFullName); ?>">
                                </div>
                                
                                <div>
                                    <label class="block mb-2 font-semibold">Phone Number <span class="text-red-500">*</span></label>
                                    <input type="tel" name="phone" required class="w-full border px-4 py-3 rounded-md" value="<?php echo htmlspecialchars($checkoutPhone); ?>">
                                </div>
                                
                                <div>
                                    <label class="block mb-2 font-semibold">Delivery Address <span class="text-red-500">*</span></label>
                                    <textarea name="delivery_address" required rows="4" class="w-full border px-4 py-3 rounded-md" placeholder="Enter your complete delivery address"><?php echo htmlspecialchars($deliveryAddress); ?></textarea>
                                </div>
                                
                                <div>
                                    <label class="block mb-2 font-semibold">Notes (Optional)</label>
                                    <textarea name="notes" rows="3" class="w-full border px-4 py-3 rounded-md" placeholder="Any special instructions?"><?php echo htmlspecialchars($checkoutNotes); ?></textarea>
                                </div>
                            </div>
                            
                            <div class="mt-6 flex justify-end gap-4">
                                <a href="?step=1" class="px-6 py-2 rounded-lg font-medium border border-gray-300 hover:bg-gray-50">Back</a>
                                <button type="submit" class="px-6 py-2 rounded-lg font-medium bg-green-600 text-white hover:bg-green-700">Continue</button>
                            </div>
                        </section>
                    </form>
                <?php elseif ($step == 3): ?>
                    <?php
                    require_once __DIR__ . '/../../includes/stripe-payment.php';
                    $paymentMethods = [
                        'stripe' => ['label' => 'Stripe Payment', 'icon' => '💳', 'description' => 'Pay securely with credit/debit card'],
                        'card' => ['label' => 'Credit/Debit Card (COD)', 'icon' => '💵', 'description' => 'Pay on delivery'],
                        'bank' => ['label' => 'Bank Transfer', 'icon' => '🏦', 'description' => 'Direct bank transfer'],
                        'ussd' => ['label' => 'USSD', 'icon' => '📱', 'description' => 'Mobile money payment']
                    ];
                    $selectedMethod = $_POST['payment_method'] ?? 'stripe';
                    $showStripeForm = isset($_POST['show_stripe_form']) && $_POST['show_stripe_form'] == '1';
                    ?>
                    <form method="POST" id="paymentForm" action="?step=3">
                        <section class="bg-white rounded-lg p-6 shadow-sm">
                            <?php if (!$showStripeForm): ?>
                            <div class="mb-6">
                                <h3 class="text-xl font-semibold mb-4">Choose Payment Method</h3>
                                <div class="space-y-3">
                                    <?php foreach ($paymentMethods as $method => $info): ?>
                                        <label class="w-full block p-4 border-2 rounded-lg cursor-pointer transition-all <?php echo $selectedMethod === $method ? 'border-green-600 bg-green-50' : 'border-gray-200 hover:border-green-300'; ?>">
                                            <div class="flex items-center gap-4">
                                                <input type="radio" name="payment_method" value="<?php echo $method; ?>" required class="accent-green-600" <?php echo $selectedMethod === $method ? 'checked' : ''; ?> />
                                                <div class="flex-1">
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-2xl"><?php echo $info['icon']; ?></span>
                                                        <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($info['label']); ?></span>
                                                    </div>
                                                    <p class="text-sm text-gray-500 mt-1"><?php echo htmlspecialchars($info['description']); ?></p>
                                                </div>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Hidden fields for order data -->
                            <input type="hidden" name="delivery_address" value="<?php echo htmlspecialchars($deliveryAddress); ?>">
                            <input type="hidden" name="full_name" value="<?php echo htmlspecialchars($checkoutFullName); ?>">
                            <input type="hidden" name="phone" value="<?php echo htmlspecialchars($checkoutPhone); ?>">
                            <input type="hidden" name="notes" value="<?php echo htmlspecialchars($checkoutNotes); ?>">
                            <input type="hidden" name="step" value="3">
                            
                            <div class="flex justify-end gap-4">
                                <a href="?step=2" class="px-6 py-2 rounded-lg font-medium border border-gray-300 hover:bg-gray-50">Back</a>
                                <button type="submit" id="submitPaymentBtn" class="px-6 py-2 rounded-lg font-medium bg-green-600 text-white hover:bg-green-700">
                                    <?php echo $selectedMethod === 'stripe' ? 'Continue to Payment' : 'Place Order'; ?>
                                </button>
                            </div>
                            <?php else: ?>
                            <!-- Stripe Payment Form UI -->
                            <div class="max-w-2xl mx-auto">
                                <div class="mb-6">
                                    <button type="button" onclick="window.location.href='?step=3'" class="text-gray-600 hover:text-gray-800 mb-4 flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                        </svg>
                                        Back to Payment Methods
                                    </button>
                                    <h3 class="text-2xl font-bold text-gray-900 mb-2">Complete Payment</h3>
                                    <p class="text-gray-600">Total Amount: <span class="font-semibold text-green-600">$<?php echo number_format($total, 2); ?></span></p>
                                </div>
                                
                                <!-- Pay with Link Button -->
                                <div class="mb-6">
                                    <button type="button" id="payWithLinkBtn" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-4 px-6 rounded-lg flex items-center justify-center gap-3 transition-colors">
                                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                        </svg>
                                        Pay with link
                                    </button>
                                </div>
                                
                                <!-- OR Divider -->
                                <div class="relative mb-6">
                                    <div class="absolute inset-0 flex items-center">
                                        <div class="w-full border-t border-gray-300"></div>
                                    </div>
                                    <div class="relative flex justify-center text-sm">
                                        <span class="px-4 bg-white text-gray-500">OR</span>
                                    </div>
                                </div>
                                
                                <!-- Payment Form -->
                                <div class="space-y-5">
                                    <!-- Email Field -->
                                    <div>
                                        <label for="stripeEmail" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                        <input type="email" id="stripeEmail" name="stripe_email" value="<?php echo htmlspecialchars($currentUser['email'] ?? ''); ?>" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition">
                                    </div>
                                    
                                    <!-- Card Information -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Card information</label>
                                        <div id="stripeCardElement" class="w-full px-4 py-3 border border-gray-300 rounded-lg mb-3 bg-white">
                                            <!-- Stripe Card Element (Combined) -->
                                        </div>
                                        <div class="flex items-center gap-2 mt-2">
                                            <img src="https://js.stripe.com/v3/fingerprinting/img/v1/visa.svg" alt="Visa" class="h-6">
                                            <img src="https://js.stripe.com/v3/fingerprinting/img/v1/mastercard.svg" alt="Mastercard" class="h-6">
                                            <img src="https://js.stripe.com/v3/fingerprinting/img/v1/amex.svg" alt="American Express" class="h-6">
                                        </div>
                                    </div>
                                    
                                    <!-- Cardholder Name -->
                                    <div>
                                        <label for="stripeCardholderName" class="block text-sm font-medium text-gray-700 mb-2">Cardholder name</label>
                                        <input type="text" id="stripeCardholderName" name="stripe_cardholder_name" value="<?php echo htmlspecialchars(trim($userFirstName . ' ' . $userLastName)); ?>" required placeholder="Full name on card" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition">
                                    </div>
                                    
                                    <!-- Country or Region -->
                                    <div>
                                        <label for="stripeCountry" class="block text-sm font-medium text-gray-700 mb-2">Country or region</label>
                                        <select id="stripeCountry" name="stripe_country" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition bg-white">
                                            <option value="US">United States</option>
                                            <option value="PK" selected>Pakistan</option>
                                            <option value="GB">United Kingdom</option>
                                            <option value="CA">Canada</option>
                                            <option value="AU">Australia</option>
                                            <option value="IN">India</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Save Information Checkbox -->
                                    <div class="flex items-center">
                                        <input type="checkbox" id="saveStripeInfo" name="save_stripe_info" class="w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500">
                                        <label for="saveStripeInfo" class="ml-2 text-sm text-gray-700">Save my information for faster checkout</label>
                                    </div>
                                    
                                    <!-- Error Display -->
                                    <div id="stripeCardErrors" class="text-red-600 text-sm" role="alert"></div>
                                    
                                    <!-- Hidden fields -->
                                    <input type="hidden" id="stripePaymentMethodId" name="stripe_payment_method_id" />
                                    <input type="hidden" id="stripePaymentIntentId" name="stripe_payment_intent_id" />
                                    <input type="hidden" name="payment_method" value="stripe">
                                    <input type="hidden" name="show_stripe_form" value="1">
                                    <input type="hidden" name="step" value="3">
                                    <input type="hidden" name="delivery_address" value="<?php echo htmlspecialchars($deliveryAddress); ?>">
                                    <input type="hidden" name="full_name" value="<?php echo htmlspecialchars($checkoutFullName); ?>">
                                    <input type="hidden" name="phone" value="<?php echo htmlspecialchars($checkoutPhone); ?>">
                                    <input type="hidden" name="notes" value="<?php echo htmlspecialchars($checkoutNotes); ?>">
                                    
                                    <!-- Submit Button -->
                                    <button type="submit" id="submitStripePaymentBtn" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-4 px-6 rounded-lg transition-colors">
                                        Pay $<?php echo number_format($total, 2); ?>
                                    </button>
                                </div>
                            </div>
                            <?php endif; ?>
                        </section>
                    </form>
                    
                    <script src="https://js.stripe.com/v3/"></script>
                    <script>
                    <?php if ($showStripeForm && $selectedMethod === 'stripe'): ?>
                    // Initialize Stripe Elements for separate card fields
                    const stripe = Stripe('<?php echo STRIPE_PUBLISHABLE_KEY; ?>');
                    const elements = stripe.elements();
                    
                    // Card styling
                    const elementStyles = {
                        base: {
                            fontSize: '16px',
                            color: '#32325d',
                            fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                            '::placeholder': {
                                color: '#aab7c4'
                            }
                        },
                        invalid: {
                            color: '#fa755a',
                            iconColor: '#fa755a'
                        }
                    };
                    
                    // Create combined card element (includes number, expiry, CVC)
                    const cardElement = elements.create('card', {
                        style: elementStyles,
                        hidePostalCode: true // Hide ZIP as requested
                    });
                    
                    // Mount card element
                    try {
                        cardElement.mount('#stripeCardElement');
                    } catch (error) {
                        console.error('Error mounting Stripe element:', error);
                        displayError.textContent = 'Error loading payment form. Please refresh the page.';
                    }
                    
                    // Handle errors
                    const displayError = document.getElementById('stripeCardErrors');
                    
                    cardElement.on('change', function(event) {
                        if (event.error) {
                            displayError.textContent = event.error.message;
                        } else {
                            displayError.textContent = '';
                        }
                    });
                    
                    // Handle form submission
                    document.getElementById('paymentForm').addEventListener('submit', async function(e) {
                        e.preventDefault();
                        
                        const submitBtn = document.getElementById('submitStripePaymentBtn');
                        submitBtn.disabled = true;
                        submitBtn.textContent = 'Processing...';
                        displayError.textContent = '';
                        
                        try {
                            // Create payment method using the combined card element
                            const {paymentMethod: pm, error: pmError} = await stripe.createPaymentMethod({
                                type: 'card',
                                card: cardElement,
                                billing_details: {
                                    name: document.getElementById('stripeCardholderName').value.trim(),
                                    email: document.getElementById('stripeEmail').value.trim(),
                                    address: {
                                        country: document.getElementById('stripeCountry').value
                                    }
                                }
                            });
                            
                            if (pmError) {
                                displayError.textContent = pmError.message;
                                submitBtn.disabled = false;
                                submitBtn.textContent = 'Pay $<?php echo number_format($total, 2); ?>';
                                return;
                            }
                            
                            // Create payment intent
                            const response = await fetch('<?php echo BASE_PATH; ?>/includes/stripe-create-intent.php', {
                                method: 'POST',
                                headers: {'Content-Type': 'application/json'},
                                body: JSON.stringify({
                                    amount: <?php echo $total; ?>,
                                    payment_method_id: pm.id
                                })
                            });
                            
                            const data = await response.json();
                            
                            if (data.error) {
                                displayError.textContent = data.error;
                                submitBtn.disabled = false;
                                submitBtn.textContent = 'Pay $<?php echo number_format($total, 2); ?>';
                                return;
                            }
                            
                            // Confirm payment intent
                            const {paymentIntent, error: confirmError} = await stripe.confirmCardPayment(data.client_secret, {
                                payment_method: pm.id
                            });
                            
                            if (confirmError) {
                                displayError.textContent = confirmError.message;
                                submitBtn.disabled = false;
                                submitBtn.textContent = 'Pay $<?php echo number_format($total, 2); ?>';
                                return;
                            }
                            
                            if (paymentIntent.status === 'succeeded') {
                                // Set payment method and intent IDs
                                document.getElementById('stripePaymentMethodId').value = pm.id;
                                document.getElementById('stripePaymentIntentId').value = paymentIntent.id;
                                
                                // Show success message
                                displayError.textContent = '';
                                submitBtn.textContent = 'Processing Order...';
                                
                                // Submit form to create order
                                // Ensure all required fields are present
                                if (!this.querySelector('input[name="step"]')) {
                                    const stepInput = document.createElement('input');
                                    stepInput.type = 'hidden';
                                    stepInput.name = 'step';
                                    stepInput.value = '3';
                                    this.appendChild(stepInput);
                                }
                                
                                // Change form action to order creation endpoint
                                this.action = '<?php echo BASE_PATH; ?>/includes/create-order.php';
                                this.method = 'POST';
                                
                                // Submit the form
                                this.submit();
                            } else {
                                displayError.textContent = 'Payment not completed. Status: ' + paymentIntent.status;
                                submitBtn.disabled = false;
                                submitBtn.textContent = 'Pay $<?php echo number_format($total, 2); ?>';
                            }
                            
                        } catch (error) {
                            displayError.textContent = 'An error occurred: ' + error.message;
                            submitBtn.disabled = false;
                            submitBtn.textContent = 'Pay $<?php echo number_format($total, 2); ?>';
                        }
                    });
                    
                    // Pay with Link button (placeholder)
                    document.getElementById('payWithLinkBtn').addEventListener('click', function() {
                        alert('Pay with Link feature coming soon! Please use card payment.');
                    });
                    <?php else: ?>
                    // Handle payment method selection and show Stripe form
                    document.getElementById('paymentForm').addEventListener('submit', function(e) {
                        const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
                        
                        if (paymentMethod && paymentMethod.value === 'stripe') {
                            e.preventDefault();
                            
                            // Check if show_stripe_form already exists
                            let existingInput = this.querySelector('input[name="show_stripe_form"]');
                            if (!existingInput) {
                                // Add hidden field to show Stripe form
                                const input = document.createElement('input');
                                input.type = 'hidden';
                                input.name = 'show_stripe_form';
                                input.value = '1';
                                this.appendChild(input);
                            }
                            
                            // Ensure step=3 is in the action
                            this.action = '?step=3';
                            
                            // Submit form to reload page with Stripe form
                            this.submit();
                        } else {
                            // For non-Stripe payments, submit directly to create-order.php
                            e.preventDefault();
                            
                            // Ensure all required fields are present before submitting
                            const deliveryAddress = this.querySelector('input[name="delivery_address"]');
                            const fullName = this.querySelector('input[name="full_name"]');
                            const phone = this.querySelector('input[name="phone"]');
                            
                            // Validate required fields
                            if (!deliveryAddress || !deliveryAddress.value.trim()) {
                                alert('Please provide a delivery address');
                                window.location.href = '?step=2';
                                return;
                            }
                            
                            if (!fullName || !fullName.value.trim()) {
                                alert('Please provide your full name');
                                window.location.href = '?step=2';
                                return;
                            }
                            
                            if (!phone || !phone.value.trim()) {
                                alert('Please provide your phone number');
                                window.location.href = '?step=2';
                                return;
                            }
                            
                            // Change form action to order creation endpoint
                            this.action = '<?php echo BASE_PATH; ?>/includes/create-order.php';
                            this.method = 'POST';
                            
                            // Submit the form
                            this.submit();
                        }
                    });
                    <?php endif; ?>
                    </script>
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

