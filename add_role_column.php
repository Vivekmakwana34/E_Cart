<?php
require_once 'Database_file/config.php';

// Check if role column exists
$check_column = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
if ($check_column->num_rows == 0) {
    // Add role column
    $sql = "ALTER TABLE users ADD COLUMN role VARCHAR(20) DEFAULT 'user' AFTER email";
    if ($conn->query($sql)) {
        echo 'Role column added successfully!';
        
        // Update existing users - set admin role for ID 20
        $update_sql = "UPDATE users SET role = 'admin' WHERE id = 20";
        $conn->query($update_sql);
        echo ' Admin role set for ID 20.';
    } else {
        echo 'Error adding role column: ' . $conn->error;
    }
} else {
    echo 'Role column already exists!';
}

$conn->close();
?>
