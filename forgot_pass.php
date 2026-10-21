<?php
// Enable error logging to file for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/forgot_pass_error.log');

session_start();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Set JSON header immediately
    header('Content-Type: application/json');
    
    $email = $_POST['email'] ?? '';
    $action = $_POST['action'] ?? 'send_otp';
    
    $response = ['success' => false, 'message' => '', 'redirect' => false];
    
    // Validate email
    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        
        // Database connection
        $conn = new mysqli('localhost', 'root', '', 'e_cart');
        
        if ($conn->connect_error) {
            $response['message'] = "Database connection failed: " . $conn->connect_error;
            echo json_encode($response);
            exit();
        }
        
        // Create forgot_pass table if not exists
        $createTableSQL = "CREATE TABLE IF NOT EXISTS forgot_pass (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(255) NOT NULL,
            OTP VARCHAR(6) NOT NULL,
            last_attempt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            attempt_count INT NOT NULL DEFAULT 0,
            last_otp_sent TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )";
        
        if (!$conn->query($createTableSQL)) {
            $response['message'] = "Failed to create table: " . $conn->error;
            echo json_encode($response);
            $conn->close();
            exit();
        }
        
        // Check if user exists
        $userStmt = $conn->prepare("SELECT id, full_name FROM users WHERE email = ? LIMIT 1");
        $userStmt->bind_param("s", $email);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        
        if ($userResult->num_rows === 0) {
            $response['message'] = "No account found with this email address.";
            echo json_encode($response);
            $userStmt->close();
            $conn->close();
            exit();
        }
        
        $user = $userResult->fetch_assoc();
        $userId = $user['id'];
        $userName = $user['full_name'];
        $userStmt->close();
        
        if ($action === 'resend_otp') {
            // Check if last OTP was sent less than 60 seconds ago
            $checkStmt = $conn->prepare("SELECT last_otp_sent FROM forgot_pass WHERE user_id = ? ORDER BY id DESC LIMIT 1");
            $checkStmt->bind_param("i", $userId);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                $lastOtpData = $checkResult->fetch_assoc();
                $lastOtpSent = strtotime($lastOtpData['last_otp_sent']);
                $currentTime = time();
                $timeDiff = $currentTime - $lastOtpSent;
                
                if ($timeDiff < 60) {
                    $waitTime = 60 - $timeDiff;
                    $response['message'] = "Please wait {$waitTime} seconds before resending OTP.";
                    echo json_encode($response);
                    $checkStmt->close();
                    $conn->close();
                    exit();
                }
            }
            $checkStmt->close();
        }
        
        // Generate 6-digit OTP
        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $token = bin2hex(random_bytes(32));
        $currentTime = date('Y-m-d H:i:s');
        
        // Check if entry exists and get current attempt_count
        $checkEntryStmt = $conn->prepare("SELECT id, attempt_count, last_attempt FROM forgot_pass WHERE user_id = ? ORDER BY id DESC LIMIT 1");
        $checkEntryStmt->bind_param("i", $userId);
        $checkEntryStmt->execute();
        $entryResult = $checkEntryStmt->get_result();
        
        $currentAttemptCount = 0;
        $entryExists = false;
        $entryId = null;
        $lastAttemptTime = null;
        
        if ($entryResult->num_rows > 0) {
            $entryData = $entryResult->fetch_assoc();
            $currentAttemptCount = $entryData['attempt_count'];
            $entryId = $entryData['id'];
            $lastAttemptTime = strtotime($entryData['last_attempt']);
            $entryExists = true;
        }
        $checkEntryStmt->close();
        
        // Check if maximum attempts (3) reached
        if ($currentAttemptCount >= 3) {
            $currentTimestamp = time();
            $hoursSinceLastAttempt = ($currentTimestamp - $lastAttemptTime) / 3600;
            
            if ($hoursSinceLastAttempt < 24) {
                $remainingHours = ceil(24 - $hoursSinceLastAttempt);
                $response['message'] = "Maximum 3 attempts reached. Please try again after {$remainingHours} hour(s).";
                echo json_encode($response);
                $conn->close();
                exit();
            } else {
                // Reset attempt count after 24 hours
                $currentAttemptCount = 0;
            }
        }
        
        // Increment attempt count
        $newAttemptCount = $currentAttemptCount + 1;
        
        if ($entryExists) {
            // Update existing entry with incremented attempt_count
            $updateStmt = $conn->prepare("UPDATE forgot_pass SET token = ?, OTP = ?, last_attempt = ?, last_otp_sent = ?, attempt_count = ? WHERE id = ?");
            $updateStmt->bind_param("ssssii", $token, $otp, $currentTime, $currentTime, $newAttemptCount, $entryId);
            $updateStmt->execute();
            $updateStmt->close();
        } else {
            // Insert new entry with attempt_count = 1
            $insertStmt = $conn->prepare("INSERT INTO forgot_pass (user_id, token, OTP, last_attempt, attempt_count, last_otp_sent) VALUES (?, ?, ?, ?, ?, ?)");
            $insertStmt->bind_param("isssis", $userId, $token, $otp, $currentTime, $newAttemptCount, $currentTime);
            $insertStmt->execute();
            $insertStmt->close();
        }
        
        // Store in session
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_user_id'] = $userId;
        $_SESSION['reset_token'] = $token;
        
        // Email content with OTP
        $subject = "Password Reset OTP - E_Cart";
        $message = "
        <html>
        <body>
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background: linear-gradient(135deg, #1d4ed8, #020617); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0;'>
                    <h1 style='margin: 0; font-size: 24px;'>E_Cart Password Reset</h1>
                    <p style='margin: 10px 0 0 0; opacity: 0.9;'>Secure Account Recovery</p>
                </div>
                
                <div style='background: white; padding: 30px; border: 1px solid #e5e7eb; border-radius: 0 0 10px 10px;'>
                    <h2 style='color: #1d4ed8; margin-top: 0;'>Hello {$userName},</h2>
                    <p style='color: #374151; line-height: 1.6;'>We received a request to reset your password for your E_Cart account. Use the following OTP code to verify your identity:</p>
                    
                    <div style='text-align: center; margin: 30px 0; padding: 20px; background: #f3f4f6; border-radius: 8px;'>
                        <div style='font-size: 32px; font-weight: bold; color: #1d4ed8; letter-spacing: 8px;'>{$otp}</div>
                        <p style='color: #6b7280; font-size: 14px; margin: 10px 0 0 0;'>This OTP will expire in 60 seconds</p>
                    </div>
                    
                    <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;'>
                        <p style='color: #6b7280; font-size: 13px; margin: 5px 0;'><strong>Important:</strong></p>
                        <ul style='color: #6b7280; font-size: 13px; margin: 10px 0; padding-left: 20px;'>
                            <li>This OTP will expire in 60 seconds</li>
                            <li>If you didn't request this, please ignore this email</li>
                            <li>Your password will remain unchanged</li>
                        </ul>
                    </div>
                    
                    <div style='margin-top: 20px; text-align: center; color: #9ca3af; font-size: 12px;'>
                        <p>&copy; 2024 E_Cart. All rights reserved.</p>
                    </div>
                </div>
            </div>
        </body>
        </html>";
        
        // Send email
        $email_sent = false;
        
        try {
            require_once 'vendor/autoload.php';
            $mail = new PHPMailer\PHPMailer\PHPMailer();
            
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'dudhagarabgo@gmail.com';
            $mail->Password = 'vzhk sasn skgy gfru';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
            
            $mail->setFrom('dudhagarabgo@gmail.com', 'E_Cart');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;
            
            $email_sent = $mail->send();
        } catch (Exception $e) {
            $email_sent = false;
        }
        
        if ($email_sent) {
            $response['success'] = true;
            $response['message'] = "OTP has been sent to your email! (Attempt {$newAttemptCount} of 3)";
            $response['redirect'] = true;
        } else {
            $response['message'] = "Failed to send OTP email. Please try again.";
        }
        
        $conn->close();
        
        // Return JSON response (header already set at top)
        echo json_encode($response);
        exit();
        
    } else {
        $response['message'] = "Please enter a valid email address.";
        echo json_encode($response);
        exit();
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
    <title>Forgot Password - E_Cart</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f1f2f4;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        .forgot-container {
            display: flex;
            min-height: calc(100vh - 250px);
            align-items: center;
            justify-content: center;
            padding: 15px;
        }

        .forgot-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            overflow: hidden;
        }

        .forgot-header {
            background: linear-gradient(135deg, #1d4ed8, #020617);
            color: white;
            padding: 30px 25px;
            text-align: center;
        }

        .forgot-icon {
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

        .forgot-header h1 {
            font-size: 24px;
            margin: 0 0 8px 0;
            font-weight: 600;
        }

        .forgot-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 14px;
        }

        .forgot-body {
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

        .form-input.error {
            border-color: #dc2626;
        }

        .error-message {
            color: #dc2626;
            font-size: 13px;
            margin-top: 6px;
            display: none;
        }

        .success-message {
            background: #ecfdf5;
            color: #059669;
            padding: 12px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
            display: none;
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

        .submit-btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
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

        .features {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #6b7280;
        }

        .feature-item i {
            color: #10b981;
            font-size: 14px;
        }

        @media (max-width: 480px) {
            .forgot-card {
                margin: 0 10px;
            }

            .forgot-header {
                padding: 30px 20px;
            }

            .forgot-body {
                padding: 30px 20px;
            }

            .features {
                grid-template-columns: 1fr;
            }
        }

        .spinner {
            display: none;
            width: 16px;
            height: 16px;
            border: 2px solid #ffffff;
            border-top: 2px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
     <?php include 'Header.php'; ?>

    <main class="forgot-container">
        <div class="forgot-card">
            <div class="forgot-header">
                <div class="forgot-icon">
                    <i class="fa-solid fa-lock"></i>
                </div>
                <h1>Forgot Password?</h1>
                <p>Enter your registered email address and we'll send you a link to reset your password.</p>
            </div>
            
            <div class="forgot-body">
                <?php if (isset($message)): ?>
                    <div class="success-message" style="display: block;">
                        <i class="fa-solid fa-<?php echo $success ? 'check-circle' : 'exclamation-circle'; ?>"></i> 
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <?php if (!isset($success) || !$success): ?>
                <form id="forgotPassForm" method="post" novalidate>
                    <input type="hidden" name="action" value="send_otp">
                    <div class="form-group">
                        <label class="form-label" for="email">Email Address</label>
                        <input type="email" class="form-input" id="email" name="email" placeholder="Enter your registered email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        <div class="error-message" id="emailError"></div>
                    </div>

                    <button type="submit" class="submit-btn" id="submitBtn">
                        <i class="fa-solid fa-paper-plane"></i>
                        <span>Send OTP</span>
                        <div class="spinner" id="spinner"></div>
                    </button>
                </form>
                <?php endif; ?>

                <div class="features">
                    <div class="feature-item">
                        <i class="fa-solid fa-shield-halved"></i>
                        <span>Secure password reset</span>
                    </div>
                    <div class="feature-item">
                        <i class="fa-solid fa-clock"></i>
                        <span>OTP expires in 60 seconds</span>
                    </div>
                    <div class="feature-item">
                        <i class="fa-solid fa-envelope"></i>
                        <span>Instant email delivery</span>
                    </div>
                    <div class="feature-item">
                        <i class="fa-solid fa-user-lock"></i>
                        <span>Account protection</span>
                    </div>
                </div>

                <div class="back-link">
                    <a href="login.php">
                        <i class="fa-solid fa-arrow-left"></i>
                        <span>Remember your password? Back to Login</span>
                    </a>
                </div>
            </div>
        </div>
    </main>

      <?php include "Footer.php"; ?>

    <script src="assets/js/index.js"></script>
    <script>
     document.addEventListener('DOMContentLoaded', function () {
        var forgotPassForm = document.getElementById('forgotPassForm');
        var emailInput = document.getElementById('email');
        var emailError = document.getElementById('emailError');
        var submitBtn = document.getElementById('submitBtn');
        var spinner = document.getElementById('spinner');

        if (!forgotPassForm) return; // Exit if form doesn't exist (after successful submission)

        function validateEmailField() {
            var value = emailInput.value.trim();
            var hasError = false;

            emailError.textContent = '';
            emailInput.classList.remove('error');

            if (!value) {
                emailError.textContent = 'Please enter your email address.';
                emailError.style.display = 'block';
                hasError = true;
            } else {
                var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailPattern.test(value)) {
                    emailError.textContent = 'Please enter a valid email address.';
                    emailError.style.display = 'block';
                    hasError = true;
                }
            }

            if (hasError) {
                emailInput.classList.add('error');
            }

            return !hasError;
        }

        function showLoading() {
            submitBtn.disabled = true;
            spinner.style.display = 'block';
            submitBtn.querySelector('span').textContent = 'Sending...';
        }

        function hideLoading() {
            submitBtn.disabled = false;
            spinner.style.display = 'none';
            submitBtn.querySelector('span').textContent = 'Send OTP';
        }

        function showMessage(message, isSuccess) {
            var existingMessage = document.querySelector('.success-message, .error-message:not(#emailError)');
            if (existingMessage) {
                existingMessage.remove();
            }
            
            var messageDiv = document.createElement('div');
            messageDiv.className = isSuccess ? 'success-message' : 'error-message';
            messageDiv.style.display = 'block';
            messageDiv.style.padding = '12px';
            messageDiv.style.borderRadius = '8px';
            messageDiv.style.marginBottom = '20px';
            messageDiv.style.fontSize = '14px';
            
            if (isSuccess) {
                messageDiv.style.background = '#ecfdf5';
                messageDiv.style.color = '#059669';
                messageDiv.style.border = '1px solid #bbf7d0';
                messageDiv.innerHTML = '<i class="fa-solid fa-check-circle"></i> ' + message;
            } else {
                messageDiv.style.background = '#fee2e2';
                messageDiv.style.color = '#991b1b';
                messageDiv.style.border = '1px solid #fecaca';
                messageDiv.innerHTML = '<i class="fa-solid fa-exclamation-circle"></i> ' + message;
            }
            
            forgotPassForm.parentNode.insertBefore(messageDiv, forgotPassForm);
        }

        forgotPassForm.addEventListener('submit', function (e) {
            e.preventDefault();

            var isEmailValid = validateEmailField();

            if (isEmailValid) {
                showLoading();
                
                // Create FormData
                var formData = new FormData(forgotPassForm);
                
                // Send AJAX request
                fetch('forgot_pass.php', {
                    method: 'POST',
                    body: formData
                })
                .then(function(response) {
                    if (!response.ok) {
                        throw new Error('HTTP error! status: ' + response.status);
                    }
                    return response.text().then(function(text) {
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('Invalid JSON response:', text);
                            throw new Error('Invalid JSON response from server');
                        }
                    });
                })
                .then(function(data) {
                    hideLoading();
                    
                    if (data.success) {
                        showMessage(data.message, true);
                        // Redirect to OTP verification page after 2 seconds
                        setTimeout(function() {
                            window.location.href = 'verify_otp.php';
                        }, 2000);
                    } else {
                        showMessage(data.message || 'Unknown error occurred', false);
                    }
                })
                .catch(function(error) {
                    hideLoading();
                    console.error('Fetch error:', error);
                    showMessage('Error: ' + error.message, false);
                });
            }
        });

        emailInput.addEventListener('input', function () {
            if (emailError.textContent) {
                validateEmailField();
                emailError.style.display = 'none';
            }
        });

        emailInput.addEventListener('focus', function () {
            this.select();
        });
     });
    </script>
</body>
</html>