<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'student') {
    header('Location: ../auth/login.php');
    exit();
}

// Check if there's an active quiz attempt
if (!isset($_SESSION['quiz_attempt'])) {
    header('Location: quiz-list.php');
    exit();
}

$attempt = $_SESSION['quiz_attempt'];
$current_question = $attempt['questions'][$attempt['current_question']];

// Validate current question data
if (!isset($current_question['id']) || 
    !isset($current_question['question_text']) ||
    !isset($current_question['option_ids']) || 
    !isset($current_question['option_texts']) ||
    empty($current_question['option_ids']) ||
    empty($current_question['option_texts'])) {
    // Invalid question data - clear the attempt and redirect
    $_SESSION['error'] = 'Invalid question data found';
    unset($_SESSION['quiz_attempt']);
    header('Location: quiz-list.php');
    exit();
}

// Parse options - ensure we have valid data
$option_ids = explode(',', $current_question['option_ids']);
$option_texts = explode(',', $current_question['option_texts']);

if (count($option_ids) !== count($option_texts) || empty($option_ids)) {
    $_SESSION['error'] = 'Question options are mismatched or missing';
    unset($_SESSION['quiz_attempt']);
    header('Location: quiz-list.php');
    exit();
}

$options = array_combine($option_ids, $option_texts);

// Process answer submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate answer submission
    if (!isset($_POST['answer']) || !is_numeric($_POST['answer'])) {
        $_SESSION['error'] = 'Invalid answer submitted';
        header('Location: answer-question.php');
        exit();
    }

    // Validate that the selected option belongs to the current question
    if (!in_array($_POST['answer'], $option_ids)) {
        $_SESSION['error'] = 'Invalid answer option';
        header('Location: answer-question.php');
        exit();
    }

    // Insert answer
    $stmt = $pdo->prepare("
        INSERT INTO quiz_answers (
            attempt_id, 
            question_id, 
            selected_option_id
        ) VALUES (?, ?, ?)
    ");

    // Save answer
    $stmt->execute([
        $attempt['attempt_id'],
        $current_question['id'],
        $_POST['answer']
    ]);

    // Move to next question or finish quiz
    $_SESSION['quiz_attempt']['current_question']++;
    
    if ($_SESSION['quiz_attempt']['current_question'] >= count($attempt['questions'])) {
        // Calculate score
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_correct,
                (SELECT COUNT(*) FROM quiz_answers WHERE attempt_id = ?) as total_answers
            FROM quiz_answers qa
            JOIN options o ON qa.selected_option_id = o.id
            WHERE qa.attempt_id = ? AND o.is_correct = 1
        ");
        $stmt->execute([$attempt['attempt_id'], $attempt['attempt_id']]);
        $result = $stmt->fetch();
        
        $score = ($result['total_correct'] / $result['total_answers']) * 100;

        // Update attempt with score and end time
        $stmt = $pdo->prepare("
            UPDATE quiz_attempts 
            SET score = ?, completed_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$score, $attempt['attempt_id']]);

        // Clear quiz attempt from session
        unset($_SESSION['quiz_attempt']);

        // Redirect to results
        header('Location: view-result.php?attempt=' . $attempt['attempt_id']);
        exit();
    }

    header('Location: answer-question.php');
    exit();
}

$page_title = 'Question ' . ($attempt['current_question'] + 1) . ' of ' . count($attempt['questions']);

// Calculate remaining time
$elapsed_time = time() - $attempt['start_time'];
$remaining_time = max(0, $attempt['time_limit'] - $elapsed_time);

// Start output buffering
ob_start();
?>

<div class="quiz-header">
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?php 
            echo htmlspecialchars($_SESSION['error']);
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>
    <div class="progress-info">
        <div class="question-progress">
            Question <?php echo $attempt['current_question'] + 1; ?> of <?php echo count($attempt['questions']); ?>
        </div>
        <div class="timer" id="timer" data-remaining="<?php echo $remaining_time; ?>">
            Time Remaining: <span id="time-display"><?php echo floor($remaining_time / 60); ?>:<?php echo str_pad($remaining_time % 60, 2, '0', STR_PAD_LEFT); ?></span>
        </div>
    </div>
</div>

<div class="card question-card">
    <h2 class="question-text"><?php echo htmlspecialchars($current_question['question_text']); ?></h2>
    
    <form method="POST" id="answerForm">
        <div class="options-list">
            <?php foreach ($options as $id => $text): ?>
                <label class="option-item">
                    <input type="radio" name="answer" value="<?php echo $id; ?>" required>
                    <span class="option-text"><?php echo htmlspecialchars($text); ?></span>
                </label>
            <?php endforeach; ?>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Next Question</button>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();

$extra_css = '
<style>
    .quiz-header {
        background: white;
        padding: 1rem;
        border-radius: 12px;
        margin-bottom: 2rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .alert {
        padding: 1rem;
        margin-bottom: 1rem;
        border-radius: 8px;
        font-weight: 500;
    }
    .alert-danger {
        background-color: #f8d7da;
        color: #842029;
        border: 1px solid #f5c2c7;
    }
    .progress-info {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .question-progress {
        font-size: 1.1rem;
        color: var(--text-dark);
    }
    .timer {
        font-size: 1.1rem;
        color: var(--primary-color);
        font-weight: 500;
    }
    .question-card {
        max-width: 800px;
        margin: 0 auto;
    }
    .question-text {
        font-size: 1.3rem;
        color: var(--text-dark);
        margin-bottom: 2rem;
    }
    .options-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        margin-bottom: 2rem;
    }
    .option-item {
        display: flex;
        align-items: center;
        padding: 1rem;
        border: 1px solid #eee;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .option-item:hover {
        background: #f8f9fa;
        border-color: var(--primary-color);
    }
    .option-item input[type="radio"] {
        margin-right: 1rem;
    }
    .option-text {
        color: var(--text-dark);
        font-size: 1.1rem;
    }
    .form-actions {
        text-align: center;
    }
    .btn {
        display: inline-block;
        padding: 0.8rem 2rem;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 500;
        text-align: center;
        border: none;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }
    .btn-primary {
        background: var(--primary-color);
        color: white;
    }
    .btn-primary:hover {
        background: #0b5ed7;
    }
</style>
';

$extra_js = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    const timer = document.getElementById("timer");
    let remainingTime = parseInt(timer.dataset.remaining);
    const timeDisplay = document.getElementById("time-display");
    
    const updateTimer = () => {
        if (remainingTime <= 0) {
            document.getElementById("answerForm").submit();
            return;
        }
        
        const minutes = Math.floor(remainingTime / 60);
        const seconds = remainingTime % 60;
        timeDisplay.textContent = `${minutes}:${seconds.toString().padStart(2, "0")}`;
        remainingTime--;
    };
    
    setInterval(updateTimer, 1000);
});
</script>
';

require_once 'includes/layout.php';
?> 