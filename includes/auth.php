<?php
// Authentication Helper Functions - Matching Node.js Backend Flow

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
        $user = null;
        if ($email) {
            // Try with COALESCE first, if it fails, try without it
            try {
                $stmt = $conn->prepare("SELECT *, COALESCE(isEmailConfirmed, is_email_confirmed, 0) as email_confirmed_status FROM users WHERE email = :email AND platform = :platform");
                $stmt->execute([
                    ':email' => strtolower(trim($email)),
                    ':platform' => $platform
                ]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                // If COALESCE fails (column doesn't exist), try without it
                error_log("Login: COALESCE query failed, trying simple query - " . $e->getMessage());
                try {
                    $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email AND platform = :platform");
                    $stmt->execute([
                        ':email' => strtolower(trim($email)),
                        ':platform' => $platform
                    ]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                } catch (PDOException $e2) {
                    error_log("Login: Simple query also failed - " . $e2->getMessage());
                    throw $e2;
                }
            }
        }
        
        // Step 2: Match Node.js - Check if user exists
        if (!$user) {
            $_SESSION['error'] = 'User not found for this platform';
            return false;
        }
        
        // Step 3: Match Node.js - Check email confirmation (handle both column formats)
        // Use the COALESCE value from SQL query if available, or check both columns directly
        $isEmailConfirmed = null;
        
        // First try the COALESCE result if it exists
        if (isset($user['email_confirmed_status'])) {
            $isEmailConfirmed = $user['email_confirmed_status'];
        } 
        // Otherwise check both possible column names
        elseif (isset($user['isEmailConfirmed'])) {
            $isEmailConfirmed = $user['isEmailConfirmed'];
        } elseif (isset($user['is_email_confirmed'])) {
            $isEmailConfirmed = $user['is_email_confirmed'];
        } else {
            $isEmailConfirmed = 0;
        }
        
        // Check if confirmed: must be 1, '1', true, or non-zero
        $isConfirmed = false;
        if ($isEmailConfirmed === 1 || $isEmailConfirmed === '1' || $isEmailConfirmed === true || $isEmailConfirmed === 'true') {
            $isConfirmed = true;
        } elseif (is_numeric($isEmailConfirmed) && (int)$isEmailConfirmed > 0) {
            $isConfirmed = true;
        }
        
        if (!$isConfirmed) {
            error_log("Login blocked: Email not confirmed for user " . ($user['email'] ?? 'unknown') . ". Value: " . var_export($isEmailConfirmed, true) . ", Raw: " . var_export([
                'email_confirmed_status' => $user['email_confirmed_status'] ?? 'not set',
                'isEmailConfirmed' => $user['isEmailConfirmed'] ?? 'not set',
                'is_email_confirmed' => $user['is_email_confirmed'] ?? 'not set'
            ], true));
            $_SESSION['error'] = 'Please confirm your email first';
            return false;
        }
        
        error_log("Login: Email confirmed check passed for user " . ($user['email'] ?? 'unknown') . ". Value: " . var_export($isEmailConfirmed, true));
        
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
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user'] = [
            'id' => $user['id'],
            'email' => $user['email'],
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
    
    // Match Node.js: check existing user by email AND platform
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email AND platform = :platform");
    $stmt->execute([':email' => $email, ':platform' => $platform]);
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
            ':email' => $email,
            ':phone' => $phone,
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
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email AND platform = :platform ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([':email' => $email, ':platform' => $platform]);
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
        
        // Verify the update was successful by querying again with COALESCE
        $verifyStmt = $conn->prepare("SELECT id, email, isEmailConfirmed, is_email_confirmed, COALESCE(isEmailConfirmed, is_email_confirmed, 0) as confirmed_status FROM users WHERE id = :id LIMIT 1");
        $verifyStmt->execute([':id' => $user['id']]);
        $verified = $verifyStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$verified) {
            error_log("Email confirmation: Could not verify update - user not found after update");
            return ['success' => false, 'message' => 'Could not verify email confirmation'];
        }
        
        // Check confirmation status using COALESCE value or individual columns
        $confirmed = false;
        $confirmedValue = $verified['confirmed_status'] ?? $verified['isEmailConfirmed'] ?? $verified['is_email_confirmed'] ?? 0;
        
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
