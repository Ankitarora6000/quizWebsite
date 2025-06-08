<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'student') {
    header('Location: ../auth/login.php');
    exit();
}

// Get student information
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND user_type = 'student'");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();

// Get student's quiz statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_attempts,
        COUNT(CASE WHEN score >= 70 THEN 1 END) as passed_quizzes,
        COALESCE(AVG(score), 0) as average_score
    FROM quiz_attempts 
    WHERE student_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$stats = $stmt->fetch();

// Get recent quiz attempts
$stmt = $pdo->prepare("
    SELECT 
        qa.id as attempt_id,
        q.title as quiz_title,
        s.name as subject_name,
        qa.score,
        qa.completed_at,
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
    LIMIT 10
");
$stmt->execute([$_SESSION['user_id']]);
$recent_attempts = $stmt->fetchAll();

// Get subject-wise performance
$stmt = $pdo->prepare("
    SELECT 
        s.name as subject_name,
        COUNT(*) as attempts,
        ROUND(AVG(qa.score), 1) as average_score
    FROM quiz_attempts qa
    JOIN quizzes q ON qa.quiz_id = q.id
    JOIN subjects s ON q.subject_id = s.id
    WHERE qa.student_id = ?
    GROUP BY s.id, s.name
    ORDER BY average_score DESC
");
$stmt->execute([$_SESSION['user_id']]);
$subject_performance = $stmt->fetchAll();

$page_title = 'Student Dashboard';

// Start output buffering
ob_start();
?>

<div class="dashboard-header">
    <h1>Welcome, <?php echo htmlspecialchars($student['name']); ?>!</h1>
    <p class="student-info">Email: <?php echo htmlspecialchars($student['email']); ?></p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-value"><?php echo $stats['total_attempts']; ?></div>
        <div class="stat-label">Total Quizzes Taken</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo $stats['passed_quizzes']; ?></div>
        <div class="stat-label">Quizzes Passed</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo round($stats['average_score'], 1); ?>%</div>
        <div class="stat-label">Average Score</div>
    </div>
</div>

<div class="dashboard-section">
    <div class="section-header">
        <h2>Recent Quiz Results</h2>
        <a href="quiz-list.php" class="btn btn-primary">Take New Quiz</a>
    </div>
    
    <?php if (empty($recent_attempts)): ?>
        <div class="empty-state">
            <p>You haven't taken any quizzes yet. Start by taking your first quiz!</p>
        </div>
    <?php else: ?>
        <div class="results-grid">
            <?php foreach ($recent_attempts as $attempt): ?>
                <div class="result-card">
                    <div class="result-header">
                        <h3><?php echo htmlspecialchars($attempt['quiz_title']); ?></h3>
                        <span class="subject-badge"><?php echo htmlspecialchars($attempt['subject_name']); ?></span>
                    </div>
                    <div class="result-stats">
                        <div class="score <?php echo $attempt['score'] >= 70 ? 'passed' : 'failed'; ?>">
                            <?php echo round($attempt['score'], 1); ?>%
                        </div>
                        <div class="details">
                            <div class="detail-item">
                                <span class="label">Correct:</span>
                                <span class="value"><?php echo $attempt['correct_answers']; ?>/<?php echo $attempt['total_questions']; ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Date:</span>
                                <span class="value"><?php echo date('M j, Y', strtotime($attempt['completed_at'])); ?></span>
                            </div>
                        </div>
                    </div>
                    <a href="view-result.php?attempt=<?php echo $attempt['attempt_id']; ?>" class="btn btn-secondary btn-sm">View Details</a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="dashboard-section">
    <h2>Subject Performance</h2>
    <?php if (empty($subject_performance)): ?>
        <div class="empty-state">
            <p>Take quizzes to see your subject-wise performance.</p>
        </div>
    <?php else: ?>
        <div class="subject-performance">
            <?php foreach ($subject_performance as $subject): ?>
                <div class="subject-card">
                    <div class="subject-name"><?php echo htmlspecialchars($subject['subject_name']); ?></div>
                    <div class="subject-stats">
                        <div class="progress-bar">
                            <div class="progress" style="width: <?php echo $subject['average_score']; ?>%"></div>
                        </div>
                        <div class="stats-detail">
                            <span class="average"><?php echo $subject['average_score']; ?>%</span>
                            <span class="attempts"><?php echo $subject['attempts']; ?> attempts</span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();

$extra_css = '
<style>
    .dashboard-header {
        margin-bottom: 2rem;
    }
    .student-info {
        color: var(--text-light);
    }
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    .stat-card {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        text-align: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .stat-value {
        font-size: 2rem;
        font-weight: bold;
        color: var(--primary-color);
        margin-bottom: 0.5rem;
    }
    .stat-label {
        color: var(--text-light);
    }
    .dashboard-section {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        margin-bottom: 2rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }
    .results-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
    }
    .result-card {
        background: #f8f9fa;
        padding: 1.5rem;
        border-radius: 8px;
    }
    .result-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 1rem;
    }
    .result-header h3 {
        margin: 0;
        font-size: 1.1rem;
    }
    .subject-badge {
        background: var(--primary-color);
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 15px;
        font-size: 0.9rem;
    }
    .result-stats {
        display: flex;
        align-items: center;
        margin-bottom: 1rem;
    }
    .score {
        font-size: 1.8rem;
        font-weight: bold;
        margin-right: 1.5rem;
    }
    .score.passed {
        color: #28a745;
    }
    .score.failed {
        color: #dc3545;
    }
    .details {
        flex-grow: 1;
    }
    .detail-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.25rem;
    }
    .label {
        color: var(--text-light);
    }
    .subject-performance {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
    }
    .subject-card {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 8px;
    }
    .subject-name {
        font-weight: 500;
        margin-bottom: 0.5rem;
    }
    .progress-bar {
        height: 8px;
        background: #e9ecef;
        border-radius: 4px;
        margin-bottom: 0.5rem;
    }
    .progress {
        height: 100%;
        background: var(--primary-color);
        border-radius: 4px;
    }
    .stats-detail {
        display: flex;
        justify-content: space-between;
        font-size: 0.9rem;
    }
    .average {
        font-weight: 500;
    }
    .attempts {
        color: var(--text-light);
    }
    .empty-state {
        text-align: center;
        padding: 2rem;
        color: var(--text-light);
    }
    .btn {
        display: inline-block;
        padding: 0.5rem 1rem;
        border-radius: 5px;
        text-decoration: none;
        font-weight: 500;
    }
    .btn-primary {
        background: var(--primary-color);
        color: white;
    }
    .btn-secondary {
        background: #6c757d;
        color: white;
    }
    .btn-sm {
        padding: 0.25rem 0.75rem;
        font-size: 0.9rem;
    }
</style>
';

require_once 'includes/layout.php';
?>