-- Drop database if exists and create new one
DROP DATABASE IF EXISTS quiz_platform;
CREATE DATABASE quiz_platform;
USE quiz_platform;

-- Create tables
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    user_type ENUM('student', 'tutor') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE subjects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT
);

CREATE TABLE quizzes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    subject_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    time_limit INT NOT NULL DEFAULT 600, -- Time limit in seconds (default 10 minutes)
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE questions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    quiz_id INT NOT NULL,
    question_text TEXT NOT NULL,
    question_order INT NOT NULL DEFAULT 0,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id)
);

CREATE TABLE options (
    id INT PRIMARY KEY AUTO_INCREMENT,
    question_id INT NOT NULL,
    option_text TEXT NOT NULL,
    is_correct BOOLEAN NOT NULL DEFAULT FALSE,
    FOREIGN KEY (question_id) REFERENCES questions(id)
);

CREATE TABLE quiz_attempts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    quiz_id INT NOT NULL,
    student_id INT NOT NULL,
    score DECIMAL(5,2) NOT NULL DEFAULT 0,
    time_taken INT, -- Time taken in seconds
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id),
    FOREIGN KEY (student_id) REFERENCES users(id)
);

CREATE TABLE quiz_answers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    attempt_id INT NOT NULL,
    question_id INT NOT NULL,
    selected_option_id INT NOT NULL,
    FOREIGN KEY (attempt_id) REFERENCES quiz_attempts(id),
    FOREIGN KEY (question_id) REFERENCES questions(id),
    FOREIGN KEY (selected_option_id) REFERENCES options(id)
);

-- Insert sample data
-- Insert subjects
INSERT INTO subjects (name, description) VALUES
('Mathematics', 'Basic to advanced mathematics concepts'),
('Science', 'General science including physics, chemistry, and biology'),
('Computer Science', 'Programming and computer fundamentals'),
('English', 'English language and literature');

-- Insert sample users (password is 'password' for both users)
INSERT INTO users (name, email, password, user_type) VALUES
('John Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tutor'),
('Jane Smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student');

-- Insert sample quizzes
INSERT INTO quizzes (subject_id, title, description, time_limit, created_by) VALUES
(1, 'Basic Algebra Quiz', 'Test your algebra knowledge', 600, 1),
(3, 'Programming Basics', 'Test your programming knowledge', 600, 1);

-- Insert questions for Mathematics Quiz
INSERT INTO questions (quiz_id, question_text, question_order) VALUES
(1, 'What is the value of x in the equation 2x + 5 = 15?', 1),
(1, 'Simplify: 3(x + 2) - 2(x - 1)', 2),
(1, 'Solve for y: y/3 + 4 = 10', 3),
(1, 'What is the slope of the line y = 2x + 3?', 4),
(1, 'Factor: x² + 5x + 6', 5);

-- Insert options for Mathematics Quiz
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
(3, '15', false),

(4, '2', true),
(4, '3', false),
(4, '0', false),
(4, '1', false),

(5, '(x + 2)(x + 3)', true),
(5, '(x + 1)(x + 6)', false),
(5, '(x + 3)(x + 3)', false),
(5, '(x + 2)(x + 4)', false);

-- Insert questions for Programming Quiz
INSERT INTO questions (quiz_id, question_text, question_order) VALUES
(2, 'What does HTML stand for?', 1),
(2, 'Which of these is not a programming language?', 2),
(2, 'What is the correct way to declare a variable in JavaScript?', 3),
(2, 'What does CSS stand for?', 4),
(2, 'Which symbol is used for single-line comments in JavaScript?', 5);

-- Insert options for Programming Quiz
INSERT INTO options (question_id, option_text, is_correct) VALUES
(6, 'HyperText Markup Language', true),
(6, 'HighText Machine Language', false),
(6, 'HyperText Machine Language', false),
(6, 'HyperTool Markup Language', false),

(7, 'Microsoft Word', true),
(7, 'Python', false),
(7, 'Java', false),
(7, 'PHP', false),

(8, 'let x = 5;', true),
(8, 'x := 5;', false),
(8, 'x => 5;', false),
(8, '#x = 5;', false),

(9, 'Cascading Style Sheets', true),
(9, 'Computer Style Sheets', false),
(9, 'Creative Style Sheets', false),
(9, 'Colorful Style Sheets', false),

(10, '//', true),
(10, '#', false),
(10, '--', false),
(10, '/*', false);

-- Insert sample quiz attempts
INSERT INTO quiz_attempts (quiz_id, student_id, score, time_taken, started_at, completed_at) VALUES
(1, 2, 80.00, 300, DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 2, 90.00, 250, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY));

-- Insert sample quiz answers
INSERT INTO quiz_answers (attempt_id, question_id, selected_option_id) VALUES
(1, 1, 1), -- Correct answer for question 1
(1, 2, 5), -- Correct answer for question 2
(1, 3, 9), -- Correct answer for question 3
(1, 4, 13), -- Correct answer for question 4
(1, 5, 18), -- Wrong answer for question 5

(2, 6, 21), -- Correct answer for HTML question
(2, 7, 25), -- Correct answer for programming language question
(2, 8, 29), -- Correct answer for JavaScript variable question
(2, 9, 33), -- Correct answer for CSS question
(2, 10, 37); -- Correct answer for JavaScript comment question 