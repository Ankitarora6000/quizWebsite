<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a tutor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'tutor') {
    header('Location: ../auth/login.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $success = false;
    $error = null;

    try {
        // Update profile
        if (isset($_POST['update_profile'])) {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
            $stmt->execute([
                $_POST['name'],
                $_POST['email'],
                $_SESSION['user_id']
            ]);
            $_SESSION['name'] = $_POST['name'];
            $success = "Profile updated successfully!";
        }
        
        // Change password
        if (isset($_POST['change_password'])) {
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();

            if (password_verify($_POST['current_password'], $user['password'])) {
                if ($_POST['new_password'] === $_POST['confirm_password']) {
                    $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$new_password, $_SESSION['user_id']]);
                    $success = "Password changed successfully!";
                } else {
                    $error = "New passwords do not match!";
                }
            } else {
                $error = "Current password is incorrect!";
            }
        }
    } catch (PDOException $e) {
        $error = "Failed to update. Please try again.";
    }
}

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - Quiz Platform</title>
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
            <li><a href="summary.php">Summary</a></li>
            <li><a href="account.php" class="active">Account</a></li>
        </ul>
        <form action="../auth/logout.php" method="POST">
            <button type="submit" class="logout-btn">Logout</button>
        </form>
    </div>

    <div class="main-content">
        <div class="dashboard-header">
            <h1>Account Settings</h1>
        </div>

        <?php if (isset($success)): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="account-sections">
            <div class="section profile-section">
                <h2>Profile Information</h2>
                <form method="POST" class="account-form">
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                </form>
            </div>

            <div class="section password-section">
                <h2>Change Password</h2>
                <form method="POST" class="account-form">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html> 