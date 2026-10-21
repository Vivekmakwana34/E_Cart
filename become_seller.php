<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Become a Seller - E_Cart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <style>
        /* Become Seller Page - Modern Professional Design */
        .become-seller-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 24px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: calc(100vh - 200px);
            position: relative;
        }

        .become-seller-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%232874f0" fill-opacity="0.03" d="M0,96L48,112C96,128,192,160,288,165.3C384,171,480,149,576,128C672,107,768,85,864,96C960,107,1056,149,1152,154.7C1248,160,1344,128,1392,112L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
            background-size: cover;
            pointer-events: none;
        }

        /* Hero Section - Modern Gradient Style */
        .hero-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            color: #2d3748;
            padding: 80px 40px;
            margin: 40px 0;
            border-radius: 24px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, #667eea, #764ba2, #f093fb, #f5576c);
            opacity: 0.1;
            animation: gradientShift 8s ease-in-out infinite;
        }

        @keyframes gradientShift {
            0%, 100% { transform: rotate(0deg); }
            50% { transform: rotate(180deg); }
        }

        .hero-content {
            max-width: 900px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .hero-section h1 {
            font-size: 56px;
            font-weight: 700;
            margin-bottom: 24px;
            line-height: 1.1;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-section p {
            font-size: 20px;
            margin-bottom: 32px;
            color: #4a5568;
            font-weight: 400;
            line-height: 1.6;
        }

        .cta-button {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 18px 48px;
            font-size: 18px;
            font-weight: 600;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-block;
            text-decoration: none;
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
            position: relative;
            overflow: hidden;
        }

        .cta-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }

        .cta-button:hover::before {
            left: 100%;
        }

        .cta-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 32px rgba(102, 126, 234, 0.4);
        }

        /* Section Headers */
        .section-title {
            font-size: 36px;
            font-weight: 700;
            color: #2d3748;
            margin: 60px 0 40px;
            text-align: center;
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(135deg, #f093fb, #f5576c);
            border-radius: 2px;
        }

        /* Benefits Section - Modern Glass Card Style */
        .benefits-section {
            margin: 60px 0;
        }

        .benefits-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 32px;
        }

        .benefit-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            padding: 40px 32px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.4s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .benefit-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .benefit-card:hover::before {
            transform: scaleX(1);
        }

        .benefit-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .benefit-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 32px;
            color: white;
            box-shadow: 0 8px 16px rgba(102, 126, 234, 0.3);
            transition: all 0.3s ease;
        }

        .benefit-card:hover .benefit-icon {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 12px 24px rgba(102, 126, 234, 0.4);
        }

        .benefit-card h3 {
            font-size: 24px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 16px;
        }

        .benefit-card p {
            color: #4a5568;
            line-height: 1.6;
            font-size: 16px;
        }

        /* Process Section - Modern Timeline Style */
        .process-section {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            padding: 60px 40px;
            margin: 60px 0;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .process-timeline {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            position: relative;
        }

        .process-step {
            text-align: center;
            position: relative;
        }

        .step-number {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #f093fb, #f5576c);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 28px;
            font-weight: 700;
            box-shadow: 0 8px 16px rgba(240, 147, 251, 0.3);
            transition: all 0.3s ease;
        }

        .process-step:hover .step-number {
            transform: scale(1.1);
            box-shadow: 0 12px 24px rgba(240, 147, 251, 0.4);
        }

        .process-step h3 {
            font-size: 20px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 12px;
        }

        .process-step p {
            color: #4a5568;
            line-height: 1.6;
            font-size: 16px;
        }

        /* Stats Section - Modern Counter Style */
        .stats-section {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            padding: 60px 40px;
            margin: 60px 0;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
            text-align: center;
        }

        .stat-item {
            padding: 24px;
            position: relative;
        }

        .stat-number {
            font-size: 48px;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 12px;
            animation: countUp 2s ease-out;
        }

        @keyframes countUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .stat-label {
            font-size: 18px;
            color: #4a5568;
            font-weight: 500;
        }

        /* Testimonials Section - Modern Card Style */
        .testimonials-section {
            margin: 60px 0;
        }

        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 32px;
        }

        .testimonial-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            padding: 40px 32px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        .testimonial-card::before {
            content: '"';
            position: absolute;
            top: -20px;
            left: 20px;
            font-size: 120px;
            color: linear-gradient(135deg, #667eea, #764ba2);
            opacity: 0.1;
            font-family: Georgia, serif;
        }

        .testimonial-text {
            font-size: 18px;
            color: #2d3748;
            line-height: 1.8;
            margin-bottom: 24px;
            font-style: italic;
            position: relative;
            z-index: 1;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .author-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .author-info h4 {
            font-size: 18px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 4px;
        }

        .author-info p {
            font-size: 14px;
            color: #4a5568;
        }

        /* FAQ Section - Modern Accordion Style */
        .faq-section {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            padding: 60px 40px;
            margin: 60px 0;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .faq-container {
            max-width: 900px;
            margin: 0 auto;
        }

        .faq-item {
            background: white;
            border: 1px solid rgba(102, 126, 234, 0.2);
            border-radius: 12px;
            margin-bottom: 16px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .faq-item:hover {
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.1);
        }

        .faq-question {
            padding: 24px 32px;
            font-size: 18px;
            font-weight: 600;
            color: #2d3748;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
            background: white;
        }

        .faq-question:hover {
            background: rgba(102, 126, 234, 0.05);
        }

        .faq-question i {
            transition: transform 0.3s ease;
            color: #667eea;
            font-size: 20px;
        }

        .faq-item.active .faq-question i {
            transform: rotate(180deg);
        }

        .faq-answer {
            padding: 0 32px;
            max-height: 0;
            overflow: hidden;
            transition: all 0.4s ease;
        }

        .faq-item.active .faq-answer {
            padding: 0 32px 24px;
            max-height: 300px;
        }

        .faq-answer p {
            color: #4a5568;
            line-height: 1.8;
            font-size: 16px;
        }

        /* Contact Section - Modern CTA Style */
        .contact-section {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            color: #2d3748;
            padding: 60px 40px;
            text-align: center;
            margin: 60px 0;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .contact-section h2 {
            font-size: 36px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 20px;
        }

        .contact-section p {
            font-size: 18px;
            margin-bottom: 32px;
            color: #4a5568;
            line-height: 1.6;
        }

        .contact-buttons {
            display: flex;
            gap: 24px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .contact-button {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 18px 32px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
        }

        .contact-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 32px rgba(102, 126, 234, 0.4);
        }

        .contact-button.secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
            box-shadow: none;
        }

        .contact-button.secondary:hover {
            background: rgba(102, 126, 234, 0.1);
        }

        /* Categories Section - Modern Grid Style */
        .categories-section {
            margin: 60px 0;
        }

        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
        }

        .category-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            padding: 32px 24px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.4s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .category-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #f093fb, #f5576c);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .category-card:hover::before {
            transform: scaleX(1);
        }

        .category-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .category-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #f093fb, #f5576c);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 28px;
            color: white;
            box-shadow: 0 8px 16px rgba(240, 147, 251, 0.3);
            transition: all 0.3s ease;
        }

        .category-card:hover .category-icon {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 12px 24px rgba(240, 147, 251, 0.4);
        }

        .category-card h3 {
            font-size: 22px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 12px;
        }

        .category-card p {
            color: #4a5568;
            line-height: 1.5;
            font-size: 15px;
            margin-bottom: 16px;
        }

        .category-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 16px;
            border-top: 1px solid rgba(102, 126, 234, 0.1);
        }

        .category-stats span {
            font-size: 13px;
            color: #4a5568;
            font-weight: 500;
        }

        /* Metrics Section - Performance Cards Style */
        .metrics-section {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            padding: 60px 40px;
            margin: 60px 0;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .metrics-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 32px;
        }

        .metric-card {
            text-align: center;
            padding: 32px 24px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05));
            border-radius: 16px;
            border: 1px solid rgba(102, 126, 234, 0.1);
            transition: all 0.3s ease;
        }

        .metric-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.15);
            border-color: rgba(102, 126, 234, 0.2);
        }

        .metric-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 24px;
            color: white;
            box-shadow: 0 6px 12px rgba(102, 126, 234, 0.3);
            transition: all 0.3s ease;
        }

        .metric-card:hover .metric-icon {
            transform: scale(1.1);
            box-shadow: 0 8px 16px rgba(102, 126, 234, 0.4);
        }

        .metric-card h3 {
            font-size: 16px;
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 8px;
        }

        .metric-value {
            font-size: 32px;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
        }

        .metric-card p {
            color: #718096;
            line-height: 1.5;
            font-size: 14px;
        }

        /* Responsive Design - Flipkart Style */
        @media (max-width: 768px) {
            .become-seller-container {
                padding: 0 12px;
            }

            .hero-section {
                padding: 32px 12px;
                margin: 12px 0;
            }

            .hero-section h1 {
                font-size: 28px;
            }

            .hero-section p {
                font-size: 16px;
            }

            .cta-button {
                padding: 14px 28px;
                font-size: 14px;
            }

            .section-title {
                font-size: 20px;
                margin: 32px 0 20px;
            }

            .benefits-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .benefit-card {
                padding: 20px;
            }

            .benefit-icon {
                width: 48px;
                height: 48px;
                font-size: 20px;
            }

            .benefit-card h3 {
                font-size: 16px;
            }

            .benefit-card p {
                font-size: 13px;
            }

            .process-section {
                padding: 32px 12px;
            }

            .process-timeline {
                grid-template-columns: 1fr;
                gap: 24px;
            }

            .stats-section {
                padding: 24px 12px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 16px;
            }

            .stat-number {
                font-size: 24px;
            }

            .testimonials-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .testimonial-card {
                padding: 20px;
            }

            .faq-section {
                padding: 32px 12px;
            }

            .contact-section {
                padding: 32px 12px;
            }

            .contact-section h2 {
                font-size: 20px;
            }

            .contact-buttons {
                flex-direction: column;
                align-items: center;
            }

            .contact-button {
                width: 100%;
                max-width: 280px;
            }

            .categories-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .category-card {
                padding: 24px 20px;
            }

            .category-icon {
                width: 60px;
                height: 60px;
                font-size: 24px;
            }

            .category-card h3 {
                font-size: 18px;
            }

            .category-card p {
                font-size: 14px;
            }

            .metrics-container {
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }

            .metric-card {
                padding: 24px 16px;
            }

            .metric-value {
                font-size: 24px;
            }
        }

        @media (max-width: 480px) {
            .hero-section h1 {
                font-size: 24px;
            }

            .section-title {
                font-size: 18px;
            }

            .benefit-card h3 {
                font-size: 15px;
            }

            .benefit-card p {
                font-size: 12px;
            }

            .faq-question {
                font-size: 14px;
                padding: 16px;
            }

            .contact-section h2 {
                font-size: 18px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .metrics-container {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .category-card {
                padding: 20px 16px;
            }

            .metric-card {
                padding: 20px 16px;
            }
        }
    </style>
</head>
<body>

    <?php include 'Header.php'; ?>

    <!-- Become Seller Main Content -->
    <main class="become-seller-container">
        <!-- Hero Section -->
        <section class="hero-section">
            <div class="hero-content">
                <h1>Start Selling on E_Cart</h1>
                <p>Join thousands of sellers and grow your business with India's trusted marketplace</p>
                <a href="#register" class="cta-button">Start Selling Today</a>
            </div>
        </section>

        <!-- Benefits Section -->
        <section class="benefits-section">
            <h2 class="section-title">Why Sell on E_Cart?</h2>
            <div class="benefits-grid">
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Millions of Customers</h3>
                    <p>Reach over 10 million+ active customers across India and expand your business exponentially.</p>
                </div>

                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Secure Payments</h3>
                    <p>Get secure and timely payments directly to your bank account with our trusted payment system.</p>
                </div>

                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-truck"></i>
                    </div>
                    <h3>Easy Logistics</h3>
                    <p>Access our nationwide logistics network and deliver products to customers seamlessly.</p>
                </div>

                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Growth Tools</h3>
                    <p>Use advanced analytics and business insights to grow your sales and optimize performance.</p>
                </div>

                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h3>24/7 Support</h3>
                    <p>Get dedicated support from our team whenever you need help with your seller account.</p>
                </div>

                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <h3>Marketing Support</h3>
                    <p>Leverage our marketing tools and promotional campaigns to increase your product visibility.</p>
                </div>
            </div>
        </section>

        <!-- Process Section -->
        <section class="process-section">
            <h2 class="section-title">How It Works</h2>
            <div class="process-timeline">
                <div class="process-step">
                    <div class="step-number">1</div>
                    <h3>Register Your Business</h3>
                    <p>Sign up and complete your business registration with basic details and documents.</p>
                </div>

                <div class="process-step">
                    <div class="step-number">2</div>
                    <h3>List Your Products</h3>
                    <p>Add your products with images, descriptions, and competitive pricing.</p>
                </div>

                <div class="process-step">
                    <div class="step-number">3</div>
                    <h3>Start Selling</h3>
                    <p>Receive orders, process them, and deliver products to customers.</p>
                </div>

                <div class="process-step">
                    <div class="step-number">4</div>
                    <h3>Get Paid</h3>
                    <p>Receive secure payments directly to your bank account.</p>
                </div>
            </div>
        </section>

        <!-- Stats Section -->
        <section class="stats-section">
            <h2 class="section-title">Our Impact</h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number">50K+</div>
                    <div class="stat-label">Active Sellers</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">10M+</div>
                    <div class="stat-label">Happy Customers</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">2M+</div>
                    <div class="stat-label">Products Sold</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">24/7</div>
                    <div class="stat-label">Seller Support</div>
                </div>
            </div>
        </section>

        <!-- Seller Categories Section -->
        <section class="categories-section">
            <h2 class="section-title">Popular Seller Categories</h2>
            <div class="categories-grid">
                <div class="category-card">
                    <div class="category-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>Electronics</h3>
                    <p>Mobile phones, laptops, accessories</p>
                    <div class="category-stats">
                        <span>15K+ Sellers</span>
                        <span>4.8⭐ Rating</span>
                    </div>
                </div>
                <div class="category-card">
                    <div class="category-icon">
                        <i class="fas fa-tshirt"></i>
                    </div>
                    <h3>Fashion</h3>
                    <p>Clothing, shoes, accessories</p>
                    <div class="category-stats">
                        <span>12K+ Sellers</span>
                        <span>4.7⭐ Rating</span>
                    </div>
                </div>
                <div class="category-card">
                    <div class="category-icon">
                        <i class="fas fa-home"></i>
                    </div>
                    <h3>Home & Living</h3>
                    <p>Furniture, decor, kitchen items</p>
                    <div class="category-stats">
                        <span>8K+ Sellers</span>
                        <span>4.6⭐ Rating</span>
                    </div>
                </div>
                <div class="category-card">
                    <div class="category-icon">
                        <i class="fas fa-dumbbell"></i>
                    </div>
                    <h3>Sports & Fitness</h3>
                    <p>Equipment, apparel, accessories</p>
                    <div class="category-stats">
                        <span>5K+ Sellers</span>
                        <span>4.5⭐ Rating</span>
                    </div>
                </div>
                <div class="category-card">
                    <div class="category-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <h3>Books & Media</h3>
                    <p>Books, music, movies, games</p>
                    <div class="category-stats">
                        <span>6K+ Sellers</span>
                        <span>4.7⭐ Rating</span>
                    </div>
                </div>
                <div class="category-card">
                    <div class="category-icon">
                        <i class="fas fa-gamepad"></i>
                    </div>
                    <h3>Gaming</h3>
                    <p>Consoles, games, accessories</p>
                    <div class="category-stats">
                        <span>4K+ Sellers</span>
                        <span>4.8⭐ Rating</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Success Metrics Section -->
        <section class="metrics-section">
            <h2 class="section-title">Why Sellers Choose E_Cart</h2>
            <div class="metrics-container">
                <div class="metric-card">
                    <div class="metric-icon">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <h3>Quick Setup</h3>
                    <div class="metric-value">15 mins</div>
                    <p>Average time to start selling</p>
                </div>
                <div class="metric-card">
                    <div class="metric-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Growth Rate</h3>
                    <div class="metric-value">300%</div>
                    <p>Average business growth in 6 months</p>
                </div>
                <div class="metric-card">
                    <div class="metric-icon">
                        <i class="fas fa-award"></i>
                    </div>
                    <h3>Success Rate</h3>
                    <div class="metric-value">94%</div>
                    <p>Sellers achieve profitability</p>
                </div>
                <div class="metric-card">
                    <div class="metric-icon">
                        <i class="fas fa-globe"></i>
                    </div>
                    <h3>Reach</h3>
                    <div class="metric-value">500+</div>
                    <p>Cities across India</p>
                </div>
            </div>
        </section>

        <!-- Testimonials Section -->
        <section class="testimonials-section">
            <h2 class="section-title">Success Stories</h2>
            <div class="testimonials-grid">
                <div class="testimonial-card">
                    <div class="testimonial-text">
                        "E_Cart has transformed our family business. We now sell to customers across India and our revenue has grown by 300% in just 6 months."
                    </div>
                    <div class="testimonial-author">
                        <div class="author-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="author-info">
                            <h4>Rajesh Kumar</h4>
                            <p>Electronics Store Owner</p>
                        </div>
                    </div>
                </div>

                <div class="testimonial-card">
                    <div class="testimonial-text">
                        "The support and tools provided by E_Cart helped us scale our fashion business. The payment system is reliable and the logistics are seamless."
                    </div>
                    <div class="testimonial-author">
                        <div class="author-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="author-info">
                            <h4>Priya Sharma</h4>
                            <p>Fashion Boutique Owner</p>
                        </div>
                    </div>
                </div>

                <div class="testimonial-card">
                    <div class="testimonial-text">
                        "Starting as a small home-based business, E_Cart gave us the platform to reach millions. Today we're a trusted brand with thousands of happy customers."
                    </div>
                    <div class="testimonial-author">
                        <div class="author-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="author-info">
                            <h4>Amit Patel</h4>
                            <p>Home Decor Business</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ Section -->
        <section class="faq-section">
            <h2 class="section-title">Frequently Asked Questions</h2>
            <div class="faq-container">
                <div class="faq-item">
                    <div class="faq-question">
                        <span>What documents are required to become a seller?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>You'll need your business registration, GST certificate, bank account details, and address proof. Our team will guide you through the complete documentation process.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <span>How much does it cost to sell on E_Cart?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Registration is free. We charge a small commission on successful sales. There are no hidden fees or listing charges.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <span>How do I handle shipping and delivery?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>We provide integrated logistics solutions. You can choose from our partner courier services or use your own delivery system. We also offer warehousing facilities.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <span>When will I receive my payments?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Payments are transferred to your bank account within 7-10 business days after successful delivery. We offer instant payment options for trusted sellers.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <span>Can I sell internationally?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Currently, we focus on the Indian market. However, we're planning to launch international selling capabilities soon for eligible sellers.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contact Section -->
        <section class="contact-section">
            <h2>Ready to Start Your Selling Journey?</h2>
            <p>Join thousands of successful sellers on E_Cart today!</p>
            <div class="contact-buttons">
                <a href="#" class="contact-button">
                    <i class="fas fa-phone"></i>
                    Call Us: 1800-123-4567
                </a>
                <a href="#" class="contact-button">
                    <i class="fas fa-envelope"></i>
                    Email: sellers@e_cart.com
                </a>
            </div>
        </section>
    </main>

   <?php include "Footer.php"; ?>

    <script>
        // FAQ Accordion
        document.querySelectorAll('.faq-question').forEach(question => {
            question.addEventListener('click', () => {
                const faqItem = question.parentElement;
                const isActive = faqItem.classList.contains('active');
                
                // Close all FAQ items
                document.querySelectorAll('.faq-item').forEach(item => {
                    item.classList.remove('active');
                });
                
                // Open clicked item if it wasn't active
                if (!isActive) {
                    faqItem.classList.add('active');
                }
            });
        });

        // Smooth scrolling for anchor links
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

        // Navbar toggle (if needed)
        function toggleMenu() {
            const navLinks = document.getElementById('navLinks');
            if (navLinks) {
                navLinks.classList.toggle('active');
            }
        }
    </script>

</body>
</html>