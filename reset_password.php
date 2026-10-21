<?php
session_start();

// Check if user came through OTP verification
if (!isset($_SESSION['reset_email']) || !isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
    header('Location: forgot_pass.php');
    exit();
}

$email = $_SESSION['reset_email'];
$success = false;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (empty($password) || empty($confirm)) {
        $message = "Please enter password in both fields.";
    } elseif ($password !== $confirm) {
        $message = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters long.";
    } else {
        // Update password in database
        $conn = new mysqli('localhost', 'root', '', 'e_cart');
        
        if ($conn->connect_error) {
            $success = false;
            $message = "Database connection failed. Please try again.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Update password and clear reset token
            $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, token_expiry = NULL WHERE email = ?");
            $stmt->bind_param("ss", $hashed_password, $email);
            
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $success = true;
                    $message = "Your password has been reset successfully! You can now login with your new password.";
                    
                    // Clear session variables
                    unset($_SESSION['reset_email']);
                    unset($_SESSION['reset_token']);
                    unset($_SESSION['otp_verified']);
                } else {
                    $success = false;
                    $message = "Failed to update password. User not found.";
                }
            } else {
                $success = false;
                $message = "Failed to update password. Please try again.";
            }
            
            $stmt->close();
            $conn->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/login.css">
    <title>Reset Password - E_Cart</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f1f2f4;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        .reset-container {
            display: flex;
            min-height: calc(100vh - 250px);
            align-items: center;
            justify-content: center;
            padding: 15px;
        }
        .reset-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            overflow: hidden;
        }
        .reset-header {
            background: linear-gradient(135deg, #1d4ed8, #020617);
            color: white;
            padding: 30px 25px;
            text-align: center;
        }
        .reset-icon {
            width: 64px;
            height: 64px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 28px;
        }
        .reset-header h1 {
            font-size: 24px;
            margin: 0 0 8px 0;
            font-weight: 600;
        }
        .reset-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 14px;
        }
        .reset-body {
            padding: 40px 30px;
        }
        .form-group {
            margin-bottom: 24px;
        }
        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 8px;
        }
        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        .form-input:focus {
            outline: none;
            border-color: #1d4ed8;
            box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.1);
        }
        .error-text {
            color: #dc2626;
            font-size: 13px;
            margin-top: 6px;
        }
        .success-text {
            background: #ecfdf5;
            color: #059669;
            padding: 12px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
            border: 1px solid #bbf7d0;
        }
        .submit-btn {
            width: 100%;
            background: #1d4ed8;
            color: white;
            border: none;
            padding: 14px 24px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .submit-btn:hover {
            background: #1e40af;
            transform: translateY(-1px);
        }
        .back-link {
            text-align: center;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
        }
        .back-link a {
            color: #1d4ed8;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <?php include 'Header.php'; ?>

<div class="reset-container">
    <div class="reset-card">
        <div class="reset-header">
            <div class="reset-icon">
                <i class="fas fa-key"></i>
            </div>
            <h1>Reset Password</h1>
            <p>Set a new password for your E_Cart account</p>
        </div>
        <div class="reset-body">
            <?php if (!empty($message)): ?>
                <div class="<?php echo $success ? 'success-text' : 'error-text'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if (!$success && isset($_SESSION['otp_verified']) && $_SESSION['otp_verified'] === true): ?>
            <form method="post">
                <div class="form-group">
                    <label class="form-label" for="password">New Password</label>
                    <input type="password" id="password" name="password" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
                </div>
                <button type="submit" class="submit-btn">
                    <span>Update Password</span>
                </button>
            </form>
            <?php endif; ?>

            <div class="back-link">
                <a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
            </div>
        </div>
    </div>
</div>

    <?php include 'Footer.php'; ?>

</body>
</html>
