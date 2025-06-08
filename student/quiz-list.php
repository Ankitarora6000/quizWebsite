<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'student') {
    header('Location: ../auth/login.php');
    exit();
}

$page_title = 'Available Quizzes';

// Get available quizzes with question count
$stmt = $pdo->prepare("
    SELECT 
        q.*,
        s.name as subject_name,
        (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as question_count,
        (SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id = q.id AND student_id = ?) as attempts
    FROM quizzes q
    JOIN subjects s ON q.subject_id = s.id
    ORDER BY q.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$available_quizzes = $stmt->fetchAll();

// Start output buffering
ob_start();
?>

<div class="dashboard-header">
    <h1>Available Quizzes</h1>
</div>

<div class="quiz-grid">
    <?php if (empty($available_quizzes)): ?>
        <div class="card">
            <p>No new quizzes available at the moment.</p>
        </div>
    <?php else: ?>
        <?php foreach ($available_quizzes as $quiz): ?>
            <div class="quiz-card">
                <div class="quiz-header">
                    <h3><?php echo htmlspecialchars($quiz['title']); ?></h3>
                    <span class="subject-badge"><?php echo htmlspecialchars($quiz['subject_name']); ?></span>
                </div>
                <div class="quiz-details">
                    <p><strong>Subject:</strong> <?php echo htmlspecialchars($quiz['subject_name']); ?></p>
                    <p><strong>Questions:</strong> <?php echo (int)$quiz['question_count']; ?></p>
                    <p><strong>Time Limit:</strong> <?php echo (int)($quiz['time_limit'] / 60); ?> minutes</p>
                    <p><strong>Description:</strong> <?php echo htmlspecialchars($quiz['description']); ?></p>
                </div>
                <div class="quiz-actions">
                    <?php if ((int)$quiz['question_count'] > 0): ?>
                        <a href="take-quiz.php?id=<?php echo $quiz['id']; ?>" class="btn btn-primary">Start Quiz</a>
                    <?php else: ?>
                        <button class="btn btn-secondary" disabled>No Questions Yet</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();

$extra_css = '
<style>
    .quiz-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1.5rem;
        padding: 1rem;
    }
    .quiz-card {
        background: white;
        padding: 1.5rem;
        border-radius: 12px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        display: flex;
        flex-direction: column;
        transition: transform 0.2s ease;
    }
    .quiz-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .quiz-header {
        margin-bottom: 1rem;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }
    .quiz-header h3 {
        margin: 0;
        color: var(--text-dark);
        font-size: 1.25rem;
    }
    .subject-badge {
        background: var(--primary-color);
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 15px;
        font-size: 0.875rem;
    }
    .quiz-details {
        flex-grow: 1;
        margin-bottom: 1.5rem;
    }
    .quiz-details p {
        margin: 0.5rem 0;
        color: var(--text-dark);
    }
    .quiz-actions {
        margin-top: auto;
    }
    .btn {
        display: inline-block;
        padding: 0.75rem 1rem;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 500;
        text-align: center;
        width: 100%;
        transition: all 0.3s ease;
    }
    .btn-primary {
        background: var(--primary-color);
        color: white;
        border: 2px solid var(--primary-color);
    }
    .btn-primary:hover {
        background: white;
        color: var(--primary-color);
    }
    .btn-secondary {
        background: #6c757d;
        color: white;
        opacity: 0.65;
        cursor: not-allowed;
    }
</style>
';

require_once 'includes/layout.php';
?> 