<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Platform</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
    <?php
    session_start();
    require_once 'config/database.php';
    
    if(isset($_SESSION['user_type'])) {
        if($_SESSION['user_type'] == 'tutor') {
            header('Location: tutor/dashboard.php');
        } else {
            header('Location: student/dashboard.php');
        }
    }
    ?>
    
    <div class="login-container">
        <div class="login-form">
            <h2>Welcome to Quiz Platform</h2>
            <p class="text-center" style="margin-top: -1rem; margin-bottom: 2rem;">Test your knowledge and learn something new!</p>
            
            <a href="auth/login.php" class="btn btn-primary" style="margin-bottom: 1rem;">Sign in</a>
            <a href="auth/register.php" class="btn btn-secondary">Create an account</a>
            
            <div class="text-center" style="margin-top: 2rem;">
                By continuing, you agree to our<br>
                <a href="#" style="color: var(--primary-color);">Terms of Service</a> & <a href="#" style="color: var(--primary-color);">Privacy Policy</a>
            </div>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html> 