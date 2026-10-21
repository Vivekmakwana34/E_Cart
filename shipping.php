<?php
session_start();
// shipping.php - Shipping Information Page for E_Cart
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipping Information - E_Cart</title>
    <link rel="stylesheet" href="assets/css/index.css">
   
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../../floating_home.css">

    <style>
        body {
            background-color: #f1f2f4;
            margin: 0;
            padding: 0;
        }

        .shipping-main {
            padding: 26px 16px 40px;
            min-height: calc(100vh - 200px);
        }

        .shipping-wrapper {
            max-width: 1200px;
            margin: 0 auto;
        }

        .shipping-header {
            background: linear-gradient(135deg, #1d4ed8, #020617);
            color: white;
            padding: 40px 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .shipping-header-top {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .shipping-header-icon {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: rgba(15, 23, 42, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        .shipping-header h1 {
            font-size: 28px;
            margin: 0;
            font-weight: 600;
        }

        .shipping-header p {
            margin: 0;
            opacity: 0.9;
        }

        .shipping-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }

        .shipping-tag {
            background: rgba(15, 23, 42, 0.4);
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .shipping-grid {
            display: grid;
            grid-template-columns: 2fr 1.3fr;
            gap: 20px;
        }

        .shipping-left,
        .shipping-right {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 22px 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            border: 1px solid #e5e7eb;
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: #111827;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-title i {
            color: #1d4ed8;
        }

        .card-subtitle {
            font-size: 13px;
            color: #6b7280;
        }

        /* Shipping options */
        .shipping-options-list {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
            margin-top: 8px;
        }

        .shipping-option {
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            padding: 12px 14px;
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
        }

        .shipping-option-main {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .shipping-option-name {
            font-weight: 600;
            color: #111827;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .shipping-option-meta {
            font-size: 13px;
            color: #6b7280;
        }

        .shipping-option-badge {
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 999px;
            background: #ecfdf5;
            color: #047857;
            font-weight: 600;
            border: 1px solid #bbf7d0;
        }

        .shipping-option-price {
            text-align: right;
        }

        .shipping-option-price-main {
            font-weight: 600;
            color: #111827;
        }

        .shipping-option-price-sub {
            font-size: 12px;
            color: #6b7280;
        }

        /* PIN code checker */
        .pincode-form {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }

        .pincode-form input[type="text"] {
            padding: 10px 12px;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            flex: 1 1 150px;
            font-size: 14px;
        }

        .pincode-form button {
            padding: 10px 16px;
            border-radius: 8px;
            border: none;
            background: #1d4ed8;
            color: white;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
        }

        .pincode-form button:hover {
            background: #1e40af;
        }

        .pincode-result {
            margin-top: 12px;
            font-size: 14px;
            display: none;
        }

        .pincode-result.success {
            color: #047857;
        }

        .pincode-result.error {
            color: #b91c1c;
        }

        /* Timeline */
        .timeline {
            margin-top: 10px;
        }

        .timeline-step {
            display: flex;
            gap: 10px;
            position: relative;
            padding-left: 26px;
            margin-bottom: 14px;
        }

        .timeline-step:last-child {
            margin-bottom: 0;
        }

        .timeline-marker {
            position: absolute;
            left: 0;
            top: 4px;
            width: 14px;
            height: 14px;
            border-radius: 999px;
            background: #1d4ed8;
        }

        .timeline-line {
            position: absolute;
            left: 6px;
            top: 18px;
            width: 2px;
            height: calc(100% - 18px);
            background: #d1d5db;
        }

        .timeline-title {
            font-size: 14px;
            font-weight: 600;
            color: #111827;
        }

        .timeline-text {
            font-size: 13px;
            color: #6b7280;
        }

        /* Small FAQ */
        .mini-faq-item {
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .mini-faq-item:last-child {
            border-bottom: none;
        }

        .mini-faq-q {
            font-size: 14px;
            font-weight: 500;
            color: #111827;
        }

        .mini-faq-a {
            font-size: 13px;
            color: #6b7280;
            margin-top: 4px;
        }

        /* Badges row */
        .shipping-badges-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 12px;
        }

        .shipping-badge {
            background: #eff6ff;
            color: #1d4ed8;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .shipping-badge i {
            font-size: 13px;
        }

        @media (max-width: 900px) {
            .shipping-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .shipping-header {
                padding: 30px 20px;
            }

            .shipping-header h1 {
                font-size: 22px;
            }

            .card {
                padding: 18px 16px;
            }

            .shipping-options-list {
                gap: 10px;
            }
        }
    </style>
</head>

<body>
    <?php include 'Header.php'; ?>

    <main class="shipping-main">
        <div class="shipping-wrapper">
            <!-- Header -->
            <section class="shipping-header">
                <div class="shipping-header-top">
                    <div class="shipping-header-icon">
                        <i class="fas fa-truck"></i>
                    </div>
                    <div>
                        <h1>Shipping & Delivery</h1>
                        <p>Fast, reliable and safe delivery to your doorstep</p>
                    </div>
                </div>
                <div class="shipping-tags">
                    <span class="shipping-tag"><i class="fas fa-rocket"></i> Express Delivery Available</span>
                    <span class="shipping-tag"><i class="fas fa-indian-rupee-sign"></i> Free shipping on orders above ₹500</span>
                    <span class="shipping-tag"><i class="fas fa-shield-alt"></i> Safe & secure packaging</span>
                </div>
            </section>

            <!-- Content Grid -->
            <section class="shipping-grid">
                <!-- Left Column -->
                <div class="shipping-left">
                    <!-- Shipping Options -->
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <div class="card-title"><i class="fas fa-box-open"></i> Delivery Options</div>
                                <div class="card-subtitle">Choose the shipping option that works best for you</div>
                            </div>
                        </div>

                        <div class="shipping-options-list">
                            <div class="shipping-option">
                                <div class="shipping-option-main">
                                    <div class="shipping-option-name">
                                        Standard Delivery
                                        <span class="shipping-option-badge">Most Popular</span>
                                    </div>
                                    <div class="shipping-option-meta">Delivered in 4-7 business days</div>
                                </div>
                                <div class="shipping-option-price">
                                    <div class="shipping-option-price-main">Free above ₹500</div>
                                    <div class="shipping-option-price-sub">₹40 below ₹500</div>
                                </div>
                            </div>

                            <div class="shipping-option">
                                <div class="shipping-option-main">
                                    <div class="shipping-option-name">
                                        Express Delivery
                                        <span class="shipping-option-badge" style="background:#eef2ff;color:#4f46e5;border-color:#c7d2fe;">Fastest</span>
                                    </div>
                                    <div class="shipping-option-meta">Delivered in 2-3 business days</div>
                                </div>
                                <div class="shipping-option-price">
                                    <div class="shipping-option-price-main">Free above ₹1000</div>
                                    <div class="shipping-option-price-sub">₹80 below ₹1000</div>
                                </div>
                            </div>

                            <div class="shipping-option">
                                <div class="shipping-option-main">
                                    <div class="shipping-option-name">
                                        Same Day Delivery
                                    </div>
                                    <div class="shipping-option-meta">Available in select metro cities for orders before 12 PM</div>
                                </div>
                                <div class="shipping-option-price">
                                    <div class="shipping-option-price-main">₹120 flat</div>
                                    <div class="shipping-option-price-sub">Subject to availability</div>
                                </div>
                            </div>

                            <div class="shipping-option">
                                <div class="shipping-option-main">
                                    <div class="shipping-option-name">
                                        Scheduled Delivery
                                    </div>
                                    <div class="shipping-option-meta">Choose a convenient date & time slot</div>
                                </div>
                                <div class="shipping-option-price">
                                    <div class="shipping-option-price-main">₹50 extra</div>
                                    <div class="shipping-option-price-sub">On top of standard charges</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Process / Timeline -->
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <div class="card-title"><i class="fas fa-route"></i> How Shipping Works</div>
                                <div class="card-subtitle">From order placement to delivery at your doorstep</div>
                            </div>
                        </div>

                        <div class="timeline">
                            <div class="timeline-step">
                                <div class="timeline-marker"></div>
                                <div class="timeline-line"></div>
                                <div>
                                    <div class="timeline-title">Order Placed</div>
                                    <div class="timeline-text">You place your order and receive an instant confirmation via SMS & email.</div>
                                </div>
                            </div>

                            <div class="timeline-step">
                                <div class="timeline-marker"></div>
                                <div class="timeline-line"></div>
                                <div>
                                    <div class="timeline-title">Processing & Packing</div>
                                    <div class="timeline-text">Your items are collected, quality-checked and securely packed.</div>
                                </div>
                            </div>

                            <div class="timeline-step">
                                <div class="timeline-marker"></div>
                                <div class="timeline-line"></div>
                                <div>
                                    <div class="timeline-title">Shipped with Courier Partner</div>
                                    <div class="timeline-text">We hand over your package to our trusted delivery partners.</div>
                                </div>
                            </div>

                            <div class="timeline-step">
                                <div class="timeline-marker"></div>
                                <div>
                                    <div class="timeline-title">Out for Delivery & Delivered</div>
                                    <div class="timeline-text">The delivery agent delivers your package at your doorstep.</div>
                                </div>
                            </div>
                        </div>

                        <div class="shipping-badges-row">
                            <span class="shipping-badge"><i class="fas fa-box"></i> Tamper-proof packaging</span>
                            <span class="shipping-badge"><i class="fas fa-shield-halved"></i> Insured shipments</span>
                            <span class="shipping-badge"><i class="fas fa-undo"></i> Easy returns on most items</span>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="shipping-right">
                    <!-- PIN Code Checker -->
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <div class="card-title"><i class="fas fa-map-marker-alt"></i> Check Delivery Options</div>
                                <div class="card-subtitle">Enter your PIN code to see estimated delivery time</div>
                            </div>
                        </div>

                        <form class="pincode-form" onsubmit="event.preventDefault(); checkPincode();">
                            <input type="text" id="pincodeInput" maxlength="6" placeholder="Enter your 6-digit PIN code">
                            <button type="submit"><i class="fas fa-search"></i> Check</button>
                        </form>

                        <div id="pincodeResult" class="pincode-result"></div>
                    </div>

                    <!-- Coverage Info -->
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <div class="card-title"><i class="fas fa-globe-asia"></i> Delivery Coverage</div>
                                <div class="card-subtitle">We deliver to 18,000+ PIN codes across India</div>
                            </div>
                        </div>
                        <ul style="list-style:none;padding-left:0;margin:0;font-size:14px;color:#4b5563;display:flex;flex-direction:column;gap:8px;">
                            <li><i class="fas fa-circle" style="font-size:6px;margin-right:8px;color:#10b981;"></i> Metro cities: 2-3 business days with express delivery</li>
                            <li><i class="fas fa-circle" style="font-size:6px;margin-right:8px;color:#10b981;"></i> Tier-1 & tier-2 cities: 3-5 business days</li>
                            <li><i class="fas fa-circle" style="font-size:6px;margin-right:8px;color:#10b981;"></i> Remote & rural areas: 5-7 business days</li>
                            <li><i class="fas fa-circle" style="font-size:6px;margin-right:8px;color:#10b981;"></i> International shipping available on select products</li>
                        </ul>
                    </div>

                    <!-- Mini FAQ -->
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <div class="card-title"><i class="fas fa-question-circle"></i> Shipping FAQs</div>
                                <div class="card-subtitle">Quick answers about shipping & delivery</div>
                            </div>
                        </div>
                        <div class="mini-faq-item">
                            <div class="mini-faq-q">Do you ship on weekends?</div>
                            <div class="mini-faq-a">Deliveries are usually done Monday to Saturday. In some cities, Sunday delivery is also available.</div>
                        </div>
                        <div class="mini-faq-item">
                            <div class="mini-faq-q">Will I be charged extra for multiple items?</div>
                            <div class="mini-faq-a">No, shipping charges are calculated on the total order value, not per item.</div>
                        </div>
                        <div class="mini-faq-item">
                            <div class="mini-faq-q">Can I change my delivery address after placing the order?</div>
                            <div class="mini-faq-a">You can request an address change within a few hours of placing the order, before it is shipped.</div>
                        </div>
                        <div style="margin-top:10px;font-size:13px;">
                            <a href="faq.php" style="color:#1d4ed8;text-decoration:none;font-weight:500;">View full FAQ &rarr;</a>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

 
      <?php include "Footer.php"; ?>

    <script>
        function checkPincode() {
            const input = document.getElementById('pincodeInput');
            const result = document.getElementById('pincodeResult');
            const value = input.value.trim();

            if (value.length !== 6 || !/^\d{6}$/.test(value)) {
                result.textContent = 'Please enter a valid 6-digit PIN code.';
                result.className = 'pincode-result error';
                result.style.display = 'block';
                return;
            }

            // Simple demo logic for estimation
            const firstDigit = value.charAt(0);
            let message = '';
            let cls = 'success';

            if (['1', '2', '4'].includes(firstDigit)) {
                message = 'Good news! Delivery available with Express (2-3 days) and Standard (4-5 days).';
            } else if (['5', '6', '7'].includes(firstDigit)) {
                message = 'Delivery available with Standard shipping (4-7 days).';
            } else {
                message = 'Delivery available, but it may take 5-8 business days depending on your location.';
            }

            result.textContent = message;
            result.className = 'pincode-result ' + cls;
            result.style.display = 'block';
        }
    </script>

    <script src="assets/js/index.js"></script>
     <a href="../../index.php" class="floating-home" title="Go to Home">
        <i class="fas fa-home"></i>
    </a>
    
</body>
</html>
