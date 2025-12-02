<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAuth();
$currentUser = getCurrentUser();
$pageTitle = 'Customer Dashboard';
?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="min-h-screen bg-gray-100 p-8 mt-24">
    <h1 class="text-3xl font-bold text-gray-800">Customer Dashboard</h1>
    <p class="text-gray-600 mt-4">Welcome, <?php echo htmlspecialchars($currentUser['firstName'] ?? 'User'); ?>!</p>
    <p class="text-gray-600 mt-2">This is your customer dashboard.</p>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

