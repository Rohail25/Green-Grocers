<?php
/**
 * Test Script to Verify VendorId/ClientId Generation
 * 
 * This script helps you test and verify that vendorId/clientId
 * are being generated and stored correctly.
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Start session
session_start();

echo "<h2>Vendor ID / Client ID Test</h2>";

// Test 1: Check existing users
echo "<h3>1. Existing Users in Database</h3>";
$conn = getDBConnection();
$stmt = $conn->query("
    SELECT id, email, platform, vendorId, clientId, role, created_at 
    FROM users 
    ORDER BY created_at DESC 
    LIMIT 10
");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($users)) {
    echo "<p>No users found in database. Register a user first!</p>";
} else {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr>
            <th>Email</th>
            <th>Platform</th>
            <th>Vendor ID</th>
            <th>Client ID</th>
            <th>Role</th>
            <th>Created</th>
          </tr>";
    
    foreach ($users as $user) {
        $vendorId = $user['vendorId'] ?: '<span style="color: gray;">NULL</span>';
        $clientId = $user['clientId'] ?: '<span style="color: gray;">NULL</span>';
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
        echo "<td>" . htmlspecialchars($user['platform']) . "</td>";
        echo "<td><strong>" . htmlspecialchars($vendorId) . "</strong></td>";
        echo "<td><strong>" . htmlspecialchars($clientId) . "</strong></td>";
        echo "<td>" . htmlspecialchars($user['role']) . "</td>";
        echo "<td>" . htmlspecialchars($user['created_at']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Test 2: Statistics
echo "<h3>2. Statistics</h3>";
$stats = $conn->query("
    SELECT 
        COUNT(*) as total_users,
        COUNT(vendorId) as users_with_vendorId,
        COUNT(clientId) as users_with_clientId,
        COUNT(CASE WHEN platform = 'trivestore' THEN 1 END) as trivestore_users,
        COUNT(CASE WHEN platform = 'trivemart' THEN 1 END) as trivemart_users
    FROM users
")->fetch(PDO::FETCH_ASSOC);

echo "<ul>";
echo "<li><strong>Total Users:</strong> " . $stats['total_users'] . "</li>";
echo "<li><strong>Users with Vendor ID:</strong> " . $stats['users_with_vendorId'] . "</li>";
echo "<li><strong>Users with Client ID:</strong> " . $stats['users_with_clientId'] . "</li>";
echo "<li><strong>Trivestore Users:</strong> " . $stats['trivestore_users'] . "</li>";
echo "<li><strong>Trivemart Users:</strong> " . $stats['trivemart_users'] . "</li>";
echo "</ul>";

// Test 3: Check current session
echo "<h3>3. Current Session (If Logged In)</h3>";
if (isAuthenticated()) {
    $user = getCurrentUser();
    echo "<ul>";
    echo "<li><strong>User ID:</strong> " . htmlspecialchars($user['id']) . "</li>";
    echo "<li><strong>Email:</strong> " . htmlspecialchars($user['email']) . "</li>";
    echo "<li><strong>Platform:</strong> " . htmlspecialchars($user['platform']) . "</li>";
    echo "<li><strong>Vendor ID:</strong> " . ($user['vendorId'] ?: '<span style="color: gray;">Not set</span>') . "</li>";
    echo "<li><strong>Client ID:</strong> " . ($user['clientId'] ?: '<span style="color: gray;">Not set</span>') . "</li>";
    echo "<li><strong>Role:</strong> " . htmlspecialchars($user['role']) . "</li>";
    echo "</ul>";
} else {
    echo "<p>Not logged in. <a href='auth/login.php'>Login</a> to see session data.</p>";
}

// Test 4: Test ID Generation (without saving)
echo "<h3>4. Test ID Generation (Preview)</h3>";
echo "<p>This shows what IDs would be generated (not saved to database):</p>";
echo "<ul>";
echo "<li><strong>Trivestore Vendor ID:</strong> VEND-" . strtoupper(substr(uniqid(), -8)) . "</li>";
echo "<li><strong>Trivemart Client ID:</strong> MART-" . strtoupper(substr(uniqid(), -8)) . "</li>";
echo "</ul>";

// Test 5: Verification Check
echo "<h3>5. Verification Check</h3>";
$issues = [];

// Check if trivestore users have vendorId
$trivestoreWithoutVendor = $conn->query("
    SELECT COUNT(*) as count 
    FROM users 
    WHERE platform = 'trivestore' AND (vendorId IS NULL OR vendorId = '')
")->fetch(PDO::FETCH_ASSOC);

if ($trivestoreWithoutVendor['count'] > 0) {
    $issues[] = "⚠️ Found " . $trivestoreWithoutVendor['count'] . " trivestore user(s) without vendorId";
}

// Check if trivemart users have clientId
$trivemartWithoutClient = $conn->query("
    SELECT COUNT(*) as count 
    FROM users 
    WHERE platform = 'trivemart' AND (clientId IS NULL OR clientId = '')
")->fetch(PDO::FETCH_ASSOC);

if ($trivemartWithoutClient['count'] > 0) {
    $issues[] = "⚠️ Found " . $trivemartWithoutClient['count'] . " trivemart user(s) without clientId";
}

if (empty($issues)) {
    echo "<p style='color: green;'>✅ All users have correct IDs assigned!</p>";
} else {
    echo "<ul>";
    foreach ($issues as $issue) {
        echo "<li style='color: orange;'>" . $issue . "</li>";
    }
    echo "</ul>";
}

echo "<hr>";
echo "<p><a href='auth/register.php'>Register New User</a> | <a href='index.php'>Home</a></p>";
?>

