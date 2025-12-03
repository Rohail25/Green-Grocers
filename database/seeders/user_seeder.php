<?php
/**
 * User Seeder - Creates test users with Vendor IDs and Client IDs
 * 
 * Usage: Run this file to seed the database with test users
 * 
 * This seeder creates:
 * - Trivestore users (with vendorId)
 * - Trivemart users (with clientId)
 * - Admin users
 * - Logistic users
 * - Vendor users
 */

require_once __DIR__ . '/../../config/database.php';

echo "🌱 Starting User Seeder...\n\n";

$conn = getDBConnection();

// Function to generate vendorId/clientId (same as in auth.php)
function generateVendorId() {
    return 'VEND-' . strtoupper(substr(uniqid(), -8));
}

function generateClientId() {
    return 'MART-' . strtoupper(substr(uniqid(), -8));
}

// Test users data - Passwords will be hashed during insertion
$users = [
    // Trivestore Users (with vendorId)
    [
        'email' => 'vendor1@trivestore.com',
        'phone' => '+1234567890',
        'password' => 'password123', // Plain password - will be hashed
        'firstName' => 'John',
        'lastName' => 'Vendor',
        'role' => 'vendor',
        'platform' => 'trivestore',
        'vendorId' => generateVendorId(),
        'clientId' => null,
        'isEmailConfirmed' => true,
        'isVerified' => true,
    ],
    [
        'email' => 'vendor2@trivestore.com',
        'phone' => '+1234567891',
        'password' => 'password123',
        'firstName' => 'Jane',
        'lastName' => 'Store',
        'role' => 'vendor',
        'platform' => 'trivestore',
        'vendorId' => generateVendorId(),
        'clientId' => null,
        'isEmailConfirmed' => true,
        'isVerified' => true,
    ],
    [
        'email' => 'customer1@trivestore.com',
        'phone' => '+1234567892',
        'password' => 'password123',
        'firstName' => 'Bob',
        'lastName' => 'Customer',
        'role' => 'customer',
        'platform' => 'trivestore',
        'vendorId' => generateVendorId(),
        'clientId' => null,
        'isEmailConfirmed' => true,
        'isVerified' => true,
    ],
    
    // Trivemart Users (with clientId)
    [
        'email' => 'client1@trivemart.com',
        'phone' => '+1234567893',
        'password' => 'password123',
        'firstName' => 'Alice',
        'lastName' => 'Client',
        'role' => 'customer',
        'platform' => 'trivemart',
        'vendorId' => null,
        'clientId' => generateClientId(),
        'isEmailConfirmed' => true,
        'isVerified' => true,
    ],
    [
        'email' => 'client2@trivemart.com',
        'phone' => '+1234567894',
        'password' => 'password123',
        'firstName' => 'Charlie',
        'lastName' => 'Mart',
        'role' => 'customer',
        'platform' => 'trivemart',
        'vendorId' => null,
        'clientId' => generateClientId(),
        'isEmailConfirmed' => true,
        'isVerified' => true,
    ],
    
    // Admin Users
    [
        'email' => 'admin@green-grocers.com',
        'phone' => '+1234567895',
        'password' => 'admin123',
        'firstName' => 'Admin',
        'lastName' => 'User',
        'role' => 'admin',
        'platform' => 'trivemart',
        'vendorId' => null,
        'clientId' => generateClientId(),
        'isEmailConfirmed' => true,
        'isVerified' => true,
    ],
    
    // Logistic Users
    [
        'email' => 'logistic1@green-grocers.com',
        'phone' => '+1234567896',
        'password' => 'password123',
        'firstName' => 'Logistic',
        'lastName' => 'Driver',
        'role' => 'logistic',
        'platform' => 'trivemart',
        'vendorId' => null,
        'clientId' => generateClientId(),
        'isEmailConfirmed' => true,
        'isVerified' => false, // Logistic needs admin approval
        'documentsUploaded' => true,
    ],
];

// Insert users
$inserted = 0;
$skipped = 0;
$errors = [];

foreach ($users as $userData) {
    try {
        // Check if user already exists
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = :email AND platform = :platform");
        $checkStmt->execute([
            ':email' => strtolower(trim($userData['email'])),
            ':platform' => $userData['platform']
        ]);
        
        if ($checkStmt->fetch()) {
            echo "⏭️  Skipped: {$userData['email']} (already exists)\n";
            $skipped++;
            continue;
        }
        
        // Hash password (same method as in auth.php)
        $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
        
        // Insert user
        $stmt = $conn->prepare("
            INSERT INTO users (
                id, email, phone, password, firstName, lastName, role, platform,
                vendorId, clientId, isEmailConfirmed, isVerified,
                verificationDocuments, preferredVendors, addresses,
                documentsUploaded, created_at, updated_at
            ) VALUES (
                UUID(), :email, :phone, :password, :firstName, :lastName, :role, :platform,
                :vendorId, :clientId, :isEmailConfirmed, :isVerified,
                '[]', '[]', '[]',
                :documentsUploaded, NOW(), NOW()
            )
        ");
        
        $stmt->execute([
            ':email' => strtolower(trim($userData['email'])),
            ':phone' => $userData['phone'],
            ':password' => $hashedPassword,
            ':firstName' => $userData['firstName'],
            ':lastName' => $userData['lastName'],
            ':role' => $userData['role'],
            ':platform' => $userData['platform'],
            ':vendorId' => $userData['vendorId'],
            ':clientId' => $userData['clientId'],
            ':isEmailConfirmed' => $userData['isEmailConfirmed'] ? 1 : 0,
            ':isVerified' => $userData['isVerified'] ? 1 : 0,
            ':documentsUploaded' => isset($userData['documentsUploaded']) ? ($userData['documentsUploaded'] ? 1 : 0) : 0,
        ]);
        
        $inserted++;
        $vendorIdDisplay = $userData['vendorId'] ? " | Vendor ID: {$userData['vendorId']}" : '';
        $clientIdDisplay = $userData['clientId'] ? " | Client ID: {$userData['clientId']}" : '';
        
        echo "✅ Created: {$userData['email']} ({$userData['platform']}){$vendorIdDisplay}{$clientIdDisplay}\n";
        
    } catch (PDOException $e) {
        $errorMsg = "❌ Error creating {$userData['email']}: " . $e->getMessage();
        echo $errorMsg . "\n";
        $errors[] = $errorMsg;
    }
}

// Summary
echo "\n" . str_repeat("=", 60) . "\n";
echo "📊 Seeder Summary:\n";
echo str_repeat("=", 60) . "\n";
echo "✅ Inserted: {$inserted} users\n";
echo "⏭️  Skipped: {$skipped} users (already exist)\n";
echo "❌ Errors: " . count($errors) . "\n";

if (!empty($errors)) {
    echo "\nErrors:\n";
    foreach ($errors as $error) {
        echo "  - {$error}\n";
    }
}

// Display created users with IDs
echo "\n" . str_repeat("=", 60) . "\n";
echo "📋 Created Users with IDs:\n";
echo str_repeat("=", 60) . "\n";

$displayStmt = $conn->query("
    SELECT email, platform, role, vendorId, clientId, isEmailConfirmed 
    FROM users 
    WHERE email LIKE '%@trivestore.com' 
       OR email LIKE '%@trivemart.com' 
       OR email LIKE '%@green-grocers.com'
    ORDER BY platform, role
");

$displayUsers = $displayStmt->fetchAll(PDO::FETCH_ASSOC);

echo sprintf("%-30s %-12s %-10s %-15s %-15s %-5s\n", 
    "Email", "Platform", "Role", "Vendor ID", "Client ID", "Confirmed");
echo str_repeat("-", 100) . "\n";

foreach ($displayUsers as $user) {
    $vendorId = $user['vendorId'] ?: 'NULL';
    $clientId = $user['clientId'] ?: 'NULL';
    $confirmed = $user['isEmailConfirmed'] ? 'YES' : 'NO';
    
    echo sprintf("%-30s %-12s %-10s %-15s %-15s %-5s\n",
        $user['email'],
        $user['platform'],
        $user['role'],
        $vendorId,
        $clientId,
        $confirmed
    );
}

echo "\n✅ Seeder completed!\n";
echo "\n💡 Test Login Credentials:\n";
echo "   - Admin: admin@green-grocers.com / admin123 (Platform: trivemart)\n";
echo "   - Vendor: vendor1@trivestore.com / password123 (Platform: trivestore)\n";
echo "   - Customer: client1@trivemart.com / password123 (Platform: trivemart)\n";
echo "\n⚠️  IMPORTANT: Make sure to select the correct platform when logging in!\n";
?>
