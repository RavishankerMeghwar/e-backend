<?php
require_once 'cors.php';
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
        case 'delete_question':
            deleteQuestion($data);
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

    try {
        // Begin transaction
        $pdo->beginTransaction();

        // Update test details
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

        // Get existing question IDs for this test
        $existingStmt = $pdo->prepare("SELECT id FROM questions WHERE test_id = :test_id");
        $existingStmt->execute(['test_id' => $id]);
        $existingQuestionIds = $existingStmt->fetchAll(PDO::FETCH_COLUMN);
        
        $processedQuestionIds = [];

        // Update/Insert questions
        foreach ($questions as $q) {
            // Safely handle options
            $options = null;
            if (isset($q['options'])) {
                if (is_array($q['options'])) {
                    $options = json_encode($q['options']);
                } else if (is_string($q['options'])) {
                    // Check if it's already JSON
                    $decoded = json_decode($q['options'], true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $options = $q['options']; // Already valid JSON
                    } else {
                        $options = json_encode([$q['options']]); // Wrap in array
                    }
                } else {
                    $options = json_encode([]);
                }
            } else {
                $options = json_encode([]);
            }

            if (isset($q['id']) && !empty($q['id'])) {
                // Update existing question
                $qstmt = $pdo->prepare("UPDATE questions 
                                        SET question_type = :question_type, question_text = :question_text, 
                                            marks = :marks, correct_answer = :correct_answer, options = :options
                                        WHERE id = :id AND test_id = :test_id");
                $qstmt->execute([
                    'id' => $q['id'],
                    'test_id' => $id,
                    'question_type' => $q['question_type'] ?? 'objective',
                    'question_text' => $q['question_text'] ?? '',
                    'marks' => $q['marks'] ?? 1,
                    'correct_answer' => $q['correct_answer'] ?? '',
                    'options' => $options
                ]);
                $processedQuestionIds[] = $q['id'];
            } else {
                // Insert new question
                $qstmt = $pdo->prepare("INSERT INTO questions (test_id, question_type, question_text, marks, correct_answer, options) 
                                        VALUES (:test_id, :question_type, :question_text, :marks, :correct_answer, :options)");
                $qstmt->execute([
                    'test_id' => $id,
                    'question_type' => $q['question_type'] ?? 'objective',
                    'question_text' => $q['question_text'] ?? '',
                    'marks' => $q['marks'] ?? 1,
                    'correct_answer' => $q['correct_answer'] ?? '',
                    'options' => $options
                ]);
                $processedQuestionIds[] = $pdo->lastInsertId();
            }
        }

        // Delete questions that were removed (exist in DB but not in the update)
        $questionsToDelete = array_diff($existingQuestionIds, $processedQuestionIds);
        if (!empty($questionsToDelete)) {
            $placeholders = str_repeat('?,', count($questionsToDelete) - 1) . '?';
            $deleteStmt = $pdo->prepare("DELETE FROM questions WHERE test_id = ? AND id IN ($placeholders)");
            $deleteStmt->execute(array_merge([$id], $questionsToDelete));
        }

        // Commit transaction
        $pdo->commit();

        echo json_encode([
            'status' => 'success',
            'message' => 'Test and questions updated successfully'
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to update test: ' . $e->getMessage()
        ]);
    }
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


function deleteQuestion($data)
{
    global $pdo;

    $question_id = $data['question_id'] ?? null;
    $test_id = $data['test_id'] ?? null;

    // Validate required parameters
    if (!$question_id) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Question ID is required'
        ]);
        return;
    }

    try {
        // Begin transaction
        $pdo->beginTransaction();

        // If test_id is provided, verify the question belongs to that test
        if ($test_id) {
            $verifyStmt = $pdo->prepare("SELECT id FROM questions WHERE id = :question_id AND test_id = :test_id");
            $verifyStmt->execute([
                'question_id' => $question_id,
                'test_id' => $test_id
            ]);

            if (!$verifyStmt->fetch()) {
                http_response_code(404);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Question not found or does not belong to the specified test'
                ]);
                $pdo->rollBack();
                return;
            }
        }

        // Delete the question
        $deleteStmt = $pdo->prepare("DELETE FROM questions WHERE id = :question_id");
        $result = $deleteStmt->execute(['question_id' => $question_id]);

        if ($result && $deleteStmt->rowCount() > 0) {
            // Commit transaction
            $pdo->commit();

            echo json_encode([
                'status' => 'success',
                'message' => 'Question deleted successfully',
                'deleted_question_id' => $question_id
            ]);
        } else {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode([
                'status' => 'error',
                'message' => 'Question not found or already deleted'
            ]);
        }

    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to delete question: ' . $e->getMessage()
        ]);
    }
}

