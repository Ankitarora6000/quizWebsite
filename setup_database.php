<?php
require_once 'config/database.php';

try {
    // Create tables
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            user_type ENUM('student', 'tutor') NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS subjects (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            description TEXT
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS quizzes (
            id INT PRIMARY KEY AUTO_INCREMENT,
            subject_id INT NOT NULL,
            title VARCHAR(200) NOT NULL,
            description TEXT,
            time_limit INT NOT NULL DEFAULT 600,
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (subject_id) REFERENCES subjects(id),
            FOREIGN KEY (created_by) REFERENCES users(id)
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS questions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            quiz_id INT NOT NULL,
            question_text TEXT NOT NULL,
            FOREIGN KEY (quiz_id) REFERENCES quizzes(id)
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS options (
            id INT PRIMARY KEY AUTO_INCREMENT,
            question_id INT NOT NULL,
            option_text TEXT NOT NULL,
            is_correct BOOLEAN NOT NULL DEFAULT FALSE,
            FOREIGN KEY (question_id) REFERENCES questions(id)
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS quiz_attempts (
            id INT PRIMARY KEY AUTO_INCREMENT,
            quiz_id INT NOT NULL,
            user_id INT NOT NULL,
            score DECIMAL(5,2) NOT NULL,
            time_taken INT,
            started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (quiz_id) REFERENCES quizzes(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS quiz_answers (
            id INT PRIMARY KEY AUTO_INCREMENT,
            attempt_id INT NOT NULL,
            question_id INT NOT NULL,
            selected_option_id INT NOT NULL,
            FOREIGN KEY (attempt_id) REFERENCES quiz_attempts(id),
            FOREIGN KEY (question_id) REFERENCES questions(id),
            FOREIGN KEY (selected_option_id) REFERENCES options(id)
        )
    ");

    // Insert sample data
    // Insert subjects
    $pdo->exec("
        INSERT INTO subjects (name, description) VALUES
        ('Mathematics', 'Basic to advanced mathematics concepts'),
        ('Science', 'General science including physics, chemistry, and biology'),
        ('Computer Science', 'Programming and computer fundamentals'),
        ('English', 'English language and literature')
    ");

    // Insert sample users
    $pdo->exec("
        INSERT INTO users (name, email, password, user_type) VALUES
        ('John Doe', 'john@example.com', '" . password_hash('password', PASSWORD_DEFAULT) . "', 'tutor'),
        ('Jane Smith', 'jane@example.com', '" . password_hash('password', PASSWORD_DEFAULT) . "', 'student')
    ");

    // Insert sample quiz
    $pdo->exec("
        INSERT INTO quizzes (subject_id, title, description, time_limit, created_by) VALUES
        (1, 'Basic Algebra Quiz', 'Test your algebra knowledge', 600, 1)
    ");

    // Insert sample questions
    $pdo->exec("
        INSERT INTO questions (quiz_id, question_text) VALUES
        (1, 'What is the value of x in the equation 2x + 5 = 15?'),
        (1, 'Simplify: 3(x + 2) - 2(x - 1)'),
        (1, 'Solve for y: y/3 + 4 = 10')
    ");

    // Insert sample options
    $pdo->exec("
        INSERT INTO options (question_id, option_text, is_correct) VALUES
        (1, '5', true),
        (1, '4', false),
        (1, '6', false),
        (1, '7', false),
        
        (2, 'x + 7', true),
        (2, 'x + 5', false),
        (2, '5x + 1', false),
        (2, '3x + 4', false),
        
        (3, '18', true),
        (3, '16', false),
        (3, '20', false),
        (3, '15', false)
    ");

    echo "Database setup completed successfully!";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 