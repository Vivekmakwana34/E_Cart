<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help & Support - E_Cart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <style>
        .help-container {
            max-width: 1200px;
            margin: 100px auto 50px;
            padding: 0 20px;
            background-color: #f3f4f6;
        }

        .help-hero {
            max-width: 1200px;
            margin: 0 auto 50px;
            display: flex;
            flex-wrap: wrap;
            gap: 32px;
            align-items: center;
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .help-hero-text {
            flex: 1 1 320px;
        }

        .help-hero-text h1 {
            font-size: 32px;
            margin-bottom: 12px;
            color: #111827;
        }

        .help-hero-text p {
            font-size: 15px;
            color: #4b5563;
            line-height: 1.6;
        }

        .help-hero-tags {
            margin-top: 16px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .help-hero-tags span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            padding: 6px 10px;
            border-radius: 999px;
            background-color: #e5f0ff;
            color: #1d4ed8;
        }

        .help-hero-stats {
            flex: 1 1 260px;
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
        }

        .help-stat-card {
            flex: 1 1 120px;
            text-align: center;
            padding: 20px 10px;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .help-stat-number {
            font-size: 24px;
            font-weight: 700;
            color: #1d4ed8;
            margin-bottom: 4px;
        }

        .help-stat-card p {
            font-size: 12px;
            color: #64748b;
            margin: 0;
        }

        .help-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 50px;
        }

        .help-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
            border: 1px solid #f0f0f0;
        }

        .help-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
            border-color: #2563eb;
        }

        .help-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 24px;
            color: white;
        }

        .help-card h3 {
            font-size: 20px;
            color: #333;
            margin-bottom: 15px;
            text-align: center;
        }

        .help-card p {
            font-size: 14px;
            color: #666;
            line-height: 1.6;
            text-align: center;
            margin-bottom: 20px;
        }

        .help-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #ff4757;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .help-link:hover {
            color: #e74c3c;
        }

        .faq-section {
            background: white;
            border-radius: 12px;
            padding: 40px;
            margin-bottom: 50px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .faq-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .faq-header h2 {
            font-size: 28px;
            color: #333;
            margin-bottom: 10px;
        }

        .faq-item {
            border-bottom: 1px solid #f0f0f0;
            padding: 20px 0;
        }

        .faq-item:last-child {
            border-bottom: none;
        }

        .faq-question {
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
            transition: color 0.3s ease;
        }

        .faq-question:hover {
            color: #ff4757;
        }

        .faq-icon {
            font-size: 14px;
            transition: transform 0.3s ease;
        }

        .faq-item.active .faq-icon {
            transform: rotate(180deg);
        }

        .faq-answer {
            font-size: 14px;
            color: #666;
            line-height: 1.6;
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .faq-item.active .faq-answer {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .contact-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            padding: 40px;
            color: white;
            text-align: center;
            margin-bottom: 50px;
        }

        .contact-section h2 {
            font-size: 28px;
            margin-bottom: 20px;
        }

        .contact-section p {
            font-size: 16px;
            margin-bottom: 30px;
            opacity: 0.9;
        }

        .contact-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .contact-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px 25px;
            background: white;
            color: #333;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .contact-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .help-container {
                margin: 50px auto 30px;
                padding: 0 15px;
            }

            .help-header h1 {
                font-size: 28px;
            }

            .help-header p {
                font-size: 14px;
            }

            .help-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .help-card {
                padding: 25px 20px;
            }

            .help-icon {
                width: 50px;
                height: 50px;
                font-size: 20px;
                margin: 0 auto 15px;
            }

            .help-card h3 {
                font-size: 18px;
            }

            .help-card p {
                font-size: 13px;
            }

            .faq-section {
                padding: 30px 20px;
            }

            .faq-header h2 {
                font-size: 24px;
            }

            .faq-question {
                font-size: 15px;
            }

            .faq-answer {
                font-size: 13px;
            }

            .contact-section {
                padding: 30px 20px;
            }

            .contact-section h2 {
                font-size: 24px;
            }

            .contact-section p {
                font-size: 14px;
            }

            .contact-buttons {
                flex-direction: column;
                align-items: center;
            }

            .contact-btn {
                width: 100%;
                max-width: 250px;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .help-container {
                margin: 30px auto 20px;
            }

            .help-header h1 {
                font-size: 24px;
            }

            .help-card {
                padding: 20px 15px;
            }

            .faq-section {
                padding: 25px 15px;
            }

            .contact-section {
                padding: 25px 15px;
            }
        }
    </style>
</head>
<body>
    <!-- NAVBAR Section -->
    <?php include 'Header.php'; ?>

    <div class="help-container">
        <!-- Help Hero Section -->
        <div class="help-hero">
            <div class="help-hero-text">
                <h1>How Can We Help You?</h1>
                <p>Find answers to common questions or get in touch with our support team. We're here to assist you 24/7.</p>
                <div class="help-hero-tags">
                    <span><i class="fa-solid fa-circle-check"></i> 24/7 Support</span>
                    <span><i class="fa-solid fa-bolt"></i> Quick Response</span>
                    <span><i class="fa-solid fa-shield-halved"></i> Secure Help</span>
                </div>
            </div>

            <div class="help-hero-stats">
                <div class="help-stat-card">
                    <h2 class="help-stat-number">98%</h2>
                    <p>Satisfaction Rate</p>
                </div>
                <div class="help-stat-card">
                    <h2 class="help-stat-number">< 1min</h2>
                    <p>Response Time</p>
                </div>
                <div class="help-stat-card">
                    <h2 class="help-stat-number">24/7</h2>
                    <p>Availability</p>
                </div>
            </div>
        </div>

        <!-- Help Grid -->
        <div class="help-grid">
            <div class="help-card" onclick="window.location.href='#faq'">
                <div class="help-icon">
                    <i class="fa-solid fa-circle-question"></i>
                </div>
                <h3>Frequently Asked Questions</h3>
                <p>Find answers to the most common questions about orders, payments, returns, and more.</p>
                <a href="#faq" class="help-link">
                    View FAQs
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>

            <div class="help-card" onclick="window.location.href='mailto:support@ecart.com'">
                <div class="help-icon">
                    <i class="fa-solid fa-envelope"></i>
                </div>
                <h3>Email Support</h3>
                <p>Get help via email. We typically respond within 24 hours.</p>
                <a href="mailto:support@ecart.com" class="help-link">
                    Email Us
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>

            <div class="help-card" onclick="window.location.href='tel:+18001234567'">
                <div class="help-icon">
                    <i class="fa-solid fa-phone"></i>
                </div>
                <h3>Phone Support</h3>
                <p>Call our customer support team for immediate assistance.</p>
                <a href="tel:+18001234567" class="help-link">
                    Call Now
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>

            <div class="help-card" onclick="window.location.href='#contact'">
                <div class="help-icon">
                    <i class="fa-solid fa-comments"></i>
                </div>
                <h3>Live Chat</h3>
                <p>Chat with our support team in real-time for quick answers.</p>
                <a href="#contact" class="help-link">
                    Start Chat
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>

            <div class="help-card" onclick="window.location.href='#contact'">
                <div class="help-icon">
                    <i class="fa-solid fa-headset"></i>
                </div>
                <h3>OR Customer Care</h3>
                <p>Get 24/7 support through multiple channels - phone, email, or live chat.</p>
                <a href="customer_care.php" class="help-link">
                    Visit Customer Care
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>

            <div class="help-card" onclick="window.location.href='#track'">
                <div class="help-icon">
                    <i class="fa-solid fa-truck"></i>
                </div>
                <h3>Track Order</h3>
                <p>Check the status of your orders and get real-time delivery updates.</p>
                <a href="#track" class="help-link">
                    Track Order
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>

            <div class="help-card" onclick="window.location.href='#returns'">
                <div class="help-icon">
                    <i class="fa-solid fa-rotate-left"></i>
                </div>
                <h3>Returns & Refunds</h3>
                <p>Learn about our return policy and initiate returns or refunds.</p>
                <a href="#returns" class="help-link">
                    Learn More
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
        </div>

        <!-- FAQ Section -->
        <div class="faq-section" id="faq">
            <div class="faq-header">
                <h2>Frequently Asked Questions</h2>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>How do I track my order?</span>
                    <i class="fa-solid fa-chevron-down faq-icon"></i>
                </div>
                <div class="faq-answer">
                    <p>You can track your order by clicking on the "Track Order" option in the help menu above, or by logging into your account and viewing your order history. Enter your order ID to get real-time updates on your delivery status.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>What is your return policy?</span>
                    <i class="fa-solid fa-chevron-down faq-icon"></i>
                </div>
                <div class="faq-answer">
                    <p>We offer a 30-day return policy for most items. Products must be unused and in original packaging. Simply initiate a return from your account or contact our support team for assistance.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>How do I cancel an order?</span>
                    <i class="fa-solid fa-chevron-down faq-icon"></i>
                </div>
                <div class="faq-answer">
                    <p>You can cancel an order within 1 hour of placing it. Go to your order history and select the order you want to cancel. If the order has already been processed, please contact our support team immediately.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>What payment methods do you accept?</span>
                    <i class="fa-solid fa-chevron-down faq-icon"></i>
                </div>
                <div class="faq-answer">
                    <p>We accept all major credit/debit cards, UPI, net banking, wallets, and cash on delivery. All payments are secure and encrypted.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>How long does delivery take?</span>
                    <i class="fa-solid fa-chevron-down faq-icon"></i>
                </div>
                <div class="faq-answer">
                    <p>Standard delivery takes 3-5 business days. Express delivery takes 1-2 business days. Delivery times may vary based on your location and product availability.</p>
                </div>
            </div>
        </div>

        <!-- Contact Section -->
        <div class="contact-section" id="contact">
            <h2>Still Need Help?</h2>
            <p>Our customer support team is available 24/7 to assist you with any questions or concerns.</p>
            <div class="contact-buttons">
                <a href="mailto:support@ecart.com" class="contact-btn">
                    <i class="fa-solid fa-envelope"></i>
                    Email Support
                </a>
                <a href="tel:+18001234567" class="contact-btn">
                    <i class="fa-solid fa-phone"></i>
                    Call 1800-123-4567
                </a>
                <a href="#" class="contact-btn" onclick="startChat()">
                    <i class="fa-solid fa-comments"></i>
                    Start Live Chat
                </a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'Footer.php'; ?>

    <script>
        function toggleFaq(element) {
            const faqItem = element.parentElement;
            const isActive = faqItem.classList.contains('active');
            
            // Close all FAQ items
            document.querySelectorAll('.faq-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Open clicked item if it wasn't active
            if (!isActive) {
                faqItem.classList.add('active');
            }
        }

        function startChat() {
            // Simulate chat opening
            alert('Live chat feature coming soon! Please email us at support@ecart.com for immediate assistance.');
        }

        // Smooth scroll to sections
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>
