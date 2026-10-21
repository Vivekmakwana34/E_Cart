<?php
session_start();
require_once 'Database_file/config.php';
require_once 'PHPMailer/EmailVerification.php';

$success_message = null;
$error_message = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    
    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Check if user exists
        $sql = "SELECT id, full_name, email_verified FROM users WHERE email = ?";
        $stmt = executeQuery($conn, $sql, [$email], "s");
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Check if already verified
            if ($user['email_verified']) {
                $error_message = "This email is already verified. You can login directly.";
            } else {
                // Generate new token and send email
                $emailVerification = new EmailVerification($conn);
                $token = $emailVerification->generateVerificationToken($user['id'], $email);
                
                if ($token) {
                    if ($emailVerification->sendVerificationEmail($email, $user['full_name'], $token)) {
                        $success_message = "Verification email has been sent to your email address. Please check your inbox (including spam folder).";
                    } else {
                        $error_message = "Failed to send verification email. Please try again later.";
                    }
                } else {
                    $error_message = "Failed to generate verification token. Please try again.";
                }
            }
        } else {
            $error_message = "No account found with this email address.";
        }
    } else {
        $error_message = "Please enter a valid email address.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resend Verification - E_Cart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>

<?php include 'Header.php'; ?>

<main class="auth-main">
    <div class="auth-main-inner">
        <div style="max-width: 500px; margin: 0 auto; background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
            
            <div style="text-align: center; margin-bottom: 30px;">
                <div style="width: 80px; height: 80px; background: #2563eb; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                    <i class="fas fa-envelope" style="color: white; font-size: 36px;"></i>
                </div>
                <h1 style="color: #1f2937; margin: 0 0 10px; font-size: 28px;">Resend Verification Email</h1>
                <p style="color: #6b7280; margin: 0; line-height: 1.6;">
                    Enter your email address and we'll send you a new verification link.
                </p>
            </div>
            
            <?php if ($success_message): ?>
                <div style="background: #d1fae5; color: #065f46; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
                    <i class="fas fa-check-circle" style="margin-right: 8px;"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
                    <i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!$success_message): ?>
                <form method="POST" action="resend_verification.php" style="margin-bottom: 20px;">
                    <div style="margin-bottom: 20px;">
                        <label for="email" style="display: block; margin-bottom: 8px; color: #374151; font-weight: 500;">Email Address</label>
                        <div style="position: relative;">
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   placeholder="Enter your email address" 
                                   required
                                   style="width: 100%; padding: 12px 15px 12px 45px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 16px; box-sizing: border-box;">
                            <i class="fas fa-envelope" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #9ca3af;"></i>
                        </div>
                    </div>
                    
                    <button type="submit" 
                            style="width: 100%; background: #2563eb; color: white; padding: 12px; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; transition: background 0.3s;">
                        <i class="fas fa-paper-plane" style="margin-right: 8px;"></i>
                        Send Verification Email
                    </button>
                </form>
            <?php endif; ?>
            
            <div style="text-align: center; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                <p style="color: #6b7280; margin: 0 0 15px;">
                    Remember your password?
                </p>
                <a href="login.php" style="color: #2563eb; text-decoration: none; font-weight: 600;">
                    <i class="fas fa-arrow-left" style="margin-right: 5px;"></i>
                    Back to Login
                </a>
            </div>
            
        </div>
    </div>
</main>

<script src="assets/js/index.js"></script>
</body>
</html>
