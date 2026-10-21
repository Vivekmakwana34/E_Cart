<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - E_Cart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/about_us.css">
</head>
<body>

  
  <?php include 'Header.php'; ?>

    <main class="about-main">
        <section class="about-hero">
            <div class="about-hero-text">
                <h1>About E_Cart</h1>
                <p>
                    E_Cart is a modern online marketplace built to make shopping simple,
                    reliable and affordable for everyone.
                </p>
                <div class="about-hero-tags">
                    <span><i class="fa-solid fa-circle-check"></i> Trusted Sellers</span>
                    <span><i class="fa-solid fa-truck-fast"></i> Fast Delivery</span>
                    <span><i class="fa-solid fa-shield-halved"></i> Secure Payments</span>
                </div>
            </div>

            <div class="about-hero-stats">
                <div class="about-stat-card">
                    <h2 class="about-stat-number" data-target="5" data-suffix="L+">0</h2>
                    <p>Happy Customers</p>
                </div>
                <div class="about-stat-card">
                    <h2 class="about-stat-number" data-target="50" data-suffix="K+">0</h2>
                    <p>Products Listed</p>
                </div>
                <div class="about-stat-card">
                    <h2 class="about-stat-number" data-target="500" data-suffix="+">0</h2>
                    <p>Partner Sellers</p>
                </div>
            </div>
        </section>

        <section class="about-content">
            <div class="about-section">
                <h2>Our Story</h2>
                <p>
                    Started in Rajkot, Gujarat, E_Cart began as a small idea between friends: 
                    to bring the experience of local markets to everyone's mobile phone.
                    Today we connect thousands of sellers and customers across India with a
                    focus on quality, transparency and support.
                </p>
            </div>

            <div class="about-grid">
                <div class="about-card">
                    <i class="fa-solid fa-bullseye"></i>
                    <h3>Our Mission</h3>
                    <p>
                        To make online shopping effortless for every Indian household with
                        fair prices, fast delivery and friendly support.
                    </p>
                </div>
                <div class="about-card">
                    <i class="fa-solid fa-eye"></i>
                    <h3>Our Vision</h3>
                    <p>
                        To become the most loved destination for discovering and buying
                        everyday essentials and trending products.
                    </p>
                </div>
                <div class="about-card">
                    <i class="fa-solid fa-hands-holding-circle"></i>
                    <h3>What We Value</h3>
                    <p>
                        We care about trust, long-term relationships and a smooth experience
                        from browsing to delivery.
                    </p>
                </div>
            </div>

            <div class="about-section about-highlights">
                <h2>Why shop with E_Cart?</h2>
                <ul>
                    <li><i class="fa-solid fa-check"></i> Wide range of categories from mobiles to home essentials.</li>
                    <li><i class="fa-solid fa-check"></i> Easy returns and clear refund policies.</li>
                    <li><i class="fa-solid fa-check"></i> Multiple secure payment options.</li>
                    <li><i class="fa-solid fa-check"></i> Dedicated support team to help with your orders.</li>
                </ul>
            </div>

            <div class="about-section about-timeline">
                <h2>Our Journey</h2>
                <div class="about-timeline-list">
                    <div class="about-timeline-item">
                        <span class="about-timeline-year">2024</span>
                        <p>Launched E_Cart in Rajkot with a small catalog of electronics and fashion.</p>
                    </div>
                    <div class="about-timeline-item">
                        <span class="about-timeline-year">2025</span>
                        <p>Expanded to home & kitchen, partnered with 200+ sellers across Gujarat.</p>
                    </div>
                    <div class="about-timeline-item">
                        <span class="about-timeline-year">2026</span>
                        <p>Introduced faster deliveries, better tracking and more secure payment options.</p>
                    </div>
                </div>
            </div>

            <div class="about-section about-team">
                <h2>Meet our team</h2>
                <div class="about-team-grid">
                    <div class="about-team-card">
                        <div class="about-team-avatar">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <h3>Rohan Patel</h3>
                        <p class="about-team-role">Founder &amp; Product Lead</p>
                        <p class="about-team-text">Looks after the product experience and ensures shopping stays smooth and simple.</p>
                    </div>
                    <div class="about-team-card">
                        <div class="about-team-avatar">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <h3>Priya Shah</h3>
                        <p class="about-team-role">Operations &amp; Sellers</p>
                        <p class="about-team-text">Works closely with sellers so that orders are packed and shipped on time.</p>
                    </div>
                    <div class="about-team-card">
                        <div class="about-team-avatar">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <h3>Arjun Mehta</h3>
                        <p class="about-team-role">Customer Support Lead</p>
                        <p class="about-team-text">Leads the support team that helps you with payments, returns and queries.</p>
                    </div>
                </div>
            </div>

            <div class="about-section about-testimonials">
                <h2>What our customers say</h2>
                <div class="about-testimonial-grid">
                    <div class="about-testimonial-card">
                        <p class="about-testimonial-text">“Fast delivery and genuine products. I always check E_Cart first.”</p>
                        <p class="about-testimonial-name">Amit, Rajkot</p>
                        <p class="about-testimonial-meta">Mobiles &amp; Accessories</p>
                    </div>
                    <div class="about-testimonial-card">
                        <p class="about-testimonial-text">“Easy returns and helpful support when I had an issue with my order.”</p>
                        <p class="about-testimonial-name">Sneha, Ahmedabad</p>
                        <p class="about-testimonial-meta">Home &amp; Kitchen</p>
                    </div>
                    <div class="about-testimonial-card">
                        <p class="about-testimonial-text">“Good range of products and clear information about delivery and refunds.”</p>
                        <p class="about-testimonial-name">Vivek, Surat</p>
                        <p class="about-testimonial-meta">Fashion &amp; Lifestyle</p>
                    </div>
                </div>
            </div>

            <div class="about-section about-faq">
                <h2>Frequently asked questions</h2>
                <div class="about-faq-list">
                    <div class="about-faq-item">
                        <p class="about-faq-question">How long does delivery take?</p>
                        <p class="about-faq-answer">Most orders are delivered within 2–5 working days depending on your city or town.</p>
                    </div>
                    <div class="about-faq-item">
                        <p class="about-faq-question">What if I receive a damaged product?</p>
                        <p class="about-faq-answer">You can raise a return request from your orders page. Our team will help you with a quick replacement or refund.</p>
                    </div>
                    <div class="about-faq-item">
                        <p class="about-faq-question">Which payment methods are supported?</p>
                        <p class="about-faq-answer">We support UPI, net banking, major debit/credit cards and popular wallets.</p>
                    </div>
                    <div class="about-faq-item">
                        <p class="about-faq-question">Do you offer Cash on Delivery (COD)?</p>
                        <p class="about-faq-answer">COD is available on selected products and pin codes. You will see the COD option on the checkout page whenever it is supported.</p>
                    </div>
                    <div class="about-faq-item">
                        <p class="about-faq-question">How can I track my order?</p>
                        <p class="about-faq-answer">Once your order is shipped, you can track it from your orders section using the tracking link shown next to the order.</p>
                    </div>
                    <div class="about-faq-item">
                        <p class="about-faq-question">How can I contact support?</p>
                        <p class="about-faq-answer">You can reach our support team from the Contact page. Just fill the form on <a href="contact.php">contact.php</a> and we will get back to you.</p>
                    </div>
                </div>
            </div>

            <div class="about-section about-cta">
                <h2>Grow with us</h2>
                <p>
                    Are you a seller or brand who wants to reach more customers?
                    Join E_Cart and grow your business with us.
                </p>
                <a href="#" class="about-cta-button">
                    <i class="fa-solid fa-store"></i>
                    Become a Seller
                </a>
            </div>
        </section>
    </main>

       <?php include "Footer.php"; ?>

    <script src="assets/js/index.js"></script>
    <script src="assets/js/about_us.js"></script>
</body>
</html>
