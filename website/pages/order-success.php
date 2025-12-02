<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAuth();
$pageTitle = 'Order Placed Successfully';

$orderNumber = $_GET['order'] ?? '';
?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="min-h-screen bg-gray-50 mt-24 flex items-center justify-center px-4">
    <div class="max-w-2xl w-full bg-white rounded-lg shadow-lg p-8 text-center">
        <div class="mb-6">
            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Order Placed Successfully!</h1>
            <p class="text-gray-600">Thank you for your order. We'll process it shortly.</p>
        </div>
        
        <?php if ($orderNumber): ?>
            <div class="bg-gray-50 rounded-lg p-6 mb-6">
                <p class="text-sm text-gray-600 mb-2">Order Number</p>
                <p class="text-2xl font-bold text-green-600"><?php echo htmlspecialchars($orderNumber); ?></p>
            </div>
        <?php endif; ?>
        
        <div class="space-y-4">
            <a href="<?php echo BASE_PATH; ?>/" class="inline-block w-full md:w-auto px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold">
                Continue Shopping
            </a>
            <a href="<?php echo BASE_PATH; ?>/customer/dashboard.php" class="inline-block w-full md:w-auto px-6 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 font-semibold">
                View My Orders
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

