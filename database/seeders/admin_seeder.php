<?php
/**
 * Admin Seeder - Creates admin user with specific credentials
 * 
 * Usage: php database/seeders/admin_seeder.php
 * 
 * Creates admin user:
 * - Email: admin@example.com
 * - Password: admin123
 * - Role: admin
 * - Platform: trivemart
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/encryption.php';

echo "🌱 Starting Admin Seeder...\n\n";

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

// Admin user data
$adminEmail = 'admin@example.com';
$adminPassword = 'admin123';
$normalizedEmail = strtolower(trim($adminEmail));

// Encrypt email (using deterministic encryption)
$encryptedEmail = encryptEmail($normalizedEmail);

echo "📝 Admin Credentials:\n";
echo "   Email: {$adminEmail}\n";
echo "   Password: {$adminPassword}\n";
echo "   Encrypted Email: " . substr($encryptedEmail, 0, 50) . "...\n\n";

try {
    // Check if admin already exists (try both encrypted and plain text)
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
    $checkStmt->execute([':email' => $encryptedEmail]);
    $existingUser = $checkStmt->fetch();
    
    // If not found with encrypted, try plain text
    if (!$existingUser) {
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
        $checkStmt->execute([':email' => $normalizedEmail]);
        $existingUser = $checkStmt->fetch();
    }
    
    if ($existingUser) {
        echo "⚠️  Admin user already exists!\n";
        echo "   Updating existing admin user...\n\n";
        
        // Update existing admin
        $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
        
        $updateStmt = $conn->prepare("
            UPDATE users SET 
                email = :email,
                password = :password,
                firstName = :firstName,
                lastName = :lastName,
                role = :role,
                platform = :platform,
                isEmailConfirmed = :isEmailConfirmed,
                isVerified = :isVerified,
                updated_at = NOW()
            WHERE id = :id
        ");
        
        $updateStmt->execute([
            ':id' => $existingUser['id'],
            ':email' => $encryptedEmail,
            ':password' => $hashedPassword,
            ':firstName' => 'Admin',
            ':lastName' => 'User',
            ':role' => 'admin',
            ':platform' => 'trivemart',
            ':isEmailConfirmed' => 1,
            ':isVerified' => 1
        ]);
        
        echo "✅ Admin user updated successfully!\n";
        echo "   User ID: " . $existingUser['id'] . "\n";
    } else {
        // Create new admin user
        echo "📦 Creating new admin user...\n\n";
        
        // Hash password
        $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
        
        // Generate UUID for user ID
        $userId = generateUUID();
        
        // Insert admin user
        $stmt = $conn->prepare("
            INSERT INTO users (
                id, email, phone, password, firstName, lastName, role, platform,
                vendorId, clientId, isEmailConfirmed, isVerified,
                verificationDocuments, preferredVendors, addresses,
                created_at, updated_at
            ) VALUES (
                :id, :email, :phone, :password, :firstName, :lastName, :role, :platform,
                :vendorId, :clientId, :isEmailConfirmed, :isVerified,
                '[]', '[]', '[]',
                NOW(), NOW()
            )
        ");
        
        $result = $stmt->execute([
            ':id' => $userId,
            ':email' => $encryptedEmail, // Store encrypted email
            ':phone' => '', // No phone for admin
            ':password' => $hashedPassword,
            ':firstName' => 'Admin',
            ':lastName' => 'User',
            ':role' => 'admin',
            ':platform' => 'trivemart',
            ':vendorId' => null,
            ':clientId' => null,
            ':isEmailConfirmed' => 1, // Email confirmed
            ':isVerified' => 1, // Verified
        ]);
        
        if (!$result) {
            throw new Exception("Insert failed for admin user");
        }
        
        echo "✅ Admin user created successfully!\n";
        echo "   User ID: {$userId}\n";
    }
    
    // Verify the admin user can be found
    echo "\n🔍 Verifying admin user...\n";
    
    // Test finding with encrypted email
    $verifyStmt = $conn->prepare("SELECT id, email, role, platform, isEmailConfirmed FROM users WHERE email = :email");
    $verifyStmt->execute([':email' => $encryptedEmail]);
    $admin = $verifyStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "✅ Admin user found in database!\n";
        echo "   User ID: " . $admin['id'] . "\n";
        echo "   Role: " . $admin['role'] . "\n";
        echo "   Platform: " . $admin['platform'] . "\n";
        echo "   Email Confirmed: " . ($admin['isEmailConfirmed'] ? 'YES' : 'NO') . "\n";
        
        // Test decryption
        $decryptedEmail = decryptEmail($admin['email']);
        echo "   Decrypted Email: {$decryptedEmail}\n";
        
        if ($decryptedEmail === $normalizedEmail) {
            echo "✅ Email encryption/decryption working correctly!\n";
        } else {
            echo "⚠️  Warning: Email decryption mismatch!\n";
        }
    } else {
        echo "❌ ERROR: Could not find admin user after creation!\n";
        exit(1);
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "✅ Admin Seeder Completed Successfully!\n";
    echo str_repeat("=", 60) . "\n";
    echo "\n💡 Login Credentials:\n";
    echo "   Email: admin@example.com\n";
    echo "   Password: admin123\n";
    echo "   Platform: trivemart\n";
    echo "\n🚀 You can now login to the admin panel!\n";
    
} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
    echo "   SQL Error Code: " . $e->getCode() . "\n";
    if (isset($stmt) && $stmt->errorInfo()) {
        echo "   SQL Error Info: " . print_r($stmt->errorInfo(), true) . "\n";
    }
    exit(1);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

?>
