<?php
// Individual pages should handle their own session and database connections
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Student Dashboard'; ?> - Quiz Platform</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root {
            --primary-color: #0D6EFD;
            --sidebar-bg: #1a1c23;
            --main-bg: #f5f6fa;
            --text-light: #a0a3bd;
            --text-dark: #333;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
            background: var(--main-bg);
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 260px;
            height: 100vh;
            background: var(--sidebar-bg);
            padding: 2rem;
            position: fixed;
            color: white;
        }

        .profile {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 3rem;
            text-align: center;
        }

        .profile img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin-bottom: 1rem;
            object-fit: cover;
        }

        .profile h3 {
            margin: 0;
            font-size: 1.2rem;
            color: white;
        }

        .profile p {
            margin: 0.5rem 0 0;
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .nav-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-links li {
            margin-bottom: 0.5rem;
        }

        .nav-links a {
            display: flex;
            align-items: center;
            padding: 0.8rem 1rem;
            color: var(--text-light);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .nav-links a:hover,
        .nav-links a.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .nav-links a i {
            margin-right: 10px;
            font-size: 1.2rem;
        }

        .logout-btn {
            position: absolute;
            bottom: 2rem;
            left: 2rem;
            right: 2rem;
            padding: 0.8rem;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .logout-btn:hover {
            background: #c82333;
        }

        .main-content {
            margin-left: 260px;
            padding: 2rem;
            width: calc(100% - 260px);
        }

        .dashboard-header {
            margin-bottom: 2rem;
        }

        .dashboard-header h1 {
            margin: 0;
            font-size: 1.8rem;
            color: var(--text-dark);
        }

        /* Additional styles for cards and grids */
        .card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
    </style>
    <!-- Add Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <?php if (isset($extra_css)) echo $extra_css; ?>
</head>
<body>
    <div class="sidebar">
        <div class="profile">
            <img src="../assets/images/user-avatar.svg" alt="Profile">
            <h3><?php echo htmlspecialchars($_SESSION['name'] ?? 'Student'); ?></h3>
            <p>Student</p>
        </div>
        <ul class="nav-links">
            <li>
                <a href="dashboard.php" <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-home"></i>
                    Dashboard
                </a>
            </li>
            <li>
                <a href="quiz-list.php" <?php echo basename($_SERVER['PHP_SELF']) == 'quiz-list.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-edit"></i>
                    Manage Quiz
                </a>
            </li>
            <li>
                <a href="see-result.php" <?php echo basename($_SERVER['PHP_SELF']) == 'see-result.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-chart-bar"></i>
                    See Results
                </a>
            </li>
            <li>
                <a href="summary.php" <?php echo basename($_SERVER['PHP_SELF']) == 'summary.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-chart-line"></i>
                    Summary
                </a>
            </li>
            <li>
                <a href="account.php" <?php echo basename($_SERVER['PHP_SELF']) == 'account.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-user"></i>
                    Account
                </a>
            </li>
        </ul>
        <form action="../auth/logout.php" method="POST">
            <button type="submit" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </button>
        </form>
    </div>

    <div class="main-content">
        <div class="dashboard-header">
            <h1><?php echo $page_title ?? 'Student Dashboard'; ?></h1>
        </div>
        <?php echo $content ?? ''; ?>
    </div>

    <?php if (isset($extra_js)) echo $extra_js; ?>
</body>
</html> 