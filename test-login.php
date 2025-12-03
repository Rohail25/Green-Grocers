<?php
/**
 * Test Login - Verify seeder users can login
 * 
 * This script helps you test if seeder-created users can login correctly
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

session_start();

echo "<!DOCTYPE html>
<html>
<head>
    <title>Test Login</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 800px; margin: 0 auto; }
        .test-section { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #17a2b8; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        th { background: #007bff; color: white; }
        .test-btn { padding: 10px 20px; margin: 5px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .test-btn:hover { background: #218838; }
    </style>
</head>
<body>
    <h1>🔐 Test Login - Seeder Users</h1>";

$conn = getDBConnection();

// Get all seeder users
$stmt = $conn->query("
    SELECT email, platform, role, isEmailConfirmed, isVerified, vendorId, clientId
    FROM users 
    WHERE email LIKE '%@trivestore.com' 
       OR email LIKE '%@trivemart.com' 
       OR email LIKE '%@green-grocers.com'
    ORDER BY platform, role
");

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<div class='test-section'>";
echo "<h2>📋 Seeder Users</h2>";
echo "<table>";
echo "<tr><th>Email</th><th>Platform</th><th>Role</th><th>Email Confirmed</th><th>Verified</th><th>Vendor ID</th><th>Client ID</th></tr>";

foreach ($users as $user) {
    $confirmed = $user['isEmailConfirmed'] ? '✅' : '❌';
    $verified = $user['isVerified'] ? '✅' : '❌';
    $vendorId = $user['vendorId'] ?: '-';
    $clientId = $user['clientId'] ?: '-';
    
    echo "<tr>";
    echo "<td>" . htmlspecialchars($user['email']) . "</td>";
    echo "<td>" . htmlspecialchars($user['platform']) . "</td>";
    echo "<td>" . htmlspecialchars($user['role']) . "</td>";
    echo "<td>$confirmed</td>";
    echo "<td>$verified</td>";
    echo "<td>" . htmlspecialchars($vendorId) . "</td>";
    echo "<td>" . htmlspecialchars($clientId) . "</td>";
    echo "</tr>";
}

echo "</table>";
echo "</div>";

// Test login for each user
echo "<div class='test-section'>";
echo "<h2>🧪 Test Login</h2>";

$testPasswords = [
    'admin@green-grocers.com' => 'admin123',
    'vendor1@trivestore.com' => 'password123',
    'vendor2@trivestore.com' => 'password123',
    'customer1@trivestore.com' => 'password123',
    'client1@trivemart.com' => 'password123',
    'client2@trivemart.com' => 'password123',
    'logistic1@green-grocers.com' => 'password123',
];

$testResults = [];

foreach ($users as $user) {
    $email = $user['email'];
    $platform = $user['platform'];
    $password = $testPasswords[$email] ?? 'password123';
    
    // Test login
    $result = loginUser($email, $password, $platform);
    
    $testResults[] = [
        'email' => $email,
        'platform' => $platform,
        'password' => $password,
        'success' => $result,
        'error' => $_SESSION['error'] ?? null
    ];
    
    // Clear session error
    unset($_SESSION['error']);
}

echo "<table>";
echo "<tr><th>Email</th><th>Platform</th><th>Password</th><th>Result</th><th>Error</th></tr>";

foreach ($testResults as $test) {
    $status = $test['success'] ? '<span class="success">✅ SUCCESS</span>' : '<span class="error">❌ FAILED</span>';
    $error = $test['error'] ? '<span class="error">' . htmlspecialchars($test['error']) . '</span>' : '-';
    
    echo "<tr>";
    echo "<td>" . htmlspecialchars($test['email']) . "</td>";
    echo "<td>" . htmlspecialchars($test['platform']) . "</td>";
    echo "<td>" . htmlspecialchars($test['password']) . "</td>";
    echo "<td>$status</td>";
    echo "<td>$error</td>";
    echo "</tr>";
}

echo "</table>";
echo "</div>";

// Login form for manual testing
echo "<div class='test-section'>";
echo "<h2>🔑 Manual Login Test</h2>";
echo "<form method='POST' action='auth/login.php' style='max-width: 400px;'>";
echo "<div style='margin: 10px 0;'>";
echo "<label>Email:</label><br>";
echo "<input type='email' name='email' value='admin@green-grocers.com' style='width: 100%; padding: 8px;' required>";
echo "</div>";
echo "<div style='margin: 10px 0;'>";
echo "<label>Password:</label><br>";
echo "<input type='password' name='password' value='admin123' style='width: 100%; padding: 8px;' required>";
echo "</div>";
echo "<div style='margin: 10px 0;'>";
echo "<label>Platform:</label><br>";
echo "<select name='platform' style='width: 100%; padding: 8px;'>";
echo "<option value='trivemart'>Trivemart</option>";
echo "<option value='trivestore'>Trivestore</option>";
echo "</select>";
echo "</div>";
echo "<button type='submit' class='test-btn'>Test Login</button>";
echo "</form>";
echo "</div>";

echo "<p><a href='index.php'>← Back to Home</a></p>";
echo "</body></html>";
?>

