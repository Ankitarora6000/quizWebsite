<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'student') {
    header('Location: ../auth/login.php');
    exit();
}

// Get all quiz attempts for the student
$stmt = $pdo->prepare("
    SELECT 
        qa.id as attempt_id,
        q.title as quiz_title,
        s.name as subject_name,
        qa.score,
        qa.started_at,
        qa.completed_at,
        TIMESTAMPDIFF(MINUTE, qa.started_at, qa.completed_at) as time_taken,
        (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as total_questions,
        (
            SELECT COUNT(*)
            FROM quiz_answers ans
            JOIN options o ON ans.selected_option_id = o.id
            WHERE ans.attempt_id = qa.id AND o.is_correct = 1
        ) as correct_answers
    FROM quiz_attempts qa
    JOIN quizzes q ON qa.quiz_id = q.id
    JOIN subjects s ON q.subject_id = s.id
    WHERE qa.student_id = ?
    ORDER BY qa.completed_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$attempts = $stmt->fetchAll();

$page_title = 'Quiz Results';

// Start output buffering
ob_start();
?>

<div class="page-header">
    <h1>Quiz Results</h1>
    <a href="quiz-list.php" class="btn btn-primary">Take New Quiz</a>
</div>

<?php if (empty($attempts)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">📝</div>
        <h2>No Quiz Attempts Yet</h2>
        <p>You haven't taken any quizzes yet. Start by taking your first quiz!</p>
        <a href="quiz-list.php" class="btn btn-primary">Browse Available Quizzes</a>
    </div>
<?php else: ?>
    <div class="results-container">
        <?php foreach ($attempts as $attempt): ?>
            <div class="result-card">
                <div class="result-header">
                    <div class="quiz-info">
                        <h2><?php echo htmlspecialchars($attempt['quiz_title']); ?></h2>
                        <span class="subject-badge"><?php echo htmlspecialchars($attempt['subject_name']); ?></span>
                    </div>
                    <div class="score-badge <?php echo $attempt['score'] >= 70 ? 'passed' : 'failed'; ?>">
                        <?php echo round($attempt['score'], 1); ?>%
                    </div>
                </div>
                
                <div class="result-details">
                    <div class="detail-item">
                        <span class="label">Correct Answers:</span>
                        <span class="value"><?php echo $attempt['correct_answers']; ?> / <?php echo $attempt['total_questions']; ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Time Taken:</span>
                        <span class="value"><?php echo $attempt['time_taken']; ?> minutes</span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Completed:</span>
                        <span class="value"><?php echo date('M j, Y g:i A', strtotime($attempt['completed_at'])); ?></span>
                    </div>
                </div>
                
                <div class="result-actions">
                    <a href="view-result.php?attempt=<?php echo $attempt['attempt_id']; ?>" class="btn btn-secondary">
                        View Details
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();

$extra_css = '
<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
    }
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .empty-state-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
    }
    .empty-state h2 {
        color: var(--text-dark);
        margin-bottom: 0.5rem;
    }
    .empty-state p {
        color: var(--text-light);
        margin-bottom: 1.5rem;
    }
    .results-container {
        display: grid;
        gap: 1.5rem;
    }
    .result-card {
        background: white;
        border-radius: 10px;
        padding: 1.5rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .result-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1.5rem;
    }
    .quiz-info h2 {
        margin: 0 0 0.5rem 0;
        font-size: 1.25rem;
        color: var(--text-dark);
    }
    .subject-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        background: var(--primary-color);
        color: white;
        border-radius: 15px;
        font-size: 0.875rem;
    }
    .score-badge {
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 1.25rem;
    }
    .score-badge.passed {
        background: #d4edda;
        color: #155724;
    }
    .score-badge.failed {
        background: #f8d7da;
        color: #721c24;
    }
    .result-details {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
    }
    .detail-item {
        display: flex;
        justify-content: space-between;
        padding: 0.5rem 0;
    }
    .detail-item:not(:last-child) {
        border-bottom: 1px solid #dee2e6;
    }
    .detail-item .label {
        color: var(--text-light);
    }
    .detail-item .value {
        color: var(--text-dark);
        font-weight: 500;
    }
    .result-actions {
        display: flex;
        justify-content: flex-end;
    }
    .btn {
        display: inline-block;
        padding: 0.5rem 1rem;
        border-radius: 5px;
        text-decoration: none;
        font-weight: 500;
        transition: background-color 0.2s;
    }
    .btn-primary {
        background: var(--primary-color);
        color: white;
    }
    .btn-primary:hover {
        background: var(--primary-dark);
    }
    .btn-secondary {
        background: #6c757d;
        color: white;
    }
    .btn-secondary:hover {
        background: #5a6268;
    }
    @media (max-width: 768px) {
        .result-header {
            flex-direction: column;
            gap: 1rem;
        }
        .score-badge {
            align-self: flex-start;
        }
    }
</style>
';

require_once 'includes/layout.php';
?> 