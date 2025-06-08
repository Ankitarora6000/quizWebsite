<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a tutor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'tutor') {
    header('Location: ../auth/login.php');
    exit();
}

// Get tutor's quizzes with more details
$stmt = $pdo->prepare("
    SELECT 
        q.*, 
        s.name as subject_name,
        (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as question_count,
        (SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id = q.id) as attempt_count,
        (SELECT AVG(score) FROM quiz_attempts WHERE quiz_id = q.id) as avg_score
    FROM quizzes q
    JOIN subjects s ON q.subject_id = s.id
    WHERE q.created_by = ?
    ORDER BY q.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$quizzes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Quizzes - Quiz Platform</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="sidebar">
        <div class="profile">
            <img src="../assets/images/user-avatar.svg" alt="Profile">
            <div>
                <h3><?php echo htmlspecialchars($_SESSION['name']); ?></h3>
                <p>Tutor</p>
            </div>
        </div>
        <ul class="nav-links">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="manage-quiz.php" class="active">Manage Quiz</a></li>
            <li><a href="see-results.php">See Results</a></li>
            <li><a href="summary.php">Summary</a></li>
            <li><a href="account.php">Account</a></li>
        </ul>
        <form action="../auth/logout.php" method="POST">
            <button type="submit" class="logout-btn">Logout</button>
        </form>
    </div>

    <div class="main-content">
        <div class="dashboard-header">
            <h1>Manage Quizzes</h1>
            <button class="btn btn-primary" onclick="location.href='add-quiz.php'">Add New Quiz</button>
        </div>

        <div class="quiz-management">
            <?php if (empty($quizzes)): ?>
                <div class="no-quizzes">
                    <p>No quizzes created yet.</p>
                    <a href="add-quiz.php" class="btn btn-primary">Create your first quiz</a>
                </div>
            <?php else: ?>
                <div class="quiz-grid">
                    <?php foreach ($quizzes as $quiz): ?>
                        <div class="quiz-card">
                            <div class="quiz-info">
                                <h3><?php echo htmlspecialchars($quiz['title']); ?></h3>
                                <div class="quiz-meta">
                                    <span>
                                        <strong>Subject:</strong>
                                        <?php echo htmlspecialchars($quiz['subject_name']); ?>
                                    </span>
                                    <span>
                                        <strong>Questions:</strong>
                                        <?php echo $quiz['question_count']; ?>
                                    </span>
                                    <span>
                                        <strong>Attempts:</strong>
                                        <?php echo $quiz['attempt_count']; ?>
                                    </span>
                                    <span>
                                        <strong>Avg Score:</strong>
                                        <?php echo $quiz['avg_score'] ? round($quiz['avg_score'], 1) . '%' : 'N/A'; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="quiz-actions">
                                <a href="edit-quiz.php?id=<?php echo $quiz['id']; ?>" class="btn btn-secondary">Edit Quiz</a>
                                <a href="edit-questions.php?id=<?php echo $quiz['id']; ?>" class="btn btn-secondary">Edit Questions</a>
                                <a href="view-results.php?id=<?php echo $quiz['id']; ?>" class="btn btn-primary">View Results</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 