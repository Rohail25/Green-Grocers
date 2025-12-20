<?php
// Authentication Helper Functions - Matching Node.js Backend Flow

require_once __DIR__ . '/encryption.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isAuthenticated() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user']);
}

function requireAuth($requiredRole = null) {
    if (!isAuthenticated()) {
        // Store the current URL as return URL for redirect after login
        $currentUrl = $_SERVER['REQUEST_URI'] ?? '';
        if (!empty($currentUrl)) {
            $_SESSION['return_url'] = $currentUrl;
        }
        header('Location: ' . (defined('BASE_PATH') ? BASE_PATH : '') . '/auth/login.php');
        exit;
    }
    
    if ($requiredRole && $_SESSION['user']['role'] !== $requiredRole) {
        header('Location: ' . (defined('BASE_PATH') ? BASE_PATH : ''));
        exit;
    }
}

function loginUser($email, $password, $platform = 'trivemart') {
    $conn = getDBConnection();
    
    try {
        // Step 1: Match Node.js - Find user by email AND platform (or phone if email not provided)
        // Try both encrypted and plain text email for backward compatibility
        $user = null;
        if ($email) {
            $normalizedEmail = strtolower(trim($email));
            $encryptedEmail = encryptEmail($normalizedEmail);
            
            error_log("Login attempt - Email: " . $normalizedEmail . ", Platform: " . $platform);
            error_log("Login - Encrypted email: " . substr($encryptedEmail, 0, 50) . "...");
            
            // First, try to find user with encrypted email (new users) - WITH platform
            // Use simple query first to avoid COALESCE issues with missing columns
            try {
                $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email AND platform = :platform");
                $stmt->execute([
                    ':email' => $encryptedEmail,
                    ':platform' => $platform
                ]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user) {
                    error_log("Login: Found user with encrypted email and platform match");
                    error_log("Login: User ID: " . ($user['id'] ?? 'unknown') . ", Role: " . ($user['role'] ?? 'unknown'));
                    // Add email_confirmed_status to user array for consistency
                    $user['email_confirmed_status'] = $user['isEmailConfirmed'] ?? 0;
                } else {
                    error_log("Login: No user found with encrypted email: " . substr($encryptedEmail, 0, 30) . "... and platform: " . $platform);
                }
            } catch (PDOException $e) {
                error_log("Login: Query failed - " . $e->getMessage());
            }
            
            // If not found, try WITHOUT platform restriction (in case platform is wrong)
            if (!$user) {
                error_log("Login: Trying search without platform restriction...");
                try {
                    $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
                    $stmt->execute([':email' => $encryptedEmail]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($user) {
                        error_log("Login: Found user with encrypted email (no platform restriction). User platform: " . ($user['platform'] ?? 'unknown'));
                        error_log("Login: User ID: " . ($user['id'] ?? 'unknown') . ", Role: " . ($user['role'] ?? 'unknown'));
                        // Add email_confirmed_status to user array
                        $user['email_confirmed_status'] = $user['isEmailConfirmed'] ?? 0;
                        // Update platform if it was wrong
                        if (($user['platform'] ?? '') !== $platform) {
                            error_log("Login: Platform mismatch detected. User platform: " . ($user['platform'] ?? 'unknown') . ", Login platform: " . $platform);
                            $platform = $user['platform'] ?? $platform;
                        }
                    } else {
                        error_log("Login: No user found even without platform restriction");
                    }
                } catch (PDOException $e) {
                    error_log("Login: Query without platform failed - " . $e->getMessage());
                }
            }
            
            // If not found with deterministic encrypted email, try plain text (for backward compatibility)
            if (!$user) {
                try {
                    $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email AND platform = :platform");
                    $stmt->execute([
                        ':email' => $normalizedEmail,
                        ':platform' => $platform
                    ]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // If found with plain text, encrypt email and phone (if phone exists and is plain text)
                    if ($user) {
                        error_log("Login: Found user with plain text email, encrypting and updating...");
                        // Add email_confirmed_status to user array
                        $user['email_confirmed_status'] = $user['isEmailConfirmed'] ?? 0;
                        
                        $updates = [':encryptedEmail' => $encryptedEmail, ':id' => $user['id']];
                        $updateFields = ['email = :encryptedEmail'];
                        
                        // Check if phone needs encryption
                        if (!empty($user['phone']) && !isEncrypted($user['phone'])) {
                            $encryptedPhone = encryptPhone($user['phone']);
                            $updates[':encryptedPhone'] = $encryptedPhone;
                            $updateFields[] = 'phone = :encryptedPhone';
                            $user['phone'] = $encryptedPhone;
                        }
                        
                        $updateStmt = $conn->prepare("UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = :id");
                        $updateStmt->execute($updates);
                        // Update the user array with encrypted email
                        $user['email'] = $encryptedEmail;
                    }
                } catch (PDOException $e) {
                    error_log("Login: Plain text query failed - " . $e->getMessage());
                }
            }
            
            // If still not found, try to find users by decrypting and comparing (across ALL platforms)
            // This handles users created before we switched to deterministic encryption
            // Also handles cases where platform detection was wrong
            if (!$user) {
                try {
                    error_log("Login: Trying final fallback - searching all users by decrypting emails...");
                    // Get all users (limit to reasonable number for performance)
                    // Try current platform first, then all platforms
                    $searchPlatforms = [$platform, null]; // Try current platform, then all
                    
                    foreach ($searchPlatforms as $searchPlatform) {
                        if ($searchPlatform) {
                            $stmt = $conn->prepare("SELECT * FROM users WHERE platform = :platform LIMIT 200");
                            $stmt->execute([':platform' => $searchPlatform]);
                        } else {
                            $stmt = $conn->query("SELECT * FROM users LIMIT 200");
                        }
                        $allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        // Add email_confirmed_status to each user
                        foreach ($allUsers as &$u) {
                            $u['email_confirmed_status'] = $u['isEmailConfirmed'] ?? 0;
                        }
                        unset($u); // Break reference
                        
                        error_log("Login: Checking " . count($allUsers) . " users in " . ($searchPlatform ? "platform: {$searchPlatform}" : "all platforms"));
                        
                        // Try to decrypt each email and compare
                        foreach ($allUsers as $potentialUser) {
                            if (empty($potentialUser['email'])) {
                                continue;
                            }
                            
                            // Try to decrypt (works for both encrypted and plain text)
                            $decryptedStoredEmail = decryptEmail($potentialUser['email']);
                            
                            // Compare decrypted email with input email
                            if (strtolower(trim($decryptedStoredEmail)) === $normalizedEmail) {
                                $user = $potentialUser;
                                $platform = $potentialUser['platform'] ?? $platform; // Update platform from found user
                                error_log("Login: Found user by decrypting! User ID: " . ($user['id'] ?? 'unknown') . ", Platform: " . $platform);
                                
                                // Re-encrypt with deterministic method if needed
                                if ($user['email'] !== $encryptedEmail) {
                                    error_log("Login: Re-encrypting email with deterministic method...");
                                    $updates = [':encryptedEmail' => $encryptedEmail, ':id' => $user['id']];
                                    $updateFields = ['email = :encryptedEmail'];
                                    
                                    // Check if phone needs encryption
                                    if (!empty($user['phone']) && isEncrypted($user['phone'])) {
                                        $decryptedPhone = decryptPhone($user['phone']);
                                        if (!empty($decryptedPhone)) {
                                            $encryptedPhone = encryptPhone($decryptedPhone);
                                            $updates[':encryptedPhone'] = $encryptedPhone;
                                            $updateFields[] = 'phone = :encryptedPhone';
                                            $user['phone'] = $encryptedPhone;
                                        }
                                    } elseif (!empty($user['phone']) && !isEncrypted($user['phone'])) {
                                        $encryptedPhone = encryptPhone($user['phone']);
                                        $updates[':encryptedPhone'] = $encryptedPhone;
                                        $updateFields[] = 'phone = :encryptedPhone';
                                        $user['phone'] = $encryptedPhone;
                                    }
                                    
                                    $updateStmt = $conn->prepare("UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = :id");
                                    $updateStmt->execute($updates);
                                }
                                
                                // Update the user array with encrypted email
                                $user['email'] = $encryptedEmail;
                                break 2; // Found the user, stop searching both loops
                            }
                        }
                        
                        // If found, break outer loop
                        if ($user) {
                            break;
                        }
                    }
                } catch (PDOException $e) {
                    error_log("Login: Fallback decryption search failed - " . $e->getMessage());
                } catch (Exception $e) {
                    error_log("Login: Fallback search exception - " . $e->getMessage());
                }
            }
        }
        
        // Step 2: Match Node.js - Check if user exists
        if (!$user) {
            // Final attempt: Try to find ANY user with this email (decrypt all and compare)
            error_log("Login: Final attempt - searching ALL users by decrypting emails (no limit)...");
            try {
                // Search ALL users without limit
                $stmt = $conn->query("SELECT id, email, platform, role FROM users");
                $allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                error_log("Login: Checking " . count($allUsers) . " total users in final search...");
                
                $foundCount = 0;
                foreach ($allUsers as $potentialUser) {
                    if (empty($potentialUser['email'])) {
                        continue;
                    }
                    
                    $foundCount++;
                    if ($foundCount % 10 == 0) {
                        error_log("Login: Checked {$foundCount} users so far...");
                    }
                    
                    $decrypted = decryptEmail($potentialUser['email']);
                    if (strtolower(trim($decrypted)) === $normalizedEmail) {
                        // Found! Get full user data with all fields
                        error_log("Login: ✓✓✓ FOUND MATCHING USER BY DECRYPTING! ✓✓✓");
                        error_log("Login: User ID: " . ($potentialUser['id'] ?? 'unknown'));
                        error_log("Login: Decrypted email matches: {$decrypted}");
                        
                        $fullStmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
                        $fullStmt->execute([':id' => $potentialUser['id']]);
                        $user = $fullStmt->fetch(PDO::FETCH_ASSOC);
                        
                        // Add email_confirmed_status to user array
                        if ($user) {
                            $user['email_confirmed_status'] = $user['isEmailConfirmed'] ?? 0;
                        }
                        
                        if ($user) {
                            $platform = $potentialUser['platform'] ?? $platform;
                            error_log("Login: Full user data retrieved! User ID: " . ($user['id'] ?? 'unknown') . ", Platform: " . $platform);
                            error_log("Login: Email confirmed status: " . var_export($user['email_confirmed_status'] ?? 'not set', true));
                            
                            // IMPORTANT: Re-encrypt with deterministic method and update database
                            if ($user['email'] !== $encryptedEmail) {
                                error_log("Login: Re-encrypting user email with deterministic method...");
                                error_log("Login: Old email: " . substr($user['email'], 0, 40) . "...");
                                error_log("Login: New email: " . substr($encryptedEmail, 0, 40) . "...");
                                $reEncryptStmt = $conn->prepare("UPDATE users SET email = :email WHERE id = :id");
                                $reEncryptStmt->execute([
                                    ':email' => $encryptedEmail,
                                    ':id' => $user['id']
                                ]);
                                $user['email'] = $encryptedEmail; // Update user array
                                error_log("Login: Email re-encrypted successfully!");
                            } else {
                                error_log("Login: Email already using deterministic encryption");
                            }
                            break; // Found user, exit loop
                        } else {
                            error_log("Login: ERROR - Could not retrieve full user data after finding match!");
                        }
                    }
                }
                
                if (!$user) {
                    error_log("Login: ✗✗✗ User NOT found even after decrypting all " . count($allUsers) . " users ✗✗✗");
                    error_log("Login: Searched email: {$normalizedEmail}");
                    error_log("Login: Encrypted email searched: " . substr($encryptedEmail, 0, 40) . "...");
                } else {
                    error_log("Login: ✓✓✓ User found in final search! Proceeding with login... ✓✓✓");
                }
            } catch (Exception $e) {
                error_log("Login: Final search failed - " . $e->getMessage());
                error_log("Login: Exception trace: " . $e->getTraceAsString());
            }
        }
        
        if (!$user) {
            error_log("Login failed: User not found. Email: " . ($email ?? 'empty') . ", Platform: " . $platform);
            error_log("Login: Tried encrypted email: " . (isset($encryptedEmail) ? substr($encryptedEmail, 0, 50) . "..." : 'not set'));
            error_log("Login: Tried normalized email: " . (isset($normalizedEmail) ? $normalizedEmail : 'not set'));
            
            // Check if user exists at all (for debugging)
            try {
                $checkStmt = $conn->query("SELECT COUNT(*) as count FROM users");
                $count = $checkStmt->fetch(PDO::FETCH_ASSOC);
                error_log("Login: Total users in database: " . ($count['count'] ?? 0));
            } catch (Exception $e) {
                error_log("Login: Could not check user count - " . $e->getMessage());
            }
            
            $_SESSION['error'] = 'User not found. Please check your email and password.';
            return false;
        }
        
        error_log("Login: User found successfully. User ID: " . ($user['id'] ?? 'unknown'));
        error_log("Login: User role: " . ($user['role'] ?? 'unknown'));
        
        // Step 3: Match Node.js - Check email confirmation (handle both column formats)
        // Use the COALESCE value from SQL query if available, or check both columns directly
        $isEmailConfirmed = null;
        
        // First try the COALESCE result if it exists
        if (isset($user['email_confirmed_status'])) {
            $isEmailConfirmed = $user['email_confirmed_status'];
            error_log("Login: Using email_confirmed_status from COALESCE: " . var_export($isEmailConfirmed, true));
        } 
        // Otherwise check both possible column names
        elseif (isset($user['isEmailConfirmed'])) {
            $isEmailConfirmed = $user['isEmailConfirmed'];
            error_log("Login: Using isEmailConfirmed column: " . var_export($isEmailConfirmed, true));
        } elseif (isset($user['is_email_confirmed'])) {
            $isEmailConfirmed = $user['is_email_confirmed'];
            error_log("Login: Using is_email_confirmed column: " . var_export($isEmailConfirmed, true));
        } else {
            // If not found in user array, query database directly
            error_log("Login: Email confirmation not in user array, querying database...");
            try {
                $confirmStmt = $conn->prepare("SELECT isEmailConfirmed FROM users WHERE id = :id");
                $confirmStmt->execute([':id' => $user['id']]);
                $confirmData = $confirmStmt->fetch(PDO::FETCH_ASSOC);
                if ($confirmData) {
                    $isEmailConfirmed = $confirmData['isEmailConfirmed'] ?? 0;
                    error_log("Login: Retrieved from database: " . var_export($isEmailConfirmed, true));
                } else {
                    $isEmailConfirmed = 0;
                }
            } catch (Exception $e) {
                error_log("Login: Could not query email confirmation: " . $e->getMessage());
                $isEmailConfirmed = 0;
            }
        }
        
        // Check if confirmed: must be 1, '1', true, or non-zero
        $isConfirmed = false;
        if ($isEmailConfirmed === 1 || $isEmailConfirmed === '1' || $isEmailConfirmed === true || $isEmailConfirmed === 'true') {
            $isConfirmed = true;
        } elseif (is_numeric($isEmailConfirmed) && (int)$isEmailConfirmed > 0) {
            $isConfirmed = true;
        }
        
        error_log("Login: Email confirmation check - Value: " . var_export($isEmailConfirmed, true) . ", Is Confirmed: " . ($isConfirmed ? 'YES' : 'NO'));
        
        if (!$isConfirmed) {
            error_log("Login blocked: Email not confirmed for user " . ($user['id'] ?? 'unknown') . ". Value: " . var_export($isEmailConfirmed, true));
            error_log("Login: Raw values - email_confirmed_status: " . var_export($user['email_confirmed_status'] ?? 'not set', true) . ", isEmailConfirmed: " . var_export($user['isEmailConfirmed'] ?? 'not set', true) . ", is_email_confirmed: " . var_export($user['is_email_confirmed'] ?? 'not set', true));
            
            // For admin users, auto-confirm email if not confirmed
            if (($user['role'] ?? '') === 'admin') {
                error_log("Login: Admin user email not confirmed, auto-confirming...");
                try {
                    $autoConfirmStmt = $conn->prepare("UPDATE users SET isEmailConfirmed = 1 WHERE id = :id");
                    $autoConfirmStmt->execute([':id' => $user['id']]);
                    $isConfirmed = true;
                    error_log("Login: Admin email auto-confirmed!");
                } catch (Exception $e) {
                    error_log("Login: Failed to auto-confirm admin email: " . $e->getMessage());
                }
            }
            
            if (!$isConfirmed) {
                $_SESSION['error'] = 'Please confirm your email first';
                return false;
            }
        }
        
        error_log("Login: Email confirmed check passed for user " . ($user['id'] ?? 'unknown'));
        
        // Step 4: Match Node.js - Check logistic/agent verification (BEFORE password check)
        if (in_array($user['role'], ['logistic', 'agent']) && !$user['isVerified']) {
            if (!$user['documentsUploaded']) {
                // Case 1: documents not uploaded yet
                $_SESSION['error'] = 'Please upload documents for verification';
                $_SESSION['requiresVerification'] = true;
                $_SESSION['documentsUploaded'] = false;
                return false;
            } else {
                // Case 2: documents uploaded but waiting for admin
                $_SESSION['error'] = 'Admin has not verified your account yet';
                $_SESSION['requiresVerification'] = true;
                $_SESSION['documentsUploaded'] = true;
                return false;
            }
        }
        
        // Step 5: Match Node.js - Check agent's parent logistic verification
        if ($user['role'] === 'agent' && $user['parentLogistic']) {
            $parentStmt = $conn->prepare("SELECT isVerified FROM users WHERE id = :id");
            $parentStmt->execute([':id' => $user['parentLogistic']]);
            $parent = $parentStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$parent || !$parent['isVerified']) {
                $_SESSION['error'] = 'Parent logistic not verified yet';
                return false;
            }
        }
        
        // Step 6: Match Node.js - Verify password (AFTER all checks above)
        if ($password) {
            if (!$user['password']) {
                $_SESSION['error'] = 'Password not set. Use social login.';
                return false;
            }
            
            if (!password_verify($password, $user['password'])) {
                $_SESSION['error'] = 'Invalid password';
                return false;
            }
        }
        
        // Step 7: Match Node.js - Set session (user data without password)
        // Decrypt email for session (user should see their own decrypted email)
        // decryptEmail() now handles both encrypted and plain text automatically
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user'] = [
            'id' => $user['id'],
            'email' => decryptEmail($user['email']), // Decrypt for user session (handles both encrypted and plain text)
            'firstName' => $user['firstName'] ?? $user['first_name'] ?? '',
            'lastName' => $user['lastName'] ?? $user['last_name'] ?? '',
            'role' => $user['role'],
            'platform' => $user['platform'],
            'vendorId' => $user['vendorId'] ?? null,
            'clientId' => $user['clientId'] ?? null
        ];
        
        // Step 8: Merge session cart into database cart if user had items in session
        require_once __DIR__ . '/cart.php';
        mergeSessionCartToDatabase($user['id']);
        
        return true;
        
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        $_SESSION['error'] = 'Internal server error';
        return false;
    }
}

function registerUser($userData) {
    $conn = getDBConnection();
    
    $email = strtolower(trim($userData['email'] ?? ''));
    $password = $userData['password'] ?? '';
    $confirmPassword = $userData['confirmPassword'] ?? '';
    $platform = $userData['platform'] ?? 'trivemart';
    $role = $userData['role'] ?? 'customer';
    $phone = $userData['phone'] ?? '';
    $firstName = $userData['firstName'] ?? '';
    $lastName = $userData['lastName'] ?? '';
    
    // Match Node.js: validation
    if (empty($platform) && $role !== 'agent') {
        return ['success' => false, 'message' => 'Platform is required'];
    }
    
    if ($role !== 'agent' && $password !== $confirmPassword) {
        return ['success' => false, 'message' => 'Passwords do not match'];
    }
    
    // Encrypt email and phone before storing
    $encryptedEmail = encryptEmail($email);
    $encryptedPhone = !empty($phone) ? encryptPhone($phone) : '';
    
    // Match Node.js: check existing user by email AND platform (compare encrypted emails)
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email AND platform = :platform");
    $stmt->execute([':email' => $encryptedEmail, ':platform' => $platform]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Email already registered'];
    }
    
    // Match Node.js: generate vendorId/clientId based on platform
    $vendorId = null;
    $clientId = null;
    if ($platform === 'trivestore') {
        $vendorId = 'VEND-' . strtoupper(substr(uniqid(), -8));
    } elseif ($platform === 'trivemart') {
        $clientId = 'MART-' . strtoupper(substr(uniqid(), -8));
    }
    
    // Match Node.js: hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Match Node.js: isVerified logic (logistic must be admin-approved)
    $isVerified = ($role === 'logistic') ? false : true;
    
    // Match Node.js: insert user with JSON fields initialized as empty arrays
    $stmt = $conn->prepare("
        INSERT INTO users (
            id, email, phone, password, firstName, lastName, role, platform,
            vendorId, clientId, isEmailConfirmed, isVerified,
            verificationDocuments, preferredVendors, addresses,
            created_at, updated_at
        ) VALUES (
            UUID(), :email, :phone, :password, :firstName, :lastName, :role, :platform,
            :vendorId, :clientId, FALSE, :isVerified,
            '[]', '[]', '[]',
            NOW(), NOW()
        )
    ");
    
    try {
        $stmt->execute([
            ':email' => $encryptedEmail, // Store encrypted email
            ':phone' => $encryptedPhone, // Store encrypted phone
            ':password' => $hashedPassword,
            ':firstName' => $firstName,
            ':lastName' => $lastName,
            ':role' => $role,
            ':platform' => $platform,
            ':vendorId' => $vendorId,
            ':clientId' => $clientId,
            ':isVerified' => $isVerified ? 1 : 0
        ]);
        
        // Get the user ID (for UUID(), we need to fetch it after insertion)
        // Use encrypted email for lookup
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email AND platform = :platform ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([':email' => $encryptedEmail, ':platform' => $platform]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $userId = $user['id'] ?? null;
        
        if (!$userId) {
            return ['success' => false, 'message' => 'Failed to retrieve user ID'];
        }
        
        // Match Node.js: generate confirmation token (simplified - in production use JWT)
        $verificationToken = bin2hex(random_bytes(32));
        $updateStmt = $conn->prepare("UPDATE users SET emailVerificationToken = :token WHERE id = :id");
        $updateStmt->execute([':token' => $verificationToken, ':id' => $userId]);
        
        // Send confirmation email asynchronously to speed up registration
        // Don't block registration process waiting for email
        // Use decrypted email for sending (email is stored encrypted but we need plain text to send)
        require_once __DIR__ . '/email.php';
        
        // For faster registration, send email in background (if supported) or optimize SMTP
        if (function_exists('fastcgi_finish_request')) {
            // FastCGI - send email after response
            register_shutdown_function(function() use ($email, $firstName, $verificationToken) {
                @sendVerificationEmail($email, $firstName, $verificationToken);
            });
            $emailSent = true; // Assume it will be sent
        } else {
            // Regular PHP - send email but with optimized SMTP
            $emailSent = @sendVerificationEmail($email, $firstName, $verificationToken);
        }
        
        if (!$emailSent) {
            // Log warning but don't fail registration
            error_log("Warning: Failed to send verification email to: " . $email . ". User can still verify manually using the token.");
        } else {
            error_log("Verification email queued/sent to: " . $email);
        }
        
        return [
            'success' => true,
            'user_id' => $userId,
            'verification_token' => $verificationToken,
            'message' => 'User registered. Please check your email to confirm.',
            'requiresConfirmation' => true,
            'email_sent' => $emailSent
        ];
    } catch (PDOException $e) {
        error_log("Registration error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
    }
}

function confirmEmail($token) {
    if (empty($token)) {
        error_log("Email confirmation: Empty token provided");
        return ['success' => false, 'message' => 'Invalid or expired token'];
    }
    
    $conn = getDBConnection();
    
    try {
        // Find user by token - try both possible column names
        $user = null;
        $columnFormat = 'camelCase'; // Track which format works
        
        // Try camelCase first
        try {
            $stmt = $conn->prepare("SELECT id, email, isEmailConfirmed, emailVerificationToken FROM users WHERE emailVerificationToken = :token LIMIT 1");
            $stmt->execute([':token' => $token]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Email confirmation: camelCase query failed, trying snake_case - " . $e->getMessage());
        }
        
        // If not found, try snake_case
        if (!$user) {
            try {
                $stmt = $conn->prepare("SELECT id, email, isEmailConfirmed , emailVerificationToken  FROM users WHERE emailVerificationToken = :token LIMIT 1");
                $stmt->execute([':token' => $token]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                $columnFormat = 'snake_case';
            } catch (PDOException $e) {
                error_log("Email confirmation: snake_case query also failed - " . $e->getMessage());
            }
        }
        
        if (!$user) {
            error_log("Email confirmation: Token not found - " . substr($token, 0, 10) . "...");
            return ['success' => false, 'message' => 'Invalid or expired token'];
        }
        
        error_log("Email confirmation: Found user ID " . $user['id'] . " with email " . ($user['email'] ?? 'unknown') . " (format: " . $columnFormat . ")");
        
        // Check if already confirmed
        $isConfirmed = ($user['isEmailConfirmed'] ?? 0);
        if ($isConfirmed == 1 || $isConfirmed === true) {
            error_log("Email confirmation: Already confirmed for user ID " . $user['id']);
            return ['success' => false, 'message' => 'Email is already confirmed'];
        }
        
        // Update email confirmation status - try multiple UPDATE queries to ensure one works
        $updateSuccess = false;
        $rowsAffected = 0;
        $lastError = '';
        
        // Try multiple UPDATE queries - one will work
        $updateQueries = [
            "UPDATE users SET isEmailConfirmed = 1, emailVerificationToken = NULL WHERE id = :id",
            "UPDATE users SET isEmailConfirmed = TRUE, emailVerificationToken = NULL WHERE id = :id",
            "UPDATE users SET is_email_confirmed = 1, email_verification_token = NULL WHERE id = :id",
            "UPDATE users SET is_email_confirmed = TRUE, email_verification_token = NULL WHERE id = :id"
        ];
        
        foreach ($updateQueries as $query) {
            try {
                $updateStmt = $conn->prepare($query);
                $updateStmt->execute([':id' => $user['id']]);
                $rowsAffected = $updateStmt->rowCount();
                
                if ($rowsAffected > 0) {
                    $updateSuccess = true;
                    error_log("Email confirmation: UPDATE successful with query: " . substr($query, 0, 60) . "... rows affected: " . $rowsAffected);
                    break;
                } else {
                    error_log("Email confirmation: UPDATE query executed but no rows affected: " . substr($query, 0, 60));
                }
            } catch (PDOException $e) {
                $lastError = $e->getMessage();
                error_log("Email confirmation: UPDATE query failed: " . substr($query, 0, 60) . " - Error: " . $e->getMessage());
                // Continue to next query
                continue;
            }
        }
        
        if (!$updateSuccess) {
            error_log("Email confirmation: All UPDATE queries failed. Last error: " . $lastError);
            return ['success' => false, 'message' => 'Failed to update email confirmation: ' . $lastError];
        }
        
        // Wait a tiny bit to ensure database commit
        usleep(100000); // 0.1 seconds
        
        // Verify the update was successful by querying again
        $verifyStmt = $conn->prepare("SELECT id, email, isEmailConfirmed FROM users WHERE id = :id LIMIT 1");
        $verifyStmt->execute([':id' => $user['id']]);
        $verified = $verifyStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$verified) {
            error_log("Email confirmation: Could not verify update - user not found after update");
            return ['success' => false, 'message' => 'Could not verify email confirmation'];
        }
        
        // Check confirmation status using isEmailConfirmed column
        $confirmed = false;
        $confirmedValue = $verified['isEmailConfirmed'] ?? 0;
        
        // Check if confirmed: must be 1, '1', true, or non-zero
        if ($confirmedValue === 1 || $confirmedValue === '1' || $confirmedValue === true || $confirmedValue === 'true') {
            $confirmed = true;
        } elseif (is_numeric($confirmedValue) && (int)$confirmedValue > 0) {
            $confirmed = true;
        }
        
        if ($confirmed) {
            error_log("Email confirmed successfully for user ID: " . $user['id'] . ", email: " . ($user['email'] ?? 'unknown') . ", confirmed_value: " . var_export($confirmedValue, true));
            return ['success' => true, 'message' => 'Email confirmed successfully'];
        } else {
            error_log("Email confirmation verification failed - user ID: " . $user['id']);
            error_log("Email confirmation debug - confirmed_status: " . var_export($verified['confirmed_status'] ?? 'null', true) . ", isEmailConfirmed: " . var_export($verified['isEmailConfirmed'] ?? 'null', true) . ", is_email_confirmed: " . var_export($verified['is_email_confirmed'] ?? 'null', true));
            
            // Force update one more time with explicit value - try both formats
            $forceUpdateSuccess = false;
            $forceQueries = [
                "UPDATE users SET isEmailConfirmed = 1 WHERE id = :id",
                "UPDATE users SET is_email_confirmed = 1 WHERE id = :id"
            ];
            
            foreach ($forceQueries as $forceQuery) {
                try {
                    $forceUpdate = $conn->prepare($forceQuery);
                    $forceUpdate->execute([':id' => $user['id']]);
                    if ($forceUpdate->rowCount() > 0) {
                        error_log("Email confirmation: Force update successful with: " . substr($forceQuery, 0, 50));
                        $forceUpdateSuccess = true;
                        break;
                    }
                } catch (PDOException $e) {
                    error_log("Email confirmation: Force update failed: " . $e->getMessage());
                    continue;
                }
            }
            
            if ($forceUpdateSuccess) {
                return ['success' => true, 'message' => 'Email confirmed successfully'];
            }
            
            return ['success' => false, 'message' => 'Email confirmation update did not take effect. Please try again or contact support.'];
        }
        
    } catch (PDOException $e) {
        error_log("Email confirmation error: " . $e->getMessage());
        error_log("Email confirmation error trace: " . $e->getTraceAsString());
        return ['success' => false, 'message' => 'Database error occurred. Please try again.'];
    }
}

function logoutUser() {
    session_destroy();
    // Always redirect to app login page instead of XAMPP root
    $base = defined('BASE_PATH') ? BASE_PATH : '/green-grocers';
    header('Location: ' . $base . '/auth/login.php');
    exit;
}

function getCurrentUser() {
    return $_SESSION['user'] ?? null;
}

function getUserProfile($userId) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("
        SELECT id, email, phone, firstName, lastName, role, platform, vendorId, clientId,
               isEmailConfirmed, isVerified, addresses, preferredVendors, created_at, updated_at
        FROM users WHERE id = :id
    ");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Decrypt email and phone for user viewing their own profile
        $user['email'] = decryptEmail($user['email']);
        $user['phone'] = decryptPhone($user['phone']);
        
        // Decode JSON fields (matching Node.js)
        $user['addresses'] = !empty($user['addresses']) ? json_decode($user['addresses'], true) : [];
        $user['preferredVendors'] = !empty($user['preferredVendors']) ? json_decode($user['preferredVendors'], true) : [];
    }
    
    return $user;
}

function addAddress($userId, $address) {
    $conn = getDBConnection();
    
    // Validate required fields
    $requiredFields = ['streetAddressLine1', 'suburb', 'state', 'postalCode'];
    foreach ($requiredFields as $field) {
        if (empty($address[$field])) {
            return ['success' => false, 'message' => ucfirst($field) . ' is required'];
        }
    }
    
    // Validate postal code (4 digits for Australia)
    if (!preg_match('/^\d{4}$/', $address['postalCode'])) {
        return ['success' => false, 'message' => 'Postal code must be 4 digits'];
    }
    
    $stmt = $conn->prepare("SELECT addresses FROM users WHERE id = :id");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        return ['success' => false, 'message' => 'User not found'];
    }
    
    // Format address
    $formattedAddress = [
        'streetAddressLine1' => $address['streetAddressLine1'],
        'streetAddressLine2' => $address['streetAddressLine2'] ?? '',
        'suburb' => $address['suburb'],
        'state' => $address['state'],
        'postalCode' => $address['postalCode'],
        'isDefault' => $address['isDefault'] ?? false
    ];
    
    // Match Node.js: parse addresses JSON and add new address
    $addresses = !empty($user['addresses']) ? json_decode($user['addresses'], true) : [];
    
    // If this is set as default, unset others
    if ($formattedAddress['isDefault']) {
        foreach ($addresses as &$addr) {
            $addr['isDefault'] = false;
        }
    }
    
    $addresses[] = $formattedAddress;
    
    $stmt = $conn->prepare("UPDATE users SET addresses = :addresses WHERE id = :id");
    $stmt->execute([
        ':addresses' => json_encode($addresses),
        ':id' => $userId
    ]);
    
    return ['success' => true, 'address' => end($addresses), 'addresses' => $addresses];
}

function addFavoriteProduct($userId, $productData) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT preferredVendors FROM users WHERE id = :id");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        return ['success' => false, 'message' => 'User not found'];
    }
    
    // Match Node.js: parse preferredVendors (used for favorites)
    $favorites = !empty($user['preferredVendors']) ? json_decode($user['preferredVendors'], true) : [];
    $productId = $productData['productId'] ?? '';
    
    // Check if already favorite
    $isFavorite = false;
    foreach ($favorites as $index => $fav) {
        $favId = is_array($fav) ? ($fav['productId'] ?? $fav['id'] ?? '') : $fav;
        if ($favId === $productId) {
            unset($favorites[$index]);
            $isFavorite = true;
            break;
        }
    }
    
    if (!$isFavorite) {
        $favorites[] = [
            'productId' => $productId,
            'name' => $productData['name'] ?? '',
            'images' => $productData['images'] ?? [],
            'retailPrice' => $productData['retailPrice'] ?? 0
        ];
    }
    
    $favorites = array_values($favorites);
    
    $stmt = $conn->prepare("UPDATE users SET preferredVendors = :favorites WHERE id = :id");
    $stmt->execute([
        ':favorites' => json_encode($favorites),
        ':id' => $userId
    ]);
    
    return [
        'success' => true,
        'message' => $isFavorite ? 'Product removed from favorites' : 'Product added to favorites',
        'favoriteProducts' => $favorites
    ];
}

// Update user password
function updatePassword($userId, $currentPassword, $newPassword) {
    $conn = getDBConnection();
    
    // Get current user password
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = :id");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        return ['success' => false, 'message' => 'User not found'];
    }
    
    // Verify current password
    if (!password_verify($currentPassword, $user['password'])) {
        return ['success' => false, 'message' => 'Current password is incorrect'];
    }
    
    // Validate new password
    if (strlen($newPassword) < 6) {
        return ['success' => false, 'message' => 'Password must be at least 6 characters long'];
    }
    
    // Hash new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Update password
    $stmt = $conn->prepare("UPDATE users SET password = :password, updated_at = NOW() WHERE id = :id");
    $stmt->execute([
        ':password' => $hashedPassword,
        ':id' => $userId
    ]);
    
    return ['success' => true, 'message' => 'Password updated successfully'];
}

// Update user address
function updateAddress($userId, $addressIndex, $addressData) {
    $conn = getDBConnection();
    
    // Validate required fields
    $requiredFields = ['streetAddressLine1', 'suburb', 'state', 'postalCode'];
    foreach ($requiredFields as $field) {
        if (empty($addressData[$field])) {
            return ['success' => false, 'message' => ucfirst($field) . ' is required'];
        }
    }
    
    // Validate postal code (4 digits for Australia)
    if (!preg_match('/^\d{4}$/', $addressData['postalCode'])) {
        return ['success' => false, 'message' => 'Postal code must be 4 digits'];
    }
    
    // Get current addresses
    $stmt = $conn->prepare("SELECT addresses FROM users WHERE id = :id");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        return ['success' => false, 'message' => 'User not found'];
    }
    
    $addresses = !empty($user['addresses']) ? json_decode($user['addresses'], true) : [];
    
    // Format address
    $formattedAddress = [
        'streetAddressLine1' => $addressData['streetAddressLine1'],
        'streetAddressLine2' => $addressData['streetAddressLine2'] ?? '',
        'suburb' => $addressData['suburb'],
        'state' => $addressData['state'],
        'postalCode' => $addressData['postalCode'],
        'isDefault' => $addressData['isDefault'] ?? false
    ];
    
    // If updating existing address
    if ($addressIndex !== null && isset($addresses[$addressIndex])) {
        $addresses[$addressIndex] = $formattedAddress;
    } else {
        // Adding new address
        // If this is set as default, unset others
        if ($formattedAddress['isDefault']) {
            foreach ($addresses as &$addr) {
                $addr['isDefault'] = false;
            }
        }
        $addresses[] = $formattedAddress;
    }
    
    // Update addresses
    $stmt = $conn->prepare("UPDATE users SET addresses = :addresses, updated_at = NOW() WHERE id = :id");
    $stmt->execute([
        ':addresses' => json_encode($addresses),
        ':id' => $userId
    ]);
    
    return ['success' => true, 'address' => $formattedAddress, 'addresses' => $addresses];
}

// Delete address
function deleteAddress($userId, $addressIndex) {
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("SELECT addresses FROM users WHERE id = :id");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        return ['success' => false, 'message' => 'User not found'];
    }
    
    $addresses = !empty($user['addresses']) ? json_decode($user['addresses'], true) : [];
    
    if (!isset($addresses[$addressIndex])) {
        return ['success' => false, 'message' => 'Address not found'];
    }
    
    unset($addresses[$addressIndex]);
    $addresses = array_values($addresses); // Re-index array
    
    $stmt = $conn->prepare("UPDATE users SET addresses = :addresses, updated_at = NOW() WHERE id = :id");
    $stmt->execute([
        ':addresses' => json_encode($addresses),
        ':id' => $userId
    ]);
    
    return ['success' => true, 'addresses' => $addresses];
}

// Set default address
function setDefaultAddress($userId, $addressIndex) {
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("SELECT addresses FROM users WHERE id = :id");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        return ['success' => false, 'message' => 'User not found'];
    }
    
    $addresses = !empty($user['addresses']) ? json_decode($user['addresses'], true) : [];
    
    if (!isset($addresses[$addressIndex])) {
        return ['success' => false, 'message' => 'Address not found'];
    }
    
    // Unset all defaults
    foreach ($addresses as &$addr) {
        $addr['isDefault'] = false;
    }
    
    // Set selected address as default
    $addresses[$addressIndex]['isDefault'] = true;
    
    $stmt = $conn->prepare("UPDATE users SET addresses = :addresses, updated_at = NOW() WHERE id = :id");
    $stmt->execute([
        ':addresses' => json_encode($addresses),
        ':id' => $userId
    ]);
    
    return ['success' => true, 'addresses' => $addresses];
}
?>
