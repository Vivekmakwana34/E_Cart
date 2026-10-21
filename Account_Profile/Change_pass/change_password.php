<?php
session_start();

include '../../Database_file/config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

$userId = (int)$_SESSION['user_id'];
$user = null;

$userStmt = $conn->prepare("SELECT id, full_name, email, created_at FROM users WHERE id = ? LIMIT 1");
if ($userStmt) {
    $userStmt->bind_param('i', $userId);
    $userStmt->execute();
    $userRes = $userStmt->get_result();
    $user = $userRes ? $userRes->fetch_assoc() : null;
}

if (!$user) {
    header('Location: ../../logout.php');
    exit();
}

function make_initials($name) {
    $name = trim((string)$name);
    if ($name === '') return 'U';
    $parts = preg_split('/\s+/', $name);
    $first = strtoupper(substr($parts[0] ?? 'U', 0, 1));
    $second = '';
    if (count($parts) > 1) {
        $second = strtoupper(substr($parts[1] ?? '', 0, 1));
    } elseif (strlen($name) > 1) {
        $second = strtoupper(substr($name, 1, 1));
    }
    $initials = $first . $second;
    return substr($initials, 0, 2);
}

function get_user_data_file_path_change_pass($userId) {
    return __DIR__ . '/../Profile/user_data/' . $userId . '.json';
}

function read_user_data_change_pass($userId) {
    $path = get_user_data_file_path_change_pass($userId);
    if (!file_exists($path)) return [];
    $raw = @file_get_contents($path);
    if ($raw === false) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

$userData = read_user_data_change_pass($userId);
$profileInitials = (string)($userData['profile_initials'] ?? '');
if ($profileInitials !== '' && preg_match('/^[A-Za-z]{1,3}$/', $profileInitials)) {
    $initials = strtoupper($profileInitials);
} else {
    $initials = make_initials($user['full_name'] ?? '');
}

$profileTagline = trim((string)($userData['profile_tagline'] ?? ''));

$avatarFile = (string)($userData['profile_image'] ?? '');
$avatarUrl = $avatarFile !== '' ? '../../assets/img/' . $avatarFile : '';

$message = '';
$error = '';

// Handle password change
if (isset($_POST['current_password']) && isset($_POST['new_password']) && isset($_POST['confirm_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Validation
    $hasError = false;
    
    if (empty($currentPassword)) {
        $error = 'Please enter your current password.';
        $hasError = true;
    } elseif (empty($newPassword)) {
        $error = 'Please enter a new password.';
        $hasError = true;
    } elseif (strlen($newPassword) < 8) {
        $error = 'New password should be at least 8 characters.';
        $hasError = true;
    } elseif (!preg_match('/[A-Za-z]/', $newPassword) || !preg_match('/\d/', $newPassword)) {
        $error = 'New password should include letters and numbers.';
        $hasError = true;
    } elseif ($currentPassword === $newPassword) {
        $error = 'New password must be different from current password.';
        $hasError = true;
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Confirm password does not match the new password.';
        $hasError = true;
    }
    
    if (!$hasError) {
        // Fetch current password from database
        $passStmt = $conn->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
        $passStmt->bind_param('i', $userId);
        $passStmt->execute();
        $passResult = $passStmt->get_result();
        $passData = $passResult->fetch_assoc();
        
        if (!$passData) {
            $error = 'User not found.';
        } elseif (!password_verify($currentPassword, $passData['password'])) {
            $error = 'Current password is incorrect.';
        } else {
            // Hash and update new password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $updateStmt->bind_param('si', $hashedPassword, $userId);
            
            if ($updateStmt->execute()) {
                if ($updateStmt->affected_rows > 0) {
                    $message = 'Password updated successfully!';
                } else {
                    $error = 'Password update failed - no rows affected.';
                }
            } else {
                $error = 'Failed to update password: ' . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - E_Cart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/index.css">
    <link rel="stylesheet" href="../../floating_home.css">

    <style>
        body {
            background-color: #f1f2f4;
        }

        .profile-main {
            padding: 26px 16px 40px;
        }

        .profile-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: flex-start;
            gap: 24px;
        }

        .profile-sidebar {
            flex: 0 0 280px;
        }

        .profile-card {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.08);
            padding: 20px 20px 18px;
            margin-bottom: 16px;
        }

        .profile-header-label {
            font-size: 13px;
            color: #717478;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .profile-header-label.profile-initials-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .profile-initials-value {
            padding: 2px 8px;
            border-radius: 999px;
            background: #eff6ff;
            border: 1px solid #dbeafe;
            color: #1d4ed8;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.08em;
            flex: 0 0 auto;
        }

        .profile-user-row {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .profile-avatar {
            width: 84px;
            height: 84px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1d4ed8, #020617);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 32px;
            font-weight: 600;
            overflow: hidden;
            flex-shrink: 0;
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .profile-name {
            font-size: 20px;
            font-weight: 900;
            color: #0b1220;
            letter-spacing: -0.02em;
            margin: 0;
            line-height: 1.1;
            display: inline-block;
            position: relative;
        }

        .profile-name::after {
            content: '';
            display: block;
            width: 36px;
            height: 3px;
            border-radius: 999px;
            background: #2874f0;
            margin-top: 6px;
        }

        .profile-username-label {
            font-size: 10px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 4px;
        }

        .profile-email {
            margin-top: 2px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 999px;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            color: #334155;
            font-size: 14px;
            max-width: 100%;
        }

        .profile-email i {
            color: #2874f0;
            font-size: 12px;
            flex: 0 0 auto;
        }

        .profile-email span {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            display: inline-block;
            max-width: 210px;
        }

        .profile-bio {
            margin-top: 10px;
            padding: 6px 8px;
            border-radius: 10px;
            background: #f3f6ff;
            border: 1px solid #e2e9ff;
            border-left: 3px solid #2874f0;
            color: #1f2937;
            font-size: 11px;
            line-height: 1.35;
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }

        .profile-bio span {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .profile-edit-btn {
            margin-top: 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: 6px;
            border: 1px solid #dadada;
            background-color: #ffffff;
            color: #2874f0;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }

        .profile-edit-btn i {
            font-size: 13px;
        }

        .profile-menu {
            margin-top: 12px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .profile-menu-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 9px 10px;
            border-radius: 8px;
            cursor: pointer;
            color: #212121;
            font-size: 15px;
            text-decoration: none;
            transition: background-color 0.15s ease, color 0.15s ease;
        }

        .profile-menu-item-left {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .profile-menu-item i {
            color: #2874f0;
            font-size: 15px;
        }

        .profile-menu-item span.profile-menu-label {
            font-weight: 500;
        }

        .profile-menu-item span.profile-menu-meta {
            font-size: 16px;
            color: #717478;
        }

        .profile-menu-item.profile-menu-active,
        .profile-menu-item:hover {
            background-color: #f0f5ff;
            color: #212121;
        }

        .profile-menu-item.profile-menu-danger i,
        .profile-menu-item.profile-menu-danger span.profile-menu-label {
            color: #dc2626;
        }

        .profile-content {
            flex: 1;
            min-width: 0;
        }

        .change-pass-main {
            padding: 26px 16px 40px;
        }

        .change-pass-wrapper {
            max-width: 600px;
            margin: 0 auto;
        }

        .change-pass-card {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.08);
            padding: 20px 20px 22px;
        }

        .change-pass-header h1 {
            font-size: 20px;
            font-weight: 600;
            color: #111827;
        }

        .change-pass-header p {
            margin-top: 6px;
            font-size: 13px;
            color: #6b7280;
        }

        .change-pass-form {
            margin-top: 18px;
            display: grid;
            grid-template-columns: 1fr;
            gap: 14px 0;
        }

        .change-pass-field {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .change-pass-field label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .change-pass-field input[type="password"] {
            padding: 9px 11px;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            font-size: 14px;
            outline: none;
        }

        .change-pass-field input[type="password"]:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 1px rgba(37, 99, 235, 0.4);
        }

        .change-pass-meta {
            margin-top: 2px;
            font-size: 12px;
            color: #9ca3af;
        }

        .change-pass-error {
            display: block;
            font-size: 11px;
            color: #dc2626;
            margin-top: 2px;
        }

        .change-pass-error:empty {
            display: none;
        }

        .input-error {
            border-color: #dc2626 !important;
            box-shadow: 0 0 0 1px rgba(220, 38, 38, 0.25);
        }

        .change-pass-footer {
            margin-top: 18px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn-secondary,
        .btn-primary {
            border-radius: 6px;
            padding: 9px 18px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid transparent;
        }

        .btn-secondary {
            background-color: #ffffff;
            border-color: #d1d5db;
            color: #374151;
            text-decoration: none;
        }

        .btn-primary {
            background-color: #fb641b;
            border-color: #fb641b;
            color: #ffffff;
        }

        .btn-secondary:hover {
            background-color: #f3f4f6;
        }

        .btn-primary:hover {
            background-color: #e45a18;
            border-color: #e45a18;
        }

        @media (max-width: 900px) {
            .profile-main {
                padding: 22px 12px 32px;
            }

            .profile-wrapper {
                flex-direction: column;
            }

            .profile-sidebar {
                flex: 1;
                width: 100%;
            }

            .profile-card {
                padding: 18px 16px 16px;
            }
        }

        @media (max-width: 600px) {
            .profile-main {
                padding: 18px 10px 26px;
            }

            .change-pass-card {
                padding: 18px 14px 20px;
            }

            .change-pass-header h1 {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
 <?php include '../../Header.php'; ?>


    <main class="profile-main">
        <div class="profile-wrapper">
            <aside class="profile-sidebar">
                <div class="profile-card">
                    <div class="profile-header-label profile-initials-label">
                        <span class="profile-initials-value"><?php echo htmlspecialchars($initials); ?></span>
                    </div>
                    <div class="profile-user-row">
                        <div class="profile-avatar">
                            <?php if (!empty($avatarUrl)): ?>
                                <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="Profile">
                            <?php else: ?>
                                <?php echo htmlspecialchars($initials); ?>
                            <?php endif; ?>
                        </div>
                        <div class="profile-user-text">
                            <div class="profile-username-label">Username</div>
                            <div class="profile-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                            <div class="profile-email">
                                <i class="fa-regular fa-envelope"></i>
                                <span><?php echo htmlspecialchars($user['email']); ?></span>
                            </div>
                            <?php if (!empty($profileTagline)): ?>
                                <div class="profile-bio">
                                    <span><?php echo htmlspecialchars($profileTagline); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="profile-card">
                    <div class="profile-header-label">Account Menu</div>
                    <div class="profile-menu">
                        <a href="../Profile/profile.php" class="profile-menu-item">
                            <div class="profile-menu-item-left">
                                <i class="fa-regular fa-user"></i>
                                <div>
                                    <span class="profile-menu-label">Overview</span><br>
                                    <span class="profile-menu-meta">Profile, contact info</span>
                                </div>
                            </div>
                            <i class="fa-solid fa-angle-right"></i>
                        </a>

                        <a href="../Order/order.php" class="profile-menu-item">
                            <div class="profile-menu-item-left">
                                <i class="fa-solid fa-bag-shopping"></i>
                                <div>
                                    <span class="profile-menu-label">Orders</span><br>
                                    <span class="profile-menu-meta">Track, return or buy again</span>
                                </div>
                            </div>
                            <i class="fa-solid fa-angle-right"></i>
                        </a>

                        <a href="../Wishlist/wishlist.php" class="profile-menu-item">
                            <div class="profile-menu-item-left">
                                <i class="fa-regular fa-heart"></i>
                                <div>
                                    <span class="profile-menu-label">Wishlist</span><br>
                                    <span class="profile-menu-meta">Saved items</span>
                                </div>
                            </div>
                            <i class="fa-solid fa-angle-right"></i>
                        </a>

                          <a href="../Coupons/coupon.php" class="profile-menu-item">
                            <div class="profile-menu-item-left">
                                <i class="fa-solid fa-tags"></i>
                                <div>
                                    <span class="profile-menu-label">Coupons</span><br>
                                    <span class="profile-menu-meta">Available offers</span>
                                </div>
                            </div>
                            <i class="fa-solid fa-angle-right"></i>
                        </a>

                          <a href="../Gift_Cards/gift.php" class="profile-menu-item">
                            <div class="profile-menu-item-left">
                                <i class="fa-solid fa-gift"></i>
                                <div>
                                    <span class="profile-menu-label">Gift Cards</span><br>
                                    <span class="profile-menu-meta">Buy & manage cards</span>
                                </div>
                            </div>
                            <i class="fa-solid fa-angle-right"></i>
                        </a>
                        
                        <a href="../Address/Address.php" class="profile-menu-item">
                            <div class="profile-menu-item-left">
                                <i class="fa-solid fa-location-dot"></i>
                                <div>
                                    <span class="profile-menu-label">Addresses</span><br>
                                    <span class="profile-menu-meta">Manage delivery locations</span>
                                </div>
                            </div>
                            <i class="fa-solid fa-angle-right"></i>
                        </a>

                        <a href="change_password.php" class="profile-menu-item profile-menu-active">
                            <div class="profile-menu-item-left">
                                <i class="fa-solid fa-lock"></i>
                                <div>
                                    <span class="profile-menu-label">Change Password</span><br>
                                    <span class="profile-menu-meta">Update your login password</span>
                                </div>
                            </div>
                            <i class="fa-solid fa-angle-right"></i>
                        </a>

                        <a href="../Payments/payments.php" class="profile-menu-item">
                            <div class="profile-menu-item-left">
                                <i class="fa-solid fa-wallet"></i>
                                <div>
                                    <span class="profile-menu-label">Payments</span><br>
                                    <span class="profile-menu-meta">Cards, UPI & wallets</span>
                                </div>
                            </div>
                            <i class="fa-solid fa-angle-right"></i>
                        </a>

                        <a href="#" class="profile-menu-item profile-menu-danger">
                            <div class="profile-menu-item-left">
                                <i class="fa-solid fa-right-from-bracket"></i>
                                <span class="profile-menu-label">Logout</span>
                            </div>
                        </a>
                    </div>
                </div>
            </aside>

            <section class="profile-content">
                <section class="change-pass-card">
                    <div class="change-pass-header">
                        <h1>Change Password</h1>
                        <p>Update the password you use to sign in to your E_Cart account.</p>
                    </div>
                    
                    <?php if ($message): ?>
                        <div style="background: #e3fcef; color: #0e9f6e; padding: 12px 16px; border-radius: 6px; margin-bottom: 16px;">
                            <i class="fa-solid fa-check-circle" style="margin-right: 8px;"></i><?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div style="background: #fdecea; color: #e02424; padding: 12px 16px; border-radius: 6px; margin-bottom: 16px;">
                            <i class="fa-solid fa-exclamation-circle" style="margin-right: 8px;"></i><?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form id="changePasswordForm" class="change-pass-form" action="#" method="post" novalidate>
                        <div class="change-pass-field">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" placeholder="Enter your current password">
                            <span class="change-pass-error" id="current_password_error"></span>
                        </div>

                        <div class="change-pass-field">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" placeholder="Enter a new password">
                            <div class="change-pass-meta">Use at least 8 characters, including letters and numbers.</div>
                            <span class="change-pass-error" id="new_password_error"></span>
                        </div>

                        <div class="change-pass-field">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter the new password">
                            <span class="change-pass-error" id="confirm_password_error"></span>
                        </div>

                        <div class="change-pass-footer">
                            <a href="../Profile/profile.php" class="btn-secondary">Cancel</a>
                            <button type="submit" class="btn-primary">Update Password</button>
                        </div>
                    </form>
                </section>
            </section>
        </div>
    </main>

        <?php include "../../Footer.php"; ?>
        
    <script>
        (function () {
            const form = document.getElementById('changePasswordForm');
            if (!form) return;

            const currentPasswordInput = document.getElementById('current_password');
            const newPasswordInput = document.getElementById('new_password');
            const confirmPasswordInput = document.getElementById('confirm_password');

            const currentPasswordError = document.getElementById('current_password_error');
            const newPasswordError = document.getElementById('new_password_error');
            const confirmPasswordError = document.getElementById('confirm_password_error');

            function clearError(input, errorElement) {
                if (input) {
                    input.classList.remove('input-error');
                }
                if (errorElement) {
                    errorElement.textContent = '';
                }
            }

            function showError(input, errorElement, message) {
                if (input) {
                    input.classList.add('input-error');
                }
                if (errorElement) {
                    errorElement.textContent = message || '';
                }
            }

            function validateChangePasswordForm() {
                clearError(currentPasswordInput, currentPasswordError);
                clearError(newPasswordInput, newPasswordError);
                clearError(confirmPasswordInput, confirmPasswordError);

                let hasError = false;

                if (currentPasswordInput) {
                    const value = currentPasswordInput.value.trim();
                    if (!value) {
                        showError(currentPasswordInput, currentPasswordError, 'Please enter your current password.');
                        hasError = true;
                    } else if (value.length < 6) {
                        showError(currentPasswordInput, currentPasswordError, 'Current password should be at least 6 characters.');
                        hasError = true;
                    }
                }

                let newValue = '';
                if (newPasswordInput) {
                    newValue = newPasswordInput.value.trim();
                    if (!newValue) {
                        showError(newPasswordInput, newPasswordError, 'Please enter a new password.');
                        hasError = true;
                    } else if (newValue.length < 8) {
                        showError(newPasswordInput, newPasswordError, 'New password should be at least 8 characters.');
                        hasError = true;
                    } else {
                        const hasLetter = /[A-Za-z]/.test(newValue);
                        const hasNumber = /\d/.test(newValue);
                        if (!hasLetter || !hasNumber) {
                            showError(newPasswordInput, newPasswordError, 'New password should include letters and numbers.');
                            hasError = true;
                        }
                    }
                }

                if (currentPasswordInput && newPasswordInput) {
                    const currentValue = currentPasswordInput.value.trim();
                    if (currentValue && newValue && currentValue === newValue) {
                        showError(newPasswordInput, newPasswordError, 'New password must be different from current password.');
                        hasError = true;
                    }
                }

                if (confirmPasswordInput) {
                    const confirmValue = confirmPasswordInput.value.trim();
                    if (!confirmValue) {
                        showError(confirmPasswordInput, confirmPasswordError, 'Please confirm your new password.');
                        hasError = true;
                    } else if (newValue && confirmValue !== newValue) {
                        showError(confirmPasswordInput, confirmPasswordError, 'Confirm password does not match the new password.');
                        hasError = true;
                    }
                }

                return !hasError;
            }

            form.addEventListener('submit', function (e) {
                e.preventDefault();

                const isValid = validateChangePasswordForm();
                if (!isValid) {
                    return;
                }

                // Submit form for PHP processing
                form.submit();
            });

            [currentPasswordInput, newPasswordInput, confirmPasswordInput].forEach(function (input) {
                if (!input) return;
                input.addEventListener('input', function () {
                    if (input === currentPasswordInput) {
                        clearError(currentPasswordInput, currentPasswordError);
                    } else if (input === newPasswordInput) {
                        clearError(newPasswordInput, newPasswordError);
                    } else if (input === confirmPasswordInput) {
                        clearError(confirmPasswordInput, confirmPasswordError);
                    }
                });
            });
        })();
    </script>

    <script src="../../assets/js/index.js"></script>
     <a href="../../index.php" class="floating-home" title="Go to Home">
        <i class="fas fa-home"></i>
    </a>
</body>
</html>
