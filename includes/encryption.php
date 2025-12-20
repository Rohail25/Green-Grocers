<?php
/**
 * Encryption/Decryption Utility for Email and Phone
 * Uses AES-256-CBC encryption for secure storage
 */

// Encryption key - In production, store this in environment variables or a secure config file
// For now, using a default key. CHANGE THIS IN PRODUCTION!
define('ENCRYPTION_KEY', 'green-grocers-encryption-key-2024-change-in-production');
define('ENCRYPTION_METHOD', 'AES-256-CBC');

/**
 * Encrypt sensitive data (email or phone)
 * @param string $data The data to encrypt
 * @param bool $deterministic If true, uses deterministic IV (same input = same output) for lookups
 * @return string Base64 encoded encrypted data
 */
function encryptData($data, $deterministic = false) {
    if (empty($data)) {
        return '';
    }
    
    $ivLength = openssl_cipher_iv_length(ENCRYPTION_METHOD);
    
    if ($deterministic) {
        // Use deterministic IV derived from data + key for consistent encryption
        // This ensures same input always produces same output (needed for database lookups)
        $ivSource = hash('sha256', ENCRYPTION_KEY . $data, true);
        $iv = substr($ivSource, 0, $ivLength);
    } else {
        // Generate a random IV (Initialization Vector) for non-deterministic encryption
        $iv = openssl_random_pseudo_bytes($ivLength);
    }
    
    // Encrypt the data
    $encrypted = openssl_encrypt($data, ENCRYPTION_METHOD, ENCRYPTION_KEY, 0, $iv);
    
    // Combine IV and encrypted data, then base64 encode
    return base64_encode($iv . $encrypted);
}

/**
 * Decrypt sensitive data (email or phone)
 * @param string $encryptedData Base64 encoded encrypted data
 * @return string Decrypted data or empty string on failure
 */
function decryptData($encryptedData) {
    if (empty($encryptedData)) {
        return '';
    }
    
    try {
        // Decode from base64
        $data = base64_decode($encryptedData, true);
        if ($data === false) {
            return '';
        }
        
        // Extract IV and encrypted data
        $ivLength = openssl_cipher_iv_length(ENCRYPTION_METHOD);
        if (strlen($data) < $ivLength) {
            return '';
        }
        
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);
        
        // Decrypt the data
        $decrypted = openssl_decrypt($encrypted, ENCRYPTION_METHOD, ENCRYPTION_KEY, 0, $iv);
        
        return $decrypted !== false ? $decrypted : '';
    } catch (Exception $e) {
        error_log("Decryption error: " . $e->getMessage());
        return '';
    }
}

/**
 * Encrypt email address
 * @param string $email The email to encrypt
 * @return string Encrypted email
 */
function encryptEmail($email) {
    return encryptData(strtolower(trim($email)));
}

/**
 * Decrypt email address
 * @param string $encryptedEmail The encrypted email (or plain text for backward compatibility)
 * @return string Decrypted email or original if decryption fails/not encrypted
 */
function decryptEmail($encryptedEmail) {
    if (empty($encryptedEmail)) {
        return '';
    }
    
    // Check if it's actually encrypted (base64 pattern and length check)
    if (!isEncrypted($encryptedEmail)) {
        // It's plain text, return as is
        return $encryptedEmail;
    }
    
    // Try to decrypt
    $decrypted = decryptData($encryptedEmail);
    
    // If decryption failed or returned empty, return original (might be plain text)
    if (empty($decrypted)) {
        return $encryptedEmail;
    }
    
    return $decrypted;
}

/**
 * Encrypt phone number
 * Uses deterministic encryption so same phone always produces same encrypted value
 * @param string $phone The phone to encrypt
 * @return string Encrypted phone
 */
function encryptPhone($phone) {
    // Use deterministic encryption for phone (same input = same output)
    return encryptData(trim($phone), true);
}

/**
 * Decrypt phone number
 * @param string $encryptedPhone The encrypted phone (or plain text for backward compatibility)
 * @return string Decrypted phone or original if decryption fails/not encrypted
 */
function decryptPhone($encryptedPhone) {
    if (empty($encryptedPhone)) {
        return '';
    }
    
    // Check if it's actually encrypted (base64 pattern and length check)
    if (!isEncrypted($encryptedPhone)) {
        // It's plain text, return as is
        return $encryptedPhone;
    }
    
    // Try to decrypt
    $decrypted = decryptData($encryptedPhone);
    
    // If decryption failed or returned empty, return original (might be plain text)
    if (empty($decrypted)) {
        return $encryptedPhone;
    }
    
    return $decrypted;
}

/**
 * Check if a string is encrypted (heuristic check)
 * @param string $data The data to check
 * @return bool True if likely encrypted
 */
function isEncrypted($data) {
    if (empty($data)) {
        return false;
    }
    
    // Encrypted data is base64 encoded, so it should only contain base64 characters
    // and be longer than typical email/phone
    return base64_encode(base64_decode($data, true)) === $data && strlen($data) > 20;
}
