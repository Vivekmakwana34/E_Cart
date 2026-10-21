<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ - E_Cart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="floating_home.css">

    <style>
        body {
            background-color: #f1f2f4;
            margin: 0;
            padding: 0;
        }

        .faq-main {
            padding: 26px 16px 40px;
            min-height: calc(100vh - 200px);
        }

        .faq-wrapper {
            max-width: 1200px;
            margin: 0 auto;
        }

        .faq-header {
            background: linear-gradient(135deg, #1d4ed8, #020617);
            color: white;
            padding: 40px 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
        }

        .faq-header h1 {
            font-size: 32px;
            margin: 0 0 10px 0;
            font-weight: 600;
        }

        .faq-header p {
            font-size: 16px;
            margin: 0;
            opacity: 0.9;
        }

        .faq-search {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }

        .search-container {
            position: relative;
            max-width: 600px;
            margin: 0 auto;
        }

        .search-input {
            width: 100%;
            padding: 15px 50px 15px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 16px;
            outline: none;
            transition: border-color 0.3s ease;
        }

        .search-input:focus {
            border-color: #1d4ed8;
        }

        .search-btn {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            background: #1d4ed8;
            border: none;
            color: white;
            padding: 10px 15px;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .search-btn:hover {
            background: #1e40af;
        }

        .faq-categories {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .category-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .category-card:hover {
            border-color: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .category-card.active {
            background: #1d4ed8;
            color: white;
            border-color: #1d4ed8;
        }

        .category-icon {
            font-size: 24px;
            margin-bottom: 10px;
            color: #1d4ed8;
        }

        .category-card.active .category-icon {
            color: white;
        }

        .category-title {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .category-count {
            font-size: 12px;
            opacity: 0.7;
        }

        .faq-content {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .faq-section {
            margin-bottom: 30px;
        }

        .faq-section:last-child {
            margin-bottom: 0;
        }

        .section-title {
            font-size: 20px;
            color: #1d4ed8;
            margin-bottom: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            font-size: 18px;
        }

        .faq-item {
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 0;
        }

        .faq-item:last-child {
            border-bottom: none;
        }

        .faq-question {
            padding: 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: background-color 0.3s ease;
            user-select: none;
        }

        .faq-question:hover {
            background-color: #f8fafc;
        }

        .faq-question.active {
            background-color: #eff6ff;
            color: #1d4ed8;
        }

        .question-text {
            font-weight: 500;
            flex: 1;
            margin-right: 15px;
        }

        .faq-toggle {
            font-size: 14px;
            transition: transform 0.3s ease;
        }

        .faq-question.active .faq-toggle {
            transform: rotate(180deg);
        }

        .faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease, padding 0.3s ease;
            background-color: #f8fafc;
        }

        .faq-answer.active {
            max-height: 500px;
            padding: 20px;
            border-top: 1px solid #e5e7eb;
        }

        .answer-text {
            color: #4b5563;
            line-height: 1.6;
        }

        .help-section {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border-radius: 12px;
            padding: 30px;
            margin-top: 30px;
            text-align: center;
        }

        .help-section h3 {
            font-size: 24px;
            margin-bottom: 15px;
        }

        .help-section p {
            font-size: 16px;
            margin-bottom: 20px;
            opacity: 0.9;
        }

        .help-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .help-btn {
            background: white;
            color: #10b981;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .help-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .popular-questions {
            background: #fef3c7;
            border: 1px solid #fbbf24;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .popular-questions h3 {
            color: #d97706;
            margin: 0 0 15px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .popular-questions h3 i {
            font-size: 16px;
        }

        .popular-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .popular-list li {
            padding: 8px 0;
            color: #92400e;
        }

        .popular-list li a {
            color: #d97706;
            text-decoration: none;
            font-weight: 500;
        }

        .popular-list li a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .faq-header {
                padding: 30px 20px;
            }

            .faq-header h1 {
                font-size: 24px;
            }

            .faq-content {
                background: white;
                border-radius: 12px;
                padding: 30px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            }

            .faq-section {
                margin-bottom: 30px;
            }

            .faq-section:last-child {
                margin-bottom: 0;
            }

            .section-title {
                font-size: 20px;
                color: #1d4ed8;
                margin-bottom: 20px;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .section-title i {
                font-size: 18px;
            }

            .faq-item {
                border-bottom: 1px solid #e5e7eb;
                margin-bottom: 0;
            }

            .faq-item:last-child {
                border-bottom: none;
            }

            .faq-question {
                padding: 20px;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: space-between;
                transition: background-color 0.3s ease;
                user-select: none;
            }

            .faq-question:hover {
                background-color: #f8fafc;
            }

            .faq-question.active {
                background-color: #eff6ff;
                color: #1d4ed8;
            }

            .question-text {
                font-weight: 500;
                flex: 1;
                margin-right: 15px;
            }

            .faq-toggle {
                font-size: 14px;
                transition: transform 0.3s ease;
            }

            .faq-question.active .faq-toggle {
                transform: rotate(180deg);
            }

            .faq-answer {
                max-height: 0;
                overflow: hidden;
                transition: max-height 0.3s ease, padding 0.3s ease;
                background-color: #f8fafc;
            }

            .faq-answer.active {
                max-height: 500px;
                padding: 20px;
                border-top: 1px solid #e5e7eb;
            }

            .answer-text {
                color: #4b5563;
                line-height: 1.6;
            }

            .help-section {
                background: linear-gradient(135deg, #10b981, #059669);
                color: white;
                border-radius: 12px;
                padding: 30px;
                margin-top: 30px;
                text-align: center;
            }

            .help-section h3 {
                font-size: 24px;
                margin-bottom: 15px;
            }

            .help-section p {
                font-size: 16px;
                margin-bottom: 20px;
                opacity: 0.9;
            }

            .help-buttons {
                display: flex;
                justify-content: center;
                gap: 15px;
                flex-wrap: wrap;
            }

            .help-btn {
                background: white;
                color: #10b981;
                padding: 12px 24px;
                border-radius: 8px;
                text-decoration: none;
                font-weight: 600;
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }

            .help-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            }

            .popular-questions {
                background: #fef3c7;
                border: 1px solid #fbbf24;
                border-radius: 8px;
                padding: 20px;
                margin-bottom: 30px;
            }

            .popular-questions h3 {
                color: #d97706;
                margin: 0 0 15px 0;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .popular-questions h3 i {
                font-size: 16px;
            }

            .popular-list {
                list-style: none;
                padding: 0;
                margin: 0;
            }

            .popular-list li {
                padding: 8px 0;
                color: #92400e;
            }

            .popular-list li a {
                color: #d97706;
                text-decoration: none;
                font-weight: 500;
            }

            .popular-list li a:hover {
                text-decoration: underline;
            }

            @media (max-width: 768px) {
                .faq-header {
                    padding: 30px 20px;
                }

                .faq-header h1 {
                    font-size: 24px;
                }

                .faq-content {
                    padding: 20px;
                }

                .faq-categories {
                    grid-template-columns: repeat(2, 1fr);
                }

                .help-buttons {
                    flex-direction: column;
                }

                .help-btn {
                    text-align: center;
                }
            }

            /* Quick Help Tools Styles */
            .quick-help-tools {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
                margin-top: 30px;
            }

            .help-tool-card {
                background: white;
                border: 2px solid #e5e7eb;
                border-radius: 12px;
                padding: 25px;
                text-align: center;
                transition: all 0.3s ease;
                cursor: pointer;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            }

            .help-tool-card:hover {
                border-color: #10b981;
                transform: translateY(-3px);
                box-shadow: 0 8px 25px rgba(16, 185, 129, 0.15);
            }

            .tool-icon {
                width: 60px;
                height: 60px;
                background: linear-gradient(135deg, #10b981, #059669);
                color: white;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 15px;
                font-size: 24px;
            }

            .tool-content h4 {
                color: #1f2937;
                font-size: 18px;
                margin-bottom: 8px;
                font-weight: 600;
            }

            .tool-content p {
                color: #6b7280;
                font-size: 14px;
                line-height: 1.5;
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
                max-width: 600px;
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
        }
    </style>

<body>
    <?php include 'Header.php'; ?>

    <main class="faq-main">
        <div class="faq-wrapper">
            <!-- Header Section -->
            <div class="faq-header">
                <h1><i class="fas fa-question-circle"></i> Frequently Asked Questions</h1>
                <p>Find answers to common questions about E_Cart</p>
            </div>

            <!-- Search Section -->
            <div class="faq-search">
                <div class="search-container">
                    <input type="text" class="search-input" placeholder="Search for answers..." id="faqSearch">
                    <button class="search-btn" onclick="searchFAQ()">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>

            <!-- Popular Questions -->
            <div class="popular-questions">
                <h3><i class="fas fa-fire"></i> Popular Questions</h3>
                <ul class="popular-list">
                    <li><a href="#" onclick="scrollToFAQ('shipping')">How long does shipping take?</a></li>
                    <li><a href="#" onclick="scrollToFAQ('returns')">What is your return policy?</a></li>
                    <li><a href="#" onclick="scrollToFAQ('payment')">What payment methods are accepted?</a></li>
                    <li><a href="#" onclick="scrollToFAQ('account')">How do I create an account?</a></li>
                </ul>
            </div>

            <!-- FAQ Categories -->
            <div class="faq-categories">
                <div class="category-card active" onclick="filterFAQ('all')">
                    <div class="category-icon"><i class="fas fa-th"></i></div>
                    <div class="category-title">All Topics</div>
                    <div class="category-count">24 Questions</div>
                </div>
                <div class="category-card" onclick="filterFAQ('account')">
                    <div class="category-icon"><i class="fas fa-user"></i></div>
                    <div class="category-title">Account</div>
                    <div class="category-count">6 Questions</div>
                </div>
                <div class="category-card" onclick="filterFAQ('orders')">
                    <div class="category-icon"><i class="fas fa-shopping-bag"></i></div>
                    <div class="category-title">Orders</div>
                    <div class="category-count">8 Questions</div>
                </div>
                <div class="category-card" onclick="filterFAQ('payment')">
                    <div class="category-icon"><i class="fas fa-credit-card"></i></div>
                    <div class="category-title">Payment</div>
                    <div class="category-count">5 Questions</div>
                </div>
                <div class="category-card" onclick="filterFAQ('shipping')">
                    <div class="category-icon"><i class="fas fa-truck"></i></div>
                    <div class="category-title">Shipping</div>
                    <div class="category-count">5 Questions</div>
                </div>
            </div>

            <!-- FAQ Content -->
            <div class="faq-content">
                <!-- Account Section -->
                <div class="faq-section" data-category="account">
                    <h2 class="section-title" id="account">
                        <i class="fas fa-user"></i> Account & Registration
                    </h2>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            <span class="question-text">How do I create an account on E_Cart?</span>
                            <span class="faq-toggle"><i class="fas fa-chevron-down"></i></span>
                        </div>
                        <div class="faq-answer">
                            <div class="answer-text">
                                Creating an account on E_Cart is simple and free. Click on the "Login" button in the top right corner, then select "Register". Fill in your details including name, email, and password. You'll receive a verification email - click the link to verify your account and start shopping!
                            </div>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            <span class="question-text">I forgot my password. How can I reset it?</span>
                            <span class="faq-toggle"><i class="fas fa-chevron-down"></i></span>
                        </div>
                        <div class="faq-answer">
                            <div class="answer-text">
                                Click on "Login" and then "Forgot Password". Enter your registered email address. We'll send you a password reset link. Click the link in the email and create a new password. Make sure to choose a strong password with at least 6 characters including uppercase and lowercase letters.
                            </div>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            <span class="question-text">How do I update my profile information?</span>
                            <span class="faq-toggle"><i class="fas fa-chevron-down"></i></span>
                        </div>
                        <div class="faq-answer">
                            <div class="answer-text">
                                Log in to your account and go to "My Profile" from the user menu. Click on "Edit" to update your personal information, contact details, and preferences. Don't forget to save your changes before exiting.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Orders Section -->
                <div class="faq-section" data-category="orders">
                    <h2 class="section-title" id="orders">
                        <i class="fas fa-shopping-bag"></i> Orders & Shopping
                    </h2>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            <span class="question-text">How do I place an order?</span>
                            <span class="faq-toggle"><i class="fas fa-chevron-down"></i></span>
                        </div>
                        <div class="faq-answer">
                            <div class="answer-text">
                                Browse our products, select the items you want, and click "Add to Cart". Once you're ready, go to your cart, review your items, and click "Proceed to Checkout". Enter your delivery address, select a payment method, and confirm your order. You'll receive an order confirmation via email.
                            </div>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            <span class="question-text">How can I track my order?</span>
                            <span class="faq-toggle"><i class="fas fa-chevron-down"></i></span>
                        </div>
                        <div class="faq-answer">
                            <div class="answer-text">
                                Go to "My Orders" in your account dashboard. Click on the order you want to track. You'll see real-time updates including order confirmation, processing, shipping, and delivery status. You'll also receive SMS and email notifications at each stage.
                            </div>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            <span class="question-text">Can I cancel or modify my order?</span>
                            <span class="faq-toggle"><i class="fas fa-chevron-down"></i></span>
                        </div>
                        <div class="faq-answer">
                            <div class="answer-text">
                                You can cancel or modify your order within 1 hour of placing it. Go to "My Orders" and select the order you want to cancel. If the order hasn't been processed yet, you'll see the option to cancel. For modifications, please contact our customer support team immediately.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Section -->
                <div class="faq-section" data-category="payment">
                    <h2 class="section-title" id="payment">
                        <i class="fas fa-credit-card"></i> Payment & Pricing
                    </h2>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            <span class="question-text">What payment methods are accepted?</span>
                            <span class="faq-toggle"><i class="fas fa-chevron-down"></i></span>
                        </div>
                        <div class="faq-answer">
                            <div class="answer-text">
                                We accept multiple payment methods including: Credit Cards (Visa, MasterCard, American Express), Debit Cards, Net Banking, UPI, Wallets (Paytm, PhonePe, Amazon Pay), and Cash on Delivery (available for orders below ₹5000).
                            </div>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            <span class="question-text">Is it safe to use my credit/debit card on E_Cart?</span>
                            <span class="faq-toggle"><i class="fas fa-chevron-down"></i></span>
                        </div>
                        <div class="faq-answer">
                            <div class="answer-text">
                                Yes, absolutely! We use industry-standard SSL encryption to protect your payment information. Our payment gateway is PCI DSS compliant, and we never store your card details on our servers. Your transactions are 100% secure.
                            </div>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            <span class="question-text">What is Cash on Delivery (COD)?</span>
                            <span class="faq-toggle"><i class="fas fa-chevron-down"></i></span>
                        </div>
                        <div class="faq-answer">
                            <div class="answer-text">
                                Cash on Delivery allows you to pay for your order when it arrives at your doorstep. COD is available for orders up to ₹5000. Please note that COD orders may require phone verification and some PIN codes may not be eligible for COD.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Shipping Section -->
                <div class="faq-section" data-category="shipping">
                    <h2 class="section-title" id="shipping">
                        <i class="fas fa-truck"></i> Shipping & Delivery
                    </h2>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            <span class="question-text">How long does shipping take?</span>
                            <span class="faq-toggle"><i class="fas fa-chevron-down"></i></span>
                        </div>
                        <div class="faq-answer">
                            <div class="answer-text">
                                Standard delivery takes 4-7 business days, while express delivery takes 2-3 business days. Delivery times may vary based on your location and product availability. You'll get an estimated delivery date when you place your order.
                            </div>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            <span class="question-text">What are the shipping charges?</span>
                            <span class="faq-toggle"><i class="fas fa-chevron-down"></i></span>
                        </div>
                        <div class="faq-answer">
                            <div class="answer-text">
                                Shipping is free for orders above ₹500. For orders below ₹500, standard shipping charges of ₹40 apply. Express delivery charges are ₹80 for orders above ₹500 and ₹120 for orders below ₹500.
                            </div>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            <span class="question-text">Can I change my delivery address after placing an order?</span>
                            <span class="faq-toggle"><i class="fas fa-chevron-down"></i></span>
                        </div>
                        <div class="faq-answer">
                            <div class="answer-text">
                                You can change your delivery address within 2 hours of placing an order, provided it hasn't been shipped yet. Contact our customer support team immediately with your order number and new address details.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Returns Section -->
                <div class="faq-section" data-category="returns">
                    <h2 class="section-title" id="returns">
                        <i class="fas fa-undo"></i> Returns & Refunds
                    </h2>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            <span class="question-text">What is your return policy?</span>
                            <span class="faq-toggle"><i class="fas fa-chevron-down"></i></span>
                        </div>
                        <div class="faq-answer">
                            <div class="answer-text">
                                We offer a 10-day return policy from the date of delivery. Items must be unused, in original condition, with all tags and packaging intact. Some items like personal care products, undergarments, and perishables cannot be returned. Visit our Returns Policy page for detailed information.
                            </div>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            <span class="question-text">How do I initiate a return?</span>
                            <span class="faq-toggle"><i class="fas fa-chevron-down"></i></span>
                        </div>
                        <div class="faq-answer">
                            <div class="answer-text">
                                Go to "My Orders", select the order and item you want to return, and click "Return Item". Select a return reason, provide details, and schedule a pickup. Our delivery partner will collect the item from your address. Once we receive and inspect the item, your refund will be processed.
                            </div>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            <span class="question-text">When will I receive my refund?</span>
                            <span class="faq-toggle"><i class="fas fa-chevron-down"></i></span>
                        </div>
                        <div class="faq-answer">
                            <div class="answer-text">
                                Refunds are typically processed within 5-7 business days after we receive the returned item. The refund will be credited to your original payment method. For COD orders, refunds are processed to your bank account or UPI ID.
                            </div>
                </div>
            </div>

            <!-- Help Section -->
            <div class="help-section">
                <h3><i class="fas fa-headset"></i> Still Need Help?</h3>
                <p>Can't find what you're looking for? Our customer support team is here to help!</p>
                <div class="help-buttons">
                    <a href="contact.php" class="help-btn">
                        <i class="fas fa-envelope"></i> Contact Support
                    </a>
                    <a href="#" class="help-btn" onclick="openLiveChat()">
                        <i class="fas fa-comments"></i> Live Chat
                    </a>
                    <a href="tel:1800-123-4567" class="help-btn">
                        <i class="fas fa-phone"></i> Call Us
                    </a>
                </div>

                <!-- New Features: Quick Help Tools -->
                <div class="quick-help-tools">
                    <div class="help-tool-card" onclick="openHelpAssistant()">
                        <div class="tool-icon">
                            <i class="fas fa-robot"></i>
                        </div>
                        <div class="tool-content">
                            <h4>AI Help Assistant</h4>
                            <p>Get instant answers from our AI assistant</p>
                        </div>
                    </div>
                    
                    <div class="help-tool-card" onclick="openVideoGuide()">
                        <div class="tool-icon">
                            <i class="fas fa-video"></i>
                        </div>
                        <div class="tool-content">
                            <h4>Video Guides</h4>
                            <p>Watch step-by-step video tutorials</p>
                        </div>
                    </div>
                    
                    <div class="help-tool-card" onclick="openTicketSystem()">
                        <div class="tool-icon">
                            <i class="fas fa-ticket"></i>
                        </div>
                        <div class="tool-content">
                            <h4>Support Ticket</h4>
                            <p>Create a support ticket for complex issues</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

   <?php include "Footer.php"; ?>

    <script>
        // Toggle FAQ Answer
        function toggleFAQ(element) {
            const answer = element.nextElementSibling;
            const allQuestions = document.querySelectorAll('.faq-question');
            const allAnswers = document.querySelectorAll('.faq-answer');
            
            // Close all other FAQs
            allQuestions.forEach(q => {
                if (q !== element) {
                    q.classList.remove('active');
                }
            });
            
            allAnswers.forEach(a => {
                if (a !== answer) {
                    a.classList.remove('active');
                }
            });
            
            // Toggle current FAQ
            element.classList.toggle('active');
            answer.classList.toggle('active');
        }

        // Filter FAQ by Category
        function filterFAQ(category) {
            const sections = document.querySelectorAll('.faq-section');
            const cards = document.querySelectorAll('.category-card');
            
            // Update active card
            cards.forEach(card => card.classList.remove('active'));
            event.target.closest('.category-card').classList.add('active');
            
            // Show/hide sections
            sections.forEach(section => {
                if (category === 'all' || section.dataset.category === category) {
                    section.style.display = 'block';
                } else {
                    section.style.display = 'none';
                }
            });
        }

        // Search FAQ
        function searchFAQ() {
            const searchTerm = document.getElementById('faqSearch').value.toLowerCase();
            const faqItems = document.querySelectorAll('.faq-item');
            
            faqItems.forEach(item => {
                const question = item.querySelector('.question-text').textContent.toLowerCase();
                const answer = item.querySelector('.answer-text').textContent.toLowerCase();
                
                if (question.includes(searchTerm) || answer.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        // Scroll to specific FAQ section
        function scrollToFAQ(sectionId) {
            const section = document.getElementById(sectionId);
            if (section) {
                section.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        // Search on Enter key
        document.getElementById('faqSearch').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchFAQ();
            }
        });

        // Additional Functions for New Features
        function openLiveChat() {
            alert('Live chat feature would open here. This would connect to a live chat system.');
        }

        function openHelpAssistant() {
            alert('AI Help Assistant would open here. This would connect to an AI chatbot system.');
        }

        function openVideoGuide() {
            alert('Video Guide Library would open here. This would show a collection of tutorial videos.');
        }

        function openTicketSystem() {
            alert('Support Ticket System would open here. This would allow users to create and track support tickets.');
        }
    </script>

    <script src="assets/js/index.js"></script>
</body>

</html>