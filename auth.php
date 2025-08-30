<?php
// ✅ Allow CORS (set before any output)
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:3000"); // Change to your Next.js domain
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true"); // Only if using cookies/sessions

// ✅ Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
// ✅ Include database connection
require_once 'database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'login':
            login($data);
            break;
        case 'create_user':
            createUser($data);
            break;
        case 'forgot_password':
            forgotPassword($data);
            break;
        case 'reset_password':
            resetPassword($data);
            break;
        default:
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid action'
            ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed'
    ]);
}

function login($data)
{
    global $pdo;
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $token = bin2hex(random_bytes(16));
        $stmt = $pdo->prepare("UPDATE users SET token = :token WHERE id = :id");
        $stmt->execute(['token' => $token, 'id' => $user['id']]);

        echo json_encode([
            'status' => 'success',
            'message' => 'Login successful',
            'user' => [
                'id' => $user['id'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'email' => $user['email'],
                'role' => $user['role']
            ],
            'token' => $token
        ]);
    } else {
        http_response_code(401);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid email or password'
        ]);
    }
}

function createUser($data)
{
    global $pdo;
    $firstName = $data['first_name'] ?? '';
    $lastName = $data['last_name'] ?? '';
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    $role = $data['role'] ?? 'user';

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, role, created_at) VALUES (:first_name, :last_name, :email, :password, :role, NOW())");
    $stmt->execute([
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'password' => $hashedPassword,
        'role' => $role
    ]);

    echo json_encode([
        'status' => 'success',
        'message' => 'User created successfully'
    ]);
}

function forgotPassword($data)
{
    global $pdo;
    $email = $data['email'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if ($user) {
        $token = rand(4, 999999);
        $stmt = $pdo->prepare("UPDATE users SET token = :token WHERE id = :id");
        $stmt->execute(['token' => $token, 'id' => $user['id']]);

        // Send email logic here (e.g., using PHPMailer)
        echo json_encode([
            'status' => 'success',
            'message' => 'Password reset link sent to your email',
            "token" => $token
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'Email not found'
        ]);
    }
}

function resetPassword($data)
{
    global $pdo;
    $token = $data['token'] ?? '';
    $newPassword = $data['new_password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE token = :token");
    $stmt->execute(['token' => $token]);
    $user = $stmt->fetch();

    if ($user) {
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET password = :password, token = NULL WHERE id = :id");
        $stmt->execute(['password' => $hashedPassword, 'id' => $user['id']]);

        echo json_encode([
            'status' => 'success',
            'message' => 'Password reset successfully'
        ]);
    } else {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid token'
        ]);
    }
}
