<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config/database.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'student') {
    header('Location: ../auth/login.php');
    exit();
}

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $errors = [];
    
    // Validate name and email
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    // Check if email is already taken by another user
    if ($email !== $user['email']) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $_SESSION['user_id']]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Email is already taken";
        }
    }
    
    // Handle password change if requested
    if (!empty($new_password)) {
        if (empty($current_password)) {
            $errors[] = "Current password is required to change password";
        } elseif (!password_verify($current_password, $user['password'])) {
            $errors[] = "Current password is incorrect";
        } elseif (strlen($new_password) < 6) {
            $errors[] = "New password must be at least 6 characters long";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match";
        }
    }
    
    // Update profile if no errors
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Update basic info
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
            $stmt->execute([$name, $email, $_SESSION['user_id']]);
            
            // Update password if provided
            if (!empty($new_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $_SESSION['user_id']]);
            }
            
            $pdo->commit();
            $_SESSION['success'] = "Profile updated successfully";
            header('Location: account.php');
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "An error occurred while updating your profile";
        }
    }
}

$page_title = 'My Account';

// Start output buffering
ob_start();
?>

<div class="card account-card">
    <h2>My Account</h2>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php 
            echo htmlspecialchars($_SESSION['success']);
            unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" class="account-form">
        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" class="form-control" 
                   value="<?php echo htmlspecialchars($user['name']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" class="form-control" 
                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
        </div>
        
        <h3 class="mt-4">Change Password</h3>
        <p class="text-muted">Leave blank to keep current password</p>
        
        <div class="form-group">
            <label for="current_password">Current Password</label>
            <input type="password" id="current_password" name="current_password" class="form-control">
        </div>
        
        <div class="form-group">
            <label for="new_password">New Password</label>
            <input type="password" id="new_password" name="new_password" class="form-control">
        </div>
        
        <div class="form-group">
            <label for="confirm_password">Confirm New Password</label>
            <input type="password" id="confirm_password" name="confirm_password" class="form-control">
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Profile</button>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();

$extra_css = '
<style>
    .account-card {
        max-width: 600px;
        margin: 2rem auto;
        padding: 2rem;
    }
    .account-form {
        margin-top: 1.5rem;
    }
    .form-group {
        margin-bottom: 1.5rem;
    }
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        color: var(--text-dark);
        font-weight: 500;
    }
    .form-control {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 1rem;
    }
    .form-control:focus {
        border-color: var(--primary-color);
        outline: none;
        box-shadow: 0 0 0 2px rgba(var(--primary-rgb), 0.1);
    }
    .alert {
        padding: 1rem;
        margin-bottom: 1rem;
        border-radius: 8px;
    }
    .alert-danger {
        background-color: #f8d7da;
        color: #842029;
        border: 1px solid #f5c2c7;
    }
    .alert-success {
        background-color: #d1e7dd;
        color: #0f5132;
        border: 1px solid #badbcc;
    }
    .alert ul {
        margin: 0;
        padding-left: 1.5rem;
    }
    .text-muted {
        color: #6c757d;
        font-size: 0.9rem;
    }
    .mt-4 {
        margin-top: 2rem;
    }
    .mb-0 {
        margin-bottom: 0;
    }
    .form-actions {
        margin-top: 2rem;
        text-align: center;
    }
    .btn {
        display: inline-block;
        padding: 0.8rem 2rem;
        border-radius: 8px;
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

require_once 'includes/layout.php';
?> 