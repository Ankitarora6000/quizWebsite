<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a tutor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'tutor') {
    header('Location: ../auth/login.php');
    exit();
}

// Get quiz ID from URL
$quiz_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get quiz details if ID is provided
if ($quiz_id > 0) {
    $stmt = $pdo->prepare("
        SELECT q.*, s.name as subject_name 
        FROM quizzes q 
        JOIN subjects s ON q.subject_id = s.id 
        WHERE q.id = ? AND q.created_by = ?
    ");
    $stmt->execute([$quiz_id, $_SESSION['user_id']]);
    $quiz = $stmt->fetch();

    if ($quiz) {
        // Get all attempts for this quiz
        $stmt = $pdo->prepare("
            SELECT 
                qa.*,
                u.name as student_name,
                u.email as student_email,
                (SELECT COUNT(*) FROM quiz_attempt_answers qaa 
                 WHERE qaa.attempt_id = qa.id AND qaa.is_correct = 1) as correct_answers,
                (SELECT COUNT(*) FROM questions q WHERE q.quiz_id = qa.quiz_id) as total_questions
            FROM quiz_attempts qa
            JOIN users u ON qa.student_id = u.id
            WHERE qa.quiz_id = ?
            ORDER BY qa.start_time DESC
        ");
        $stmt->execute([$quiz_id]);
        $attempts = $stmt->fetchAll();
    }
}

// Get all quizzes for dropdown
$stmt = $pdo->prepare("
    SELECT q.*, s.name as subject_name, 
           (SELECT COUNT(*) FROM quiz_attempts qa WHERE qa.quiz_id = q.id) as attempt_count
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
    <title>View Results - Quiz Platform</title>
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
            <h1>View Quiz Results</h1>
        </div>

        <div class="quiz-selector">
            <form method="GET" class="form-card">
                <div class="form-group">
                    <label for="id">Select Quiz</label>
                    <select id="id" name="id" onchange="this.form.submit()">
                        <option value="">Select a quiz</option>
                        <?php foreach ($quizzes as $q): ?>
                            <option value="<?php echo $q['id']; ?>" <?php echo $quiz_id == $q['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($q['title']); ?> 
                                (<?php echo htmlspecialchars($q['subject_name']); ?>) - 
                                <?php echo $q['attempt_count']; ?> attempts
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>

        <?php if (isset($quiz)): ?>
            <div class="quiz-details">
                <h2><?php echo htmlspecialchars($quiz['title']); ?></h2>
                <p>Subject: <?php echo htmlspecialchars($quiz['subject_name']); ?></p>
                <p>Time Limit: <?php echo $quiz['time_limit']; ?> minutes</p>
            </div>

            <?php if (empty($attempts)): ?>
                <p>No attempts yet for this quiz.</p>
            <?php else: ?>
                <div class="results-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Email</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Score</th>
                                <th>Correct/Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attempts as $attempt): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($attempt['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($attempt['student_email']); ?></td>
                                    <td><?php echo date('Y-m-d H:i:s', strtotime($attempt['start_time'])); ?></td>
                                    <td><?php echo $attempt['end_time'] ? date('Y-m-d H:i:s', strtotime($attempt['end_time'])) : 'In Progress'; ?></td>
                                    <td><?php echo number_format($attempt['score'], 2); ?>%</td>
                                    <td><?php echo $attempt['correct_answers']; ?>/<?php echo $attempt['total_questions']; ?></td>
                                    <td>
                                        <?php if ($attempt['end_time']): ?>
                                            <?php if ($attempt['score'] >= 70): ?>
                                                <span class="badge success">Passed</span>
                                            <?php else: ?>
                                                <span class="badge danger">Failed</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge warning">In Progress</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html> 