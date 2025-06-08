<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a tutor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'tutor') {
    header('Location: ../auth/login.php');
    exit();
}

// Get tutor's quizzes
$stmt = $pdo->prepare("
    SELECT q.*, s.name as subject_name, 
    (SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id = q.id) as attempt_count
    FROM quizzes q
    JOIN subjects s ON q.subject_id = s.id
    WHERE q.created_by = ?
    ORDER BY q.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$quizzes = $stmt->fetchAll();

// Get subjects for quiz creation
$stmt = $pdo->prepare("SELECT * FROM subjects");
$stmt->execute();
$subjects = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutor Dashboard - Quiz Platform</title>
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
            <li><a href="dashboard.php" class="active">Dashboard</a></li>
            <li><a href="manage-quiz.php">Manage Quiz</a></li>
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
            <h1>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h1>
            <button class="btn btn-primary" onclick="location.href='add-quiz.php'">Add New Quiz</button>
        </div>

        <div class="dashboard-stats">
            <div class="stat-card">
                <h3>Total Quizzes</h3>
                <p class="stat-number"><?php echo count($quizzes); ?></p>
            </div>
            <div class="stat-card">
                <h3>Total Attempts</h3>
                <p class="stat-number"><?php 
                    $total_attempts = array_sum(array_column($quizzes, 'attempt_count'));
                    echo $total_attempts;
                ?></p>
            </div>
        </div>

        <div class="latest-attempts">
            <h2>Your Quizzes</h2>
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
                                        <strong>Attempts:</strong>
                                        <?php echo $quiz['attempt_count']; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="quiz-actions">
                                <a href="edit-quiz.php?id=<?php echo $quiz['id']; ?>" class="btn btn-secondary">Edit</a>
                                <a href="view-results.php?id=<?php echo $quiz['id']; ?>" class="btn btn-primary">Results</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html> 