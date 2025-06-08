<?php
require_once 'config/database.php';

try {
    // Check if the quiz_attempts table exists and its structure
    $stmt = $pdo->query("DESCRIBE quiz_attempts");
    echo "quiz_attempts table structure:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 