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

$quiz_id = (int)$_GET['id'];

// Get quiz details
$stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ? AND created_by = ?");
$stmt->execute([$quiz_id, $_SESSION['user_id']]);
$quiz = $stmt->fetch();

if (!$quiz) {
    header('Location: manage-quiz.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Validate input
        $question_text = isset($_POST['question_text']) ? trim($_POST['question_text']) : '';
        $options = [
            isset($_POST['option_a']) ? trim($_POST['option_a']) : '',
            isset($_POST['option_b']) ? trim($_POST['option_b']) : '',
            isset($_POST['option_c']) ? trim($_POST['option_c']) : '',
            isset($_POST['option_d']) ? trim($_POST['option_d']) : ''
        ];
        $correct_option = isset($_POST['correct_answer']) ? (int)$_POST['correct_answer'] : -1;

        if (isset($_POST['action'])) {
            if ($_POST['action'] == 'save_quiz') {
                // Redirect to manage quiz page
                header('Location: manage-quiz.php');
                exit();
            }

            // Validate required fields for question actions
            if ($_POST['action'] != 'save_quiz') {
                if (empty($question_text)) {
                    throw new Exception('Question text is required');
                }
                if (in_array('', $options)) {
                    throw new Exception('All options are required');
                }
                if ($correct_option < 0 || $correct_option > 3) {
                    throw new Exception('Please select a valid correct option');
                }
            }

            $pdo->beginTransaction();

            if ($_POST['action'] == 'add') {
                // Insert question
                $stmt = $pdo->prepare("
                    INSERT INTO questions (quiz_id, question_text, question_order)
                    VALUES (?, ?, (SELECT COALESCE(MAX(question_order), 0) + 1 FROM questions q2 WHERE quiz_id = ?))
                ");
                $stmt->execute([$quiz_id, $question_text, $quiz_id]);
                $question_id = $pdo->lastInsertId();

                // Insert options
                $stmt = $pdo->prepare("
                    INSERT INTO options (question_id, option_text, is_correct)
                    VALUES (?, ?, ?)
                ");

                foreach ($options as $index => $option_text) {
                    $stmt->execute([
                        $question_id,
                        $option_text,
                        $index === $correct_option ? 1 : 0
                    ]);
                }

                $success = "Question added successfully!";
            }
            else if ($_POST['action'] == 'update' && isset($_POST['question_id'])) {
                $question_id = (int)$_POST['question_id'];

                // Update question
                $stmt = $pdo->prepare("
                    UPDATE questions 
                    SET question_text = ?
                    WHERE id = ? AND quiz_id = ?
                ");
                $stmt->execute([$question_text, $question_id, $quiz_id]);

                // Get existing options
                $stmt = $pdo->prepare("SELECT id FROM options WHERE question_id = ? ORDER BY id");
                $stmt->execute([$question_id]);
                $option_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

                // Update options
                $stmt = $pdo->prepare("
                    UPDATE options 
                    SET option_text = ?, is_correct = ?
                    WHERE id = ?
                ");

                foreach ($options as $index => $option_text) {
                    if (isset($option_ids[$index])) {
                        $stmt->execute([
                            $option_text,
                            $index === $correct_option ? 1 : 0,
                            $option_ids[$index]
                        ]);
                    }
                }

                $success = "Question updated successfully!";
            }
            else if ($_POST['action'] == 'delete' && isset($_POST['question_id'])) {
                // Delete options first (due to foreign key constraint)
                $stmt = $pdo->prepare("DELETE FROM options WHERE question_id = ?");
                $stmt->execute([$_POST['question_id']]);

                // Then delete question
                $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ? AND quiz_id = ?");
                $stmt->execute([$_POST['question_id'], $quiz_id]);

                $success = "Question deleted successfully!";
            }

            $pdo->commit();
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = $e->getMessage() ?: "Failed to process question. Please try again.";
    }
}

// Get existing questions with their options
$stmt = $pdo->prepare("
    SELECT 
        q.id,
        q.question_text,
        GROUP_CONCAT(o.option_text ORDER BY o.id) as options_text,
        GROUP_CONCAT(o.is_correct ORDER BY o.id) as options_correct
    FROM questions q
    LEFT JOIN options o ON q.id = o.question_id
    WHERE q.quiz_id = ?
    GROUP BY q.id
    ORDER BY q.question_order
");
$stmt->execute([$quiz_id]);
$questions = $stmt->fetchAll();

// Count questions
$question_count = count($questions);

$page_title = 'Edit Questions';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Quiz Platform</title>
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
            <h1>Edit Questions - <?php echo htmlspecialchars($quiz['title']); ?></h1>
            <a href="manage-quiz.php" class="btn btn-secondary">Back to Quizzes</a>
        </div>

        <div class="quiz-form">
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (isset($success)): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST" id="questionForm" class="form-card">
                <input type="hidden" name="action" value="add" id="formAction">
                <input type="hidden" name="question_id" id="questionId">

                <div class="form-group">
                    <label for="question_text">Question Text</label>
                    <textarea id="question_text" name="question_text" rows="3" required></textarea>
                </div>

                <div class="options-group">
                    <div class="form-group">
                        <label for="option_a">Option A</label>
                        <input type="text" id="option_a" name="option_a" required>
                    </div>

                    <div class="form-group">
                        <label for="option_b">Option B</label>
                        <input type="text" id="option_b" name="option_b" required>
                    </div>

                    <div class="form-group">
                        <label for="option_c">Option C</label>
                        <input type="text" id="option_c" name="option_c" required>
                    </div>

                    <div class="form-group">
                        <label for="option_d">Option D</label>
                        <input type="text" id="option_d" name="option_d" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Correct Answer</label>
                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" name="correct_answer" value="0" required> Option A
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="correct_answer" value="1" required> Option B
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="correct_answer" value="2" required> Option C
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="correct_answer" value="3" required> Option D
                        </label>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Add Question</button>
                    <button type="button" class="btn btn-secondary" onclick="resetForm()">Clear Form</button>
                </div>
            </form>

            <div class="questions-list">
                <h2>Added Questions</h2>
                <?php foreach ($questions as $index => $question): ?>
                    <?php
                    $options_text = explode(',', $question['options_text']);
                    $options_correct = explode(',', $question['options_correct']);
                    $correct_index = array_search('1', $options_correct);
                    ?>
                    <div class="question-card">
                        <div class="question-header">
                            <h3>Question <?php echo $index + 1; ?></h3>
                            <div class="question-actions">
                                <button onclick="editQuestion(<?php 
                                    echo htmlspecialchars(json_encode([
                                        'id' => $question['id'],
                                        'text' => $question['question_text'],
                                        'options' => $options_text,
                                        'correct' => $correct_index
                                    ])); 
                                ?>)" class="btn btn-secondary btn-sm">Edit</button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this question?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="question_id" value="<?php echo $question['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </div>
                        </div>
                        <p class="question-text"><?php echo htmlspecialchars($question['question_text']); ?></p>
                        <div class="options-list">
                            <?php foreach ($options_text as $opt_index => $option): ?>
                                <div class="option <?php echo $opt_index === $correct_index ? 'correct' : ''; ?>">
                                    <?php echo chr(65 + $opt_index) . '. ' . htmlspecialchars($option); ?>
                                    <?php if ($opt_index === $correct_index): ?>
                                        <span class="correct-badge">✓</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="quiz-actions">
                <div class="quiz-status">
                    Questions Added: <span id="questionCount"><?php echo $question_count; ?></span>
                </div>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="save_quiz">
                    <button type="submit" class="btn btn-primary save-quiz" <?php echo $question_count == 0 ? 'disabled' : ''; ?>>
                        Save Quiz and Return
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function editQuestion(question) {
            document.getElementById('formAction').value = 'update';
            document.getElementById('questionId').value = question.id;
            document.getElementById('question_text').value = question.text;
            
            // Set options
            document.getElementById('option_a').value = question.options[0] || '';
            document.getElementById('option_b').value = question.options[1] || '';
            document.getElementById('option_c').value = question.options[2] || '';
            document.getElementById('option_d').value = question.options[3] || '';
            
            // Set correct answer
            const radios = document.getElementsByName('correct_answer');
            if (question.correct >= 0 && question.correct < radios.length) {
                radios[question.correct].checked = true;
            }
            
            // Update submit button text
            document.querySelector('#questionForm button[type="submit"]').textContent = 'Update Question';
            
            // Scroll to form
            document.getElementById('questionForm').scrollIntoView({ behavior: 'smooth' });
        }

        function resetForm() {
            document.getElementById('formAction').value = 'add';
            document.getElementById('questionId').value = '';
            document.getElementById('questionForm').reset();
            document.querySelector('#questionForm button[type="submit"]').textContent = 'Add Question';
        }

        // Form validation
        document.getElementById('questionForm').addEventListener('submit', function(e) {
            const questionText = document.getElementById('question_text').value.trim();
            const optionA = document.getElementById('option_a').value.trim();
            const optionB = document.getElementById('option_b').value.trim();
            const optionC = document.getElementById('option_c').value.trim();
            const optionD = document.getElementById('option_d').value.trim();
            const correctAnswer = document.querySelector('input[name="correct_answer"]:checked');

            if (!questionText) {
                e.preventDefault();
                alert('Please enter the question text');
                return;
            }

            if (!optionA || !optionB || !optionC || !optionD) {
                e.preventDefault();
                alert('Please fill in all options');
                return;
            }

            if (!correctAnswer) {
                e.preventDefault();
                alert('Please select the correct option');
                return;
            }
        });
    </script>

    <style>
        .quiz-form {
            max-width: 800px;
            margin: 0 auto;
        }
        .form-card {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .options-group {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        .radio-group {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
        }
        .radio-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .question-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
        }
        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .question-text {
            font-size: 1.1rem;
            margin-bottom: 1rem;
        }
        .options-list {
            display: grid;
            gap: 0.5rem;
        }
        .option {
            padding: 0.75rem;
            border-radius: 6px;
            background: #f8f9fa;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .option.correct {
            background: #d1e7dd;
            color: #0f5132;
        }
        .correct-badge {
            background: #198754;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
        }
        .quiz-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 2rem;
        }
        .quiz-status {
            font-size: 1.1rem;
            font-weight: 500;
        }
        .error-message {
            background: #f8d7da;
            color: #842029;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .success-message {
            background: #d1e7dd;
            color: #0f5132;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
    </style>
</body>
</html> 