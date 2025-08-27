<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://10.144.73.68:3002"); // Change to your Next.js domain
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
        case 'create_subject':
            createSubject($data);
            break;
        case 'get_all_subjects':
            getAllSubjects();
            break;
        case 'get_subject':
            getSubject($_GET['id'] ?? null);
            break;
        case 'update_subject':
            updateSubject($data);
            break;
        case 'delete_subject':
            deleteSubject($_GET['id'] ?? null);
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

// ✅ Create Subject
function createSubject($data)
{
    global $pdo;
    $name = $data['name'] ?? '';
    $class_id = $data['class_id'] ?? null;

    if (!$class_id) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Class ID is required'
        ]);
        return;
    }

    $stmt = $pdo->prepare("INSERT INTO subjects (name, class_id, created_at) VALUES (:name, :class_id, NOW())");
    $stmt->execute([
        'name' => $name,
        'class_id' => $class_id
    ]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Subject created successfully'
    ]);
}

// ✅ Get All Subjects
function getAllSubjects()
{
    global $pdo;

    $stmt = $pdo->query("SELECT subjects.*, classes.name AS class_name 
                         FROM subjects 
                         JOIN classes ON subjects.class_id = classes.id");
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $subjects
    ]);
}

// ✅ Get Single Subject
function getSubject($id)
{
    global $pdo;

    if (!$id) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Subject ID is required'
        ]);
        return;
    }

    $stmt = $pdo->prepare("SELECT subjects.*, classes.name AS class_name 
                           FROM subjects 
                           JOIN classes ON subjects.class_id = classes.id 
                           WHERE subjects.id = :id");
    $stmt->execute(['id' => $id]);
    $subject = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($subject) {
        echo json_encode([
            'status' => 'success',
            'data' => $subject
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'Subject not found'
        ]);
    }
}

// ✅ Update Subject
function updateSubject($data)
{
    global $pdo;
    $id = $data['id'] ?? null;
    $name = $data['name'] ?? '';
    $class_id = $data['class_id'] ?? null;

    if (!$id || !$class_id) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Subject ID and Class ID are required'
        ]);
        return;
    }

    $stmt = $pdo->prepare("UPDATE subjects 
                           SET name = :name, class_id = :class_id, updated_at = NOW() 
                           WHERE id = :id");
    $stmt->execute([
        'name' => $name,
        'class_id' => $class_id,
        'id' => $id
    ]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Subject updated successfully'
    ]);
}

// ✅ Delete Subject
function deleteSubject($id)
{
    global $pdo;

    if (!$id) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Subject ID is required'
        ]);
        return;
    }

    $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = :id");
    $stmt->execute(['id' => $id]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Subject deleted successfully'
    ]);
}
