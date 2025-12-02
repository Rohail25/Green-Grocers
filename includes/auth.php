<?php
// Authentication Helper Functions

session_start();

function isAuthenticated() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user']);
}

function requireAuth($requiredRole = null) {
    if (!isAuthenticated()) {
        header('Location: ' . (defined('BASE_PATH') ? BASE_PATH : '') . '/auth/login.php');
        exit;
    }
    
    if ($requiredRole && $_SESSION['user']['role'] !== $requiredRole) {
        header('Location: ' . (defined('BASE_PATH') ? BASE_PATH : ''));
        exit;
    }
}

function loginUser($email, $password) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user'] = [
            'id' => $user['id'],
            'email' => $user['email'],
            'firstName' => $user['first_name'],
            'lastName' => $user['last_name'],
            'role' => $user['role']
        ];
        return true;
    }
    
    return false;
}

function registerUser($userData) {
    $conn = getDBConnection();
    
    // Extract email for checking
    $email = $userData['email'];
    
    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Email already exists'];
    }
    
    // Hash password
    $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
    $verificationToken = bin2hex(random_bytes(32));
    
    // Extract all values into variables for bind_param
    $phone = $userData['phone'] ?? '';
    $firstName = $userData['firstName'];
    $lastName = $userData['lastName'];
    $role = $userData['role'];
    $platform = $userData['platform'] ?? 'trivemart';
    
    // Insert user
    $stmt = $conn->prepare("INSERT INTO users (email, phone, password, first_name, last_name, role, platform, verification_token) 
                            VALUES (:email, :phone, :password, :first_name, :last_name, :role, :platform, :token)");
    
    $executed = $stmt->execute([
        ':email'      => $email,
        ':phone'      => $phone,
        ':password'   => $hashedPassword,
        ':first_name' => $firstName,
        ':last_name'  => $lastName,
        ':role'       => $role,
        ':platform'   => $platform,
        ':token'      => $verificationToken,
    ]);
    
    if ($executed) {
        $userId = $conn->lastInsertId();
        return [
            'success' => true,
            'user_id' => $userId,
            'verification_token' => $verificationToken
        ];
    }
    
    return ['success' => false, 'message' => 'Registration failed'];
}

function logoutUser() {
    session_destroy();
    header('Location: ' . (defined('BASE_PATH') ? BASE_PATH : '/'));
    exit;
}

function getCurrentUser() {
    return $_SESSION['user'] ?? null;
}
?>

