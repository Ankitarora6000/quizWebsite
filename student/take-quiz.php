<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'student') {
    header('Location: ../auth/login.php');
    exit();
}

// Check if quiz ID is provided
if (!isset($_GET['id'])) {
    header('Location: quiz-list.php');
    exit();
}

$quiz_id = $_GET['id'];

// Check if the quiz exists and get its details
$stmt = $pdo->prepare("
    SELECT q.*, s.name as subject_name
    FROM quizzes q
    JOIN subjects s ON q.subject_id = s.id
    WHERE q.id = ?
");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch();

if (!$quiz) {
    header('Location: quiz-list.php');
    exit();
}

// Check if student has already attempted this quiz
$stmt = $pdo->prepare("
    SELECT id FROM quiz_attempts 
    WHERE quiz_id = ? AND student_id = ?
");
$stmt->execute([$quiz_id, $_SESSION['user_id']]);
if ($stmt->fetch()) {
    header('Location: quiz-list.php');
    exit();
}

// Get all questions for this quiz with their options
$stmt = $pdo->prepare("
    SELECT 
        q.*,
        GROUP_CONCAT(o.id) as option_ids,
        GROUP_CONCAT(o.option_text) as option_texts
    FROM questions q
    LEFT JOIN options o ON q.id = o.question_id
    WHERE q.quiz_id = ?
    GROUP BY q.id
    ORDER BY q.question_order
");
$stmt->execute([$quiz_id]);
$questions = $stmt->fetchAll();

if (empty($questions)) {
    header('Location: quiz-list.php');
    exit();
}

$page_title = htmlspecialchars($quiz['title']);

// Start output buffering
ob_start();
?>

<div class="dashboard-header">
    <div class="quiz-info">
        <h1><?php echo htmlspecialchars($quiz['title']); ?></h1>
        <p class="subject"><?php echo htmlspecialchars($quiz['subject_name']); ?></p>
    </div>
    <div class="quiz-meta">
        <div class="time-limit">
            <i class="fas fa-clock"></i>
            <span><?php echo $quiz['time_limit']; ?> minutes</span>
        </div>
        <div class="question-count">
            <i class="fas fa-question-circle"></i>
            <span><?php echo count($questions); ?> questions</span>
        </div>
    </div>
</div>

<div class="card quiz-instructions">
    <h2>Instructions</h2>
    <ul>
        <li>This quiz contains <?php echo count($questions); ?> questions</li>
        <li>You have <?php echo $quiz['time_limit']; ?> minutes to complete the quiz</li>
        <li>Each question has only one correct answer</li>
        <li>You cannot go back to previous questions</li>
        <li>The quiz will automatically submit when the time is up</li>
    </ul>
    <form action="process-quiz.php" method="POST" id="quizForm">
        <input type="hidden" name="quiz_id" value="<?php echo $quiz_id; ?>">
        <button type="submit" class="btn btn-primary">Start Quiz</button>
    </form>
</div>

<?php
$content = ob_get_clean();

$extra_css = '
<style>
    .quiz-info {
        margin-bottom: 1rem;
    }
    .quiz-info .subject {
        color: var(--text-light);
        font-size: 1.1rem;
        margin: 0;
    }
    .quiz-meta {
        display: flex;
        gap: 2rem;
        margin-bottom: 2rem;
    }
    .quiz-meta > div {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--text-dark);
    }
    .quiz-meta i {
        color: var(--primary-color);
        font-size: 1.2rem;
    }
    .quiz-instructions {
        max-width: 800px;
        margin: 0 auto;
    }
    .quiz-instructions h2 {
        color: var(--text-dark);
        margin-bottom: 1.5rem;
    }
    .quiz-instructions ul {
        list-style-type: none;
        padding: 0;
        margin-bottom: 2rem;
    }
    .quiz-instructions li {
        padding: 0.5rem 0;
        padding-left: 1.5rem;
        position: relative;
        color: var(--text-dark);
    }
    .quiz-instructions li:before {
        content: "•";
        color: var(--primary-color);
        font-weight: bold;
        position: absolute;
        left: 0;
    }
    .btn {
        display: inline-block;
        padding: 0.8rem 2rem;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 500;
        text-align: center;
        border: none;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }
    .btn-primary {
        background: var(--primary-color);
        color: white;
    }
    .btn-primary:hover {
        background: #0b5ed7;
    }
    #quizForm {
        text-align: center;
    }
</style>
';

require_once 'includes/layout.php';
?> 