<?php
/**
 * Migration Script: Re-encrypt all emails and phones with deterministic encryption
 * 
 * This script migrates all existing encrypted emails and phones to use
 * deterministic encryption (same input = same output) for proper database lookups.
 * 
 * Run this script once after implementing deterministic encryption:
 * php database/migrations/migrate_emails_to_deterministic.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/encryption.php';

$conn = getDBConnection();

echo "Starting migration to deterministic encryption...\n";

try {
    // Get all users
    $stmt = $conn->query("SELECT id, email, phone FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalUsers = count($users);
    $migrated = 0;
    $skipped = 0;
    $errors = 0;
    
    echo "Found {$totalUsers} users to process...\n\n";
    
    foreach ($users as $user) {
        $userId = $user['id'];
        $updates = [];
        $updateFields = [];
        $params = [':id' => $userId];
        
        // Process email
        if (!empty($user['email'])) {
            // Check if it's encrypted
            if (isEncrypted($user['email'])) {
                // Try to decrypt
                $decryptedEmail = decryptEmail($user['email']);
                
                if (!empty($decryptedEmail) && $decryptedEmail !== $user['email']) {
                    // Successfully decrypted, re-encrypt with deterministic method
                    $newEncryptedEmail = encryptEmail($decryptedEmail);
                    
                    // Only update if the encrypted value is different
                    if ($newEncryptedEmail !== $user['email']) {
                        $updates[':email'] = $newEncryptedEmail;
                        $updateFields[] = 'email = :email';
                        echo "User {$userId}: Re-encrypting email...\n";
                    } else {
                        echo "User {$userId}: Email already using deterministic encryption, skipping...\n";
                        $skipped++;
                        continue;
                    }
                } else {
                    // Decryption failed or it's plain text
                    if (!isEncrypted($user['email'])) {
                        // It's plain text, encrypt it
                        $newEncryptedEmail = encryptEmail($user['email']);
                        $updates[':email'] = $newEncryptedEmail;
                        $updateFields[] = 'email = :email';
                        echo "User {$userId}: Encrypting plain text email...\n";
                    } else {
                        echo "User {$userId}: Could not decrypt email, skipping...\n";
                        $errors++;
                        continue;
                    }
                }
            } else {
                // Plain text email, encrypt it
                $newEncryptedEmail = encryptEmail($user['email']);
                $updates[':email'] = $newEncryptedEmail;
                $updateFields[] = 'email = :email';
                echo "User {$userId}: Encrypting plain text email...\n";
            }
        }
        
        // Process phone
        if (!empty($user['phone'])) {
            // Check if it's encrypted
            if (isEncrypted($user['phone'])) {
                // Try to decrypt
                $decryptedPhone = decryptPhone($user['phone']);
                
                if (!empty($decryptedPhone) && $decryptedPhone !== $user['phone']) {
                    // Successfully decrypted, re-encrypt with deterministic method
                    $newEncryptedPhone = encryptPhone($decryptedPhone);
                    
                    // Only update if the encrypted value is different
                    if ($newEncryptedPhone !== $user['phone']) {
                        $updates[':phone'] = $newEncryptedPhone;
                        $updateFields[] = 'phone = :phone';
                        echo "User {$userId}: Re-encrypting phone...\n";
                    }
                } else {
                    // Decryption failed or it's plain text
                    if (!isEncrypted($user['phone'])) {
                        // It's plain text, encrypt it
                        $newEncryptedPhone = encryptPhone($user['phone']);
                        $updates[':phone'] = $newEncryptedPhone;
                        $updateFields[] = 'phone = :phone';
                        echo "User {$userId}: Encrypting plain text phone...\n";
                    }
                }
            } else {
                // Plain text phone, encrypt it
                $newEncryptedPhone = encryptPhone($user['phone']);
                $updates[':phone'] = $newEncryptedPhone;
                $updateFields[] = 'phone = :phone';
                echo "User {$userId}: Encrypting plain text phone...\n";
            }
        }
        
        // Update user if there are changes
        if (!empty($updateFields)) {
            $updateParams = array_merge($updates, $params);
            $updateStmt = $conn->prepare("UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = :id");
            $updateStmt->execute($updateParams);
            $migrated++;
            echo "User {$userId}: Successfully migrated!\n\n";
        } else {
            $skipped++;
            echo "User {$userId}: No changes needed, skipping...\n\n";
        }
    }
    
    echo "\n=== Migration Complete ===\n";
    echo "Total users: {$totalUsers}\n";
    echo "Migrated: {$migrated}\n";
    echo "Skipped: {$skipped}\n";
    echo "Errors: {$errors}\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
