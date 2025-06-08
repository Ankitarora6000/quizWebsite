<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a tutor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'tutor') {
    header('Location: ../auth/login.php');
    exit();
}

// Get all quiz attempts for tutor's quizzes
$stmt = $pdo->prepare("
    SELECT 
        qa.*,
        q.title as quiz_title,
        s.name as subject_name,
        u.name as student_name,
        u.email as student_email
    FROM quiz_attempts qa
    JOIN quizzes q ON qa.quiz_id = q.id
    JOIN subjects s ON q.subject_id = s.id
    JOIN users u ON qa.student_id = u.id
    WHERE q.created_by = ?
    ORDER BY qa.completed_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$attempts = $stmt->fetchAll();

// Group attempts by quiz
$quizzes = [];
foreach ($attempts as $attempt) {
    if (!isset($quizzes[$attempt['quiz_id']])) {
        $quizzes[$attempt['quiz_id']] = [
            'title' => $attempt['quiz_title'],
            'subject' => $attempt['subject_name'],
            'attempts' => []
        ];
    }
    $quizzes[$attempt['quiz_id']]['attempts'][] = $attempt;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Results - Quiz Platform</title>
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
            <li><a href="manage-quiz.php">Manage Quiz</a></li>
            <li><a href="see-results.php" class="active">See Results</a></li>
            <li><a href="summary.php">Summary</a></li>
            <li><a href="account.php">Account</a></li>
        </ul>
        <form action="../auth/logout.php" method="POST">
            <button type="submit" class="logout-btn">Logout</button>
        </form>
    </div>

    <div class="main-content">
        <div class="dashboard-header">
            <h1>Quiz Results</h1>
        </div>

        <div class="results-container">
            <?php if (empty($quizzes)): ?>
                <div class="no-results">
                    <p>No quiz attempts yet.</p>
                    <p>Create a quiz and share it with your students!</p>
                    <a href="add-quiz.php" class="btn btn-primary">Create Quiz</a>
                </div>
            <?php else: ?>
                <?php foreach ($quizzes as $quiz_id => $quiz): ?>
                    <div class="quiz-results-section">
                        <div class="section-header">
                            <h2><?php echo htmlspecialchars($quiz['title']); ?></h2>
                            <p>Subject: <?php echo htmlspecialchars($quiz['subject']); ?></p>
                        </div>
                        <div class="attempts-list">
                            <table class="results-table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Email</th>
                                        <th>Score</th>
                                        <th>Time Taken</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($quiz['attempts'] as $attempt): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($attempt['student_name']); ?></td>
                                            <td><?php echo htmlspecialchars($attempt['student_email']); ?></td>
                                            <td><?php echo $attempt['score']; ?>%</td>
                                            <td><?php echo round($attempt['time_taken'] / 60, 1); ?> minutes</td>
                                            <td><?php echo date('M j, Y g:i A', strtotime($attempt['completed_at'])); ?></td>
                                            <td>
                                                <a href="view-attempt.php?id=<?php echo $attempt['id']; ?>" class="btn btn-secondary btn-sm">View Details</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <style>
        .results-container {
            padding: 20px;
        }
        .quiz-results-section {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .section-header {
            margin-bottom: 20px;
        }
        .section-header h2 {
            margin: 0;
            color: #333;
        }
        .section-header p {
            margin: 5px 0 0;
            color: #666;
        }
        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .results-table th,
        .results-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .results-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        .results-table tr:hover {
            background: #f8f9fa;
        }
        .btn-sm {
            padding: 5px 10px;
            font-size: 0.9em;
        }
        .no-results {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .no-results p {
            margin: 10px 0;
            color: #666;
        }
    </style>
</body>
</html> 