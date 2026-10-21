<?php
session_start();

// Database connection
$conn = new mysqli('localhost', 'root', '', 'e_cart');
$successMsg = '';
$errorMsg = '';

if ($conn->connect_error) {
    $errorMsg = "Database connection failed!";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_contact'])) {
    $full_name = trim($_POST['contactName'] ?? '');
    $email     = trim($_POST['contactEmail'] ?? '');
    $phone     = trim($_POST['contactPhone'] ?? '');
    $order_id  = trim($_POST['contactOrderId'] ?? '');
    $topic     = trim($_POST['contactType'] ?? '');
    $subject   = trim($_POST['contactSubject'] ?? '');
    $message   = trim($_POST['contactMessage'] ?? '');
    $consent   = isset($_POST['contactAgree']) ? 1 : 0;
    $ip        = $_SERVER['REMOTE_ADDR'] ?? '';

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMsg = "Please enter a valid email address.";
    } elseif (!empty($full_name) && !empty($email) && !empty($message)) {
        $stmt = $conn->prepare("INSERT INTO contact_messages 
            (full_name, email, phone, order_id, topic, subject, message, consent, ip_address, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'new', NOW())");
        $stmt->bind_param("sssssssss",
            $full_name, $email, $phone, $order_id,
            $topic, $subject, $message, $consent, $ip
        );
        if ($stmt->execute()) {
            $successMsg = "Thank you! Your message has been sent successfully.";
        } else {
            $errorMsg = "Error sending message. Please try again.";
        }
        $stmt->close();
    } else {
        $errorMsg = "Please fill in all required fields (Name, Email, Message).";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - E_Cart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/contact.css">
    <style>
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>

     <?php include 'Header.php'; ?>

    <main class="contact-main">
        <section class="contact-hero">
            <div class="contact-hero-text">
                <h1>Contact Us</h1>
                <p>
                    Have a question about your order, payments, or products?
                    Send us a message and our support team will get back to you shortly.
                </p>
                <div class="contact-hero-highlights">
                    <div class="contact-highlight-item">
                        <i class="fa-solid fa-headset"></i>
                        <div>
                            <h3>24x7 Support</h3>
                            <p>Reach us any time for help with your shopping.</p>
                        </div>
                    </div>
                    <div class="contact-highlight-item">
                        <i class="fa-solid fa-bolt"></i>
                        <div>
                            <h3>Fast Response</h3>
                            <p>We usually reply within a few hours.</p>
                        </div>
                    </div>
                    <div class="contact-highlight-item">
                        <i class="fa-solid fa-shield-heart"></i>
                        <div>
                            <h3>Secure Help</h3>
                            <p>Your details are kept private and safe.</p>
                        </div>
                    </div>
                    <div class="contact-highlight-item">
                        <i class="fa-solid fa-rotate-left"></i>
                        <div>
                            <h3>Easy Returns</h3>
                            <p>Quick, hassle-free replacements on most items.</p>
                        </div>
                    </div>
                </div>

                <div class="contact-details-section">
                    <div class="contact-detail-card">
                        <i class="fa-solid fa-envelope"></i>
                        <h3>Email Support</h3>
                        <p>care@e_cart.com</p>
                        <span>We usually respond within a few hours.</span>
                    </div>
                    <div class="contact-detail-card">
                        <i class="fa-solid fa-phone"></i>
                        <h3>Call Us</h3>
                        <p>+91-91065-36420</p>
                        <span>Mon-Sat, 9:00 AM - 8:00 PM</span>
                    </div>
                    <div class="contact-detail-card">
                        <i class="fa-solid fa-location-dot"></i>
                        <h3>Office Address</h3>
                        <p>E_Cart HQ, Rajkot, Gujarat, India</p>
                        <span>Visit us by prior appointment only.</span>
                    </div>
                </div>
            </div>

            <div class="contact-card">
                <div class="contact-card-header">
                    <div class="contact-card-header-icon">
                        <i class="fa-solid fa-envelope-open-text"></i>
                    </div>
                    <div>
                        <h2>Send us a message</h2>
                        <p>Tell us a bit about your issue and we'll reach out.</p>
                    </div>
                </div>

                <div class="contact-card-body">
                    <?php if ($successMsg): ?>
                        <div class="alert alert-success"><?php echo $successMsg; ?></div>
                    <?php endif; ?>
                    <?php if ($errorMsg): ?>
                        <div class="alert alert-error"><?php echo $errorMsg; ?></div>
                    <?php endif; ?>

                    <form id="contactForm" class="contact-form contact-form-modern" method="POST" action="" novalidate>
                        <div class="contact-form-grid">
                            <div class="contact-form-group">
                                <label for="contactName">Full Name</label>
                                <input type="text" id="contactName" name="contactName" placeholder="Enter your name" required>
                            </div>
                            <div class="contact-form-group">
                                <label for="contactEmail">Email</label>
                                <input type="email" id="contactEmail" name="contactEmail" placeholder="you@example.com" required>
                            </div>
                        </div>

                        <div class="contact-form-grid">
                            <div class="contact-form-group">
                                <label for="contactPhone">Phone (optional)</label>
                                <input type="tel" id="contactPhone" name="contactPhone" placeholder="+91-XXXXXXXXXX">
                            </div>
                            <div class="contact-form-group">
                                <label for="contactOrderId">Order ID (optional)</label>
                                <input type="text" id="contactOrderId" name="contactOrderId" placeholder="If your query is about an order">
                            </div>
                        </div>

                        <div class="contact-form-group contact-tight">
                            <label for="contactType">Topic</label>
                            <select id="contactType" name="contactType">
                                <option value="">Select a topic</option>
                                <option value="order">Order / Delivery</option>
                                <option value="payment">Payment / Refund</option>
                                <option value="product">Product details</option>
                                <option value="account">Account / Login</option>
                                <option value="other">Something else</option>
                            </select>
                        </div>

                        <div class="contact-form-group contact-tight contact-subject-group">
                            <label for="contactSubject">Subject</label>
                            <input type="text" id="contactSubject" name="contactSubject" placeholder="Short summary of your issue">
                        </div>

                        <div class="contact-form-group contact-message-group contact-tight">
                            <label for="contactMessage">Message</label>
                            <textarea id="contactMessage" name="contactMessage" rows="3" maxlength="500" placeholder="Write your message here..." required></textarea>
                            <div class="contact-message-counter" id="contactMessageCounter">0 / 500</div>
                        </div>

                        <div class="contact-form-footer">
                            <label class="contact-checkbox">
                                <input type="checkbox" id="contactAgree" name="contactAgree">
                                <span>I agree to be contacted on this email address.</span>
                            </label>

                            <button type="submit" name="submit_contact" class="contact-submit-btn">
                                <i class="fa-regular fa-paper-plane"></i>
                                Send Message
                            </button>
                        </div>

                        <div id="contactFormStatus" class="contact-form-status" aria-live="polite"></div>
                    </form>
                </div>
            </div>
        </section>
    </main>
    
  <?php include "Footer.php"; ?>

    <script src="assets/js/index.js"></script>
    <!-- contact.js hataya - PHP directly database me save karega -->
    <script>
        // Sirf character counter
        const msg = document.getElementById('contactMessage');
        const counter = document.getElementById('contactMessageCounter');
        if (msg && counter) {
            msg.addEventListener('input', () => {
                counter.textContent = msg.value.length + ' / 500';
            });
        }
    </script>
</body>
</html>