<?php
session_start();
require_once 'Database_file/config.php';
require_once 'PHPMailer/EmailVerification.php';

$verification_result = null;
$error_message = null;

if (isset($_GET['token'])) {
    $token = trim($_GET['token']);
    
    if (!empty($token)) {
        $emailVerification = new EmailVerification($conn);
        $user_id = $emailVerification->verifyEmail($token);
        
        if ($user_id) {
            $verification_result = 'success';
            
            // Get user details
            $sql = "SELECT full_name, email FROM users WHERE id = ?";
            $stmt = executeQuery($conn, $sql, [$user_id], "i");
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
        } else {
            $error_message = "Invalid or expired verification link. Please request a new verification email.";
        }
    } else {
        $error_message = "Verification token is required.";
    }
} else {
    $error_message = "Verification token is required.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - E_Cart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>

<?php include 'Header.php'; ?>

<main class="auth-main">
    <div class="auth-main-inner">
        <div style="max-width: 500px; margin: 0 auto; background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
            
            <?php if ($verification_result === 'success'): ?>
                <!-- Success Message -->
                <div style="text-align: center;">
                    <div style="width: 80px; height: 80px; background: #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                        <i class="fas fa-check" style="color: white; font-size: 36px;"></i>
                    </div>
                    <h1 style="color: #1f2937; margin: 0 0 10px; font-size: 28px;">Email Verified Successfully!</h1>
                    <p style="color: #6b7280; margin: 0 0 20px; line-height: 1.6;">
                        Welcome to E_Cart, <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>!<br>
                        Your email <strong><?php echo htmlspecialchars($user['email']); ?></strong> has been verified.
                    </p>
                    <p style="color: #6b7280; margin: 0 0 30px;">
                        You can now login and start shopping for the best deals on mobiles, fashion, electronics and more.
                    </p>
                    <a href="login.php" style="background: #2563eb; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: 600; display: inline-block;">
                        Go to Login
                    </a>
                </div>
                
            <?php else: ?>
                <!-- Error Message -->
                <div style="text-align: center;">
                    <div style="width: 80px; height: 80px; background: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                        <i class="fas fa-times" style="color: white; font-size: 36px;"></i>
                    </div>
                    <h1 style="color: #1f2937; margin: 0 0 10px; font-size: 28px;">Verification Failed</h1>
                    <p style="color: #6b7280; margin: 0 0 20px; line-height: 1.6;">
                        <?php echo htmlspecialchars($error_message); ?>
                    </p>
                    <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                        <a href="resend_verification.php" style="background: #2563eb; color: white; padding: 12px 20px; text-decoration: none; border-radius: 6px; font-weight: 600;">
                            Resend Verification Email
                        </a>
                        <a href="login.php" style="background: #6b7280; color: white; padding: 12px 20px; text-decoration: none; border-radius: 6px; font-weight: 600;">
                            Back to Login
                        </a>
                    </div>
                </div>
            <?php endif; ?>
            
        </div>
    </div>
</main>

<script src="assets/js/index.js"></script>
</body>
</html>
