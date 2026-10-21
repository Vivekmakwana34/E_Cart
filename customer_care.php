<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>24x7 Customer Care - E_Cart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <style>
        .care-container {
            max-width: 1200px;
            margin: 100px auto 50px;
            padding: 0 20px;
        }

        .care-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .care-header h1 {
            font-size: 36px;
            color: #333;
            margin-bottom: 15px;
        }

        .care-header p {
            font-size: 18px;
            color: #666;
            max-width: 700px;
            margin: 0 auto;
            line-height: 1.6;
        }

        .care-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-bottom: 50px;
        }

        .care-card {
            background: white;
            border-radius: 15px;
            padding: 35px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid #f0f0f0;
            position: relative;
            overflow: hidden;
        }

        .care-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #2563eb, #ff6b7a, #2563eb);
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }

        .care-card:hover::before {
            transform: translateX(0);
        }

        .care-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
            border-color: #2563eb;
        }

        .care-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            font-size: 28px;
            color: white;
            position: relative;
        }

        .care-icon::after {
            content: '';
            position: absolute;
            top: -5px;
            right: -5px;
            width: 20px;
            height: 20px;
            background: #ff4757;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: white;
            font-weight: bold;
        }

        .care-card.phone .care-icon::after {
            content: '24/7';
        }

        .care-card h3 {
            font-size: 22px;
            color: #333;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 700;
        }

        .care-card p {
            font-size: 16px;
            color: #666;
            line-height: 1.7;
            text-align: center;
            margin-bottom: 25px;
        }

        .care-action {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .care-button {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 18px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .care-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.2);
            transition: left 0.3s ease;
        }

        .care-button:hover::before {
            left: 0;
        }

        .care-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .care-button i {
            font-size: 18px;
        }

        .care-secondary {
            background: transparent;
            border: 2px solid #667eea;
            color: #667eea;
        }

        .care-secondary:hover {
            background: #667eea;
            color: white;
        }

        .quick-actions {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            border-radius: 15px;
            padding: 40px;
            margin-bottom: 50px;
            text-align: center;
        }

        .quick-actions h2 {
            font-size: 26px;
            color: #333;
            margin-bottom: 30px;
        }

        .quick-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .quick-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px 25px;
            background: white;
            color: #333;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .quick-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
            background: #f8f9fa;
        }

        .quick-btn i {
            font-size: 16px;
        }

        .stats-section {
            background: white;
            border-radius: 15px;
            padding: 40px;
            margin-bottom: 50px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            text-align: center;
        }

        .stat-item {
            padding: 20px;
        }

        .stat-number {
            font-size: 36px;
            font-weight: 700;
            color: #ff4757;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 16px;
            color: #666;
        }

        .contact-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 40px;
            color: white;
            text-align: center;
            margin-bottom: 50px;
        }

        .contact-info h2 {
            font-size: 26px;
            margin-bottom: 25px;
        }

        .contact-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            transition: transform 0.3s ease;
        }

        .contact-item:hover {
            transform: translateY(-2px);
            background: rgba(255, 255, 255, 0.2);
        }

        .contact-item i {
            font-size: 20px;
            width: 30px;
        }

        .contact-text {
            text-align: left;
        }

        .contact-text h4 {
            font-size: 16px;
            margin-bottom: 5px;
        }

        .contact-text p {
            font-size: 14px;
            opacity: 0.9;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .care-container {
                margin: 60px auto 30px;
                padding: 0 15px;
            }

            .care-header h1 {
                font-size: 30px;
            }

            .care-header p {
                font-size: 16px;
            }

            .care-grid {
                grid-template-columns: 1fr;
                gap: 25px;
            }

            .care-card {
                padding: 30px 25px;
            }

            .care-icon {
                width: 60px;
                height: 60px;
                font-size: 24px;
                margin: 0 auto 20px;
            }

            .care-card h3 {
                font-size: 20px;
            }

            .care-card p {
                font-size: 15px;
            }

            .quick-actions {
                padding: 30px 20px;
            }

            .quick-actions h2 {
                font-size: 22px;
            }

            .quick-buttons {
                flex-direction: column;
                align-items: center;
            }

            .quick-btn {
                width: 100%;
                max-width: 280px;
            }

            .stats-section {
                padding: 30px 20px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }

            .contact-info {
                padding: 30px 20px;
            }

            .contact-details {
                grid-template-columns: 1fr;
                gap: 20px;
            }
        }

        @media (max-width: 480px) {
            .care-container {
                margin: 40px auto 20px;
            }

            .care-header h1 {
                font-size: 26px;
            }

            .care-card {
                padding: 25px 20px;
            }

            .care-button {
                padding: 15px 25px;
                font-size: 15px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .contact-item {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- NAVBAR Section -->
    <?php include 'Header.php'; ?>

    <div class="care-container">
        <!-- Customer Care Header -->
        <div class="care-header">
            <h1>24x7 Customer Care</h1>
            <p>We're here to help you 24 hours a day, 7 days a week. Get instant support through multiple channels.</p>
        </div>

        <!-- Main Care Grid -->
        <div class="care-grid">
            <div class="care-card phone">
                <div class="care-icon">
                    <i class="fa-solid fa-phone"></i>
                </div>
                <h3>Call Us</h3>
                <p>Get immediate assistance from our customer support team. Available 24/7 for all your queries.</p>
                <div class="care-action">
                    <a href="tel:+18001234567" class="care-button">
                        <i class="fa-solid fa-phone"></i>
                        Call 1800-123-4567
                    </a>
                    <a href="#" class="care-button care-secondary" onclick="showTollFree()">
                        <i class="fa-solid fa-phone-volume"></i>
                        Toll-Free Numbers
                    </a>
                </div>
            </div>

            <div class="care-card">
                <div class="care-icon">
                    <i class="fa-solid fa-comments"></i>
                </div>
                <h3>Live Chat</h3>
                <p>Chat with our support team in real-time. Get instant answers to your questions.</p>
                <div class="care-action">
                    <a href="#" class="care-button" onclick="startChat()">
                        <i class="fa-solid fa-message"></i>
                        Start Live Chat
                    </a>
                    <a href="#" class="care-button care-secondary" onclick="showChatHistory()">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                        Chat History
                    </a>
                </div>
            </div>

            <div class="care-card">
                <div class="care-icon">
                    <i class="fa-solid fa-envelope"></i>
                </div>
                <h3>Email Support</h3>
                <p>Send us your queries via email. We typically respond within 24 hours.</p>
                <div class="care-action">
                    <a href="mailto:support@ecart.com" class="care-button">
                        <i class="fa-solid fa-paper-plane"></i>
                        Email Us
                    </a>
                    <a href="#" class="care-button care-secondary" onclick="showEmailTemplates()">
                        <i class="fa-solid fa-file-lines"></i>
                        Email Templates
                    </a>
                </div>
            </div>

            <div class="care-card">
                <div class="care-icon">
                    <i class="fa-solid fa-headset"></i>
                </div>
                <h3>OR Support Center</h3>
                <p>Access our comprehensive help center with FAQs, guides, and troubleshooting resources.</p>
                <div class="care-action">
                    <a href="help.php" class="care-button">
                        <i class="fa-solid fa-circle-question"></i>
                        Visit Help Center
                    </a>
                    <a href="#" class="care-button care-secondary" onclick="showVideoTutorials()">
                        <i class="fa-solid fa-video"></i>
                        Video Tutorials
                    </a>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <h2>Quick Actions</h2>
            <div class="quick-buttons">
                <a href="#" class="quick-btn" onclick="trackOrder()">
                    <i class="fa-solid fa-truck"></i>
                    Track Order
                </a>
                <a href="#" class="quick-btn" onclick="cancelOrder()">
                    <i class="fa-solid fa-xmark"></i>
                    Cancel Order
                </a>
                <a href="#" class="quick-btn" onclick="returnItem()">
                    <i class="fa-solid fa-rotate-left"></i>
                    Return Item
                </a>
                <a href="#" class="quick-btn" onclick="reportIssue()">
                    <i class="fa-solid fa-flag"></i>
                    Report Issue
                </a>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-section">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number">24/7</div>
                    <div class="stat-label">Availability</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">< 1min</div>
                    <div class="stat-label">Avg Response Time</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">98%</div>
                    <div class="stat-label">Satisfaction Rate</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">50K+</div>
                    <div class="stat-label">Daily Customers</div>
                </div>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="contact-info">
            <h2>Get in Touch</h2>
            <div class="contact-details">
                <div class="contact-item">
                    <i class="fa-solid fa-phone"></i>
                    <div class="contact-text">
                        <h4>Customer Care</h4>
                        <p>1800-123-4567</p>
                    </div>
                </div>
                <div class="contact-item">
                    <i class="fa-solid fa-phone-volume"></i>
                    <div class="contact-text">
                        <h4>Toll-Free</h4>
                        <p>1800-ECART-01</p>
                    </div>
                </div>
                <div class="contact-item">
                    <i class="fa-solid fa-envelope"></i>
                    <div class="contact-text">
                        <h4>Email Support</h4>
                        <p>support@ecart.com</p>
                    </div>
                </div>
                <div class="contact-item">
                    <i class="fa-solid fa-clock"></i>
                    <div class="contact-text">
                        <h4>Working Hours</h4>
                        <p>24/7 - Always Available</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'Footer.php'; ?>

    <script>
        function startChat() {
            // Simulate chat opening
            const chatWindow = window.open('', 'chat', 'width=400,height=600,scrollbars=yes,resizable=yes');
            chatWindow.document.write(`
                <html>
                <head><title>Live Chat - E_Cart</title></head>
                <body style="font-family: Arial, sans-serif; padding: 20px; text-align: center;">
                    <h3>🗨️ Live Chat Support</h3>
                    <p>Welcome to E_Cart Live Chat!</p>
                    <p>Our support team will be with you shortly.</p>
                    <p>Typical wait time: <strong>Less than 1 minute</strong></p>
                    <hr>
                    <p><small>This is a demo chat window. In production, this would connect to our live chat system.</small></p>
                </body>
                </html>
            `);
        }

        function showTollFree() {
            alert('Toll-Free Numbers:\\n\\n📞 Customer Care: 1800-ECART-01\\n📞 Technical Support: 1800-TECH-02\\n📞 Order Support: 1800-ORDER-03\\n\\nAvailable 24/7 for all regions!');
        }

        function showChatHistory() {
            alert('Chat History feature coming soon! You will be able to view all your past conversations with our support team.');
        }

        function showEmailTemplates() {
            alert('Email Templates feature coming soon! Quick templates for common queries like order issues, returns, refunds, etc.');
        }

        function showVideoTutorials() {
            alert('Video Tutorials library coming soon! Step-by-step guides for using E_Cart features.');
        }

        function trackOrder() {
            const orderNumber = prompt('Please enter your order number:');
            if (orderNumber) {
                alert(`Tracking order ${orderNumber}:\\n\\n📦 Status: In Transit\\n🚚 Expected Delivery: 2-3 business days\\n📍 Current Location: ${Math.random() > 0.5 ? 'Mumbai Hub' : 'Delhi Hub'}`);
            }
        }

        function cancelOrder() {
            const orderNumber = prompt('Please enter your order number to cancel:');
            if (orderNumber) {
                const confirm = confirm(`Are you sure you want to cancel order ${orderNumber}?`);
                if (confirm) {
                    alert(`Order ${orderNumber} has been cancelled. Refund will be processed within 5-7 business days.`);
                }
            }
        }

        function returnItem() {
            const orderNumber = prompt('Please enter your order number:');
            if (orderNumber) {
                alert(`Return initiated for order ${orderNumber}.\\n\\n📦 Return pickup will be scheduled within 24 hours.\\n💰 Refund will be processed after quality check.`);
            }
        }

        function reportIssue() {
            const issues = [
                'Order not delivered',
                'Wrong item received',
                'Damaged product',
                'Payment issue',
                'Website problem',
                'Other'
            ];
            const issue = prompt('What issue are you facing?\\n\\n1. Order not delivered\\n2. Wrong item received\\n3. Damaged product\\n4. Payment issue\\n5. Website problem\\n6. Other');
            if (issue) {
                alert(`Issue reported: ${issues[parseInt(issue) - 1] || 'Other'}\\n\\n📝 Ticket ID: #${Math.floor(Math.random() * 100000)}\\n⏰ Response time: Within 2 hours`);
            }
        }

        // Add some interactive animations
        document.addEventListener('DOMContentLoaded', function() {
            // Animate stats on scroll
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.animation = 'fadeIn 0.6s ease forwards';
                    }
                });
            });

            document.querySelectorAll('.stat-item').forEach(item => {
                observer.observe(item);
            });
        });
    </script>
</body>
</html>
