<?php
session_start();

include '../../Database_file/config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

$userId = (int)$_SESSION['user_id'];

$userStmt = $conn->prepare("SELECT id, full_name, email, created_at FROM users WHERE id = ? LIMIT 1");
$user = null;
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

function get_user_data_file_path($userId) {
    return __DIR__ . '/user_data/' . $userId . '.json';
}

function read_user_data($userId) {
    $path = get_user_data_file_path($userId);
    if (!file_exists($path)) return [];
    $raw = @file_get_contents($path);
    if ($raw === false) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function write_user_data($userId, $data) {
    $dir = __DIR__ . '/user_data';
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    $path = get_user_data_file_path($userId);
    @file_put_contents($path, json_encode($data, JSON_UNESCAPED_SLASHES));
}

$userData = read_user_data($userId);

$mobile = (string)($userData['mobile'] ?? '');
$gender = (string)($userData['gender'] ?? '');
$dob = (string)($userData['dob'] ?? '');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullNameInput = isset($_POST['full_name']) ? trim((string)$_POST['full_name']) : '';
    $emailInput = isset($_POST['email']) ? trim((string)$_POST['email']) : '';
    $mobileInput = isset($_POST['mobile']) ? trim((string)$_POST['mobile']) : '';
    $genderInput = isset($_POST['gender']) ? trim((string)$_POST['gender']) : '';
    $dobInput = isset($_POST['dob']) ? trim((string)$_POST['dob']) : '';

    // Validate full name
    if (empty($fullNameInput)) {
        $error = 'Please enter your full name.';
    } elseif (strlen($fullNameInput) < 3) {
        $error = 'Full name should be at least 3 characters long.';
    }
    
    // Validate email
    if (empty($error)) {
        if (empty($emailInput)) {
            $error = 'Please enter your email address.';
        } elseif (!filter_var($emailInput, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        }
    }

    if (empty($error) && $genderInput !== '' && !in_array($genderInput, ['male', 'female', 'other'], true)) {
        $error = 'Please select a valid gender.';
    }

    if (empty($error)) {
        // Update users table (full_name and email)
        $updateUserStmt = $conn->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
        $updateUserStmt->bind_param('ssi', $fullNameInput, $emailInput, $userId);
        $updateUserStmt->execute();
        $updateUserStmt->close();
        
        // Update user_data.json (mobile, gender, dob)
        $userData['mobile'] = $mobileInput;
        $userData['gender'] = $genderInput;
        $userData['dob'] = $dobInput;
        write_user_data($userId, $userData);
        
        header('Location: profile.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Account Information - E_Cart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/index.css">
    <link rel="stylesheet" href="../../floating_home.css">

    <style>
        body {
            background-color: #f1f2f4;
        }

        .account-edit-main {
            padding: 26px 16px 40px;
        }

        .account-edit-wrapper {
            max-width: 900px;
            margin: 0 auto;
        }

        .account-edit-card {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.08);
            padding: 20px 20px 22px;
        }

        .account-edit-header h1 {
            font-size: 20px;
            font-weight: 600;
            color: #111827;
        }

        .account-edit-header p {
            margin-top: 6px;
            font-size: 13px;
            color: #6b7280;
        }

        .account-edit-form {
            margin-top: 18px;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px 28px;
        }

        .account-edit-field {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .account-edit-field label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .account-edit-field input,
        .account-edit-field select {
            padding: 9px 11px;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            font-size: 14px;
            outline: none;
        }

        .account-edit-field input:focus,
        .account-edit-field select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 1px rgba(37, 99, 235, 0.4);
        }

        .account-edit-meta {
            margin-top: 4px;
            font-size: 12px;
            color: #9ca3af;
        }

        .account-edit-error {
            display: block;
            font-size: 11px;
            color: #dc2626;
            margin-top: 2px;
        }

        .account-edit-error:empty {
            display: none;
        }

        .input-error {
            border-color: #dc2626 !important;
            box-shadow: 0 0 0 1px rgba(220, 38, 38, 0.25);
        }

        .account-edit-footer {
            margin-top: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            grid-column: 1 / -1;
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
            .account-edit-main {
                padding: 22px 12px 32px;
            }

            .account-edit-form {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {
            .account-edit-main {
                padding: 18px 10px 26px;
            }

            .account-edit-card {
                padding: 18px 14px 20px;
            }

            .account-edit-header h1 {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>

    <?php include '../../Header.php'; ?>


    <main class="account-edit-main">
        <div class="account-edit-wrapper">
            <section class="account-edit-card">
                <div class="account-edit-header">
                    <h1>Edit Account Information</h1>
                    <p>Update your contact details and personal information linked with your E_Cart account.</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div style="margin-top:12px; background:#fee2e2; color:#991b1b; padding:10px 12px; border-radius:8px; font-size:13px;">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form id="accountEditForm" class="account-edit-form" action="account_edit.php" method="post" novalidate>
                    <div class="account-edit-field">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>">
                        <span class="account-edit-error" id="full_name_error"></span>
                    </div>

                    <div class="account-edit-field">
                        <label for="mobile">Mobile Number</label>
                        <input type="tel" id="mobile" name="mobile" value="<?php echo htmlspecialchars($mobile); ?>" placeholder="e.g. +91 9876543210">
                        <span class="account-edit-error" id="mobile_error"></span>
                    </div>

                    <div class="account-edit-field">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">
                        <span class="account-edit-error" id="email_error"></span>
                    </div>
                        
                    <div class="account-edit-field">
                        <label for="member_since">Member Since</label>
                        <input type="text" id="member_since" name="member_since" value="<?php echo !empty($user['created_at']) ? date('M Y', strtotime($user['created_at'])) : '--'; ?>">
                        <div class="account-edit-meta">This is the month you joined E_Cart, e.g. "Jan 2024".</div>
                        <span class="account-edit-error" id="member_since_error"></span>
                    </div>

                    <div class="account-edit-field">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender">
                            <option value="" <?php echo $gender === '' ? 'selected' : ''; ?>>Not specified</option>
                            <option value="male" <?php echo $gender === 'male' ? 'selected' : ''; ?>>Male</option>
                            <option value="female" <?php echo $gender === 'female' ? 'selected' : ''; ?>>Female</option>
                            <option value="other" <?php echo $gender === 'other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                        <span class="account-edit-error" id="gender_error"></span>
                    </div>

                    <div class="account-edit-field">
                        <label for="dob">Date of Birth</label>
                        <input type="date" id="dob" name="dob" value="<?php echo htmlspecialchars($dob); ?>">
                        <span class="account-edit-error" id="dob_error"></span>
                    </div>

                    <div class="account-edit-footer">   
                        <a href="profile.php" class="btn-secondary">Cancel</a>
                        <button type="submit" class="btn-primary">Save Changes</button>
                    </div>
                </form>
            </section>
        </div>
    </main>

        <?php include "../../Footer.php"; ?>

    <script>
        (function () {
            const form = document.getElementById('accountEditForm');
            if (!form) return;

            const fullNameInput = document.getElementById('full_name');
            const mobileInput = document.getElementById('mobile');
            const emailInput = document.getElementById('email');
            const memberSinceInput = document.getElementById('member_since');
            const genderSelect = document.getElementById('gender');
            const dobInput = document.getElementById('dob');

            const fullNameError = document.getElementById('full_name_error');
            const mobileError = document.getElementById('mobile_error');
            const emailError = document.getElementById('email_error');
            const memberSinceError = document.getElementById('member_since_error');
            const genderError = document.getElementById('gender_error');
            const dobError = document.getElementById('dob_error');

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

            function validateAccountForm() {
                clearError(fullNameInput, fullNameError);
                clearError(mobileInput, mobileError);
                clearError(emailInput, emailError);
                clearError(memberSinceInput, memberSinceError);
                clearError(genderSelect, genderError);
                clearError(dobInput, dobError);

                let hasError = false;

                if (fullNameInput) {
                    const name = fullNameInput.value.trim();
                    if (!name) {
                        showError(fullNameInput, fullNameError, 'Please enter your full name.');
                        hasError = true;
                    } else if (name.length < 3) {
                        showError(fullNameInput, fullNameError, 'Full name should be at least 3 characters long.');
                        hasError = true;
                    }
                }

                if (mobileInput) {
                    const rawMobile = mobileInput.value.trim();
                    const digits = rawMobile.replace(/\D/g, '');
                    if (!rawMobile) {
                        showError(mobileInput, mobileError, 'Please enter your mobile number.');
                        hasError = true;
                    } else if (!digits || digits.length < 10) {
                        showError(mobileInput, mobileError, 'Mobile number should have at least 10 digits.');
                        hasError = true;
                    }
                }

                if (emailInput) {
                    const email = emailInput.value.trim();
                    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!email) {
                        showError(emailInput, emailError, 'Please enter your email address.');
                        hasError = true;
                    } else if (!emailPattern.test(email)) {
                        showError(emailInput, emailError, 'Please enter a valid email address.');
                        hasError = true;
                    }
                }

                if (memberSinceInput) {
                    const value = memberSinceInput.value.trim();
                    const pattern = /^[A-Za-z]{3}\s\d{4}$/; // e.g. Jan 2024
                    if (!value) {
                        showError(memberSinceInput, memberSinceError, 'Please enter the month and year you joined (e.g. Jan 2024).');
                        hasError = true;
                    } else if (!pattern.test(value)) {
                        showError(memberSinceInput, memberSinceError, 'Please use format like "Jan 2024".');
                        hasError = true;
                    }
                }

                if (genderSelect) {
                    const value = genderSelect.value;
                    if (!value) {
                        showError(genderSelect, genderError, 'Please select your gender or choose Not specified.');
                        hasError = true;
                    }
                }

                if (dobInput) {
                    const dobValue = dobInput.value;
                    if (!dobValue) {
                        showError(dobInput, dobError, 'Please select your date of birth.');
                        hasError = true;
                    } else {
                        const dobDate = new Date(dobValue);
                        if (isNaN(dobDate.getTime())) {
                            showError(dobInput, dobError, 'Please select a valid date of birth.');
                            hasError = true;
                        } else {
                            const today = new Date();
                            today.setHours(0, 0, 0, 0);
                            if (dobDate >= today) {
                                showError(dobInput, dobError, 'Date of birth cannot be today or in the future.');
                                hasError = true;
                            }
                        }
                    }
                }

                return !hasError;
            }

            form.addEventListener('submit', function (e) {
                e.preventDefault();

                const isValid = validateAccountForm();
                if (!isValid) {
                    return;
                }

                form.submit();
            });
        })();
    </script>

    <script src="../../assets/js/index.js"></script>
     <a href="../../index.php" class="floating-home" title="Go to Home">
        <i class="fas fa-home"></i>
    </a>
</body>
</html>