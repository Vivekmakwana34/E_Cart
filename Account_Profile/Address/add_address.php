<?php
session_start();

include '../../Database_file/config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

$userId = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim((string)($_POST['full_name'] ?? ''));
    $mobile = trim((string)($_POST['mobile'] ?? ''));
    $postal = trim((string)($_POST['pincode'] ?? ''));
    $city = trim((string)($_POST['city'] ?? ''));
    $line1 = trim((string)($_POST['address_line1'] ?? ''));
    $line2 = trim((string)($_POST['address_line2'] ?? ''));
    $state = trim((string)($_POST['state'] ?? ''));
    $addressType = trim((string)($_POST['address_type'] ?? 'home'));
    $isDefault = isset($_POST['is_default']) ? 1 : 0;

    if (!in_array($addressType, ['home', 'work', 'other'], true)) {
        $addressType = 'home';
    }

    if ($isDefault === 1) {
        $clearDefaultStmt = $conn->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?");
        if ($clearDefaultStmt) {
            $clearDefaultStmt->bind_param('i', $userId);
            $clearDefaultStmt->execute();
        }
    }

    $insertStmt = $conn->prepare("INSERT INTO user_addresses (user_id, address_type, address_line1, address_line2, city, state, postal_code, country, phone, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, 'India', ?, ?)");
    if ($insertStmt) {
        $insertStmt->bind_param('isssssssi', $userId, $addressType, $line1, $line2, $city, $state, $postal, $mobile, $isDefault);
        $insertStmt->execute();
    }

    header('Location: Address.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Address - E_Cart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/index.css">
    <link rel="stylesheet" href="../../floating_home.css">


    <style>
        body {
            background-color: #f1f2f4;
        }

        .address-main {
            padding: 26px 16px 40px;
        }

        .address-wrapper {
            max-width: 900px;
            margin: 0 auto;
        }

        .address-card {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.08);
            padding: 20px 20px 22px;
        }

        .address-header h1 {
            font-size: 20px;
            font-weight: 600;
            color: #111827;
        }

        .address-header p {
            margin-top: 6px;
            font-size: 13px;
            color: #6b7280;
        }

        .address-form {
            margin-top: 18px;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px 28px;
        }

        .address-field {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .address-field-full {
            grid-column: 1 / -1;
        }

        .address-field label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .address-field input,
        .address-field select {
            padding: 9px 11px;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            font-size: 14px;
            outline: none;
        }

        .address-field input:focus,
        .address-field select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 1px rgba(37, 99, 235, 0.4);
        }

        .address-meta {
            margin-top: 4px;
            font-size: 12px;
            color: #9ca3af;
        }

        .address-type-group {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
        }

        .address-type-group label {
            font-size: 13px;
            text-transform: none;
            letter-spacing: 0;
            color: #374151;
        }

        .address-default-toggle {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #374151;
        }

        .address-default-toggle input[type="checkbox"] {
            width: 16px;
            height: 16px;
        }

        .address-footer {
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

        .address-error {
            display: block;
            font-size: 11px;
            color: #dc2626;
            margin-top: 2px;
        }

        .address-error:empty {
            display: none;
        }

        .input-error {
            border-color: #dc2626 !important;
            box-shadow: 0 0 0 1px rgba(220, 38, 38, 0.25);
        }

        @media (max-width: 900px) {
            .address-main {
                padding: 22px 12px 32px;
            }

            .address-form {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {
            .address-main {
                padding: 18px 10px 26px;
            }

            .address-card {
                padding: 18px 14px 20px;
            }

            .address-header h1 {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>

    <?php include '../../Header.php'; ?>


    <main class="address-main">
        <div class="address-wrapper">
            <section class="address-card">
                <div class="address-header">
                    <h1>Add New Address</h1>
                    <p>Save a new delivery address to use during checkout.</p>
                </div>

                <form id="addAddressForm" class="address-form" action="" method="post" novalidate>
                    <div class="address-field">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" value="">
                        <span class="address-error" id="full_name_error"></span>
                    </div>

                    <div class="address-field">
                        <label for="mobile">Mobile Number</label>
                        <input type="tel" id="mobile" name="mobile" value="">
                        <span class="address-error" id="mobile_error"></span>
                    </div>

                    <div class="address-field">
                        <label for="pincode">Pincode</label>
                        <input type="text" id="pincode" name="pincode" placeholder="e.g. 400001">
                        <span class="address-error" id="pincode_error"></span>
                    </div>

                    <div class="address-field">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city" placeholder="e.g. Gujrat">
                        <span class="address-error" id="city_error"></span>
                    </div>

                    <div class="address-field address-field-full">
                        <label for="address_line1">Address Line 1</label>
                        <input type="text" id="address_line1" name="address_line1" placeholder="House no., Building name, Street">
                        <span class="address-error" id="address_line1_error"></span>
                    </div>

                    <div class="address-field address-field-full">
                        <label for="address_line2">Address Line 2 (Optional)</label>
                        <input type="text" id="address_line2" name="address_line2" placeholder="Area, Locality, Nearby landmark">
                    </div>

                    <div class="address-field">
                        <label for="state">State</label>
                        <select id="state" name="state">
                            <option value="">Select State</option>
                            <option value="MH">Maharashtra</option>
                            <option value="DL">Delhi</option>
                            <option value="Gj">Gujrat</option>
                            <option value="TN">Tamil Nadu</option>
                            <option value="UP">Uttar Pradesh</option>
                            <option value="RJ">Rajasthan</option>
                        </select>
                        <span class="address-error" id="state_error"></span>
                    </div>

                    <div class="address-field">
                        <label>Address Type</label>
                        <div class="address-type-group">
                            <label><input type="radio" name="address_type" value="home" checked> Home</label>
                            <label><input type="radio" name="address_type" value="work"> Work</label>
                            <label><input type="radio" name="address_type" value="other"> Other</label>
                        </div>
                        <span class="address-error" id="address_type_error"></span>
                    </div>

                    <div class="address-field address-field-full">
                        <div class="address-default-toggle">
                            <input type="checkbox" id="is_default" name="is_default" checked>
                            <label for="is_default">Make this my default delivery address</label>
                        </div>
                        <div class="address-meta">You can change your default address anytime from your profile.</div>
                    </div>

                    <div class="address-footer address-field-full">
                        <a href="Address.php" class="btn-secondary">Cancel</a>
                        <button type="submit" class="btn-primary">Save Address</button>
                    </div>
                </form>
            </section>
        </div>
    </main>

         <?php include "../../Footer.php"; ?>
         
    <script>
        (function () {
            const form = document.getElementById('addAddressForm');
            if (!form) return;

            const fullNameInput = document.getElementById('full_name');
            const mobileInput = document.getElementById('mobile');
            const pincodeInput = document.getElementById('pincode');
            const cityInput = document.getElementById('city');
            const addressLine1Input = document.getElementById('address_line1');
            const stateSelect = document.getElementById('state');
            const addressTypeRadios = document.querySelectorAll('input[name="address_type"]');

            const fullNameError = document.getElementById('full_name_error');
            const mobileError = document.getElementById('mobile_error');
            const pincodeError = document.getElementById('pincode_error');
            const cityError = document.getElementById('city_error');
            const addressLine1Error = document.getElementById('address_line1_error');
            const stateError = document.getElementById('state_error');
            const addressTypeError = document.getElementById('address_type_error');

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

            function validateAddressForm() {
                clearError(fullNameInput, fullNameError);
                clearError(mobileInput, mobileError);
                clearError(pincodeInput, pincodeError);
                clearError(cityInput, cityError);
                clearError(addressLine1Input, addressLine1Error);
                clearError(stateSelect, stateError);
                if (addressTypeError) {
                    addressTypeError.textContent = '';
                }

                let hasError = false;

                if (fullNameInput) {
                    const name = fullNameInput.value.trim();
                    if (!name) {
                        showError(fullNameInput, fullNameError, 'Please enter full name.');
                        hasError = true;
                    } else if (name.length < 3) {
                        showError(fullNameInput, fullNameError, 'Full name should be at least 3 characters.');
                        hasError = true;
                    }
                }

                if (mobileInput) {
                    const rawMobile = mobileInput.value.trim();
                    const digits = rawMobile.replace(/\D/g, '');
                    if (!rawMobile) {
                        showError(mobileInput, mobileError, 'Please enter mobile number.');
                        hasError = true;
                    } else if (!digits || digits.length < 10) {
                        showError(mobileInput, mobileError, 'Mobile number should have at least 10 digits.');
                        hasError = true;
                    }
                }

                if (pincodeInput) {
                    const value = pincodeInput.value.trim();
                    const digits = value.replace(/\D/g, '');
                    if (!value) {
                        showError(pincodeInput, pincodeError, 'Please enter pincode.');
                        hasError = true;
                    } else if (!/^\d{6}$/.test(digits)) {
                        showError(pincodeInput, pincodeError, 'Pincode should be 6 digits.');
                        hasError = true;
                    }
                }

                if (cityInput) {
                    const value = cityInput.value.trim();
                    if (!value) {
                        showError(cityInput, cityError, 'Please enter city.');
                        hasError = true;
                    } else if (value.length < 2) {
                        showError(cityInput, cityError, 'City name should be at least 2 characters.');
                        hasError = true;
                    }
                }

                if (addressLine1Input) {
                    const value = addressLine1Input.value.trim();
                    if (!value) {
                        showError(addressLine1Input, addressLine1Error, 'Please enter address line 1.');
                        hasError = true;
                    } else if (value.length < 5) {
                        showError(addressLine1Input, addressLine1Error, 'Address line 1 should be at least 5 characters.');
                        hasError = true;
                    }
                }

                if (stateSelect) {
                    const value = stateSelect.value;
                    if (!value) {
                        showError(stateSelect, stateError, 'Please select your state.');
                        hasError = true;
                    }
                }

                if (addressTypeRadios && addressTypeRadios.length > 0) {
                    const anyChecked = Array.prototype.some.call(addressTypeRadios, function (radio) {
                        return radio.checked;
                    });
                    if (!anyChecked && addressTypeError) {
                        addressTypeError.textContent = 'Please select an address type.';
                        hasError = true;
                    }
                }

                return !hasError;
            }

            form.addEventListener('submit', function (e) {
                const isValid = validateAddressForm();
                if (!isValid) {
                    e.preventDefault();
                    return;
                }
            });

            [fullNameInput, mobileInput, pincodeInput, cityInput, addressLine1Input, stateSelect].forEach(function (input) {
                if (!input) return;
                input.addEventListener('input', function () {
                    if (input === fullNameInput) {
                        clearError(fullNameInput, fullNameError);
                    } else if (input === mobileInput) {
                        clearError(mobileInput, mobileError);
                    } else if (input === pincodeInput) {
                        clearError(pincodeInput, pincodeError);
                    } else if (input === cityInput) {
                        clearError(cityInput, cityError);
                    } else if (input === addressLine1Input) {
                        clearError(addressLine1Input, addressLine1Error);
                    } else if (input === stateSelect) {
                        clearError(stateSelect, stateError);
                    }
                });
            });

            if (addressTypeRadios && addressTypeRadios.length > 0) {
                addressTypeRadios.forEach(function (radio) {
                    radio.addEventListener('change', function () {
                        if (addressTypeError) {
                            addressTypeError.textContent = '';
                        }
                    });
                });
            }
        })();
    </script>

    <script src="../../assets/js/index.js"></script>
    <a href="../../index.php" class="floating-home" title="Go to Home">
        <i class="fas fa-home"></i>
    </a>
</body>
</html>