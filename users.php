<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://10.144.73.68:3000"); // Change to your Next.js domain
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true"); // Only if using cookies/sessions

// ✅ Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    $data = json_decode(file_get_contents("php://input"), true);
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'create_user':
            createUser($data);
            break;
        case 'get_all_users':
            getAllUsers();
            break;
        case 'get_user':
            getUser($_GET['id'] ?? null);
            break;
        case 'update_user':
            updateUser($data);
            break;
        case 'delete_user':
            deleteUser($_GET['id'] ?? null);
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

// ✅ Create User
function createUser($data)
{
    global $pdo;
    $first_name = $data['first_name'] ?? '';
    $last_name = $data['last_name'] ?? '';
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    $role = $data['role'] ?? 'student';

    if (!$email || !$password) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Email and Password are required'
        ]);
        return;
    }

    // ✅ Hash password before storing
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, role, created_at) 
                               VALUES (:first_name, :last_name, :email, :password, :role, NOW())");
        $stmt->execute([
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'password' => $hashedPassword,
            'role' => $role
        ]);

        echo json_encode([
            'status' => 'success',
            'message' => 'User created successfully'
        ]);
    } catch (PDOException $e) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Email already exists or invalid data'
        ]);
    }
}

// ✅ Get All Users
function getAllUsers()
{
    global $pdo;

    $stmt = $pdo->query("SELECT id, first_name, last_name, email, role, created_at FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $users
    ]);
}

// ✅ Get Single User
function getUser($id)
{
    global $pdo;

    if (!$id) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'User ID is required'
        ]);
        return;
    }

    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, role, created_at, updated_at 
                           FROM users WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo json_encode([
            'status' => 'success',
            'data' => $user
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'User not found'
        ]);
    }
}

// ✅ Update User
function updateUser($data)
{
    global $pdo;
    $id = $data['id'] ?? null;
    $first_name = $data['first_name'] ?? '';
    $last_name = $data['last_name'] ?? '';
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? null; // Optional
    $role = $data['role'] ?? 'student';

    if (!$id) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'User ID is required'
        ]);
        return;
    }

    if ($password) {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users 
                               SET first_name = :first_name, last_name = :last_name, email = :email, 
                                   password = :password, role = :role, updated_at = NOW() 
                               WHERE id = :id");
        $stmt->execute([
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'password' => $hashedPassword,
            'role' => $role,
            'id' => $id
        ]);
    } else {
        $stmt = $pdo->prepare("UPDATE users 
                               SET first_name = :first_name, last_name = :last_name, email = :email, 
                                   role = :role, updated_at = NOW() 
                               WHERE id = :id");
        $stmt->execute([
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'role' => $role,
            'id' => $id
        ]);
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'User updated successfully'
    ]);
}

// ✅ Delete User
function deleteUser($id)
{
    global $pdo;

    if (!$id) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'User ID is required'
        ]);
        return;
    }

    $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
    $stmt->execute(['id' => $id]);

    echo json_encode([
        'status' => 'success',
        'message' => 'User deleted successfully'
    ]);
}
