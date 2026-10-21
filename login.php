<?php
// Start session
session_start();

// Include database configuration
require_once 'Database_file/config.php';
require_once 'PHPMailer/EmailVerification.php';

// Handle registration form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['form_type']) && $_POST['form_type'] == 'register') {
    // Get form data
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    $errors = [];
    
    if (empty($full_name)) {
        $errors[] = "Full name is required";
    } elseif (!preg_match('/^[A-Z][a-zA-Z\s\'-]*$/', $full_name)) {
        $errors[] = "Full name must start with capital letter";
    } elseif (strlen(trim($full_name)) < 3) {
        $errors[] = "Full name must be at least 3 characters";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    } elseif (strlen($password) > 10) {
        $errors[] = "Password must be maximum 10 characters";
    } elseif (!preg_match('/^[A-Z][a-zA-Z0-9!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]{5,9}$/', $password)) {
        $errors[] = "Password must start with capital letter and contain 6-10 characters";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    // Check if email already exists
    $sql = "SELECT id FROM users WHERE email = ?";
    $stmt = executeQuery($conn, $sql, [$email], "s");
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $errors[] = "Email already exists";
    }
    
    // If no errors, register user
    if (empty($errors)) {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user into database
        $sql = "INSERT INTO users (full_name, email, password) 
                VALUES (?, ?, ?)";
        
        $params = [$full_name, $email, $hashed_password];
        $types = "sss";
        
        $stmt = executeQuery($conn, $sql, $params, $types);
        
        if ($stmt) {
            $new_user_id = $stmt->insert_id;
            
            $_SESSION['success_message'] = "Registration successful! Please check your email to verify your account.";
            $_SESSION['show_welcome_coupon'] = true; // Show welcome popup on first login
            
            // Assign WELCOME10 coupon to new user
            $welcome_coupon_id = getCouponIdByCode($conn, 'WELCOME10');
            if ($welcome_coupon_id) {
                assignCouponToUser($conn, $new_user_id, $welcome_coupon_id);
            }
            
            // Send verification email
            $emailVerification = new EmailVerification($conn);
            $token = $emailVerification->generateVerificationToken($new_user_id, $email);
            
            if ($token) {
                $emailVerification->sendVerificationEmail($email, $full_name, $token);
            }
            
            header("Location: login.php");
            exit();
        } else {
            $_SESSION['errors'] = ["Registration failed. Please try again."];
            header("Location: login.php");
            exit();
        }
    } else {
        // Store errors in session
        $_SESSION['errors'] = $errors;
        header("Location: login.php");
        exit();
    }
}

// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['form_type']) && $_POST['form_type'] == 'login') {
    // Get form data
    $login_field = trim($_POST['login_field']);
    $password = $_POST['password'];
    
    // Validation
    $errors = [];
    
    if (empty($login_field)) {
        $errors[] = "Email or Full Name is required";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    }
    
    // If no validation errors, proceed with login
    if (empty($errors)) {
        
        // Check if user exists (by email or full_name)
        $sql = "SELECT * FROM users WHERE (email = ? OR full_name = ?) AND is_active = 1";
        $stmt = executeQuery($conn, $sql, [$login_field, $login_field], "ss");
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Check if email is verified
                if (!$user['email_verified']) {
                    $errors[] = "Please verify your email address before logging in. Check your inbox for verification email.";
                    $errors[] = "Didn't receive email? ";
                } else {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['user_type'] = $user['user_type'];
                    $_SESSION['logged_in'] = true;
                    
                    $_SESSION['success_message'] = "Login successful! Welcome back, " . $user['full_name'];
                    if($_SESSION['user_type'] == 'admin') {
                        header("Location: admin/home.php");
                    } else {
                        header("Location: index.php");
                    }
                    exit();
                }
            } else {
                $errors[] = "Invalid password";
            }
        } else {
            $errors[] = "Email/Full Name not found or account not active";
        }
    }
    
    // If there are errors, store in session and redirect back
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        header("Location: login.php");
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
    <link rel="stylesheet" href="floating_home.css">
    <title>Login / Register - E_Cart</title>
</head>

<body>

    <?php include 'Header.php'; ?>

    <main class="auth-main">
        <div class="auth-main-inner">
            <?php
            // Display success messages
            if (isset($_SESSION['success_message'])) {
                echo '<div style="background: #d4edda; color: #155724; padding: 15px; margin-bottom: 20px; border-radius: 8px; text-align: center; font-weight: 500; box-shadow: 0 4px 12px rgba(21, 128, 61, 0.1);">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
                unset($_SESSION['success_message']);
            }
            
            // Display error messages
            if (isset($_SESSION['errors'])) {
                echo '<div style="background: #f8d7da; color: #721c24; padding: 15px; margin-bottom: 20px; border-radius: 8px; text-align: center; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.1);">';
                foreach ($_SESSION['errors'] as $error) {
                    echo '<div style="margin: 5px 0;">• ' . htmlspecialchars($error) . '</div>';
                }
                echo '</div>';
                unset($_SESSION['errors']);
            }
            ?>
            <div class="container" id="container">
                <div class="form-container sign-up">
                    <form id="signupForm" method="POST" action="login.php" novalidate>
                        <input type="hidden" name="form_type" value="register">
                        <h1>Create Account</h1>
                        <div class="social-icons">
                            <a href="#" class="icon"><i class="fa-brands fa-google-plus-g"></i></a>
                            <a href="#" class="icon"><i class="fa-brands fa-facebook-f"></i></a>
                            <a href="#" class="icon"><i class="fa-brands fa-github"></i></a>
                            <a href="#" class="icon"><i class="fa-brands fa-linkedin-in"></i></a>
                        </div>
                        <span>Sign up with your email and password</span>
                        <input type="text" name="full_name" id="signupName" placeholder="Full Name" >
                        <input type="email" name="email" id="signupEmail" placeholder="Email" >
                        <div class="password-field">
                            <input type="password" name="password" placeholder="Password" id="signupPassword" minlength="6" maxlength="10">
                            <i class="fa-regular fa-eye password-toggle" data-target="signupPassword"></i>
                        </div>
                        <div class="password-field">
                            <input type="password" name="confirm_password" placeholder="Confirm Password" id="signupConfirmPassword" minlength="6" maxlength="10">
                            <i class="fa-regular fa-eye password-toggle" data-target="signupConfirmPassword"></i>
                        </div>
                        <button>Register</button>
                    </form>
                </div>
                <div class="form-container sign-in">
                    <form id="loginForm" method="POST" action="login.php" novalidate>
                        <input type="hidden" name="form_type" value="login">
                        <h1>Login</h1>

                        <div class="social-icons">
                            <a href="#" class="icon"><i class="fa-brands fa-google-plus-g"></i></a>
                            <a href="#" class="icon"><i class="fa-brands fa-facebook-f"></i></a>
                            <a href="#" class="icon"><i class="fa-brands fa-github"></i></a>
                            <a href="#" class="icon"><i class="fa-brands fa-linkedin-in"></i></a>
                        </div>
                        <span>or use your email and password</span>
                        <input type="text" name="login_field" id="loginEmail" placeholder="Email or Full Name" >
                        <div class="password-field">
                            <input type="password" name="password" placeholder="Password" id="loginPassword" >
                            <i class="fa-regular fa-eye password-toggle" data-target="loginPassword"></i>
                        </div>
                        <a href="forgot_pass.php">Forgot your password?</a>
                        <label style="display: flex; align-items: center; margin: 10px 0;">
                            <input type="checkbox" name="remember" style="margin-right: 5px;"> Remember me
                        </label>
                        <button>Login</button>

                    </form>
                </div>
                <div class="toggle-container">
                    <div class="toggle">
                        <div class="toggle-panel toggle-left">
                            <h1>Welcome Back!</h1>
                            <p>Login with your details to continue shopping on E_Cart</p>
                            <button class="hidden" id="login">Login</button>
                        </div>
                        <div class="toggle-panel toggle-right">
                            <h1>Create Account</h1>
                            <p>Join E_Cart for amazing deals and offers</p>
                            <button class="hidden" id="register">Register</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>


    <?php include "Footer.php"; ?>


    <script src="assets/js/index.js"></script>
    <script src="assets/js/login.js"></script>
</body>

</html>