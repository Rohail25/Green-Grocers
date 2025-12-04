<?php
/**
 * User Seeder - Creates test users with Vendor IDs and Client IDs
 * 
 * Usage: Run this file to seed the database with test users
 * 
 * This seeder creates:
 * - Vendor users (with vendorId) - Gmail
 * - Customer users (with clientId) - Gmail
 * - Admin users - Gmail
 * - Logistic users - Gmail
 */

require_once __DIR__ . '/../../config/database.php';

echo "🌱 Starting User Seeder...\n\n";

// Test database connection
try {
    $conn = getDBConnection();
    echo "✅ Database connection successful\n";
    echo "📊 Database: " . DB_NAME . "\n";
    
    // Check if users table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'users'");
    if ($tableCheck->rowCount() == 0) {
        echo "❌ ERROR: 'users' table does not exist in database!\n";
        echo "   Please create the users table first.\n";
        exit(1);
    }
    echo "✅ Users table exists\n\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Function to generate UUID (for user ID)
function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

// Function to generate vendorId/clientId (same as in auth.php)
function generateVendorId() {
    return 'VEND-' . strtoupper(substr(uniqid(), -8));
}

function generateClientId() {
    return 'MART-' . strtoupper(substr(uniqid(), -8));
}

// Test users data - Passwords will be hashed during insertion
$users = [
    // Vendor Users (with vendorId) - Gmail
  
    [
        'email' => 'vendor@gmail.com',
        'phone' => '+1234567891',
        'password' => 'password123',
        'firstName' => 'Jane',
        'lastName' => 'Store',
        'role' => 'vendor',
        'platform' => 'trivemart',
        'vendorId' => generateVendorId(),
        'clientId' => null,
        'isEmailConfirmed' => true,
        'isVerified' => true,
    ],
    
    // Customer Users (with clientId) - Gmail
    [
        'email' => 'customer@gmail.com',
        'phone' => '+1234567892',
        'password' => 'password123',
        'firstName' => 'Bob',
        'lastName' => 'Customer',
        'role' => 'customer',
        'platform' => 'trivemart',
        'vendorId' => null,
        'clientId' => generateClientId(),
        'isEmailConfirmed' => true,
        'isVerified' => true,
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
        
        // Generate UUID for user ID
        $userId = generateUUID();
        
        // Insert user
        $stmt = $conn->prepare("
            INSERT INTO users (
                id, email, phone, password, firstName, lastName, role, platform,
                vendorId, clientId, isEmailConfirmed, isVerified,
                verificationDocuments, preferredVendors, addresses,
                documentsUploaded, created_at, updated_at
            ) VALUES (
                :id, :email, :phone, :password, :firstName, :lastName, :role, :platform,
                :vendorId, :clientId, :isEmailConfirmed, :isVerified,
                '[]', '[]', '[]',
                :documentsUploaded, NOW(), NOW()
            )
        ");
        
        $result = $stmt->execute([
            ':id' => $userId,
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
        
        if (!$result) {
            throw new Exception("Insert failed for {$userData['email']}");
        }
        
        $inserted++;
        $vendorIdDisplay = $userData['vendorId'] ? " | Vendor ID: {$userData['vendorId']}" : '';
        $clientIdDisplay = $userData['clientId'] ? " | Client ID: {$userData['clientId']}" : '';
        
        echo "✅ Created: {$userData['email']} ({$userData['platform']}){$vendorIdDisplay}{$clientIdDisplay}\n";
        
    } catch (PDOException $e) {
        $errorMsg = "❌ Error creating {$userData['email']}: " . $e->getMessage();
        echo $errorMsg . "\n";
        echo "   SQL Error Code: " . $e->getCode() . "\n";
        if ($stmt->errorInfo()) {
            echo "   SQL Error Info: " . print_r($stmt->errorInfo(), true) . "\n";
        }
        $errors[] = $errorMsg;
    } catch (Exception $e) {
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
    WHERE email LIKE '%@gmail.com'
    ORDER BY role, email
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
echo "   - Admin: admin@gmail.com / admin123 (Platform: trivemart)\n";
echo "   - Vendor: vendor1@gmail.com / password123 (Platform: trivemart)\n";
echo "   - Customer: customer1@gmail.com / password123 (Platform: trivemart)\n";
echo "   - Logistic: logistic1@gmail.com / password123 (Platform: trivemart)\n";
echo "\n⚠️  IMPORTANT: All users use trivemart platform with gmail.com emails!\n";
?>
