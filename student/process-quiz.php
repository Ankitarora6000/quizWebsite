<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'student') {
    header('Location: ../auth/login.php');
    exit();
}

// Check if quiz ID is provided
if (!isset($_POST['quiz_id'])) {
    header('Location: quiz-list.php');
    exit();
}

$quiz_id = $_POST['quiz_id'];

// Start transaction
$pdo->beginTransaction();

try {
    // Get quiz details
    $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
    $stmt->execute([$quiz_id]);
    $quiz = $stmt->fetch();

    if (!$quiz) {
        throw new Exception("Quiz not found");
    }

    // Check if student has already attempted this quiz
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM quiz_attempts WHERE quiz_id = ? AND student_id = ?");
    $stmt->execute([$quiz_id, $_SESSION['user_id']]);
    $result = $stmt->fetch();
    if ($result['count'] > 0) {
        throw new Exception("Quiz already attempted");
    }

    // Create quiz attempt with initial values
    $stmt = $pdo->prepare("
        INSERT INTO quiz_attempts (quiz_id, student_id, score, time_taken, started_at, completed_at) 
        VALUES (?, ?, 0, 0, NOW(), NULL)
    ");
    $stmt->execute([$quiz_id, $_SESSION['user_id']]);
    $attempt_id = $pdo->lastInsertId();

    // Get all questions for this quiz
    $stmt = $pdo->prepare("
        SELECT 
            q.id,
            q.question_text,
            q.question_order,
            GROUP_CONCAT(o.id ORDER BY o.id) as option_ids,
            GROUP_CONCAT(o.option_text ORDER BY o.id) as option_texts
        FROM questions q
        INNER JOIN options o ON q.id = o.question_id
        WHERE q.quiz_id = ?
        GROUP BY q.id, q.question_text, q.question_order
        ORDER BY q.question_order
    ");
    $stmt->execute([$quiz_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($questions)) {
        throw new Exception("No questions found for this quiz");
    }

    // Validate each question has options
    foreach ($questions as $question) {
        if (empty($question['option_ids']) || empty($question['option_texts'])) {
            throw new Exception("Some questions are missing options");
        }
    }

    // Store quiz attempt in session
    $_SESSION['quiz_attempt'] = [
        'attempt_id' => $attempt_id,
        'quiz_id' => $quiz_id,
        'questions' => $questions,
        'current_question' => 0,
        'start_time' => time(),
        'time_limit' => $quiz['time_limit'] // Already in seconds from database
    ];

    $pdo->commit();
    
    // Redirect to the first question
    header('Location: answer-question.php');
    exit();

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = $e->getMessage();
    header('Location: quiz-list.php');
    exit();
}
?> 