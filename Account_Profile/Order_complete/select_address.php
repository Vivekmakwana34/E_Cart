<?php
session_start();

include '../../Database_file/config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

$userId = (int)$_SESSION['user_id'];

$addresses = [];
$addrStmt = $conn->prepare("SELECT id, address_type, address_line1, address_line2, city, state, postal_code, country, phone, is_default FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC");
if ($addrStmt) {
    $addrStmt->bind_param('i', $userId);
    $addrStmt->execute();
    $addrRes = $addrStmt->get_result();
    if ($addrRes) {
        while ($row = $addrRes->fetch_assoc()) {
            $addresses[] = $row;
        }
    }
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

// Keep the same visual model currently used by cart.php: show 30% "savings" and pay 70%.
$regularDiscount = $cartSubtotal * 0.30;
$payableBeforeCoupon = max(0, $cartSubtotal - $regularDiscount);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Address - E_Cart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/index.css">
    <link rel="stylesheet" href="../../floating_home.css">
    <style>
        /* Select Address Page - Flipkart Style */
        .select-address-container {
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

        /* Address Selection Section */
        .address-section {
            background: white;
            border-radius: 2px;
            box-shadow: 0 1px 1px 0 rgba(0,0,0,0.08);
            padding: 24px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid #f0f0f0;
        }

        .section-title {
            font-size: 18px;
            font-weight: 500;
            color: #212121;
        }

        .add-address-btn {
            background: #2874f0;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 500;
            border-radius: 2px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .add-address-btn:hover {
            background: #1e5fb3;
        }

        .address-list {
            display: grid;
            gap: 16px;
        }

        .address-card {
            border: 1px solid #dbdbdb;
            border-radius: 2px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }

        .address-card:hover {
            border-color: #2874f0;
            box-shadow: 0 2px 4px 0 rgba(0,0,0,0.08);
        }

        .address-card.selected {
            border-color: #2874f0;
            background: #f8f9fa;
        }

        .address-card.selected {
            box-shadow: 0 0 0 1px rgba(40, 116, 240, 0.35), 0 2px 4px 0 rgba(0,0,0,0.08);
        }

        .address-card.selected::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: #2874f0;
            border-top-left-radius: 2px;
            border-bottom-left-radius: 2px;
        }

        .address-radio {
            position: absolute;
            top: 20px;
            right: 20px;
        }

        .address-radio input[type="radio"] {
            width: 18px;
            height: 18px;
            accent-color: #2874f0;
        }

        .address-type {
            display: inline-block;
            background: #f1f3f6;
            color: #878787;
            padding: 4px 8px;
            border-radius: 2px;
            font-size: 12px;
            font-weight: 500;
            margin-bottom: 12px;
        }

        .address-name {
            font-size: 16px;
            font-weight: 500;
            color: #212121;
            margin-bottom: 8px;
        }

        .address-text {
            font-size: 14px;
            color: #212121;
            line-height: 1.4;
            margin-bottom: 8px;
        }

        .address-phone {
            font-size: 14px;
            color: #878787;
        }

        .address-actions {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #f0f0f0;
            display: flex;
            gap: 16px;
        }

        .address-action-btn {
            background: none;
            border: none;
            color: #2874f0;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            padding: 0;
        }

        .address-action-btn:hover {
            text-decoration: underline;
        }

        .address-action-btn.delete {
            color: #ff6161;
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

        .continue-btn {
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
            position: relative;
            z-index: 1;
        }

        .continue-btn:hover {
            background: #e85a0f;
        }

        .continue-btn:disabled {
            background: #dbdbdb;
            color: #878787;
            cursor: not-allowed;
            pointer-events: none;
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

        /* Add Address Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 2px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 18px;
            font-weight: 500;
            color: #212121;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            color: #878787;
            cursor: pointer;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-body {
            padding: 24px;
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

        .address-type-selector {
            display: flex;
            gap: 16px;
            margin-bottom: 20px;
        }

        .type-option {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .type-option input[type="radio"] {
            accent-color: #2874f0;
        }

        .type-option label {
            font-size: 14px;
            color: #212121;
            cursor: pointer;
        }

        .modal-footer {
            padding: 20px 24px;
            border-top: 1px solid #f0f0f0;
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        .modal-btn {
            padding: 12px 24px;
            font-size: 14px;
            font-weight: 500;
            border-radius: 2px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .modal-btn.cancel {
            background: white;
            border: 1px solid #dbdbdb;
            color: #212121;
        }

        .modal-btn.cancel:hover {
            background: #f8f9fa;
        }

        .modal-btn.save {
            background: #fb641b;
            border: none;
            color: white;
        }

        .modal-btn.save:hover {
            background: #e85a0f;
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

            .address-actions {
                flex-direction: column;
                gap: 8px;
            }
        }
    </style>
</head>
<body>

   <?php include '../../Header.php'; ?>

    <!-- Select Address Main Content -->
    <main class="select-address-container">
        <div class="page-header">
            <h1 class="page-title">Select Delivery Address</h1>
        </div>

        <div class="checkout-container">
            <!-- Address Selection Section -->
            <section class="address-section">
                <div class="section-header">
                    <h2 class="section-title">Saved Addresses</h2>
                </div>

                <div class="address-list">
                    <?php if (empty($addresses)): ?>
                        <div style="padding: 14px; color: #878787; font-size: 14px;">
                            No saved addresses found. Please add an address in your profile.
                        </div>
                    <?php else: ?>
                        <?php foreach ($addresses as $idx => $addr): ?>
                            <?php
                                $type = strtoupper((string)($addr['address_type'] ?? 'HOME'));
                                $line1 = trim((string)($addr['address_line1'] ?? ''));
                                $line2 = trim((string)($addr['address_line2'] ?? ''));
                                $city = trim((string)($addr['city'] ?? ''));
                                $state = trim((string)($addr['state'] ?? ''));
                                $postal = trim((string)($addr['postal_code'] ?? ''));
                                $country = trim((string)($addr['country'] ?? 'India'));
                                $phone = trim((string)($addr['phone'] ?? ''));
                                $isDefault = (int)($addr['is_default'] ?? 0) === 1;
                                $addressText = $line1;
                                if ($line2 !== '') $addressText .= ', ' . $line2;
                                $addressText .= ', ' . $city . ', ' . $state;
                                if ($postal !== '') $addressText .= ' - ' . $postal;
                                if ($country !== '') $addressText .= ', ' . $country;
                            ?>
                            <div class="address-card" onclick="selectAddress(event, this)">
                                <div class="address-radio">
                                    <input type="radio" name="address" value="<?php echo (int)$addr['id']; ?>" <?php echo $idx === 0 || $isDefault ? 'checked' : ''; ?>>
                                </div>
                                <div class="address-type"><?php echo htmlspecialchars($type); ?></div>
                                <div class="address-name">Delivery Address</div>
                                <div class="address-text">
                                    <?php echo nl2br(htmlspecialchars($addressText)); ?>
                                </div>
                                <?php if ($phone !== ''): ?>
                                    <div class="address-phone"><?php echo htmlspecialchars($phone); ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
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

                    <button type="button" class="continue-btn" id="continueBtn">
                        CONTINUE
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
        // Server-calculated totals to avoid mismatch
        const SERVER_CART_SUBTOTAL = <?php echo json_encode((float)$cartSubtotal); ?>;
        const SERVER_REGULAR_DISCOUNT = <?php echo json_encode((float)$regularDiscount); ?>;
        const SERVER_PAYABLE_BEFORE_COUPON = <?php echo json_encode((float)$payableBeforeCoupon); ?>;

        let currentCouponDiscount = 0;

        // Address Selection
        function selectAddress(e, card) {
            if (e && e.target && (e.target.tagName === 'INPUT' || e.target.closest('input[type="radio"]'))) {
                enableContinueIfAddressSelected();
                return;
            }
            // Remove selected class from all cards
            document.querySelectorAll('.address-card').forEach(c => {
                c.classList.remove('selected');
            });
            
            // Add selected class to clicked card
            card.classList.add('selected');
            
            // Check the radio button
            const radio = card.querySelector('input[type="radio"]');
            radio.checked = true;
            
            // Enable continue button
            enableContinueIfAddressSelected();
        }

        function enableContinueIfAddressSelected() {
            const selected = document.querySelector('input[name="address"]:checked');
            const btn = document.getElementById('continueBtn');
            if (btn) {
                const isDisabled = !selected;
                btn.setAttribute('aria-disabled', isDisabled ? 'true' : 'false');
                btn.disabled = isDisabled;
            }
        }   

        function syncSelectedCardWithCheckedRadio() {
            const selected = document.querySelector('input[name="address"]:checked');
            document.querySelectorAll('.address-card').forEach(c => c.classList.remove('selected'));
            if (!selected) return;
            const card = selected.closest('.address-card');
            if (card) {
                card.classList.add('selected');
            }
        }

        // Continue to Payment
        function continueToPayment() {
            const selectedAddress = document.querySelector('input[name="address"]:checked');
            if (!selectedAddress) {
                return;
            }

            // Store selected address in sessionStorage
            sessionStorage.setItem('selectedAddress', selectedAddress.value);
            
            const couponDiscount = currentCouponDiscount || 0;
            
            // Store pricing information in sessionStorage
            const priceData = {
                originalTotal: Math.round(SERVER_CART_SUBTOTAL),
                discount: Math.round(SERVER_REGULAR_DISCOUNT),
                couponDiscount: couponDiscount,
                deliveryCharges: 0,
                finalTotal: Math.max(0, Math.round(SERVER_PAYABLE_BEFORE_COUPON) - couponDiscount)
            };
            sessionStorage.setItem('priceData', JSON.stringify(priceData));

            // Redirect to payment page (force)
            window.location.assign('payment.php');
        }

        // Load pricing data from cart
        function loadPricingData() {
            // Always use server-calculated values - clear any stale sessionStorage
            const serverPriceData = {
                originalTotal: Math.round(SERVER_CART_SUBTOTAL),
                discount: Math.round(SERVER_REGULAR_DISCOUNT),
                couponDiscount: 0,
                deliveryCharges: 0,
                finalTotal: Math.round(SERVER_PAYABLE_BEFORE_COUPON)
            };
            
            // Update sessionStorage with fresh server data
            sessionStorage.setItem('priceData', JSON.stringify(serverPriceData));
            updatePriceDisplay(serverPriceData);
        }

        // Check for price updates from cart
        function checkForPriceUpdates() {
            const priceData = sessionStorage.getItem('priceData');
            if (priceData) {
                const data = JSON.parse(priceData);
                
                // Validate that session data matches server data
                if (data.originalTotal !== Math.round(SERVER_CART_SUBTOTAL)) {
                    // Data mismatch - reload with server values
                    loadPricingData();
                    return;
                }
                
                // Update all price displays
                const cartPriceElement = document.getElementById('cartPrice');
                const totalAmountElement = document.getElementById('totalAmount');
                
                if (cartPriceElement) {
                    cartPriceElement.textContent = `₹${data.originalTotal}`;
                }
                
                if (totalAmountElement) {
                    totalAmountElement.textContent = `₹${data.finalTotal}`;
                }
                
                // Update coupon discount if exists
                if (data.couponDiscount > 0) {
                    updateCouponDiscount(data.couponDiscount);
                }
                
                // Force update the total amount display
                updateTotalAmount(data.finalTotal);
            }
        }
        
        // Force update total amount
        function updateTotalAmount(finalTotal) {
            const totalElement = document.getElementById('totalAmount');
            if (totalElement) {
                totalElement.textContent = `₹${finalTotal}`;
                // Also update the total class for styling
                totalElement.className = 'price-value total';
                totalElement.style.color = '#2874f0';
                totalElement.style.fontSize = '18px';
                totalElement.style.fontWeight = '700';
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
                currentCouponDiscount = parseInt(priceData.couponDiscount);
            } else {
                if (couponDiscountRow) couponDiscountRow.style.display = 'none';
                currentCouponDiscount = 0;
            }
            
            // Update total amount
            if (totalAmount) {
                totalAmount.textContent = `₹${priceData.finalTotal}`;
            }
        }

        // Update coupon discount display
        function updateCouponDiscount(discount) {
            const couponDiscountRow = document.getElementById('couponDiscountRow');
            const couponDiscountAmount = document.getElementById('couponDiscountAmount');
            const totalAmount = document.getElementById('totalAmount');
            
            if (discount > 0) {
                couponDiscountRow.style.display = 'flex';
                couponDiscountAmount.textContent = `-₹${discount}`;
                const newTotal = Math.max(0, Math.round(SERVER_PAYABLE_BEFORE_COUPON) - discount);
                totalAmount.textContent = `₹${newTotal}`;
            } else {
                couponDiscountRow.style.display = 'none';
                // Reset total to original without coupon
                totalAmount.textContent = `₹${Math.round(SERVER_PAYABLE_BEFORE_COUPON)}`;
            }
        }

        // Initialize button state early
        document.addEventListener('DOMContentLoaded', () => {
            enableContinueIfAddressSelected();
            syncSelectedCardWithCheckedRadio();

            const btn = document.getElementById('continueBtn');
            if (btn) {
                btn.addEventListener('click', function (e) {
                    continueToPayment();
                });
            }

            document.querySelectorAll('input[name="address"]').forEach(radio => {
                radio.addEventListener('change', () => {
                    syncSelectedCardWithCheckedRadio();
                    enableContinueIfAddressSelected();
                });
            });
        });

        // Load pricing data when page loads
        window.addEventListener('load', () => {
            loadPricingData();

            // Enable continue button if an address is already selected (default/first)
            syncSelectedCardWithCheckedRadio();
            enableContinueIfAddressSelected();

            // Also enable when radio changes (keyboard / direct click)
            document.querySelectorAll('input[name="address"]').forEach(radio => {
                radio.addEventListener('change', () => {
                    syncSelectedCardWithCheckedRadio();
                    enableContinueIfAddressSelected();
                });
            });
            
            // Additional check for price updates
            setTimeout(() => {
                forcePriceUpdate();
            }, 100);
        });

        // Force price update
        function forcePriceUpdate() {
            const priceData = sessionStorage.getItem('priceData');
            if (priceData) {
                const data = JSON.parse(priceData);
                
                // Update all price elements with force
                const cartPriceElement = document.getElementById('cartPrice');
                const totalAmountElement = document.getElementById('totalAmount');
                const couponDiscountRow = document.getElementById('couponDiscountRow');
                const couponDiscountAmount = document.getElementById('couponDiscountAmount');
                
                console.log('Force updating prices:', data);
                
                // Update cart price
                if (cartPriceElement) {
                    cartPriceElement.textContent = `₹${data.originalTotal}`;
                }
                
                // Update coupon discount
                if (data.couponDiscount > 0) {
                    if (couponDiscountRow) {
                        couponDiscountRow.style.display = 'flex';
                    }
                    if (couponDiscountAmount) {
                        couponDiscountAmount.textContent = `-₹${data.couponDiscount}`;
                    }
                } else {
                    if (couponDiscountRow) {
                        couponDiscountRow.style.display = 'none';
                    }
                }
                
                // Update total amount - FORCE UPDATE
                if (totalAmountElement) {
                    totalAmountElement.textContent = `₹${data.finalTotal}`;
                    totalAmountElement.style.color = '#2874f0';
                    totalAmountElement.style.fontWeight = '700';
                    totalAmountElement.style.fontSize = '18px';
                }
            }
        }

        // Initialize pricing data
        // currentCouponDiscount is declared at top

        // Mobile Menu Toggle
        function toggleMenu() {
            const navLinks = document.getElementById('navLinks');
            navLinks.classList.toggle('active');
        }
    </script>
     <a href="../../index.php" class="floating-home" title="Go to Home">
        <i class="fas fa-home"></i>
    </a>
</body>
</html>
