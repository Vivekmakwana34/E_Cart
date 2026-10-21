<?php
session_start();

include '../../Database_file/config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

$cartItems = isset($_SESSION['cart']) && is_array($_SESSION['cart']) ? $_SESSION['cart'] : [];
$cartItemCount = 0;
$cartSubtotal = 0.0;
foreach ($cartItems as $it) {
    $qty = (int)($it['quantity'] ?? 0);
    $price = (float)($it['price'] ?? 0);
    if ($qty <= 0) continue;
    $cartItemCount += $qty;
    $cartSubtotal += $price * $qty;
}

// Match cart/select_address pricing model: show 30% discount and payable 70%
$regularDiscount = $cartSubtotal * 0.30;
$payableBeforeCoupon = max(0, $cartSubtotal - $regularDiscount);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - E_Cart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/index.css">
    <link rel="stylesheet" href="../../floating_home.css">
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <style>
        /* Payment Page - Flipkart Style */
        .payment-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            background: #f1f3f6;
            min-height: calc(100vh - 200px);
        }

        .page-header {
            background: white;
            padding: 20px 0;
            border-bottom: 1px solid #dbdbdb;
            margin-bottom: 24px;
        }

        .page-title {
            font-size: 20px;
            font-weight: 500;
            color: #212121;
            text-align: center;
        }

        .checkout-container {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 24px;
            margin-bottom: 40px;
        }

        /* Payment Options Section */
        .payment-section {
            background: white;
            border-radius: 2px;
            box-shadow: 0 1px 1px 0 rgba(0,0,0,0.08);
            padding: 24px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 500;
            color: #212121;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid #f0f0f0;
        }

        .payment-options {
            display: grid;
            gap: 16px;
        }

        .payment-option {
            border: 1px solid #dbdbdb;
            border-radius: 2px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }

        .payment-option:hover {
            border-color: #2874f0;
            box-shadow: 0 2px 4px 0 rgba(0,0,0,0.08);
        }

        .payment-option.selected {
            border-color: #2874f0;
            background: #f8f9fa;
        }

        .payment-radio {
            position: absolute;
            top: 20px;
            right: 20px;
        }

        .payment-radio input[type="radio"] {
            width: 18px;
            height: 18px;
            accent-color: #2874f0;
        }

        .payment-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 12px;
        }

        .payment-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 20px;
        }

        .payment-icon.card {
            background: #e3f2fd;
            color: #1976d2;
        }

        .payment-icon.upi {
            background: #e8f5e8;
            color: #388e3c;
        }

        .payment-icon.cod {
            background: #fff3e0;
            color: #f57c00;
        }

        .payment-icon.wallet {
            background: #fce4ec;
            color: #c2185b;
        }

        .payment-name {
            font-size: 16px;
            font-weight: 500;
            color: #212121;
        }

        .payment-description {
            font-size: 14px;
            color: #878787;
            line-height: 1.4;
        }

        .payment-form {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #f0f0f0;
            display: none;
        }

        .payment-form.active {
            display: block;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #212121;
            margin-bottom: 8px;
        }

        .form-input {
            width: 100%;
            padding: 12px;
            border: 1px solid #dbdbdb;
            border-radius: 2px;
            font-size: 14px;
            transition: border-color 0.2s;
        }

        .form-input:focus {
            outline: none;
            border-color: #2874f0;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .card-input-group {
            position: relative;
        }

        .card-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 24px;
            color: #878787;
        }

        .upi-input-group {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .upi-prefix {
            background: #f8f9fa;
            padding: 12px;
            border: 1px solid #dbdbdb;
            border-radius: 2px;
            font-size: 14px;
            color: #878787;
        }

        .upi-input {
            flex: 1;
        }

        .verify-btn {
            background: #2874f0;
            color: white;
            border: none;
            padding: 12px 20px;
            font-size: 14px;
            font-weight: 500;
            border-radius: 2px;
            cursor: pointer;
            transition: background 0.2s;
            white-space: nowrap;
        }

        .verify-btn:hover {
            background: #1e5fb3;
        }

        .verify-btn:disabled {
            background: #dbdbdb;
            color: #878787;
            cursor: not-allowed;
        }

        .verify-btn.verifying {
            background: #ff9800;
            color: white;
        }

        .verify-btn.verified {
            background: #4caf50;
            color: white;
        }

        .validation-message {
            margin-top: 8px;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .validation-message.error {
            color: #ff6161;
        }

        .validation-message.success {
            color: #388e3c;
        }

        .validation-message.info {
            color: #2874f0;
        }

        .saved-cards {
            margin-bottom: 20px;
        }

        .saved-card-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px;
            border: 1px solid #dbdbdb;
            border-radius: 2px;
            margin-bottom: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .saved-card-item:hover {
            border-color: #2874f0;
            background: #f8f9fa;
        }

        .saved-card-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card-type-icon {
            width: 32px;
            height: 20px;
            border-radius: 2px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            color: white;
        }

        .card-type-icon.visa {
            background: #1a1f71;
        }

        .card-type-icon.mastercard {
            background: #eb001b;
        }

        .saved-card-details {
            font-size: 14px;
            color: #212121;
        }

        .saved-card-number {
            font-weight: 500;
        }

        .saved-card-name {
            color: #878787;
            font-size: 12px;
        }

        .saved-card-radio {
            accent-color: #2874f0;
        }

        .add-new-card {
            color: #2874f0;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-top: 12px;
        }

        .add-new-card:hover {
            text-decoration: underline;
        }

        /* Order Summary Section */
        .order-summary {
            position: sticky;
            top: 20px;
            height: fit-content;
        }

        .summary-card {
            background: white;
            border-radius: 2px;
            box-shadow: 0 1px 1px 0 rgba(0,0,0,0.08);
            padding: 24px;
        }

        .summary-title {
            font-size: 18px;
            font-weight: 500;
            color: #212121;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid #f0f0f0;
        }

        .price-details {
            margin-bottom: 20px;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .price-row:last-of-type {
            border-bottom: none;
            padding-top: 16px;
            margin-top: 8px;
            border-top: 1px solid #f0f0f0;
        }

        .price-label {
            font-size: 14px;
            color: #212121;
        }

        .price-value {
            font-size: 14px;
            color: #212121;
            font-weight: 500;
        }

        .price-value.total {
            font-size: 18px;
            font-weight: 500;
            color: #212121;
        }

        .delivery-info {
            background: #f8f9fa;
            padding: 16px;
            border-radius: 2px;
            margin-bottom: 20px;
        }

        .delivery-text {
            font-size: 14px;
            color: #878787;
            line-height: 1.4;
        }

        .delivery-date {
            font-size: 14px;
            color: #212121;
            font-weight: 500;
            margin-top: 4px;
        }

        .address-info {
            background: #f8f9fa;
            padding: 16px;
            border-radius: 2px;
            margin-bottom: 20px;
        }

        .address-label {
            font-size: 12px;
            color: #878787;
            margin-bottom: 4px;
        }

        .address-text {
            font-size: 14px;
            color: #212121;
            line-height: 1.4;
        }

        .place-order-btn {
            width: 100%;
            background: #fb641b;
            color: white;
            border: none;
            padding: 16px;
            font-size: 16px;
            font-weight: 500;
            border-radius: 2px;
            cursor: pointer;
            transition: background 0.2s;
            margin-bottom: 12px;
        }

        .place-order-btn:hover {
            background: #e85a0f;
        }

        .place-order-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .security-info {
            text-align: center;
            font-size: 12px;
            color: #878787;
        }

        .security-info i {
            color: #2874f0;
            margin-right: 4px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .checkout-container {
                grid-template-columns: 1fr;
            }

            .order-summary {
                position: static;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .upi-input-group {
                flex-direction: column;
                align-items: stretch;
            }

            .upi-prefix {
                text-align: center;
            }
        }
    </style>
</head>
<body>

     <?php include '../../Header.php'; ?>


    <!-- Payment Main Content -->
    <main class="payment-container">
        <div class="page-header">
            <h1 class="page-title">Payment Options</h1>
        </div>

        <div class="checkout-container">
            <!-- Payment Options Section -->
            <section class="payment-section">
                <h2 class="section-title">Choose Payment Method</h2>
                
                <div class="payment-options">
                    <!-- Credit/Debit Cards -->
                    <div class="payment-option" onclick="selectPayment(this, 'card')">
                        <div class="payment-radio">
                            <input type="radio" name="payment" value="card">
                        </div>
                        <div class="payment-header">
                            <div class="payment-icon card">
                                <i class="fas fa-credit-card"></i>
                            </div>
                            <div>
                                <div class="payment-name">Credit/Debit Card</div>
                                <div class="payment-description">Visa, Mastercard, Rupay, American Express</div>
                            </div>
                        </div>
                        
                        <div class="payment-form" id="cardForm">
                            <div class="saved-cards">
                                <div class="saved-card-item" onclick="selectSavedCard(this)">
                                    <div class="saved-card-info">
                                        <div class="card-type-icon visa">VISA</div>
                                        <div class="saved-card-details">
                                            <div class="saved-card-number">•••• •••• •••• 4242</div>
                                            <div class="saved-card-name">Rajesh Kumar</div>
                                        </div>
                                    </div>
                                    <input type="radio" name="savedCard" class="saved-card-radio">
                                </div>
                                
                                <div class="saved-card-item" onclick="selectSavedCard(this)">
                                    <div class="saved-card-info">
                                        <div class="card-type-icon mastercard">MC</div>
                                        <div class="saved-card-details">
                                            <div class="saved-card-number">•••• •••• •••• 5555</div>
                                            <div class="saved-card-name">Rajesh Kumar</div>
                                        </div>
                                    </div>
                                    <input type="radio" name="savedCard" class="saved-card-radio">
                                </div>
                            </div>
                            
                            <a href="#" class="add-new-card" onclick="showNewCardForm(event)">+ Add New Card</a>
                            
                            <div id="newCardForm" style="display: none; margin-top: 20px;">
                                <div class="form-group">
                                    <label class="form-label">Card Number *</label>
                                    <div class="card-input-group">
                                        <input type="text" class="form-input" placeholder="Enter 16-digit card number" maxlength="19">
                                        <i class="fas fa-credit-card card-icon"></i>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Name on Card *</label>
                                    <input type="text" class="form-input" placeholder="Enter name as on card">
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Expiry Date *</label>
                                        <input type="text" class="form-input" placeholder="MM/YY" maxlength="5">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">CVV *</label>
                                        <input type="text" class="form-input" placeholder="Enter CVV" maxlength="3">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- UPI -->
                    <div class="payment-option" onclick="selectPayment(this, 'upi')">
                        <div class="payment-radio">
                            <input type="radio" name="payment" value="upi">
                        </div>
                        <div class="payment-header">
                            <div class="payment-icon upi">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <div>
                                <div class="payment-name">UPI</div>
                                <div class="payment-description">Google Pay, PhonePe, Paytm & other UPI apps</div>
                            </div>
                        </div>
                        
                        <div class="payment-form" id="upiForm">
                            <div class="form-group">
                                <label class="form-label">UPI ID *</label>
                                <div class="upi-input-group">
                                    <div class="upi-prefix">Enter UPI ID</div>
                                    <input type="text" class="form-input upi-input" id="upiIdInput" placeholder="yourname@upi" onblur="validateUpiId()">
                                    <button type="button" class="verify-btn" id="verifyUpiBtn" onclick="verifyUpiId()">Verify</button>
                                </div>
                                <div class="validation-message" id="upiValidation"></div>
                            </div>
                            
                            <div class="upi-apps">
                                <div class="upi-app-item">
                                    <i class="fab fa-google-pay"></i>
                                    <span>Google Pay</span>
                                </div>
                                <div class="upi-app-item">
                                    <i class="fab fa-phone"></i>
                                    <span>PhonePe</span>
                                </div>
                                <div class="upi-app-item">
                                    <i class="fas fa-wallet"></i>
                                    <span>Paytm</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Wallets -->
                    <div class="payment-option" onclick="selectPayment(this, 'wallet')">
                        <div class="payment-radio">
                            <input type="radio" name="payment" value="wallet">
                        </div>
                        <div class="payment-header">
                            <div class="payment-icon wallet">
                                <i class="fas fa-wallet"></i>
                            </div>
                            <div>
                                <div class="payment-name">Wallets</div>
                                <div class="payment-description">Paytm Wallet, MobiKwik, FreeCharge & more</div>
                            </div>
                        </div>
                        
                        <div class="payment-form" id="walletForm">
                            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
                                <button type="button" style="padding: 16px; border: 1px solid #dbdbdb; background: white; border-radius: 2px; cursor: pointer; text-align: center;">
                                    <i class="fas fa-wallet" style="color: #00baff; font-size: 24px;"></i>
                                    <div style="font-size: 14px; margin-top: 8px;">Paytm Wallet</div>
                                </button>
                                <button type="button" style="padding: 16px; border: 1px solid #dbdbdb; background: white; border-radius: 2px; cursor: pointer; text-align: center;">
                                    <i class="fas fa-wallet" style="color: #ff6b6b; font-size: 24px;"></i>
                                    <div style="font-size: 14px; margin-top: 8px;">MobiKwik</div>
                                </button>
                                <button type="button" style="padding: 16px; border: 1px solid #dbdbdb; background: white; border-radius: 2px; cursor: pointer; text-align: center;">
                                    <i class="fas fa-wallet" style="color: #4ecdc4; font-size: 24px;"></i>
                                    <div style="font-size: 14px; margin-top: 8px;">FreeCharge</div>
                                </button>
                                <button type="button" style="padding: 16px; border: 1px solid #dbdbdb; background: white; border-radius: 2px; cursor: pointer; text-align: center;">
                                    <i class="fas fa-wallet" style="color: #6c5ce7; font-size: 24px;"></i>
                                    <div style="font-size: 14px; margin-top: 8px;">PhonePe Wallet</div>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Net Banking -->
                    <div class="payment-option" onclick="selectPayment(this, 'netbanking')">
                        <div class="payment-radio">
                            <input type="radio" name="payment" value="netbanking">
                        </div>
                        <div class="payment-header">
                            <div class="payment-icon netbanking">
                                <i class="fas fa-university"></i>
                            </div>
                            <div>
                                <div class="payment-name">Net Banking</div>
                                <div class="payment-description">All major banks supported</div>
                            </div>
                        </div>
                        
                        <div class="payment-form" id="netbankingForm">
                            <div class="form-group">
                                <label class="form-label">Select Bank *</label>
                                <select class="form-input" id="bankSelect">
                                    <option value="">-- Select Bank --</option>
                                    <option value="HDFC">HDFC Bank</option>
                                    <option value="ICICI">ICICI Bank</option>
                                    <option value="SBI">State Bank of India</option>
                                    <option value="PNB">Punjab National Bank</option>
                                    <option value="AXIS">Axis Bank</option>
                                    <option value="KOTAK">Kotak Mahindra Bank</option>
                                    <option value="BOI">Bank of India</option>
                                    <option value="CANARA">Canara Bank</option>
                                    <option value="INDIAN">Indian Bank</option>
                                    <option value="UCO">UCO Bank</option>
                                    <option value="BARODA">Bank of Baroda</option>
                                </select>
                            </div>
                            <div class="bank-info">
                                <i class="fas fa-info-circle"></i>
                                <span>You will be redirected to your bank's secure payment page</span>
                            </div>
                        </div>
                    </div>

                    <!-- Cash on Delivery -->
                    <div class="payment-option" onclick="selectPayment(this, 'cod')">
                        <div class="payment-radio">
                            <input type="radio" name="payment" value="cod">
                        </div>
                        <div class="payment-header">
                            <div class="payment-icon cod">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <div>
                                <div class="payment-name">Cash on Delivery</div>
                                <div class="payment-description">Pay when you receive the order</div>
                            </div>
                        </div>
                        
                        <div class="payment-form" id="codForm">
                            <div class="cod-info">
                                <i class="fas fa-check-circle" style="color: #388e3c;"></i>
                                <span>Pay cash when your order is delivered</span>
                            </div>
                            <div class="cod-info">
                                <i class="fas fa-truck" style="color: #ff9800;"></i>
                                <span>Standard delivery charges apply</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Order Summary Section -->
            <aside class="order-summary">
                <div class="summary-card">
                    <h3 class="summary-title">Price Details</h3>
                    
                    <div class="price-details">
                        <div class="price-row">
                            <span class="price-label">Price (<?php echo (int)$cartItemCount; ?> items)</span>
                            <span class="price-value" id="cartPrice">₹<?php echo number_format($cartSubtotal, 0); ?></span>
                        </div>
                        <div class="price-row">
                            <span class="price-label">Discount</span>
                            <span class="price-value" style="color: #388e3c;" id="regularDiscount">-₹<?php echo number_format($regularDiscount, 0); ?></span>
                        </div>
                        <div class="price-row" id="couponDiscountRow" style="display: none;">
                            <span class="price-label">Coupon Discount</span>
                            <span class="price-value" id="couponDiscountAmount" style="color: #388e3c;">-₹0</span>
                        </div>
                        <div class="price-row">
                            <span class="price-label">Delivery Charges</span>
                            <span class="price-value" style="color: #388e3c;"><span style="text-decoration: line-through; color: #878787;">₹40</span> <span>FREE</span></span>
                        </div>
                        <div class="price-row">
                            <span class="price-label">Total Amount</span>
                            <span class="price-value total" id="totalAmount">₹<?php echo number_format($payableBeforeCoupon, 0); ?></span>
                        </div>
                    </div>

                    <div class="delivery-info">
                        <div class="delivery-text">Delivery by</div>
                        <div class="delivery-date">Tomorrow, 10 AM - 6 PM</div>
                    </div>

                    <div class="address-info">
                        <div class="address-label">DELIVERING TO</div>
                        <div class="address-text" id="deliveryAddress">
                            Rajesh Kumar, 123 Main Street, Andheri West, Mumbai - 400001
                        </div>
                    </div>

                    <button class="place-order-btn" onclick="placeOrder()" id="placeOrderBtn" disabled>
                        PLACE ORDER
                    </button>

                    <div class="security-info">
                        <i class="fas fa-shield-alt"></i>
                        Safe and Secure Payments. Easy returns. 100% Authentic products.
                    </div>
                </div>
            </aside>
        </div>
    </main>

         <?php include "../../Footer.php"; ?>
         
    <script>
        let selectedPaymentMethod = null;

        // Payment Selection
        function selectPayment(option, method) {
            // Remove selected class from all options
            document.querySelectorAll('.payment-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            
            // Hide all payment forms
            document.querySelectorAll('.payment-form').forEach(form => {
                form.classList.remove('active');
            });
            
            // Add selected class to clicked option
            option.classList.add('selected');
            
            // Check the radio button
            const radio = option.querySelector('input[type="radio"]');
            radio.checked = true;
            
            // Show corresponding payment form
            const form = document.getElementById(method + 'Form');
            if (form) {
                form.classList.add('active');
            }
            
            selectedPaymentMethod = method;
            
            // Enable place order button
            document.getElementById('placeOrderBtn').disabled = false;
        }

        // Saved Card Selection
        function selectSavedCard(cardItem) {
            event.stopPropagation();
            
            // Remove selected state from all cards
            document.querySelectorAll('.saved-card-item').forEach(card => {
                card.style.borderColor = '#dbdbdb';
                card.style.background = 'white';
            });
            
            // Select clicked card
            cardItem.style.borderColor = '#2874f0';
            cardItem.style.background = '#f8f9fa';
            
            // Check the radio button
            const radio = cardItem.querySelector('input[type="radio"]');
            radio.checked = true;
        }

        // Show New Card Form
        function showNewCardForm(event) {
            event.preventDefault();
            const newCardForm = document.getElementById('newCardForm');
            newCardForm.style.display = newCardForm.style.display === 'none' ? 'block' : 'none';
        }

        // UPI ID Validation
        let upiVerified = false;

        function validateUpiId() {
            const upiInput = document.getElementById('upiIdInput');
            const validationMsg = document.getElementById('upiValidation');
            const verifyBtn = document.getElementById('verifyUpiBtn');
            const upiId = upiInput.value.trim();

            // Reset validation
            validationMsg.className = 'validation-message';
            validationMsg.innerHTML = '';
            verifyBtn.className = 'verify-btn';
            verifyBtn.textContent = 'Verify';
            upiVerified = false;

            if (!upiId) {
                return;
            }

            // UPI ID validation regex
            const upiRegex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+$/;
            
            if (!upiRegex.test(upiId)) {
                validationMsg.className = 'validation-message error';
                validationMsg.innerHTML = '<i class="fas fa-exclamation-circle"></i> Invalid UPI ID format. Use format: yourname@upi';
                verifyBtn.disabled = true;
                return;
            }

            // Check length
            if (upiId.length < 5 || upiId.length > 50) {
                validationMsg.className = 'validation-message error';
                validationMsg.innerHTML = '<i class="fas fa-exclamation-circle"></i> UPI ID must be 5-50 characters long';
                verifyBtn.disabled = true;
                return;
            }

            // Check valid UPI handles
            const validHandles = ['ybl', 'okhdfcbank', 'okicici', 'oksbi', 'okaxis', 'paytm', 'phonepe', 'gpay', 'upi'];
            const handle = upiId.split('@')[1];
            
            if (!validHandles.some(validHandle => handle.includes(validHandle))) {
                validationMsg.className = 'validation-message info';
                validationMsg.innerHTML = '<i class="fas fa-info-circle"></i> Please verify this UPI ID';
                verifyBtn.disabled = false;
            } else {
                validationMsg.className = 'validation-message success';
                validationMsg.innerHTML = '<i class="fas fa-check-circle"></i> Valid UPI ID format';
                verifyBtn.disabled = false;
            }
        }

        function verifyUpiId() {
            const upiInput = document.getElementById('upiIdInput');
            const validationMsg = document.getElementById('upiValidation');
            const verifyBtn = document.getElementById('verifyUpiBtn');
            const upiId = upiInput.value.trim();

            if (!upiId) {
                validationMsg.className = 'validation-message error';
                validationMsg.innerHTML = '<i class="fas fa-exclamation-circle"></i> Please enter UPI ID';
                return;
            }

            // Show verifying state
            verifyBtn.className = 'verify-btn verifying';
            verifyBtn.textContent = 'Verifying...';
            verifyBtn.disabled = true;
            validationMsg.className = 'validation-message info';
            validationMsg.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying UPI ID...';

            // Simulate API call for verification
            setTimeout(() => {
                // Mock verification - in real app, this would be an API call
                const mockValidUpiIds = [
                    'rajesh@ybl', 'kumar@okhdfcbank', 'test@paytm', 
                    'demo@phonepe', 'user@gpay', 'sample@upi'
                ];
                
                const isValid = mockValidUpiIds.some(validId => upiId.toLowerCase().includes(validId.split('@')[0])) || 
                               Math.random() > 0.3; // 70% success rate for demo

                if (isValid) {
                    // Success
                    verifyBtn.className = 'verify-btn verified';
                    verifyBtn.textContent = 'Verified ✓';
                    verifyBtn.disabled = false;
                    validationMsg.className = 'validation-message success';
                    validationMsg.innerHTML = '<i class="fas fa-check-circle"></i> UPI ID verified successfully! Account name: Rajesh Kumar';
                    upiVerified = true;
                    
                    // Update input border
                    upiInput.style.borderColor = '#4caf50';
                } else {
                    // Failed
                    verifyBtn.className = 'verify-btn';
                    verifyBtn.textContent = 'Verify';
                    verifyBtn.disabled = false;
                    validationMsg.className = 'validation-message error';
                    validationMsg.innerHTML = '<i class="fas fa-exclamation-circle"></i> UPI ID not found. Please check and try again.';
                    upiVerified = false;
                    
                    // Update input border
                    upiInput.style.borderColor = '#ff6161';
                }
            }, 2000); // 2 second delay for realistic verification
        }

        function selectUpiApp(app) {
            const validationMsg = document.getElementById('upiValidation');
            const verifyBtn = document.getElementById('verifyUpiBtn');
            const upiInput = document.getElementById('upiIdInput');

            // Reset UPI ID verification when app is selected
            upiVerified = true;
            verifyBtn.className = 'verify-btn verified';
            verifyBtn.textContent = 'App Selected ✓';
            verifyBtn.disabled = false;
            validationMsg.className = 'validation-message success';
            
            const appNames = {
                'gpay': 'Google Pay',
                'phonepe': 'PhonePe',
                'paytm': 'Paytm'
            };
            
            validationMsg.innerHTML = `<i class="fas fa-check-circle"></i> ${appNames[app]} selected. You will be redirected to the app for payment.`;
            upiInput.style.borderColor = '#4caf50';
        }

        // Place Order
        function placeOrder() {
            const selectedPaymentMethod = document.querySelector('input[name="payment"]:checked');
            if (!selectedPaymentMethod) {
                showNotification('Please select a payment method', 'error');
                return;
            }
            
            // Store payment method in sessionStorage
            sessionStorage.setItem('paymentMethod', selectedPaymentMethod.value);
            
            // Get order details
            const priceData = JSON.parse(sessionStorage.getItem('priceData'));
            const selectedAddress = sessionStorage.getItem('selectedAddress');
            
            // For now, complete order directly without Razorpay
            // Razorpay integration commented for testing
            /*
            if (selectedPaymentMethod.value === 'cod') {
                // For COD, proceed normally
                showPaymentProcessingAnimation();
            } else {
                // For all online payments, use Razorpay
                initRazorpayPayment(priceData.finalTotal);
            }
            */
            
            // Direct order completion for now
            completeOrderDirectly();
        }

        // Complete Order Directly (for testing)
        function completeOrderDirectly() {
            // Show processing animation
            showPaymentProcessingAnimation();
            
            // Simulate payment processing
            setTimeout(() => {
                const priceData = JSON.parse(sessionStorage.getItem('priceData') || 'null');
                const amount = priceData && typeof priceData.finalTotal !== 'undefined' ? priceData.finalTotal : <?php echo json_encode((int)round($payableBeforeCoupon)); ?>;

                // Store payment details
                const paymentDetails = {
                    payment_method: sessionStorage.getItem('paymentMethod'),
                    amount: amount,
                    timestamp: new Date().toISOString(),
                    status: 'success',
                    transaction_id: 'TEST' + Date.now()
                };
                
                sessionStorage.setItem('paymentDetails', JSON.stringify(paymentDetails));
                
                // Store in PHP session for order_complete.php
                fetch('store_session.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        paymentMethod: sessionStorage.getItem('paymentMethod'),
                        selectedAddress: sessionStorage.getItem('selectedAddress')
                    })
                }).then(response => response.json()).then(result => {
                    if (result.success) {
                        // Save order to database
                        fetch('../Order/save_order.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                paymentMethod: sessionStorage.getItem('paymentMethod'),
                                selectedAddress: sessionStorage.getItem('selectedAddress'),
                                priceData: priceData
                            })
                        }).then(() => {
                            // Show success message
                            showNotification('Order placed successfully! Redirecting...', 'success');
                            
                            // Redirect to order complete page
                            setTimeout(() => {
                                window.location.href = 'order_complete.php';
                            }, 2000);
                        });
                    }
                });
            }, 2000);
        }

        // Initialize Razorpay Payment
        function initRazorpayPayment(amount) {
            const finalAmount = amount;
            console.log('Initializing Razorpay payment for amount:', finalAmount);
            
            // Check if Razorpay is available
            if (typeof Razorpay === 'undefined') {
                console.error('Razorpay is not loaded');
                showNotification('Payment gateway not available. Please refresh the page.', 'error');
                return;
            }
            
            console.log('Razorpay is available, opening checkout...');
            openRazorpayCheckout(finalAmount);
        }

        // Open Razorpay Checkout
        function openRazorpayCheckout(amount) {
            console.log('Opening Razorpay checkout with amount:', amount);
            
            try {
                // Enhanced Razorpay options for UPI QR payment
                const options = {
                    key: 'rzp_test_SGlzYcAUku4FkG',
                    amount: amount * 100,
                    currency: 'INR',
                    name: 'E-Cart',
                    description: 'Purchase Payment',
                    handler: function (response) {
                        console.log('Payment successful:', response);
                        handlePaymentSuccess(response);
                    },
                    prefill: {
                        contact: '9876543210',
                        email: 'test@example.com'
                    },
                    notes: {
                        "address": "Test Address",
                        "order_id": "ORDER" + Date.now()
                    },
                    theme: {
                        color: '#3399cc'
                    },
                    modal: {
                        ondismiss: function() {
                            console.log('Payment modal dismissed');
                            showNotification('Payment cancelled', 'info');
                        },
                        escape: true,
                        handleback: true,
                        confirm_close: true,
                        backdropclose: false
                    }
                };

                console.log('Creating Razorpay instance...');
                const rzp = new Razorpay(options);
                
                rzp.on('payment.failed', function (response) {
                    console.error('Payment failed:', response.error);
                    showNotification('Payment failed: ' + response.error.description, 'error');
                });
                
                console.log('Opening Razorpay modal...');
                rzp.open();
                
            } catch (error) {
                console.error('Error opening Razorpay checkout:', error);
                showNotification('Unable to open payment gateway. Error: ' + error.message, 'error');
            }
        }

        // Handle Payment Success
        function handlePaymentSuccess(response) {
            const priceData = JSON.parse(sessionStorage.getItem('priceData') || 'null');
            const amount = priceData && typeof priceData.finalTotal !== 'undefined' ? priceData.finalTotal : <?php echo json_encode((int)round($payableBeforeCoupon)); ?>;

            // Store payment details
            const paymentDetails = {
                razorpay_payment_id: response.razorpay_payment_id,
                razorpay_order_id: response.razorpay_order_id,
                razorpay_signature: response.razorpay_signature,
                payment_method: sessionStorage.getItem('paymentMethod'),
                amount: amount,
                timestamp: new Date().toISOString()
            };
            
            sessionStorage.setItem('paymentDetails', JSON.stringify(paymentDetails));
            
            // Show success message and redirect
            showNotification('Payment successful! Redirecting...', 'success');
            
            setTimeout(() => {
                window.location.href = 'order_complete.php';
            }, 2000);
        }

        // Show payment processing animation
        function showPaymentProcessingAnimation() {
            // Create overlay
            const overlay = document.createElement('div');
            overlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.8);
                z-index: 9999;
                display: flex;
                justify-content: center;
                align-items: center;
                backdrop-filter: blur(5px);
            `;
            
            // Create animation container
            const animationContainer = document.createElement('div');
            animationContainer.style.cssText = `
                text-align: center;
                color: white;
                animation: fadeIn 0.5s ease;
            `;
            
            // Create animated card
            const animatedCard = document.createElement('div');
            animatedCard.style.cssText = `
                width: 120px;
                height: 80px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border-radius: 12px;
                margin: 0 auto 30px;
                position: relative;
                box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
                animation: cardSlide 1.5s ease infinite;
            `;
            
            // Add card chip
            const chip = document.createElement('div');
            chip.style.cssText = `
                width: 30px;
                height: 20px;
                background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
                border-radius: 4px;
                position: absolute;
                top: 15px;
                left: 15px;
            `;
            
            // Add card lines
            const lines = document.createElement('div');
            lines.innerHTML = `
                <div style="position: absolute; bottom: 15px; left: 15px; right: 15px;">
                    <div style="height: 3px; background: rgba(255,255,255,0.3); border-radius: 2px; margin-bottom: 8px;"></div>
                    <div style="height: 3px; background: rgba(255,255,255,0.3); border-radius: 2px; width: 60%;"></div>
                </div>
            `;
            
            animatedCard.appendChild(chip);
            animatedCard.appendChild(lines);
            
            // Create loading dots
            const loadingDots = document.createElement('div');
            loadingDots.style.cssText = `
                display: flex;
                justify-content: center;
                gap: 8px;
                margin-bottom: 20px;
            `;
            
            for (let i = 0; i < 3; i++) {
                const dot = document.createElement('div');
                dot.style.cssText = `
                    width: 12px;
                    height: 12px;
                    background: #2874f0;
                    border-radius: 50%;
                    animation: dotPulse 1.5s ease infinite;
                    animation-delay: ${i * 0.2}s;
                `;
                loadingDots.appendChild(dot);
            }
            
            // Create status text
            const statusText = document.createElement('div');
            statusText.style.cssText = `
                font-size: 18px;
                font-weight: 500;
                margin-bottom: 10px;
                color: #ffffff;
            `;
            statusText.textContent = 'Processing Payment...';
            
            // Create subtext
            const subText = document.createElement('div');
            subText.style.cssText = `
                font-size: 14px;
                color: rgba(255, 255, 255, 0.7);
                margin-bottom: 30px;
            `;
            subText.textContent = 'Please wait while we secure your transaction';
            
            // Create progress bar
            const progressBar = document.createElement('div');
            progressBar.style.cssText = `
                width: 300px;
                height: 4px;
                background: rgba(255, 255, 255, 0.2);
                border-radius: 2px;
                margin: 0 auto;
                overflow: hidden;
            `;
            
            const progressFill = document.createElement('div');
            progressFill.style.cssText = `
                height: 100%;
                background: linear-gradient(90deg, #2874f0, #4fc3f7);
                border-radius: 2px;
                width: 0%;
                animation: progressFill 3s ease forwards;
            `;
            progressBar.appendChild(progressFill);
            
            // Assemble animation
            animationContainer.appendChild(animatedCard);
            animationContainer.appendChild(loadingDots);
            animationContainer.appendChild(statusText);
            animationContainer.appendChild(subText);
            animationContainer.appendChild(progressBar);
            overlay.appendChild(animationContainer);
            
            // Add styles
            const styles = document.createElement('style');
            styles.textContent = `
                @keyframes fadeIn {
                    from { opacity: 0; transform: scale(0.9); }
                    to { opacity: 1; transform: scale(1); }
                }
                
                @keyframes cardSlide {
                    0%, 100% { transform: translateY(0) rotate(0deg); }
                    25% { transform: translateY(-10px) rotate(2deg); }
                    75% { transform: translateY(5px) rotate(-1deg); }
                }
                
                @keyframes dotPulse {
                    0%, 100% { transform: scale(1); opacity: 1; }
                    50% { transform: scale(1.5); opacity: 0.5; }
                }
                
                @keyframes progressFill {
                    0% { width: 0%; }
                    100% { width: 100%; }
                }
                
                @keyframes successPulse {
                    0% { transform: scale(0); opacity: 0; }
                    50% { transform: scale(1.2); opacity: 1; }
                    100% { transform: scale(1); opacity: 1; }
                }
            `;
            document.head.appendChild(styles);
            
            // Add to page
            document.body.appendChild(overlay);
            
            // Simulate payment processing
            let progress = 0;
            const progressInterval = setInterval(() => {
                progress += 5;
                if (progress >= 100) {
                    clearInterval(progressInterval);
                    
                    // Show success
                    setTimeout(() => {
                        showPaymentSuccess(overlay);
                    }, 500);
                }
            }, 150);
        }

        // Show payment success animation
        function showPaymentSuccess(overlay) {
            // Clear overlay
            overlay.innerHTML = '';
            
            // Create success container
            const successContainer = document.createElement('div');
            successContainer.style.cssText = `
                text-align: center;
                color: white;
                animation: fadeIn 0.5s ease;
            `;
            
            // Create success checkmark
            const successCheck = document.createElement('div');
            successCheck.style.cssText = `
                width: 80px;
                height: 80px;
                background: linear-gradient(135deg, #4caf50, #8bc34a);
                border-radius: 50%;
                margin: 0 auto 30px;
                display: flex;
                justify-content: center;
                align-items: center;
                animation: successPulse 0.6s ease;
                box-shadow: 0 10px 30px rgba(76, 175, 80, 0.4);
            `;
            
            const checkmark = document.createElement('i');
            checkmark.className = 'fas fa-check';
            checkmark.style.cssText = `
                font-size: 36px;
                color: white;
            `;
            successCheck.appendChild(checkmark);
            
            // Create success text
            const successText = document.createElement('div');
            successText.style.cssText = `
                font-size: 24px;
                font-weight: 600;
                margin-bottom: 10px;
                color: #ffffff;
            `;
            successText.textContent = 'Payment Successful!';
            
            // Create order text
            const orderText = document.createElement('div');
            orderText.style.cssText = `
                font-size: 16px;
                color: rgba(255, 255, 255, 0.8);
                margin-bottom: 30px;
            `;
            orderText.textContent = 'Redirecting to order confirmation...';
            
            // Assemble success animation
            successContainer.appendChild(successCheck);
            successContainer.appendChild(successText);
            successContainer.appendChild(orderText);
            overlay.appendChild(successContainer);
            
            // Add success animation style
            const successStyle = document.createElement('style');
            successStyle.textContent = `
                @keyframes successPulse {
                    0% { transform: scale(0); opacity: 0; }
                    50% { transform: scale(1.2); opacity: 1; }
                    100% { transform: scale(1); opacity: 1; }
                }
            `;
            document.head.appendChild(successStyle);
            
            // Redirect to order complete page
            setTimeout(() => {
                window.location.href = 'order_complete.php';
            }, 2000);
        }

        // Load pricing data from cart
        function loadPricingData() {
            // Get pricing data from sessionStorage or set defaults
            const savedPriceData = sessionStorage.getItem('priceData');
            
            if (savedPriceData) {
                const priceData = JSON.parse(savedPriceData);
                updatePriceDisplay(priceData);
            } else {
                // Set default display if no data found
                const defaultData = {
                    originalTotal: 3498,
                    discount: 1100,
                    couponDiscount: 0,
                    deliveryCharges: 40,
                    finalTotal: 2398
                };
                updatePriceDisplay(defaultData);
            }
        }

        // Update price display
        function updatePriceDisplay(priceData) {
            const couponDiscountRow = document.getElementById('couponDiscountRow');
            const couponDiscountAmount = document.getElementById('couponDiscountAmount');
            const totalAmount = document.getElementById('totalAmount');
            const cartPrice = document.getElementById('cartPrice');
            
            // Update cart price display
            if (cartPrice) {
                cartPrice.textContent = `₹${priceData.originalTotal}`;
            }
            
            // Update coupon discount visibility
            if (priceData.couponDiscount && parseInt(priceData.couponDiscount) > 0) {
                if (couponDiscountRow) couponDiscountRow.style.display = 'flex';
                if (couponDiscountAmount) couponDiscountAmount.textContent = `-₹${priceData.couponDiscount}`;
            } else {
                if (couponDiscountRow) couponDiscountRow.style.display = 'none';
            }
            
            // Update total amount
            if (totalAmount) {
                totalAmount.textContent = `₹${priceData.finalTotal}`;
            }
        }

        // Force price update
        function forcePriceUpdate() {
            const priceData = sessionStorage.getItem('priceData');
            if (priceData) {
                const data = JSON.parse(priceData);
                updatePriceDisplay(data);
            }
        }

        // Mobile Menu Toggle
        function toggleMenu() {
            const navLinks = document.getElementById('navLinks');
            navLinks.classList.toggle('active');
        }

        // Format card number input
        document.addEventListener('DOMContentLoaded', function() {
            const cardInput = document.querySelector('input[placeholder*="16-digit"]');
            if (cardInput) {
                cardInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\s/g, '');
                    let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
                    e.target.value = formattedValue;
                });
            }
            
            // Format expiry date input
            const expiryInput = document.querySelector('input[placeholder="MM/YY"]');
            if (expiryInput) {
                expiryInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length >= 2) {
                        value = value.slice(0, 2) + '/' + value.slice(2, 4);
                    }
                    e.target.value = value;
                });
            }
        });
    </script>
     <a href="../../index.php" class="floating-home" title="Go to Home">
        <i class="fas fa-home"></i>
    </a>
</body>
</html>
