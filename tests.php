<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://192.168.100.5:3000"); // Change to your Next.js domahttp://192.168.100.5:3000/locationsin
// header("Access-Control-Allow-Origin: http://10.144.73.68:3000"); // Change to your Next.js domain
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
        case 'create_test':
            createTest($data);
            break;
        case 'get_all_tests':
            getAllTests();
            break;
        case 'get_test':
            getTest($_GET['id'] ?? null);
            break;
        case 'update_test':
            updateTest($data);
            break;
        case 'delete_test':
            deleteTest($_GET['id'] ?? null);
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

// ✅ Create Test
function createTest($data)
{
    global $pdo;
    $title = $data['title'] ?? '';
    $class_id = $data['class_id'] ?? null;
    $subject_id = $data['subject_id'] ?? null;
    $teacher_id = $data['teacher_id'] ?? null;
    $test_type = $data['test_type'] ?? 'objective';
    $date = $data['date'] ?? null;
    $duration = $data['duration'] ?? null;

    if (!$class_id || !$subject_id || !$teacher_id) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Class ID, Subject ID and Teacher ID are required'
        ]);
        return;
    }

    $stmt = $pdo->prepare("INSERT INTO tests (title, class_id, subject_id, teacher_id, test_type, date, duration) 
                           VALUES (:title, :class_id, :subject_id, :teacher_id, :test_type, :date, :duration)");
    $stmt->execute([
        'title' => $title,
        'class_id' => $class_id,
        'subject_id' => $subject_id,
        'teacher_id' => $teacher_id,
        'test_type' => $test_type,
        'date' => $date,
        'duration' => $duration
    ]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Test created successfully'
    ]);
}

// ✅ Get All Tests
function getAllTests()
{
    global $pdo;

    $stmt = $pdo->query("SELECT tests.*, 
                                classes.name AS class_name, 
                                subjects.name AS subject_name, 
                                users.first_name AS teacher_name 
                         FROM tests
                         JOIN classes ON tests.class_id = classes.id
                         JOIN subjects ON tests.subject_id = subjects.id
                         JOIN users ON tests.teacher_id = users.id");
    $tests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $tests
    ]);
}

// ✅ Get Single Test
// function getTest($id)
// {
//     global $pdo;

//     if (!$id) {
//         http_response_code(400);
//         echo json_encode([
//             'status' => 'error',
//             'message' => 'Test ID is required'
//         ]);
//         return;
//     }

//     $stmt = $pdo->prepare("SELECT tests.*, 
//                                   classes.name AS class_name, 
//                                   subjects.name AS subject_name, 
//                                   users.first_name AS teacher_name 
//                            FROM tests
//                            JOIN classes ON tests.class_id = classes.id
//                            JOIN subjects ON tests.subject_id = subjects.id
//                            JOIN users ON tests.teacher_id = users.id
//                            WHERE tests.id = :id");
//     $stmt->execute(['id' => $id]);
//     $test = $stmt->fetch(PDO::FETCH_ASSOC);

//     if ($test) {
//         echo json_encode([
//             'status' => 'success',
//             'data' => $test
//         ]);
//     } else {
//         http_response_code(404);
//         echo json_encode([
//             'status' => 'error',
//             'message' => 'Test not found'
//         ]);
//     }
// }
function getTest($id)
{
    global $pdo;

    if (!$id) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Test ID is required'
        ]);
        return;
    }

    // Fetch test
    $stmt = $pdo->prepare("SELECT tests.*, 
                                  classes.name AS class_name, 
                                  subjects.name AS subject_name, 
                                  users.first_name AS teacher_name 
                           FROM tests
                           JOIN classes ON tests.class_id = classes.id
                           JOIN subjects ON tests.subject_id = subjects.id
                           JOIN users ON tests.teacher_id = users.id
                           WHERE tests.id = :id");
    $stmt->execute(['id' => $id]);
    $test = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($test) {
        // Fetch related questions
        $qstmt = $pdo->prepare("SELECT * FROM questions WHERE test_id = :test_id");
        $qstmt->execute(['test_id' => $id]);
        $questions = $qstmt->fetchAll(PDO::FETCH_ASSOC);

        $test['questions'] = $questions;

        echo json_encode([
            'status' => 'success',
            'data' => $test
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'Test not found'
        ]);
    }
}

// ✅ Update Test
// function updateTest($data)
// {
//     global $pdo;
//     $id = $data['id'] ?? null;
//     $title = $data['title'] ?? '';
//     $class_id = $data['class_id'] ?? null;
//     $subject_id = $data['subject_id'] ?? null;
//     $teacher_id = $data['teacher_id'] ?? null;
//     $test_type = $data['test_type'] ?? 'objective';
//     $date = $data['date'] ?? null;
//     $duration = $data['duration'] ?? null;

//     if (!$id || !$class_id || !$subject_id || !$teacher_id) {
//         http_response_code(400);
//         echo json_encode([
//             'status' => 'error',
//             'message' => 'Test ID, Class ID, Subject ID and Teacher ID are required'
//         ]);
//         return;
//     }

//     $stmt = $pdo->prepare("UPDATE tests 
//                            SET title = :title, class_id = :class_id, subject_id = :subject_id, 
//                                teacher_id = :teacher_id, test_type = :test_type, date = :date, duration = :duration 
//                            WHERE id = :id");
//     $stmt->execute([
//         'title' => $title,
//         'class_id' => $class_id,
//         'subject_id' => $subject_id,
//         'teacher_id' => $teacher_id,
//         'test_type' => $test_type,
//         'date' => $date,
//         'duration' => $duration,
//         'id' => $id
//     ]);

//     echo json_encode([
//         'status' => 'success',
//         'message' => 'Test updated successfully'
//     ]);
// }

// ✅ Update Test + Questions
function updateTest($data)
{
    global $pdo;

    $id = $data['id'] ?? null;
    $title = $data['title'] ?? '';
    $class_id = $data['class_id'] ?? null;
    $subject_id = $data['subject_id'] ?? null;
    $teacher_id = $data['teacher_id'] ?? null;
    $test_type = $data['test_type'] ?? 'objective';
    $date = $data['date'] ?? null;
    $duration = $data['duration'] ?? null;
    $questions = $data['questions'] ?? [];

    if (!$id || !$class_id || !$subject_id || !$teacher_id) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Test ID, Class ID, Subject ID and Teacher ID are required'
        ]);
        return;
    }

    $stmt = $pdo->prepare("UPDATE tests 
                           SET title = :title, class_id = :class_id, subject_id = :subject_id, 
                               teacher_id = :teacher_id, test_type = :test_type, date = :date, duration = :duration 
                           WHERE id = :id");
    $stmt->execute([
        'title' => $title,
        'class_id' => $class_id,
        'subject_id' => $subject_id,
        'teacher_id' => $teacher_id,
        'test_type' => $test_type,
        'date' => $date,
        'duration' => $duration,
        'id' => $id
    ]);

    // Update questions
    foreach ($questions as $q) {
        if (isset($q['id'])) {
            // Update existing
            $qstmt = $pdo->prepare("UPDATE questions 
                                    SET question_type = :question_type, question_text = :question_text, marks = :marks, correct_answer = :correct_answer 
                                    WHERE id = :id AND test_id = :test_id");
            $qstmt->execute([
                'id' => $q['id'],
                'test_id' => $id,
                'question_type' => $q['question_type'],
                'question_text' => $q['question_text'],
                'marks' => $q['marks'],
                'correct_answer' => $q['correct_answer']
            ]);
        } else {
            // Insert new
            $qstmt = $pdo->prepare("INSERT INTO questions (test_id, question_type, question_text, marks, correct_answer) 
                                    VALUES (:test_id, :question_type, :question_text, :marks, :correct_answer)");
            $qstmt->execute([
                'test_id' => $id,
                'question_type' => $q['question_type'],
                'question_text' => $q['question_text'],
                'marks' => $q['marks'],
                'correct_answer' => $q['correct_answer']
            ]);
        }
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Test and questions updated successfully'
    ]);
}
// ✅ Delete Test
function deleteTest($id)
{
    global $pdo;

    if (!$id) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Test ID is required'
        ]);
        return;
    }

    $stmt = $pdo->prepare("DELETE FROM tests WHERE id = :id");
    $stmt->execute(['id' => $id]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Test deleted successfully'
    ]);
}
