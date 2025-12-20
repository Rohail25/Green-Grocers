<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

// Admin only access
requireAuth();
$currentUser = getCurrentUser();
if ($currentUser['role'] !== 'admin') {
    header('Location: ' . BASE_PATH . '/dashboard/pages/dashboard.php');
    exit;
}

$pageTitle = 'Change Password';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['newPassword'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    
    if (empty($newPassword) || empty($confirmPassword)) {
        $error = 'Both password fields are required';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match';
    } elseif (strlen($newPassword) < 6) {
        $error = 'Password must be at least 6 characters long';
    } else {
        // Use the updatePassword function but admin doesn't need current password
        $conn = getDBConnection();
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = :password, updated_at = NOW() WHERE id = :id");
        $stmt->execute([
            ':password' => $hashedPassword,
            ':id' => $currentUser['id']
        ]);
        
        $message = 'Password changed successfully';
    }
}
?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="space-y-6">
    <h2 class="text-3xl font-bold text-gray-800">Change Password</h2>

    <?php if ($message): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="bg-white shadow rounded-lg p-6 max-w-md">
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1">New Password *</label>
                <input type="password" name="newPassword" required minlength="6" class="w-full px-3 py-2 border rounded-md">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Confirm Password *</label>
                <input type="password" name="confirmPassword" required minlength="6" class="w-full px-3 py-2 border rounded-md">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">Change Password</button>
                <a href="<?php echo BASE_PATH; ?>/dashboard/pages/dashboard.php" class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 text-center">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

