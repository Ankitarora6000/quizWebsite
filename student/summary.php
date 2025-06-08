<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'student') {
    header('Location: ../auth/login.php');
    exit();
}

$page_title = 'Performance Summary';

// Get overall statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_attempts,
        AVG(score) as avg_score,
        MAX(score) as highest_score
    FROM quiz_attempts
    WHERE student_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$stats = $stmt->fetch();

// Get subject-wise performance
$stmt = $pdo->prepare("
    SELECT 
        s.name as subject_name,
        COUNT(*) as attempts,
        AVG(qa.score) as avg_score,
        MAX(qa.score) as highest_score
    FROM quiz_attempts qa
    JOIN quizzes q ON qa.quiz_id = q.id
    JOIN subjects s ON q.subject_id = s.id
    WHERE qa.student_id = ?
    GROUP BY s.id, s.name
    ORDER BY s.name
");
$stmt->execute([$_SESSION['user_id']]);
$subject_stats = $stmt->fetchAll();

// Get monthly performance
$stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(started_at, '%Y-%m') as month,
        COUNT(*) as attempts,
        AVG(score) as avg_score
    FROM quiz_attempts
    WHERE student_id = ?
    GROUP BY DATE_FORMAT(started_at, '%Y-%m')
    ORDER BY month DESC
");
$stmt->execute([$_SESSION['user_id']]);
$monthly_performance = $stmt->fetchAll();

// Get recent improvement trend (last 5 quizzes)
$stmt = $pdo->prepare("
    SELECT score, completed_at
    FROM quiz_attempts
    WHERE student_id = ? AND completed_at IS NOT NULL
    ORDER BY completed_at DESC
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$recent_scores = $stmt->fetchAll();

// Start output buffering
ob_start();
?>

<div class="dashboard-header">
    <h1>Performance Summary</h1>
</div>

<div class="grid">
    <div class="card">
        <h3>Total Quizzes Taken</h3>
        <p class="stat-number"><?php echo $stats['total_attempts']; ?></p>
    </div>
    <div class="card">
        <h3>Average Score</h3>
        <p class="stat-number"><?php echo round($stats['avg_score'], 1); ?>%</p>
    </div>
    <div class="card">
        <h3>Highest Score</h3>
        <p class="stat-number"><?php echo round($stats['highest_score'], 1); ?>%</p>
    </div>
</div>

<div class="row">
    <div class="card">
        <h2>Subject Performance</h2>
        <canvas id="subjectChart"></canvas>
    </div>
    
    <div class="card">
        <h2>Monthly Progress</h2>
        <canvas id="monthlyChart"></canvas>
    </div>
</div>

<div class="card">
    <h2>Subject-wise Details</h2>
    <div class="subject-grid">
        <?php foreach ($subject_stats as $subject): ?>
            <div class="subject-card">
                <h3><?php echo htmlspecialchars($subject['subject_name']); ?></h3>
                <div class="subject-stats">
                    <div class="stat-item">
                        <span class="label">Attempts:</span>
                        <span class="value"><?php echo $subject['attempts']; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="label">Average:</span>
                        <span class="value"><?php echo round($subject['avg_score'], 1); ?>%</span>
                    </div>
                    <div class="stat-item">
                        <span class="label">Best Score:</span>
                        <span class="value"><?php echo round($subject['highest_score'], 1); ?>%</span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php
$content = ob_get_clean();

$extra_css = '
<style>
    .row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }
    .subject-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 1rem;
    }
    .subject-card {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 8px;
        border: 1px solid #eee;
    }
    .subject-stats {
        margin-top: 1rem;
    }
    .stat-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.5rem;
    }
    .stat-item .label {
        color: #666;
    }
    .stat-item .value {
        font-weight: 500;
    }
    canvas {
        width: 100% !important;
        height: 300px !important;
    }
</style>
';

$extra_js = '
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Subject Performance Chart
    new Chart(document.getElementById("subjectChart"), {
        type: "bar",
        data: {
            labels: ' . json_encode(array_column($subject_stats, 'subject_name')) . ',
            datasets: [{
                label: "Average Score",
                data: ' . json_encode(array_column($subject_stats, 'avg_score')) . ',
                backgroundColor: "#0D6EFD"
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });

    // Monthly Progress Chart
    new Chart(document.getElementById("monthlyChart"), {
        type: "line",
        data: {
            labels: ' . json_encode(array_column($monthly_performance, 'month')) . ',
            datasets: [{
                label: "Average Score",
                data: ' . json_encode(array_column($monthly_performance, 'avg_score')) . ',
                borderColor: "#0D6EFD",
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
});
</script>
';

require_once 'includes/layout.php';
?> 