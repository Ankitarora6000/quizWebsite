<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a tutor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'tutor') {
    header('Location: ../auth/login.php');
    exit();
}

// Get all subjects for dropdown
$stmt = $pdo->prepare("SELECT * FROM subjects ORDER BY name");
$stmt->execute();
$subjects = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Validate and sanitize input
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $subject_id = isset($_POST['subject_id']) ? (int)$_POST['subject_id'] : 0;
        $time_limit = isset($_POST['time_limit']) ? (int)$_POST['time_limit'] : 30;

        // Validate required fields
        if (empty($title)) {
            throw new Exception('Quiz title is required');
        }
        if ($subject_id <= 0) {
            throw new Exception('Please select a valid subject');
        }
        if ($time_limit < 1) {
            throw new Exception('Time limit must be at least 1 minute');
        }

        $pdo->beginTransaction();

        // Insert quiz
        $stmt = $pdo->prepare("
            INSERT INTO quizzes (title, description, subject_id, created_by, time_limit)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $title,
            $description,
            $subject_id,
            $_SESSION['user_id'],
            $time_limit
        ]);

        $quiz_id = $pdo->lastInsertId();
        $pdo->commit();

        // Redirect to edit questions page
        header("Location: edit-questions.php?id=" . $quiz_id);
        exit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = $e->getMessage() ?: "Failed to create quiz. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Quiz - Quiz Platform</title>
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
            <h1>Add New Quiz</h1>
            <a href="manage-quiz.php" class="btn btn-secondary">Back to Quizzes</a>
        </div>

        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="quiz-form">
            <form method="POST" class="form-card">
                <div class="form-group">
                    <label for="title">Quiz Title</label>
                    <input type="text" id="title" name="title" required 
                           value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="subject_id">Subject</label>
                    <select id="subject_id" name="subject_id" required>
                        <option value="">Select a subject</option>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?php echo $subject['id']; ?>" 
                                <?php echo (isset($_POST['subject_id']) && $_POST['subject_id'] == $subject['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($subject['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label for="time_limit">Time Limit (minutes)</label>
                    <input type="number" id="time_limit" name="time_limit" min="1" required
                           value="<?php echo isset($_POST['time_limit']) ? htmlspecialchars($_POST['time_limit']) : '30'; ?>">
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Create Quiz</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const subject = document.getElementById('subject_id').value;
            const timeLimit = document.getElementById('time_limit').value;

            if (!title) {
                e.preventDefault();
                alert('Please enter a quiz title');
                return;
            }

            if (!subject) {
                e.preventDefault();
                alert('Please select a subject');
                return;
            }

            if (!timeLimit || timeLimit < 1) {
                e.preventDefault();
                alert('Please enter a valid time limit (minimum 1 minute)');
                return;
            }
        });
    </script>
</body>
</html> 