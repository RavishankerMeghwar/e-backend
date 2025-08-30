<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:3000"); // Change to your Next.js domahttp://192.168.100.5:3000/locationsin
// header("Access-Control-Allow-Origin: http://10.144.73.68:3000"); // Change to your Next.js domahttp://192.168.100.5:3000/locationsin
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
        case 'create_class':
            createClass($data);
            break;
        case 'get_all_classes':
            getAllClasses();
            break;
        case 'get_class':
            getClass($_GET['id'] ?? null);
            break;
        case 'update_class':
            updateClass($data);
            break;
        case 'delete_class':
            deleteClass($_GET['id'] ?? null);
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

function createClass($data)
{
    global $pdo;
    $name = $data['name'] ?? '';
    $description = $data['description'] ?? '';

    $stmt = $pdo->prepare("INSERT INTO classes (name, description, created_at) VALUES (:name, :description, NOW())");
    $stmt->execute([
        'name' => $name,
        'description' => $description
    ]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Class created successfully'
    ]);
}

function getAllClasses()
{
    global $pdo;

    $stmt = $pdo->query("SELECT * FROM classes");
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $classes
    ]);
}

function getClass($id)
{
    global $pdo;

    if (!$id) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Class ID is required'
        ]);
        return;
    }

    $stmt = $pdo->prepare("SELECT * FROM classes WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $class = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($class) {
        echo json_encode([
            'status' => 'success',
            'data' => $class
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'Class not found'
        ]);
    }
}

function updateClass($data)
{
    global $pdo;
    $id = $data['id'] ?? null;
    $name = $data['name'] ?? '';
    $description = $data['description'] ?? '';

    if (!$id) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Class ID is required'
        ]);
        return;
    }

    $stmt = $pdo->prepare("UPDATE classes SET name = :name, description = :description WHERE id = :id");
    $stmt->execute([
        'name' => $name,
        'description' => $description,
        'id' => $id
    ]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Class updated successfully'
    ]);
}

function deleteClass($id)
{
    global $pdo;

    if (!$id) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Class ID is required'
        ]);
        return;
    }

    $stmt = $pdo->prepare("DELETE FROM classes WHERE id = :id");
    $stmt->execute(['id' => $id]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Class deleted successfully'
    ]);
}
