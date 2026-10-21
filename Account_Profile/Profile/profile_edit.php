<?php
session_start();

include '../../Database_file/config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

$userId = (int)$_SESSION['user_id'];

$userStmt = $conn->prepare("SELECT id, full_name, email FROM users WHERE id = ? LIMIT 1");
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
$currentAvatarFile = isset($userData['profile_image']) ? (string)$userData['profile_image'] : '';
$currentAvatarUrl = $currentAvatarFile !== '' ? '../../assets/img/' . $currentAvatarFile : '';
$initials = make_initials($user['full_name'] ?? '');

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tagline = isset($_POST['profile_tagline']) ? trim((string)$_POST['profile_tagline']) : '';
    $initialsInput = isset($_POST['profile_initials']) ? trim((string)$_POST['profile_initials']) : '';

    if ($initialsInput !== '' && preg_match('/^[A-Za-z]{1,3}$/', $initialsInput)) {
        $userData['profile_initials'] = strtoupper($initialsInput);
    }
    if ($tagline !== '') {
        $userData['profile_tagline'] = $tagline;
    }

    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
            $error = 'Image upload failed. Please try again.';
        } else {
            $tmp = $_FILES['profile_image']['tmp_name'];
            $origName = (string)($_FILES['profile_image']['name'] ?? '');
            $size = (int)($_FILES['profile_image']['size'] ?? 0);
            $mime = function_exists('mime_content_type') ? @mime_content_type($tmp) : '';

            if ($size > 2 * 1024 * 1024) {
                $error = 'Image size should not exceed 2MB.';
            } elseif ($mime && strpos($mime, 'image/') !== 0) {
                $error = 'Please upload a valid image file.';
            } else {
                $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
                    $ext = 'jpg';
                }

                $safeName = time() . '_' . $userId . '_avatar.' . $ext;
                $uploadDir = __DIR__ . '/../../assets/img/';
                if (!is_dir($uploadDir)) {
                    @mkdir($uploadDir, 0777, true);
                }
                $dest = $uploadDir . $safeName;

                if (@move_uploaded_file($tmp, $dest)) {
                    $userData['profile_image'] = $safeName;
                    $success = 'Profile updated successfully.';
                } else {
                    $error = 'Unable to save uploaded image.';
                }
            }
        }
    } else {
        $success = 'Profile updated successfully.';
    }

    if ($error === '') {
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
    <title>Edit Profile - E_Cart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/index.css">
    <link rel="stylesheet" href="../../floating_home.css">

    <style>
        body {
            background-color: #f1f2f4;
        }

        .profile-edit-main {
            padding: 26px 16px 40px;
        }

        .profile-edit-wrapper {
            max-width: 900px;
            margin: 0 auto;
        }

        .profile-edit-card {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.08);
            padding: 20px 20px 22px;
        }

        .profile-edit-header {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .profile-edit-avatar {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1d4ed8, #020617);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 28px;
            font-weight: 600;
            overflow: hidden;
        }

        .profile-edit-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .profile-edit-header-text h1 {
            font-size: 20px;
            font-weight: 600;
            color: #111827;
        }

        .profile-edit-header-text p {
            margin-top: 4px;
            font-size: 13px;
            color: #6b7280;
        }

        .profile-edit-form {
            margin-top: 20px;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px 28px;
        }

        .profile-edit-field {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .profile-edit-field label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .profile-edit-field input {
            padding: 9px 11px;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            font-size: 14px;
            outline: none;
        }

        .profile-edit-field input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 1px rgba(37, 99, 235, 0.4);
        }

        .profile-edit-field small {
            font-size: 11px;
            color: #9ca3af;
        }

        .profile-edit-error {
            font-size: 11px;
            color: #dc2626;
            margin-top: 2px;
        }

        .input-error {
            border-color: #dc2626 !important;
            box-shadow: 0 0 0 1px rgba(220, 38, 38, 0.25);
        }

        .profile-edit-footer {
            margin-top: 20px;
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
            .profile-edit-main {
                padding: 22px 12px 32px;
            }

            .profile-edit-form {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {
            .profile-edit-main {
                padding: 18px 10px 26px;
            }

            .profile-edit-card {
                padding: 18px 14px 20px;
            }

            .profile-edit-avatar {
                width: 64px;
                height: 64px;
                font-size: 24px;
            }

            .profile-edit-header-text h1 {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>

   <?php include '../../Header.php'; ?>


    <main class="profile-edit-main">
        <div class="profile-edit-wrapper">
            <section class="profile-edit-card">
                <div class="profile-edit-header">
                    <div class="profile-edit-avatar">
                        <?php if (!empty($currentAvatarUrl)): ?>
                            <img src="<?php echo htmlspecialchars($currentAvatarUrl); ?>" alt="Profile">
                        <?php else: ?>
                            <?php echo htmlspecialchars($initials); ?>
                        <?php endif; ?>
                    </div>
                    <div class="profile-edit-header-text">
                        <h1>Edit Profile</h1>
                        <p>Update your public profile details used across E_Cart.</p>
                    </div>
                </div>

                <?php if (!empty($error)): ?>
                    <div style="margin-top:12px; background:#fee2e2; color:#991b1b; padding:10px 12px; border-radius:8px; font-size:13px;">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form id="profileEditForm" class="profile-edit-form" action="profile_edit.php" method="post" enctype="multipart/form-data" novalidate>
                    <div class="profile-edit-field">
                        <label for="profile_initials">Profile Initials</label>
                        <input type="text" id="profile_initials" name="profile_initials" value="<?php echo htmlspecialchars((string)($userData['profile_initials'] ?? $initials)); ?>" maxlength="3">
                        <small>Used in your profile avatar when no photo is uploaded.</small>
                        <span class="profile-edit-error" id="profile_initials_error"></span>
                    </div>

                    <div class="profile-edit-field">
                        <label for="profile_tagline">Profile Tagline</label>
                        <input type="text" id="profile_tagline" name="profile_tagline" placeholder="e.g. Tech enthusiast, loves gadgets" value="<?php echo htmlspecialchars((string)($userData['profile_tagline'] ?? '')); ?>">
                        <small>Shown only in your account area.</small>
                        <span class="profile-edit-error" id="profile_tagline_error"></span>
                    </div>

                    <div class="profile-edit-field">
                        <label for="profile_image">Profile Image</label>
                        <input type="file" id="profile_image" name="profile_image" accept="image/*">
                        <small>Upload a clear square image for best results. JPG, PNG or WEBP recommended.</small>
                        <span class="profile-edit-error" id="profile_image_error"></span>
                    </div>

                    <div class="profile-edit-footer">
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
            const form = document.getElementById('profileEditForm');
            if (!form) return;

            const initialsInput = document.getElementById('profile_initials');
            const taglineInput = document.getElementById('profile_tagline');
            const imageInput = document.getElementById('profile_image');

            const initialsError = document.getElementById('profile_initials_error');
            const taglineError = document.getElementById('profile_tagline_error');
            const imageError = document.getElementById('profile_image_error');

            const MAX_AVATAR_IMAGE_MB = 2;
            const MAX_TAGLINE_LENGTH = 120;

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

            function isValidImageFile(file) {
                if (!file) return true;
                if (file.type && !file.type.startsWith('image/')) {
                    return false;
                }
                const maxBytes = MAX_AVATAR_IMAGE_MB * 1024 * 1024;
                if (file.size && file.size > maxBytes) {
                    return false;
                }
                return true;
            }

            function validateProfileForm() {
                clearError(initialsInput, initialsError);
                clearError(taglineInput, taglineError);
                clearError(imageInput, imageError);

                let hasError = false;

                if (initialsInput) {
                    const rawInitials = initialsInput.value.trim();
                    if (!rawInitials) {
                        showError(initialsInput, initialsError, 'Please enter your initials.');
                        hasError = true;
                    } else if (!/^[A-Za-z]{1,3}$/.test(rawInitials)) {
                        showError(initialsInput, initialsError, 'Initials should be 1 to 3 letters (A-Z).');
                        hasError = true;
                    } else {
                        initialsInput.value = rawInitials.toUpperCase();
                    }
                }

                if (taglineInput) {
                    const tagline = taglineInput.value.trim();
                    if (!tagline) {
                        showError(taglineInput, taglineError, 'Please enter your profile tagline.');
                        hasError = true;
                    } else if (tagline.length < 5) {
                        showError(taglineInput, taglineError, 'Tagline should be at least 5 characters long.');
                        hasError = true;
                    } else if (tagline.length > MAX_TAGLINE_LENGTH) {
                        showError(taglineInput, taglineError, 'Tagline should not be longer than ' + MAX_TAGLINE_LENGTH + ' characters.');
                        hasError = true;
                    }
                }

                if (imageInput) {
                    const file = imageInput.files && imageInput.files[0];
                    if (file) {
                        if (!file.type || !file.type.startsWith('image/')) {
                            showError(imageInput, imageError, 'Please upload a valid image file.');
                            hasError = true;
                        } else {
                            const maxBytes = MAX_AVATAR_IMAGE_MB * 1024 * 1024;
                            if (file.size && file.size > maxBytes) {
                                showError(imageInput, imageError, 'Image size should not exceed ' + MAX_AVATAR_IMAGE_MB + 'MB.');
                                hasError = true;
                            }
                        }
                    }
                }

                return !hasError;
            }

            form.addEventListener('submit', function (e) {
                e.preventDefault();

                const isValid = validateProfileForm();
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