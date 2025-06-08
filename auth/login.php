<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_type'] = $user['user_type'];
        $_SESSION['name'] = $user['name'];

        if ($user['user_type'] == 'tutor') {
            header('Location: ../tutor/dashboard.php');
        } else {
            header('Location: ../student/dashboard.php');
        }
        exit();
    } else {
        $error = "Invalid email or password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Quiz Platform</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 20px;
        }
        .login-form {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 420px;
        }
        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .form-header h2 {
            color: var(--primary-color);
            margin-bottom: 8px;
            font-size: 24px;
        }
        .form-header p {
            color: var(--text-light);
            font-size: 16px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-dark);
            font-weight: 500;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.2s;
        }
        .form-group input:focus {
            border-color: var(--primary-color);
            outline: none;
        }
        .forgot-password {
            text-align: right;
            margin-top: 8px;
        }
        .forgot-password a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 14px;
        }
        .btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        .btn-primary:hover {
            background: var(--primary-dark);
        }
        .divider {
            text-align: center;
            margin: 20px 0;
            position: relative;
        }
        .divider::before,
        .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 45%;
            height: 1px;
            background: #ddd;
        }
        .divider::before {
            left: 0;
        }
        .divider::after {
            right: 0;
        }
        .divider span {
            background: white;
            padding: 0 10px;
            color: var(--text-light);
            font-size: 14px;
        }
        .social-login {
            display: flex;
            justify-content: center;
            gap: 16px;
            margin-bottom: 20px;
        }
        .social-btn {
            width: 48px;
            height: 48px;
            border: 1px solid #ddd;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .social-btn:hover {
            background: #f5f5f5;
        }
        .form-footer {
            text-align: center;
            margin-top: 20px;
            color: var(--text-light);
        }
        .form-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .test-accounts {
            margin-top: 20px;
            padding: 15px;
            background: #f8fafc;
            border-radius: 8px;
            font-size: 14px;
        }
        .test-accounts h3 {
            color: var(--text-dark);
            margin-bottom: 10px;
            font-size: 16px;
        }
        .test-account {
            padding: 8px;
            background: white;
            border-radius: 4px;
            margin-bottom: 8px;
        }
        .test-account:last-child {
            margin-bottom: 0;
        }
        .account-type {
            color: var(--primary-color);
            font-weight: 500;
            margin-bottom: 4px;
        }
        .account-details {
            color: var(--text-light);
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-form">
            <div class="form-header">
                <h2>Welcome Back!</h2>
                <p>Test your knowledge, take quiz!</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
                <button type="submit" class="btn btn-primary">Sign in</button>
            </form>
            
            <div class="test-accounts">
                <h3>Test Accounts</h3>
                <div class="test-account">
                    <div class="account-type">Student Account</div>
                    <div class="account-details">Email: jane@example.com</div>
                    <div class="account-details">Password: password</div>
                </div>
                <div class="test-account">
                    <div class="account-type">Tutor Account</div>
                    <div class="account-details">Email: john@example.com</div>
                    <div class="account-details">Password: password</div>
                </div>
            </div>
            
            <div class="form-footer">
                <p>Don't have an account? <a href="register.php">Sign up</a></p>
            </div>
        </div>
    </div>
</body>
</html> 