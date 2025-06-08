<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $user_type = $_POST['user_type'];

    try {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "Email already registered";
        } else {
            // Insert new user
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, user_type) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $password, $user_type]);

            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['user_type'] = $user_type;
            $_SESSION['name'] = $name;

            if ($user_type == 'tutor') {
                header('Location: ../tutor/dashboard.php');
            } else {
                header('Location: ../student/dashboard.php');
            }
            exit();
        }
    } catch (PDOException $e) {
        $error = "Registration failed. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Quiz Platform</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <div class="login-form">
            <div class="form-header">
                <h2>Get Started</h2>
                <p>Knowledge is Wealth</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" placeholder="Enter your full name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Create a password" required>
                </div>
                <div class="form-group">
                    <label for="user_type">I am a:</label>
                    <select id="user_type" name="user_type" required>
                        <option value="" disabled selected>Select your role</option>
                        <option value="student">Student</option>
                        <option value="tutor">Tutor</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Sign up</button>
            </form>
            
            <div class="divider">
                <span>Or sign up with</span>
            </div>
            
            <div class="social-login">
                <button class="social-btn">
                    <img src="../assets/images/google.svg" alt="Google" width="20">
                </button>
                <button class="social-btn">
                    <img src="../assets/images/apple.svg" alt="Apple" width="20">
                </button>
                <button class="social-btn">
                    <img src="../assets/images/facebook.svg" alt="Facebook" width="20">
                </button>
            </div>
            
            <div class="form-footer">
                <p>Already have an account? <a href="login.php">Sign in</a></p>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html> 