<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a tutor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'tutor') {
    header('Location: ../auth/login.php');
    exit();
}

// Get summary statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT q.id) as total_quizzes,
        COUNT(DISTINCT qa.id) as total_attempts,
        COUNT(DISTINCT qa.student_id) as total_students,
        COALESCE(AVG(qa.score), 0) as avg_score,
        (
            SELECT COUNT(DISTINCT qa2.id) 
            FROM quiz_attempts qa2 
            JOIN quizzes q2 ON qa2.quiz_id = q2.id 
            WHERE q2.created_by = ? AND qa2.score >= 70
        ) as passing_attempts
    FROM quizzes q
    LEFT JOIN quiz_attempts qa ON q.id = qa.quiz_id
    WHERE q.created_by = ?
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
$stats = $stmt->fetch();

// Get recent attempts
$stmt = $pdo->prepare("
    SELECT 
        qa.*,
        q.title as quiz_title,
        s.name as subject_name,
        u.name as student_name
    FROM quiz_attempts qa
    JOIN quizzes q ON qa.quiz_id = q.id
    JOIN subjects s ON q.subject_id = s.id
    JOIN users u ON qa.student_id = u.id
    WHERE q.created_by = ?
    ORDER BY qa.completed_at DESC
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$recent_attempts = $stmt->fetchAll();

// Get quiz performance
$stmt = $pdo->prepare("
    SELECT 
        q.title,
        COUNT(qa.id) as attempt_count,
        COALESCE(AVG(qa.score), 0) as avg_score,
        COALESCE(MIN(qa.score), 0) as min_score,
        COALESCE(MAX(qa.score), 0) as max_score
    FROM quizzes q
    LEFT JOIN quiz_attempts qa ON q.id = qa.quiz_id
    WHERE q.created_by = ?
    GROUP BY q.id, q.title
    ORDER BY attempt_count DESC
");
$stmt->execute([$_SESSION['user_id']]);
$quiz_stats = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Summary - Quiz Platform</title>
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
            <li><a href="see-results.php">See Results</a></li>
            <li><a href="summary.php" class="active">Summary</a></li>
            <li><a href="account.php">Account</a></li>
        </ul>
        <form action="../auth/logout.php" method="POST">
            <button type="submit" class="logout-btn">Logout</button>
        </form>
    </div>

    <div class="main-content">
        <div class="dashboard-header">
            <h1>Summary Dashboard</h1>
        </div>

        <div class="summary-container">
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Quizzes</h3>
                    <div class="stat-value"><?php echo $stats['total_quizzes']; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Total Attempts</h3>
                    <div class="stat-value"><?php echo $stats['total_attempts']; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Total Students</h3>
                    <div class="stat-value"><?php echo $stats['total_students']; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Average Score</h3>
                    <div class="stat-value"><?php echo round($stats['avg_score'], 1); ?>%</div>
                </div>
                <div class="stat-card">
                    <h3>Passing Attempts</h3>
                    <div class="stat-value"><?php echo $stats['passing_attempts']; ?></div>
                </div>
            </div>

            <div class="summary-sections">
                <div class="section recent-attempts">
                    <h2>Recent Attempts</h2>
                    <?php if (empty($recent_attempts)): ?>
                        <p class="no-data">No attempts yet</p>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Quiz</th>
                                    <th>Subject</th>
                                    <th>Score</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_attempts as $attempt): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($attempt['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($attempt['quiz_title']); ?></td>
                                        <td><?php echo htmlspecialchars($attempt['subject_name']); ?></td>
                                        <td><?php echo round($attempt['score'], 1); ?>%</td>
                                        <td><?php echo date('M j, Y g:i A', strtotime($attempt['completed_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <div class="section quiz-performance">
                    <h2>Quiz Performance</h2>
                    <?php if (empty($quiz_stats)): ?>
                        <p class="no-data">No quiz data available</p>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Quiz</th>
                                    <th>Attempts</th>
                                    <th>Avg Score</th>
                                    <th>Min Score</th>
                                    <th>Max Score</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($quiz_stats as $quiz): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($quiz['title']); ?></td>
                                        <td><?php echo $quiz['attempt_count']; ?></td>
                                        <td><?php echo $quiz['attempt_count'] > 0 ? round($quiz['avg_score'], 1) . '%' : 'No attempts'; ?></td>
                                        <td><?php echo $quiz['attempt_count'] > 0 ? round($quiz['min_score'], 1) . '%' : 'N/A'; ?></td>
                                        <td><?php echo $quiz['attempt_count'] > 0 ? round($quiz['max_score'], 1) . '%' : 'N/A'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <style>
        .summary-container {
            padding: 20px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-card h3 {
            margin: 0 0 10px;
            color: #666;
            font-size: 1rem;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: 600;
            color: #333;
        }
        .summary-sections {
            display: grid;
            gap: 30px;
        }
        .section {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .section h2 {
            margin: 0 0 20px;
            color: #333;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table th,
        .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .data-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        .data-table tr:hover {
            background: #f8f9fa;
        }
        .no-data {
            text-align: center;
            color: #666;
            padding: 20px;
        }
    </style>
</body>
</html> 