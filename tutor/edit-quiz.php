<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a tutor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'tutor') {
    header('Location: ../auth/login.php');
    exit();
}

// Check if quiz ID is provided
if (!isset($_GET['id'])) {
    header('Location: manage-quiz.php');
    exit();
}

$quiz_id = $_GET['id'];

// Get quiz details
$stmt = $pdo->prepare("
    SELECT q.*, s.name as subject_name 
    FROM quizzes q 
    JOIN subjects s ON q.subject_id = s.id 
    WHERE q.id = ? AND q.created_by = ?
");
$stmt->execute([$quiz_id, $_SESSION['user_id']]);
$quiz = $stmt->fetch();

if (!$quiz) {
    header('Location: manage-quiz.php');
    exit();
}

// Get all subjects for dropdown
$stmt = $pdo->prepare("SELECT * FROM subjects ORDER BY name");
$stmt->execute();
$subjects = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $stmt = $pdo->prepare("
            UPDATE quizzes 
            SET title = ?, subject_id = ?, description = ?, time_limit = ?
            WHERE id = ? AND created_by = ?
        ");
        $stmt->execute([
            $_POST['title'],
            $_POST['subject_id'],
            $_POST['description'],
            $_POST['time_limit'],
            $quiz_id,
            $_SESSION['user_id']
        ]);
        
        $success = "Quiz updated successfully!";
        
        // Refresh quiz data
        $stmt = $pdo->prepare("
            SELECT q.*, s.name as subject_name 
            FROM quizzes q 
            JOIN subjects s ON q.subject_id = s.id 
            WHERE q.id = ?
        ");
        $stmt->execute([$quiz_id]);
        $quiz = $stmt->fetch();
    } catch (PDOException $e) {
        $error = "Failed to update quiz. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Quiz - Quiz Platform</title>
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
            <h1>Edit Quiz</h1>
            <a href="manage-quiz.php" class="btn btn-secondary">Back to Quizzes</a>
        </div>

        <?php if (isset($success)): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="edit-quiz-form">
            <form method="POST" class="form-card">
                <div class="form-group">
                    <label for="title">Quiz Title</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($quiz['title']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="subject_id">Subject</label>
                    <select id="subject_id" name="subject_id" required>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?php echo $subject['id']; ?>" <?php echo $subject['id'] == $quiz['subject_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($subject['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($quiz['description']); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="time_limit">Time Limit (minutes)</label>
                        <input type="number" id="time_limit" name="time_limit" value="<?php echo $quiz['time_limit']; ?>" min="1" required>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="edit-questions.php?id=<?php echo $quiz_id; ?>" class="btn btn-secondary">Edit Questions</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 