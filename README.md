# Quiz Platform

A web-based quiz platform that allows tutors to create and manage quizzes, and students to take quizzes and track their performance.

## Features

### For Tutors
- Create and manage quizzes
- Add multiple-choice questions
- View student performance
- Track quiz attempts
- Generate performance reports

### For Students
- Take quizzes
- View performance statistics
- Track progress
- Review past attempts
- See detailed results

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Modern web browser

## Installation

1. Clone the repository to your web server's directory:
```bash
git clone https://github.com/yourusername/quiz-platform.git
```

2. Create a MySQL database named `quiz_platform`

3. Import the database schema:
```bash
mysql -u your_username -p quiz_platform < database.sql
```

4. Configure the database connection:
   - Open `config/database.php`
   - Update the database credentials according to your setup

5. Set up your web server:
   - For Apache: Ensure mod_rewrite is enabled
   - For Nginx: Configure URL rewriting according to your setup

6. Make sure the following directories have write permissions:
   - `assets/images/`

## Directory Structure

```
quiz-platform/
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── auth/
│   ├── login.php
│   ├── register.php
│   └── logout.php
├── config/
│   └── database.php
├── student/
│   ├── dashboard.php
│   ├── take-quiz.php
│   └── view-result.php
├── tutor/
│   ├── dashboard.php
│   ├── add-quiz.php
│   └── manage-quiz.php
├── database.sql
└── index.php
```

## Usage

1. Access the platform through your web browser:
```
http://localhost/quiz-platform
```

2. Register as either a tutor or student

3. For tutors:
   - Create quizzes from the dashboard
   - Add questions and options
   - View student performance

4. For students:
   - Browse available quizzes
   - Take quizzes
   - View results and progress

## Security

- All user passwords are hashed using PHP's password_hash()
- SQL injection prevention using prepared statements
- XSS prevention using htmlspecialchars()
- Session-based authentication
- Input validation and sanitization

## Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a new Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details. 
