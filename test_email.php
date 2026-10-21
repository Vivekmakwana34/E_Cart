<?php
echo "<h2>📧 Email Test</h2>";

try {
    require_once 'Database_file/config.php';
    require_once 'PHPMailer/EmailVerification.php';
    
    $emailVerification = new EmailVerification($conn);
    echo "✅ EmailVerification class loaded<br>";
    
    // Test with your email
    $test_email = 'dudhagarabgo@gmail.com';
    $test_name = 'Test User';
    $test_user_id = 999;
    
    echo "📧 Generating token...<br>";
    $token = $emailVerification->generateVerificationToken($test_user_id, $test_email);
    
    if ($token) {
        echo "✅ Token generated: " . substr($token, 0, 10) . "...<br>";
        
        echo "📤 Sending test email...<br>";
        $email_sent = $emailVerification->sendVerificationEmail($test_email, $test_name, $token);
        
        if ($email_sent) {
            echo "✅ <strong>EMAIL SENT SUCCESSFULLY!</strong><br>";
            echo "📬 Please check your Gmail inbox (and spam folder)<br>";
            
            $verification_link = "http://localhost/E_Cart/verify_email.php?token=" . $token;
            echo "🔗 <a href='$verification_link' target='_blank'>Click here to verify test email</a><br>";
        } else {
            echo "❌ <strong>Email sending failed</strong><br>";
            echo "Please check Gmail App Password settings<br>";
        }
    } else {
        echo "❌ Token generation failed<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

if (isset($conn)) {
    $conn->close();
}
?>

<br><hr>
<h3>🔗 Quick Links:</h3>
1. <a href="setup_database.php">Setup Database</a><br>
2. <a href="login.php">Test Registration</a><br>
3. <a href="resend_verification.php">Resend Verification</a><br>
