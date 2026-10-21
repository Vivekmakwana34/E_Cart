<?php
// Footer.php - Professional Footer Component for E_Cart
// Usage: include 'Footer.php';
?>
<footer class="site-footer">
    <div class="footer-inner">
        <div class="footer-columns">
            <div class="footer-column">
                <div class="footer-brand">E_<span>Cart</span></div>
                <p class="footer-description">Your one-stop destination for mobiles, fashion, electronics, home essentials and more. Fresh deals, everyday.</p>
                <div class="footer-socials">
                    <a href="#" aria-label="E_Cart on Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#" aria-label="E_Cart on Instagram"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#" aria-label="E_Cart on Twitter"><i class="fa-brands fa-twitter"></i></a>
                    <a href="#" aria-label="E_Cart on YouTube"><i class="fa-brands fa-youtube"></i></a>
                </div>
            </div>

            <div class="footer-column">
                <h4>HELP</h4>
                <?php
                // Determine correct paths based on current file location
                $base_path = ''; // Default for root level
                if (strpos($_SERVER['PHP_SELF'], '/Account_Profile/') !== false || strpos($_SERVER['PHP_SELF'], '/Product_Pages/') !== false) {
                    $base_path = '../../';
                }
                ?>
                <a href="<?php echo $base_path; ?>Account_Profile/Payments/payments.php">Payments</a>
                <a href="<?php echo $base_path; ?>shipping.php">Shipping</a>  
                <a href="<?php echo $base_path; ?>return.php">Returns</a>
                <a href="<?php echo $base_path; ?>faq.php">FAQ</a>
            </div>

            <div class="footer-column">
                <h4>COMPANY</h4>
                <a href="<?php echo $base_path; ?>about_us.php">About Us</a>
                <a href="<?php echo $base_path; ?>contact.php">Contact</a>
                <a href="<?php echo $base_path; ?>blog.php">Blog</a>
                <a href="<?php echo $base_path; ?>press.php">Press</a>
            </div>

            <div class="footer-column">
                <h4>STAY UPDATED</h4>
                <p class="footer-newsletter-text">Subscribe to get updates on exclusive offers and new launches.</p>
                <form class="footer-newsletter-form">
                    <input type="email" placeholder="Enter your email" aria-label="Email address">
                    <button type="button">Subscribe</button>
                </form>
                <p class="footer-newsletter-note">No spam. Only curated offers.</p>
            </div>  
        </div>

        <div class="footer-extra">
            <div class="footer-extra-item">
                <i class="fa-solid fa-headset"></i>
                <div>
                    <h5>24x7 Support</h5>
                    <p>Need help? Chat with us or email care@e_cart.com</p>
                </div>
            </div>
            <div class="footer-extra-item">
                <i class="fa-solid fa-rotate-left"></i>
                <div>
                    <h5>Easy Returns</h5>
                    <p>10-day replacement on most items</p>
                </div>
            </div>
            <div class="footer-extra-item">
                <i class="fa-solid fa-shield-halved"></i>
                <div>
                    <h5>Secure Payments</h5>
                    <p>100% secure payments with leading banks & wallets</p>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="footer-bottom-left">
                <span>&copy; 2026 E_Cart. All rights reserved.</span>
            </div>
            <div class="footer-bottom-links">
                <a href="#">Terms of Use</a>
                <a href="#">Privacy Policy</a>
                <a href="<?php echo $base_path; ?>return.php">Returns Policy</a>
            </div>
            <div class="footer-payments">
                <i class="fa-brands fa-cc-visa"></i>
                <i class="fa-brands fa-cc-mastercard"></i>
                <i class="fa-brands fa-cc-amex"></i>
                <i class="fa-brands fa-cc-paypal"></i>
            </div>
        </div>
    </div>
</footer>
