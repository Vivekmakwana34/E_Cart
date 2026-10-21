<?php
session_start();

// Check if user came from forgot password page
if (!isset($_SESSION['reset_email']) || !isset($_SESSION['reset_token'])) {
    header('Location: forgot_pass.php');
    exit();
}

// Debug info (remove after testing)
$debug_info = '';
if (isset($_GET['debug'])) {
    $debug_info .= "Session Email: " . ($_SESSION['reset_email'] ?? 'NOT SET') . "<br>";
    $debug_info .= "Session User ID: " . ($_SESSION['reset_user_id'] ?? 'NOT SET') . "<br>";
    $debug_info .= "Session Token: " . ($_SESSION['reset_token'] ?? 'NOT SET') . "<br>";
}

$email = $_SESSION['reset_email'];
$success = false;
$error = '';

// Fetch actual OTP sent time from database
$otpSentTime = null;
$timeRemaining = 60; // Default 60 seconds

$conn = new mysqli('localhost', 'root', '', 'e_cart');
if (!$conn->connect_error && isset($_SESSION['reset_user_id'])) {
    $userId = $_SESSION['reset_user_id'];
    $timeStmt = $conn->prepare("SELECT last_otp_sent FROM forgot_pass WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $timeStmt->bind_param("i", $userId);
    $timeStmt->execute();
    $timeResult = $timeStmt->get_result();
    if ($timeResult->num_rows > 0) {
        $timeData = $timeResult->fetch_assoc();
        $otpSentTime = strtotime($timeData['last_otp_sent']);
        $currentTime = time();
        $elapsedTime = $currentTime - $otpSentTime;
        $timeRemaining = max(0, 60 - $elapsedTime); // Calculate remaining time (max 60 seconds)
    }
    $timeStmt->close();
}
if ($conn) {
    $conn->close();
}

// Handle OTP verification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = $_POST['otp'] ?? '';
        
    if (empty($otp) || strlen($otp) !== 6 || !ctype_digit($otp)) {
        $error = "Please enter a valid 6-digit OTP.";
    } else {
        // Verify OTP from forgot_pass table with 60 second expiry
        $conn = new mysqli('localhost', 'root', '', 'e_cart');
        
        if (!$conn->connect_error) {
            $userId = $_SESSION['reset_user_id'] ?? 0;
            
            // Check OTP from forgot_pass table and verify 60 second expiry
            $stmt = $conn->prepare("SELECT OTP, last_otp_sent FROM forgot_pass WHERE user_id = ? AND OTP = ? ORDER BY id DESC LIMIT 1");
            $stmt->bind_param("is", $userId, $otp);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $lastOtpSent = strtotime($row['last_otp_sent']);
                $currentTime = time();
                $timeDiff = $currentTime - $lastOtpSent;
                
                // Check if OTP is within 60 seconds
                if ($timeDiff <= 60) {
                    // Update attempt count
                    $updateStmt = $conn->prepare("UPDATE forgot_pass SET last_attempt = NOW(), attempt_count = attempt_count + 1 WHERE user_id = ? AND OTP = ?");
                    $updateStmt->bind_param("is", $userId, $otp);
                    $updateStmt->execute();
                    $updateStmt->close();
                    
                    // OTP verified successfully
                    $_SESSION['otp_verified'] = true;
                    $success = true;
                    
                    // Redirect to reset password page
                    header('Location: reset_password.php');
                    exit();
                } else {
                    $error = "OTP has expired. Please request a new OTP.";
                }
            } else {
                $error = "Invalid OTP. Please try again.";
                
                // Increment attempt count for failed OTP verification
                $incrementStmt = $conn->prepare("UPDATE forgot_pass SET attempt_count = attempt_count + 1, last_attempt = NOW() WHERE user_id = ? ORDER BY id DESC LIMIT 1");
                $incrementStmt->bind_param("i", $userId);
                $incrementStmt->execute();
                $incrementStmt->close();
                
                // Check if max attempts reached
                $attemptStmt = $conn->prepare("SELECT attempt_count FROM forgot_pass WHERE user_id = ? ORDER BY id DESC LIMIT 1");
                $attemptStmt->bind_param("i", $userId);
                $attemptStmt->execute();
                $attemptResult = $attemptStmt->get_result();
                if ($attemptResult->num_rows > 0) {
                    $attemptData = $attemptResult->fetch_assoc();
                    $remainingAttempts = 3 - $attemptData['attempt_count'];
                    if ($remainingAttempts > 0) {
                        $error .= " ({$remainingAttempts} attempt(s) remaining)";
                    } else {
                        $error = "Too many failed attempts. Please try again after 24 hours.";
                    }
                }
                $attemptStmt->close();
                
                // Debug: Check what OTP is stored in database
                if (isset($_GET['debug'])) {
                    $debugStmt = $conn->prepare("SELECT OTP, last_otp_sent FROM forgot_pass WHERE user_id = ? ORDER BY id DESC LIMIT 1");
                    $debugStmt->bind_param("i", $userId);
                    $debugStmt->execute();
                    $debugResult = $debugStmt->get_result();
                    if ($debugResult->num_rows > 0) {
                        $debugRow = $debugResult->fetch_assoc();
                        $debug_info .= "<br>DB OTP: " . $debugRow['OTP'] . "<br>";
                        $debug_info .= "DB Last Sent: " . $debugRow['last_otp_sent'] . "<br>";
                        $debug_info .= "Entered OTP: " . $otp . "<br>";
                    } else {
                        $debug_info .= "<br>No OTP record found in DB for user_id: " . $userId . "<br>";
                    }
                    $debugStmt->close();
                }
            }
            
            $stmt->close();
            $conn->close();
        } else {
            $error = "Database connection error. Please try again.";
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
    <title>Verify OTP - E_Cart</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f1f2f4;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        .verify-container {
            display: flex;
            min-height: calc(100vh - 250px);
            align-items: center;
            justify-content: center;
            padding: 15px;
        }

        .verify-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            overflow: hidden;
        }

        .verify-header {
            background: linear-gradient(135deg, #1d4ed8, #020617);
            color: white;
            padding: 30px 25px;
            text-align: center;
        }

        .verify-icon {
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

        .verify-header h1 {
            font-size: 24px;
            margin: 0 0 8px 0;
            font-weight: 600;
        }

        .verify-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 14px;
        }

        .verify-body {
            padding: 40px 30px;
        }

        .email-display {
            background: #f3f4f6;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            text-align: center;
            color: #374151;
            font-size: 14px;
        }

        .email-display strong {
            color: #1d4ed8;
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

        .otp-input {
            width: 100%;
            padding: 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 24px;
            text-align: center;
            letter-spacing: 8px;
            font-weight: 600;
            box-sizing: border-box;
            transition: all 0.3s ease;
        }

        .otp-input:focus {
            outline: none;
            border-color: #1d4ed8;
            box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.1);
        }

        .otp-input.error {
            border-color: #dc2626;
        }

        .error-message {
            color: #dc2626;
            font-size: 13px;
            margin-top: 8px;
            text-align: center;
        }

        .success-message {
            background: #ecfdf5;
            color: #059669;
            padding: 12px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
            border: 1px solid #bbf7d0;
            text-align: center;
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

        .resend-section {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }

        .resend-text {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .resend-link {
            color: #1d4ed8;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
        }

        .resend-link:hover {
            text-decoration: underline;
        }

        .timer {
            color: #6b7280;
            font-size: 13px;
            margin-top: 8px;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #6b7280;
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .back-link a:hover {
            color: #1d4ed8;
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
            .verify-card {
                margin: 0 10px;
            }

            .verify-header {
                padding: 30px 20px;
            }

            .verify-body {
                padding: 30px 20px;
            }

            .otp-input {
                font-size: 20px;
                letter-spacing: 6px;
            }

            .features {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'Header.php'; ?>

    <main class="verify-container">
        <div class="verify-card">
            <div class="verify-header">
                <div class="verify-icon">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h1>Verify OTP</h1>
                <p>Enter the 6-digit code sent to your email</p>
            </div>
            
            <div class="verify-body">
                <div class="email-display">
                    <i class="fa-solid fa-envelope"></i> Code sent to: <strong><?php echo htmlspecialchars($email); ?></strong>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="error-message" style="display: block;">
                        <i class="fa-solid fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($debug_info)): ?>
                    <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 12px; margin-bottom: 20px; border-radius: 8px; font-size: 13px; color: #856404;">
                        <strong>Debug Info:</strong><br>
                        <?php echo $debug_info; ?>
                    </div>
                <?php endif; ?>

                <form id="verifyOtpForm" method="post" novalidate>
                    <div class="form-group">
                        <label class="form-label" for="otp">Enter 6-digit OTP</label>
                        <input type="text" class="otp-input" id="otp" name="otp" maxlength="6" placeholder="000000" autocomplete="off">
                    </div>

                    <button type="submit" class="submit-btn" id="submitBtn">
                        <i class="fa-solid fa-check-circle"></i>
                        <span>Verify OTP</span>
                    </button>
                </form>

                <div class="resend-section">
                    <div class="resend-text">Didn't receive the code?</div>
                    <a href="#" class="resend-link" id="resendLink">Resend OTP</a>
                    <div class="timer" id="otpTimer">OTP expires in 60 seconds</div>
                    <div id="resendMessage" style="margin-top: 8px; font-size: 13px;"></div>
                </div>

                <div class="features">
                    <div class="feature-item">
                        <i class="fa-solid fa-shield-halved"></i>
                        <span>Secure verification</span>
                    </div>
                    <div class="feature-item">
                        <i class="fa-solid fa-clock"></i>
                        <span>Valid for 60 seconds</span>
                    </div>
                    <div class="feature-item">
                        <i class="fa-solid fa-envelope"></i>
                        <span>Check spam folder</span>
                    </div>
                    <div class="feature-item">
                        <i class="fa-solid fa-lock"></i>
                        <span>One-time code</span>
                    </div>
                </div>

                <div class="back-link">
                    <a href="forgot_pass.php">
                        <i class="fa-solid fa-arrow-left"></i>
                        <span>Back to Forgot Password</span>
                    </a>
                </div>
            </div>
        </div>
    </main>

    <?php include "Footer.php"; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var otpInput = document.getElementById('otp');
            var submitBtn = document.getElementById('submitBtn');
            var form = document.getElementById('verifyOtpForm');
            var resendLink = document.getElementById('resendLink');
            var otpTimer = document.getElementById('otpTimer');
            var resendMessage = document.getElementById('resendMessage');
            
            // Get actual remaining time from PHP (database calculated)
            var timeLeft = <?php echo $timeRemaining; ?>;
            var timerInterval;

            // Auto-focus OTP input
            if (otpInput) {
                otpInput.focus();
            }
            
            // Countdown timer function
            function startTimer() {
                // Don't reset timeLeft - use the value from PHP (actual remaining time)
                // timeLeft is already set from PHP calculation
                
                if (timeLeft <= 0) {
                    otpTimer.textContent = 'OTP has expired';
                    otpTimer.style.color = '#dc2626';
                    resendLink.style.pointerEvents = 'auto';
                    resendLink.style.opacity = '1';
                    otpInput.disabled = true;
                    submitBtn.disabled = true;
                    return;
                }
                
                resendLink.style.pointerEvents = 'none';
                resendLink.style.opacity = '0.5';
                
                timerInterval = setInterval(function() {
                    timeLeft--;
                    otpTimer.textContent = 'OTP expires in ' + timeLeft + ' seconds';
                    
                    if (timeLeft <= 0) {
                        clearInterval(timerInterval);
                        otpTimer.textContent = 'OTP has expired';
                        otpTimer.style.color = '#dc2626';
                        resendLink.style.pointerEvents = 'auto';
                        resendLink.style.opacity = '1';
                        otpInput.disabled = true;
                        submitBtn.disabled = true;
                    }
                }, 1000);
            }
            
            // Start timer on page load
            startTimer();
            
            // Resend OTP via AJAX
            resendLink.addEventListener('click', function(e) {
                e.preventDefault();
                
                resendLink.style.pointerEvents = 'none';
                resendLink.style.opacity = '0.5';
                resendMessage.textContent = 'Resending OTP...';
                resendMessage.style.color = '#1d4ed8';
                
                // Create form data
                var formData = new FormData();
                formData.append('email', '<?php echo htmlspecialchars($email); ?>');
                formData.append('action', 'resend_otp');
                
                // Send AJAX request to forgot_pass.php
                fetch('forgot_pass.php', {
                    method: 'POST',
                    body: formData
                })
                .then(function(response) {
                    return response.json();
                })
                .then(function(data) {
                    if (data.success) {
                        resendMessage.textContent = 'New OTP sent successfully!';
                        resendMessage.style.color = '#059669';
                        
                        // Clear existing timer and restart
                        clearInterval(timerInterval);
                        otpInput.disabled = false;
                        submitBtn.disabled = false;
                        otpInput.value = '';
                        otpInput.focus();
                        otpTimer.style.color = '#6b7280';
                        startTimer();
                        
                        // Clear message after 3 seconds
                        setTimeout(function() {
                            resendMessage.textContent = '';
                        }, 3000);
                    } else {
                        resendMessage.textContent = data.message;
                        resendMessage.style.color = '#dc2626';
                        resendLink.style.pointerEvents = 'auto';
                        resendLink.style.opacity = '1';
                    }
                })
                .catch(function(error) {
                    resendMessage.textContent = 'Failed to resend OTP. Please try again.';
                    resendMessage.style.color = '#dc2626';
                    resendLink.style.pointerEvents = 'auto';
                    resendLink.style.opacity = '1';
                });
            });

            // Allow only numbers
            otpInput.addEventListener('input', function (e) {
                this.value = this.value.replace(/[^0-9]/g, '');
                
                if (this.value.length === 6) {
                    submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> <span>Verifying...</span>';
                    form.submit();
                }
            });

            // Handle paste event
            otpInput.addEventListener('paste', function (e) {
                e.preventDefault();
                var pastedData = e.clipboardData.getData('text');
                var numericData = pastedData.replace(/[^0-9]/g, '').substring(0, 6);
                this.value = numericData;
                
                if (numericData.length === 6) {
                    setTimeout(function() {
                        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> <span>Verifying...</span>';
                        form.submit();
                    }, 100);
                }
            });
        });
    </script>
</body>     
</html>
