<?php
// Direct database connection
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'e_cart';

try {
    $conn = new mysqli($host, $username, $password, $database);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "<h2>🗄️ Database Setup</h2>";
    
    // Create email_verifications table
    $sql1 = "CREATE TABLE IF NOT EXISTS email_verifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        email VARCHAR(255) NOT NULL,
        verification_token VARCHAR(255) NOT NULL,
        is_verified BOOLEAN DEFAULT FALSE,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_token (verification_token)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($sql1)) {
        echo "✅ email_verifications table created<br>";
    } else {
        echo "❌ Failed to create email_verifications table: " . $conn->error . "<br>";
    }
    
    // Add email_verified column to users table
    $sql2 = "ALTER TABLE users ADD COLUMN IF NOT EXISTS email_verified BOOLEAN DEFAULT FALSE";
    
    if ($conn->query($sql2)) {
        echo "✅ email_verified column added to users table<br>";
    } else {
        echo "ℹ️ email_verified column already exists or failed: " . $conn->error . "<br>";
    }
    
    echo "<br><div style='background: #d1fae5; padding: 15px; border-radius: 8px;'>";
    echo "✅ <strong>Database setup completed!</strong><br>";
    echo "Email verification system is ready to use.";
    echo "</div>";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}
?>

<br><hr>
<h3>🚀 Next Steps:</h3>
1. <a href="login.php">Test Registration</a><br>
2. <a href="test_email.php">Test Email</a><br>
