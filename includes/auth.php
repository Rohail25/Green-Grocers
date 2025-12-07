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
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email AND platform = :platform");
            $stmt->execute([
                ':email' => strtolower(trim($email)),
                ':platform' => $platform
            ]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Step 2: Match Node.js - Check if user exists
        if (!$user) {
            $_SESSION['error'] = 'User not found for this platform';
            return false;
        }
        
        // Step 3: Match Node.js - Check email confirmation
        if (!$user['isEmailConfirmed']) {
            $_SESSION['error'] = 'Please confirm your email first';
            return false;
        }
        
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
        
        $userId = $conn->lastInsertId();
        
        // Match Node.js: generate confirmation token (simplified - in production use JWT)
        $verificationToken = bin2hex(random_bytes(32));
        $updateStmt = $conn->prepare("UPDATE users SET emailVerificationToken = :token WHERE id = :id");
        $updateStmt->execute([':token' => $verificationToken, ':id' => $userId]);
        
        // Match Node.js: send confirmation email (stubbed - implement email service)
        // In production: sendEmail($email, "Confirm your account", $confirmLink);
        
        return [
            'success' => true,
            'user_id' => $userId,
            'verification_token' => $verificationToken,
            'message' => 'User registered. Please check your email to confirm.',
            'requiresConfirmation' => true
        ];
    } catch (PDOException $e) {
        error_log("Registration error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
    }
}

function confirmEmail($token) {
    $conn = getDBConnection();
    
    // Match Node.js: verify token and confirm email
    $stmt = $conn->prepare("SELECT * FROM users WHERE emailVerificationToken = :token");
    $stmt->execute([':token' => $token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        return ['success' => false, 'message' => 'Invalid or expired token'];
    }
    
    if ($user['isEmailConfirmed']) {
        return ['success' => false, 'message' => 'Email is already confirmed'];
    }
    
    $stmt = $conn->prepare("UPDATE users SET isEmailConfirmed = TRUE WHERE id = :id");
    $stmt->execute([':id' => $user['id']]);
    
    return ['success' => true, 'message' => 'Email confirmed successfully'];
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
    $stmt = $conn->prepare("SELECT addresses FROM users WHERE id = :id");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        return ['success' => false, 'message' => 'User not found'];
    }
    
    // Match Node.js: parse addresses JSON and add new address
    $addresses = !empty($user['addresses']) ? json_decode($user['addresses'], true) : [];
    $addresses[] = $address;
    
    $stmt = $conn->prepare("UPDATE users SET addresses = :addresses WHERE id = :id");
    $stmt->execute([
        ':addresses' => json_encode($addresses),
        ':id' => $userId
    ]);
    
    return ['success' => true, 'address' => end($addresses)];
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
?>
