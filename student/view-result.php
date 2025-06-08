<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'student') {
    header('Location: ../auth/login.php');
    exit();
}

// Check if attempt ID is provided
if (!isset($_GET['attempt'])) {
    header('Location: see-result.php');
    exit();
}

$attempt_id = $_GET['attempt'];

// Get attempt details with quiz and subject information
$stmt = $pdo->prepare("
    SELECT 
        qa.*,
        q.title as quiz_title,
        q.time_limit,
        s.name as subject_name,
        (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as total_questions,
        (
            SELECT COUNT(*)
            FROM quiz_answers qaa
            JOIN options o ON qaa.selected_option_id = o.id
            WHERE qaa.attempt_id = qa.id AND o.is_correct = 1
        ) as correct_answers
    FROM quiz_attempts qa
    JOIN quizzes q ON qa.quiz_id = q.id
    JOIN subjects s ON q.subject_id = s.id
    WHERE qa.id = ? AND qa.student_id = ?
");
$stmt->execute([$attempt_id, $_SESSION['user_id']]);
$attempt = $stmt->fetch();

if (!$attempt) {
    header('Location: see-result.php');
    exit();
}

// Get detailed question answers
$stmt = $pdo->prepare("
    SELECT 
        q.question_text,
        o.is_correct,
        o.option_text as selected_option,
        (
            SELECT option_text 
            FROM options 
            WHERE question_id = q.id AND is_correct = 1 
            LIMIT 1
        ) as correct_option
    FROM quiz_answers qaa
    JOIN questions q ON qaa.question_id = q.id
    JOIN options o ON qaa.selected_option_id = o.id
    WHERE qaa.attempt_id = ?
    ORDER BY q.question_order
");
$stmt->execute([$attempt_id]);
$answers = $stmt->fetchAll();

$page_title = 'Quiz Result: ' . htmlspecialchars($attempt['quiz_title']);

// Start output buffering
ob_start();
?>

<div class="dashboard-header">
    <h1><?php echo htmlspecialchars($attempt['quiz_title']); ?></h1>
    <p class="subject"><?php echo htmlspecialchars($attempt['subject_name']); ?></p>
</div>

<div class="result-summary card">
    <div class="score-section">
        <div class="score-display <?php echo $attempt['score'] >= 70 ? 'score-passed' : 'score-failed'; ?>">
            <?php echo round($attempt['score'], 1); ?>%
        </div>
        <div class="score-label">
            <?php if ($attempt['score'] >= 70): ?>
                <span class="badge badge-success">Passed</span>
            <?php else: ?>
                <span class="badge badge-danger">Failed</span>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="stats-grid">
        <div class="stat-item">
            <span class="stat-label">Correct Answers</span>
            <span class="stat-value"><?php echo $attempt['correct_answers']; ?>/<?php echo $attempt['total_questions']; ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">Time Taken</span>
            <span class="stat-value">
                <?php 
                $start = new DateTime($attempt['started_at']);
                $end = new DateTime($attempt['completed_at']);
                $interval = $start->diff($end);
                echo $interval->format('%H:%I:%S');
                ?>
            </span>
        </div>
        <div class="stat-item">
            <span class="stat-label">Completed On</span>
            <span class="stat-value"><?php echo date('M j, Y g:i A', strtotime($attempt['completed_at'])); ?></span>
        </div>
    </div>
</div>

<div class="answers-section">
    <h2>Detailed Review</h2>
    <?php foreach ($answers as $index => $answer): ?>
        <div class="answer-card card">
            <div class="question-header">
                <span class="question-number">Question <?php echo $index + 1; ?></span>
                <?php if ($answer['is_correct']): ?>
                    <span class="badge badge-success">Correct</span>
                <?php else: ?>
                    <span class="badge badge-danger">Incorrect</span>
                <?php endif; ?>
            </div>
            
            <div class="question-text">
                <?php echo htmlspecialchars($answer['question_text']); ?>
            </div>
            
            <div class="answers-detail">
                <div class="answer-item">
                    <span class="answer-label">Your Answer:</span>
                    <span class="answer-value <?php echo $answer['is_correct'] ? 'text-success' : 'text-danger'; ?>">
                        <?php echo htmlspecialchars($answer['selected_option']); ?>
                    </span>
                </div>
                <?php if (!$answer['is_correct']): ?>
                    <div class="answer-item">
                        <span class="answer-label">Correct Answer:</span>
                        <span class="answer-value text-success">
                            <?php echo htmlspecialchars($answer['correct_option']); ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="actions">
    <a href="see-result.php" class="btn btn-secondary">Back to Results</a>
    <a href="quiz-list.php" class="btn btn-primary">Take Another Quiz</a>
</div>

<?php
$content = ob_get_clean();

$extra_css = '
<style>
    .result-summary {
        text-align: center;
        padding: 2rem;
        margin-bottom: 2rem;
    }
    .score-section {
        margin-bottom: 2rem;
    }
    .score-display {
        font-size: 4rem;
        font-weight: bold;
        margin-bottom: 0.5rem;
    }
    .score-passed {
        color: #28a745;
    }
    .score-failed {
        color: #dc3545;
    }
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-top: 2rem;
    }
    .stat-item {
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    .stat-label {
        color: var(--text-light);
        margin-bottom: 0.5rem;
    }
    .stat-value {
        font-size: 1.2rem;
        font-weight: 500;
        color: var(--text-dark);
    }
    .answers-section {
        margin-top: 2rem;
    }
    .answers-section h2 {
        margin-bottom: 1.5rem;
        color: var(--text-dark);
    }
    .answer-card {
        margin-bottom: 1.5rem;
        padding: 1.5rem;
    }
    .question-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }
    .question-number {
        font-weight: 500;
        color: var(--text-dark);
    }
    .question-text {
        font-size: 1.1rem;
        color: var(--text-dark);
        margin-bottom: 1.5rem;
    }
    .answers-detail {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 8px;
    }
    .answer-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0;
    }
    .answer-item:not(:last-child) {
        border-bottom: 1px solid #dee2e6;
    }
    .answer-label {
        color: var(--text-light);
    }
    .text-success {
        color: #28a745;
    }
    .text-danger {
        color: #dc3545;
    }
    .badge {
        padding: 0.5rem 1rem;
        border-radius: 4px;
        font-weight: 500;
    }
    .badge-success {
        background: #d4edda;
        color: #155724;
    }
    .badge-danger {
        background: #f8d7da;
        color: #721c24;
    }
    .actions {
        display: flex;
        gap: 1rem;
        justify-content: center;
        margin-top: 2rem;
    }
    .btn {
        display: inline-block;
        padding: 0.8rem 2rem;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 500;
        text-align: center;
    }
    .btn-primary {
        background: var(--primary-color);
        color: white;
    }
    .btn-secondary {
        background: #6c757d;
        color: white;
    }
</style>
';

require_once 'includes/layout.php';
?> 