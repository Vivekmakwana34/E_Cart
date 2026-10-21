<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Return Policy - E_Cart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="floating_home.css">

    <style>
        body {
            background-color: #f1f2f4;
            margin: 0;
            padding: 0;
        }

        .return-main {
            padding: 26px 16px 40px;
            min-height: calc(100vh - 200px);
        }

        .return-wrapper {
            max-width: 1200px;
            margin: 0 auto;
        }

        .return-header {
            background: linear-gradient(135deg, #1d4ed8, #020617);
            color: white;
            padding: 40px 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
        }

        .return-header h1 {
            font-size: 32px;
            margin: 0 0 10px 0;
            font-weight: 600;
        }

        .return-header p {
            font-size: 16px;
            margin: 0;
            opacity: 0.9;
        }

        .return-content {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .return-section {
            margin-bottom: 30px;
        }

        .return-section:last-child {
            margin-bottom: 0;
        }

        .return-section h2 {
            color: #1d4ed8;
            font-size: 20px;
            margin-bottom: 15px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .return-section h2 i {
            font-size: 18px;
        }

        .return-section p {
            color: #4b5563;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .return-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .return-list li {
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .return-list li:last-child {
            border-bottom: none;
        }

        .return-list li i {
            color: #10b981;
            margin-top: 2px;
            font-size: 14px;
        }

        .return-list .content {
            flex: 1;
        }

        .return-list .title {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 4px;
        }

        .return-list .description {
            color: #6b7280;
            font-size: 14px;
            line-height: 1.5;
        }

        .return-steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .step-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .step-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .step-number {
            width: 40px;
            height: 40px;
            background: #1d4ed8;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-weight: 600;
        }

        .step-title {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .step-description {
            color: #6b7280;
            font-size: 14px;
            line-height: 1.5;
        }

        .highlight-box {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }

        .highlight-box h3 {
            color: #1d4ed8;
            margin: 0 0 10px 0;
            font-size: 16px;
        }

        .highlight-box p {
            color: #1e40af;
            margin: 0;
            font-weight: 500;
        }

        .contact-info {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
            text-align: center;
        }

        .contact-info h3 {
            color: #16a34a;
            margin: 0 0 15px 0;
        }

        .contact-methods {
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
        }

        .contact-method {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #16a34a;
            font-weight: 500;
        }

        .contact-method i {
            font-size: 18px;
        }

        /* New Feature Styles */
        .return-features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .feature-card {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .feature-card:hover {
            border-color: #1d4ed8;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(29, 78, 216, 0.15);
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #1d4ed8, #020617);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 24px;
        }

        .feature-content h4 {
            color: #1f2937;
            font-size: 18px;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .feature-content p {
            color: #6b7280;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 15px;
        }

        .feature-btn {
            background: #1d4ed8;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .feature-btn:hover {
            background: #1e40af;
            transform: translateY(-1px);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            position: relative;
            animation: slideIn 0.3s ease;
        }

        .close-modal {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 24px;
            cursor: pointer;
            color: #6b7280;
            transition: color 0.3s ease;
        }

        .close-modal:hover {
            color: #1f2937;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @media (max-width: 768px) {
            .return-header {
                padding: 30px 20px;
            }

            .return-header h1 {
                font-size: 24px;
            }

            .return-content {
                padding: 20px;
            }

            .return-steps {
                grid-template-columns: 1fr;
            }

            .contact-methods {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>

<body>
    <?php include 'Header.php'; ?>

    <main class="return-main">
        <div class="return-wrapper">
            <!-- Header Section -->
            <div class="return-header">
                <h1><i class="fas fa-undo"></i> Return Policy</h1>
                <p>Hassle-free returns within 10 days of delivery</p>
            </div>

            <!-- Main Content -->
            <div class="return-content">
                <!-- Return Period Section -->
                <div class="return-section">
                    <h2><i class="fas fa-clock"></i> Return Period</h2>
                    <p>At E_Cart, we want you to be completely satisfied with your purchase. If you're not happy with your order, you can return it within 10 days of delivery.</p>
                    
                    <div class="highlight-box">
                        <h3>10-Day Return Window</h3>
                        <p>Starts from the date of delivery to your address</p>
                    </div>
                </div>

                <!-- Eligible Items Section -->
                <div class="return-section">
                    <h2><i class="fas fa-check-circle"></i> Eligible Items for Return</h2>
                    <p>Most items sold on E_Cart are eligible for return, except for the following categories:</p>
                    
                    <ul class="return-list">
                        <li>
                            <i class="fas fa-times-circle"></i>
                            <div class="content">
                                <div class="title">Personal Care Products</div>
                                <div class="description">Opened or used cosmetics, grooming products, and personal hygiene items</div>
                            </div>
                        </li>
                        <li>
                            <i class="fas fa-times-circle"></i>
                            <div class="content">
                                <div class="title">Undergarments & Socks</div>
                                <div class="description">For hygiene reasons, these items cannot be returned once opened</div>
                            </div>
                        </li>
                        <li>
                            <i class="fas fa-times-circle"></i>
                            <div class="content">
                                <div class="title">Perishable Items</div>
                                <div class="description">Food items, flowers, and other perishable goods</div>
                            </div>
                        </li>
                        <li>
                            <i class="fas fa-times-circle"></i>
                            <div class="content">
                                <div class="title">Customized Products</div>
                                <div class="description">Items made specifically for you with customizations</div>
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- Return Conditions Section -->
                <div class="return-section">
                    <h2><i class="fas fa-list-check"></i> Return Conditions</h2>
                    <p>To be eligible for a return, please ensure the following conditions are met:</p>
                    
                    <ul class="return-list">
                        <li>
                            <i class="fas fa-check"></i>
                            <div class="content">
                                <div class="title">Original Condition</div>
                                <div class="description">Product must be unused, unwashed, and in original condition</div>
                            </div>
                        </li>
                        <li>
                            <i class="fas fa-check"></i>
                            <div class="content">
                                <div class="title">Original Packaging</div>
                                <div class="description">All original tags, labels, and packaging must be intact</div>
                            </div>
                        </li>
                        <li>
                            <i class="fas fa-check"></i>
                            <div class="content">
                                <div class="title">Proof of Purchase</div>
                                <div class="description">Original invoice or order confirmation must be provided</div>
                            </div>
                        </li>
                        <li>
                            <i class="fas fa-check"></i>
                            <div class="content">
                                <div class="title">No Damage</div>
                                <div class="description">Product should not be damaged or altered in any way</div>
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- How to Return Section -->
                <div class="return-section">
                    <h2><i class="fas fa-box"></i> How to Return an Item</h2>
                    <p>Returning an item is simple and hassle-free. Follow these steps:</p>
                    
                    <div class="return-steps">
                        <div class="step-card">
                            <div class="step-number">1</div>
                            <div class="step-title">Initiate Return</div>
                            <div class="step-description">Go to My Orders and select the item you want to return</div>
                        </div>
                        <div class="step-card">
                            <div class="step-number">2</div>
                            <div class="step-title">Select Reason</div>
                            <div class="step-description">Choose a return reason and provide details</div>
                        </div>
                        <div class="step-card">
                            <div class="step-number">3</div>
                            <div class="step-title">Schedule Pickup</div>
                            <div class="step-description">Our delivery partner will pick up the item from your address</div>
                        </div>
                        <div class="step-card">
                            <div class="step-number">4</div>
                            <div class="step-title">Get Refund</div>
                            <div class="step-description">Refund will be processed after quality check</div>
                        </div>
                    </div>
                </div>

                <!-- Refund Process Section -->
                <div class="return-section">
                    <h2><i class="fas fa-money-bill-wave"></i> Refund Process</h2>
                    <p>Once we receive your returned item, our team will inspect it for quality and condition. The refund process typically takes 5-7 business days.</p>
                    
                    <ul class="return-list">
                        <li>
                            <i class="fas fa-credit-card"></i>
                            <div class="content">
                                <div class="title">Payment Method</div>
                                <div class="description">Refund will be credited to your original payment method</div>
                            </div>
                        </li>
                        <li>
                            <i class="fas fa-shipping-fast"></i>
                            <div class="content">
                                <div class="title">Free Return Shipping</div>
                                <div class="description">Return pickup is free of charge for all eligible items</div>
                            </div>
                        </li>
                        <li>
                            <i class="fas fa-shield-alt"></i>
                            <div class="content">
                                <div class="title">Quality Check</div>
                                <div class="description">Items undergo quality check before refund approval</div>
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- Contact Information -->
                <div class="contact-info">
                    <h3><i class="fas fa-headset"></i> Need Help with Returns?</h3>
                    <p>Our customer support team is here to assist you with any return-related queries.</p>
                    
                    <div class="contact-methods">
                        <div class="contact-method">
                            <i class="fas fa-phone"></i>
                            <span>1800-123-4567</span>
                        </div>
                        <div class="contact-method">
                            <i class="fas fa-envelope"></i>
                            <span>returns@e_cart.com</span>
                        </div>
                        <div class="contact-method">
                            <i class="fas fa-comments"></i>
                            <span>24/7 Chat Support</span>
                        </div>
                    </div>

                    <!-- New Features: Return Calculator & Quick Actions -->
                    <div class="return-features">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-calculator"></i>
                            </div>
                            <div class="feature-content">
                                <h4>Refund Calculator</h4>
                                <p>Calculate your estimated refund amount</p>
                                <button class="feature-btn" onclick="openRefundCalculator()">
                                    Calculate Now
                                </button>
                            </div>
                        </div>
                        
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-truck"></i>
                            </div>
                            <div class="feature-content">
                                <h4>Track Return</h4>
                                <p>Check your return status in real-time</p>
                                <button class="feature-btn" onclick="openReturnTracker()">
                                    Track Return
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

     <?php include "Footer.php"; ?>

    <script src="assets/js/index.js"></script>
    
    <!-- Refund Calculator Modal -->
    <div id="refundCalculatorModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeRefundCalculator()">&times;</span>
            <h3><i class="fas fa-calculator"></i> Refund Calculator</h3>
            <div style="margin-top: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 500;">Order Amount (₹)</label>
                <input type="number" id="orderAmount" placeholder="Enter order amount" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; margin-bottom: 15px;">
                
                <label style="display: block; margin-bottom: 8px; font-weight: 500;">Return Reason</label>
                <select id="returnReason" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; margin-bottom: 15px;">
                    <option value="defective">Defective Product</option>
                    <option value="wrong">Wrong Product</option>
                    <option value="damaged">Damaged in Transit</option>
                    <option value="changed">Changed Mind</option>
                </select>
                
                <div style="background: #f8fafc; padding: 15px; border-radius: 6px; margin-bottom: 15px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span>Order Amount:</span>
                        <span id="displayOrderAmount">₹0</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span>Shipping Charges:</span>
                        <span id="shippingCharges">₹0</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; color: #dc2626;">
                        <span>Restocking Fee:</span>
                        <span id="restockingFee">₹0</span>
                    </div>
                    <hr style="margin: 10px 0;">
                    <div style="display: flex; justify-content: space-between; font-weight: 600; font-size: 18px;">
                        <span>Estimated Refund:</span>
                        <span id="estimatedRefund" style="color: #16a34a;">₹0</span>
                    </div>
                </div>
                
                <button onclick="calculateRefund()" style="width: 100%; background: #1d4ed8; color: white; padding: 12px; border: none; border-radius: 6px; font-weight: 500; cursor: pointer;">
                    Calculate Refund
                </button>
            </div>
        </div>
    </div>
    
    <!-- Return Tracker Modal -->
    <div id="returnTrackerModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeReturnTracker()">&times;</span>
            <h3><i class="fas fa-truck"></i> Track Your Return</h3>
            <div style="margin-top: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 500;">Return ID / Order ID</label>
                <input type="text" id="trackingId" placeholder="Enter Return ID or Order ID" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; margin-bottom: 15px;">
                
                <button onclick="trackReturn()" style="width: 100%; background: #1d4ed8; color: white; padding: 12px; border: none; border-radius: 6px; font-weight: 500; cursor: pointer; margin-bottom: 20px;">
                    Track Return
                </button>
                
                <div id="trackingResult" style="display: none;">
                    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; padding: 15px;">
                        <h4 style="color: #16a34a; margin: 0 0 10px 0;">Return Status: In Transit</h4>
                        <div style="position: relative; padding-left: 30px; margin-bottom: 10px;">
                            <div style="position: absolute; left: 0; top: 0; width: 20px; height: 20px; background: #16a34a; border-radius: 50%;"></div>
                            <div style="font-weight: 500;">Return Initiated</div>
                            <div style="font-size: 12px; color: #6b7280;">Dec 20, 2023 - 10:30 AM</div>
                        </div>
                        <div style="position: relative; padding-left: 30px; margin-bottom: 10px;">
                            <div style="position: absolute; left: 0; top: 0; width: 20px; height: 20px; background: #16a34a; border-radius: 50%;"></div>
                            <div style="font-weight: 500;">Pickup Scheduled</div>
                            <div style="font-size: 12px; color: #6b7280;">Dec 21, 2023 - 2:00 PM</div>
                        </div>
                        <div style="position: relative; padding-left: 30px; margin-bottom: 10px;">
                            <div style="position: absolute; left: 0; top: 0; width: 20px; height: 20px; background: #16a34a; border-radius: 50%;"></div>
                            <div style="font-weight: 500;">Item Picked Up</div>
                            <div style="font-size: 12px; color: #6b7280;">Dec 22, 2023 - 11:00 AM</div>
                        </div>
                        <div style="position: relative; padding-left: 30px;">
                            <div style="position: absolute; left: 0; top: 0; width: 20px; height: 20px; background: #e5e7eb; border-radius: 50%;"></div>
                            <div style="font-weight: 500; color: #9ca3af;">Quality Check Pending</div>
                            <div style="font-size: 12px; color: #9ca3af;">Expected: Dec 23, 2023</div>
                        </div>
                    </div>
                    
                    <div style="margin-top: 15px; padding: 10px; background: #eff6ff; border-radius: 6px;">
                        <div style="font-size: 12px; color: #1e40af;">
                            <strong>Estimated Refund Date:</strong> Dec 25, 2023
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Refund Calculator Functions
        function openRefundCalculator() {
            document.getElementById('refundCalculatorModal').style.display = 'block';
        }
        
        function closeRefundCalculator() {
            document.getElementById('refundCalculatorModal').style.display = 'none';
        }
        
        function calculateRefund() {
            const orderAmount = parseFloat(document.getElementById('orderAmount').value) || 0;
            const returnReason = document.getElementById('returnReason').value;
            
            let shippingCharges = 0;
            let restockingFee = 0;
            
            // Calculate based on return reason
            if (returnReason === 'changed') {
                restockingFee = orderAmount * 0.10; // 10% restocking fee
                shippingCharges = 40; // Return shipping
            } else if (returnReason === 'defective' || returnReason === 'damaged') {
                shippingCharges = 0; // Free return for defective/damaged
                restockingFee = 0;
            } else {
                shippingCharges = 0;
                restockingFee = 0;
            }
            
            const estimatedRefund = orderAmount - shippingCharges - restockingFee;
            
            // Update display
            document.getElementById('displayOrderAmount').textContent = '₹' + orderAmount.toFixed(2);
            document.getElementById('shippingCharges').textContent = '₹' + shippingCharges.toFixed(2);
            document.getElementById('restockingFee').textContent = '₹' + restockingFee.toFixed(2);
            document.getElementById('estimatedRefund').textContent = '₹' + estimatedRefund.toFixed(2);
        }
        
        // Return Tracker Functions
        function openReturnTracker() {
            document.getElementById('returnTrackerModal').style.display = 'block';
        }
        
        function closeReturnTracker() {
            document.getElementById('returnTrackerModal').style.display = 'none';
        }
        
        function trackReturn() {
            const trackingId = document.getElementById('trackingId').value;
            if (trackingId.trim() === '') {
                alert('Please enter a Return ID or Order ID');
                return;
            }
            
            // Show tracking result (mock data)
            document.getElementById('trackingResult').style.display = 'block';
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const refundModal = document.getElementById('refundCalculatorModal');
            const trackerModal = document.getElementById('returnTrackerModal');
            
            if (event.target === refundModal) {
                refundModal.style.display = 'none';
            }
            if (event.target === trackerModal) {
                trackerModal.style.display = 'none';
            }
        }
    </script>
</body>

</html>