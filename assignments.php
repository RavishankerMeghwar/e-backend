<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://192.168.100.5:3000"); // Change to your Next.js domahttp://192.168.100.5:3000/locationsin
// header("Access-Control-Allow-Origin: http://10.144.73.68:3000"); // Change to your Next.js domahttp://192.168.100.5:3000/locationsin
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

// ✅ Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'database.php'; // make sure you have PDO in database.php

$action = $_GET['action'] ?? '';
$data = json_decode(file_get_contents("php://input"), true);

switch ($action) {
    case 'create_assignment':
        createAssignment($data);
        break;
    case 'get_all_assignments':
        getAllAssignments();
        break;
    case 'get_assignment':
        getAssignment($_GET['id'] ?? null);
        break;
    case 'update_assignment':
        updateAssignment($data);
        break;
    case 'delete_assignment':
        deleteAssignment($_GET['id'] ?? null);
        break;
    default:
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid action'
        ]);
}

// ✅ Create Assignment
function createAssignment($data)
{
    global $pdo;

    $title = $data['title'] ?? '';
    $description = $data['description'] ?? '';
    $class_id = $data['class_id'] ?? null;
    $subject_id = $data['subject_id'] ?? null;
    $teacher_id = $data['teacher_id'] ?? null;
    $due_date = $data['due_date'] ?? null;

    if (!$class_id || !$subject_id || !$teacher_id) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Class ID, Subject ID and Teacher ID are required']);
        return;
    }

    $stmt = $pdo->prepare("INSERT INTO assignments (title, description, class_id, subject_id, teacher_id, due_date) 
                           VALUES (:title, :description, :class_id, :subject_id, :teacher_id, :due_date)");
    $stmt->execute([
        'title' => $title,
        'description' => $description,
        'class_id' => $class_id,
        'subject_id' => $subject_id,
        'teacher_id' => $teacher_id,
        'due_date' => $due_date
    ]);

    echo json_encode(['status' => 'success', 'message' => 'Assignment created successfully']);
}

// ✅ Get All Assignments
function getAllAssignments()
{
    global $pdo;

    $stmt = $pdo->query("SELECT a.*, 
                                c.name AS class_name, 
                                s.name AS subject_name, 
                                u.first_name AS teacher_name
                         FROM assignments a
                         JOIN classes c ON a.class_id = c.id
                         JOIN subjects s ON a.subject_id = s.id
                         JOIN users u ON a.teacher_id = u.id
                         ORDER BY a.created_at DESC");
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $assignments]);
}

// ✅ Get Single Assignment
function getAssignment($id)
{
    global $pdo;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Assignment ID is required']);
        return;
    }

    $stmt = $pdo->prepare("SELECT a.*, 
                                  c.name AS class_name, 
                                  s.name AS subject_name, 
                                  u.first_name AS teacher_name
                           FROM assignments a
                           JOIN classes c ON a.class_id = c.id
                           JOIN subjects s ON a.subject_id = s.id
                           JOIN users u ON a.teacher_id = u.id
                           WHERE a.id = :id");
    $stmt->execute(['id' => $id]);
    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($assignment) {
        echo json_encode(['status' => 'success', 'data' => $assignment]);
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Assignment not found']);
    }
}

// ✅ Update Assignment
function updateAssignment($data)
{
    global $pdo;

    $id = $data['id'] ?? null;
    $title = $data['title'] ?? '';
    $description = $data['description'] ?? '';
    $class_id = $data['class_id'] ?? null;
    $subject_id = $data['subject_id'] ?? null;
    $teacher_id = $data['teacher_id'] ?? null;
    $due_date = $data['due_date'] ?? null;

    if (!$id || !$class_id || !$subject_id || !$teacher_id) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Assignment ID, Class ID, Subject ID and Teacher ID are required']);
        return;
    }

    $stmt = $pdo->prepare("UPDATE assignments 
                           SET title = :title, description = :description, class_id = :class_id, 
                               subject_id = :subject_id, teacher_id = :teacher_id, due_date = :due_date
                           WHERE id = :id");
    $stmt->execute([
        'title' => $title,
        'description' => $description,
        'class_id' => $class_id,
        'subject_id' => $subject_id,
        'teacher_id' => $teacher_id,
        'due_date' => $due_date,
        'id' => $id
    ]);

    echo json_encode(['status' => 'success', 'message' => 'Assignment updated successfully']);
}

// ✅ Delete Assignment
function deleteAssignment($id)
{
    global $pdo;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Assignment ID is required']);
        return;
    }

    $stmt = $pdo->prepare("DELETE FROM assignments WHERE id = :id");
    $stmt->execute(['id' => $id]);

    echo json_encode(['status' => 'success', 'message' => 'Assignment deleted successfully']);
}
