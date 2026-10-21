<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Global Settings - Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="stylesheet" href="../admin/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .settings-container {
            margin-top: 10px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            padding-bottom: 40px;
        }

        .settings-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
        }

        .settings-card h3 {
            margin: 0 0 20px 0;
            font-size: 18px;
            font-weight: 600;
            color: #111827;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid #f3f4f6;
            padding-bottom: 12px;
        }

        .settings-card h3 i {
            color: #ff9800;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            font-size: 14px;
            color: #374151;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            font-size: 14px;
            border-radius: 6px;
            border: 1px solid #d1d5db;
            box-sizing: border-box;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #ff9800;
            box-shadow: 0 0 0 3px rgba(255, 152, 0, 0.1);
        }

        .toggle-group {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f9fafb;
        }

        .toggle-group:last-child {
            border-bottom: none;
        }

        .toggle-label {
            font-size: 14px;
            color: #374151;
            font-weight: 500;
        }

        /* Diverse UI Elements */
        .btn-group {
            display: flex;
            gap: 8px;
            margin-top: 8px;
        }

        .btn-check {
            flex: 1;
            padding: 10px;
            border: 1px solid #d1d5db;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            text-align: center;
            transition: all 0.2s;
        }

        .btn-check.active {
            background: #ff9800;
            color: white;
            border-color: #ff9800;
        }

        .range-group {
            margin-top: 10px;
        }

        .range-slider {
            width: 100%;
            height: 6px;
            background: #e5e7eb;
            border-radius: 5px;
            outline: none;
            -webkit-appearance: none;
            margin: 15px 0;
        }

        .range-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 18px;
            height: 18px;
            background: #ff9800;
            border-radius: 50%;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .range-val {
            font-weight: 600;
            color: #ff9800;
            float: right;
        }

        .social-input-wrapper {
            display: flex;
            align-items: center;
            background: #f9fafb;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 12px;
        }

        .social-icon-box {
            padding: 10px 14px;
            background: #f3f4f6;
            border-right: 1px solid #d1d5db;
            color: #6b7280;
            width: 45px;
            text-align: center;
        }

        .social-input {
            border: none;
            padding: 10px;
            width: 100%;
            background: transparent;
            font-size: 14px;
        }

        .social-input:focus {
            outline: none;
        }

        .maintenance-btn-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .action-card-btn {
            padding: 15px;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            background: #f9fafb;
            cursor: pointer;
            text-align: center;
            transition: all 0.2s;
        }

        .action-card-btn:hover {
            border-color: #ff9800;
            background: #fffcf0;
        }

        .action-card-btn i {
            display: block;
            font-size: 20px;
            margin-bottom: 8px;
            color: #ff9800;
        }

        .action-card-btn span {
            font-size: 12px;
            font-weight: 600;
            color: #374151;
        }

        /* Switch Style */
        .switch {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 22px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 22px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: #ff9800;
        }

        input:checked + .slider:before {
            transform: translateX(22px);
        }

        .btn-save-all {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #ff9800;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(255, 152, 0, 0.3);
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 100;
            transition: all 0.2s ease;
        }

        .btn-save-all:hover {
            background-color: #f57c00;
            transform: translateY(-2px);
        }

        .error-text {
            color: #dc2626;
            font-size: 12px;
            margin-top: 4px;
            display: none;
        }

        .input-error {
            border-color: #dc2626 !important;
        }

        @media (max-width: 768px) {
            .settings-container {
                grid-template-columns: 1fr;
            }
            .btn-save-all {
                width: calc(100% - 40px);
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <nav class="navbar" id="sidebarNav">
            <div class="navbar-header">
                <h2>Admin Panel</h2>
                <button class="navbar-toggle" id="navbarToggle" aria-label="Toggle navigation">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
            <ul>
               <li><a href="home.php"><i class="fa-solid fa-gauge" style="margin-right: 8px;"></i>Dashboard</a></li>
                <li><a href="users.php"><i class="fa-solid fa-users" style="margin-right: 8px;"></i>Users</a></li>
                <li><a href="banner.php"><i class="fa-solid fa-image" style="margin-right: 8px;"></i>Banner</a></li>
                <li><a href="categories.php"><i class="fa-solid fa-list" style="margin-right: 8px;"></i>Categories</a></li>
                <li><a href="products.php"><i class="fa-solid fa-box" style="margin-right: 8px;"></i>Products</a></li>
                <li><a href="orders.php"><i class="fa-solid fa-cart-shopping" style="margin-right: 8px;"></i>Orders</a></li>
                <li><a href="coupons.php"><i class="fa-solid fa-ticket" style="margin-right: 8px;"></i>Coupons</a></li>
                <li><a href="Gift.php"><i class="fa-solid fa-gift" style="margin-right: 8px;"></i>Gift Cards</a></li>
                <li><a href="payments.php"><i class="fa-solid fa-credit-card" style="margin-right: 8px;"></i>Payments</a></li>
                <li><a href="shipping.php"><i class="fa-solid fa-truck" style="margin-right: 8px;"></i>Shipping</a></li>
                <li><a href="reports.php"><i class="fa-solid fa-chart-line" style="margin-right: 8px;"></i>Reports</a></li>
                <li><a href="reviews.php"><i class="fa-solid fa-star" style="margin-right: 8px;"></i>Reviews</a></li>
                <li><a href="transactions.php"><i class="fa-solid fa-wallet" style="margin-right: 8px;"></i>Transactions</a></li>
                <li><a href="contact.php"><i class="fa-solid fa-envelope" style="margin-right: 8px;"></i>Contact</a></li>
                <li><a href="support.php"><i class="fa-solid fa-headset" style="margin-right: 8px;"></i>Support</a></li>
                <li><a href="settings.php"><i class="fa-solid fa-gear" style="margin-right: 8px;"></i>Settings</a></li>
            </ul>
        </nav>

        <main class="main-content">
            <header>
                <h1>Global Settings</h1>
            </header>

            <div class="settings-container">
                <!-- General Website Control -->
                <div class="settings-card">
                    <h3><i class="fas fa-globe"></i> Website Control</h3>
                    <div class="form-group">
                        <label>Website Name</label>
                        <input type="text" class="form-control" id="siteName" value="E_Cart">
                        <span class="error-text" id="siteNameError"></span>
                    </div>
                    <div class="form-group">
                        <label>Site Tagline</label>
                        <input type="text" class="form-control" id="siteTagline" value="Your Best Shopping Destination">
                    </div>
                    <div class="toggle-group">
                        <span class="toggle-label">Maintenance Mode</span>
                        <label class="switch">
                            <input type="checkbox" id="maintenanceMode">
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="toggle-group">
                        <span class="toggle-label">User Registration</span>
                        <label class="switch">
                            <input type="checkbox" id="userRegistration" checked>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>

                <!-- Appearance Control -->
                <div class="settings-card">
                    <h3><i class="fas fa-paint-brush"></i> UI & Appearance</h3>
                    <div class="form-group">
                        <label>Primary Color</label>
                        <input type="color" class="form-control" id="primaryColor" value="#ff9800" style="height: 40px; padding: 2px;">
                    </div>
                    <div class="form-group">
                        <label>Secondary Color</label>
                        <input type="color" class="form-control" id="secondaryColor" value="#2874f0" style="height: 40px; padding: 2px;">
                    </div>
                    <div class="form-group">
                        <label>Admin Theme</label>
                        <select class="form-control" id="adminTheme">
                            <option value="light">Light Mode</option>
                            <option value="dark">Dark Mode</option>
                        </select>
                    </div>
                </div>

                <!-- Store Controls -->
                <div class="settings-card">
                    <h3><i class="fas fa-shopping-cart"></i> Order & Store</h3>
                    <div class="form-group">
                        <label>Currency Symbol</label>
                        <input type="text" class="form-control" id="currencySymbol" value="₹">
                    </div>
                    <div class="form-group">
                        <label>Min Order Amount (₹)</label>
                        <input type="number" class="form-control" id="minOrder" value="100">
                    </div>
                    <div class="toggle-group">
                        <span class="toggle-label">Enable COD</span>
                        <label class="switch">
                            <input type="checkbox" id="enableCod" checked>
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="toggle-group">
                        <span class="toggle-label">Gift Card System</span>
                        <label class="switch">
                            <input type="checkbox" id="enableGift" checked>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>

                <!-- Social Media Presence - Redesigned with Social Icons -->
                <div class="settings-card">
                    <h3><i class="fas fa-share-alt"></i> Social Presence</h3>
                    <div class="social-input-wrapper">
                        <div class="social-icon-box"><i class="fab fa-google"></i></div>
                        <input type="text" class="social-input" id="googleUrl" value="https://google.com/ecart">
                    </div>
                    <div class="social-input-wrapper">
                        <div class="social-icon-box"><i class="fab fa-facebook-f"></i></div>
                        <input type="text" class="social-input" id="fbUrl" value="https://facebook.com/ecart">
                    </div>
                    <div class="social-input-wrapper">
                        <div class="social-icon-box"><i class="fab fa-instagram"></i></div>
                        <input type="text" class="social-input" id="instaUrl" value="https://instagram.com/ecart">
                    </div>
                    <div class="social-input-wrapper">
                        <div class="social-icon-box"><i class="fab fa-twitter"></i></div>
                        <input type="text" class="social-input" id="twitterUrl" value="https://twitter.com/ecart">
                    </div>
                    
                </div>

                <!-- SEO & Search - Redesigned with Range Slider -->
                <div class="settings-card">
                    <h3><i class="fas fa-search"></i> SEO & Search</h3>
                    <div class="form-group">
                        <label>Meta Description</label>
                        <textarea class="form-control" id="metaDescription" rows="2">Best online shopping site in India.</textarea>
                    </div>
                    <div class="range-group">
                        <label>Search Result Priority <span class="range-val" id="seoPriorityVal">80%</span></label>
                        <input type="range" class="range-slider" min="0" max="100" value="80" oninput="document.getElementById('seoPriorityVal').innerText = this.value + '%'">
                    </div>
                    <div class="btn-group">
                        <button class="btn-check active" onclick="selectBtn(this)">Index All</button>
                        <button class="btn-check" onclick="selectBtn(this)">No Index</button>
                    </div>
                </div>

                <!-- Shop Availability - New Section -->
                <div class="settings-card">
                    <h3><i class="fas fa-clock"></i> Shop Availability</h3>
                    <div class="btn-group">
                        <button class="btn-check active" onclick="selectBtn(this)">Always Open</button>
                        <button class="btn-check" onclick="selectBtn(this)">Custom Hours</button>
                    </div>
                    <div class="toggle-group" style="margin-top: 15px;">
                        <span class="toggle-label"><i class="fas fa-umbrella-beach"></i> Holiday Mode</span>
                        <label class="switch">
                            <input type="checkbox" id="holidayMode">
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="form-group" style="margin-top: 10px;">
                        <label>Auto-Reply Message</label>
                        <textarea class="form-control" id="autoReply" rows="2">We are currently on holiday. Orders will be processed soon.</textarea>
                    </div>
                </div>

                <!-- Search & Discovery - Diverse Section with Select & Range -->
                <div class="settings-card">
                    <h3><i class="fas fa-search-plus"></i> Search & Discovery</h3>
                    <div class="form-group">
                        <label>Search Logic</label>
                        <div class="btn-group">
                            <button class="btn-check active" onclick="selectBtn(this)">Fuzzy Search</button>
                            <button class="btn-check" onclick="selectBtn(this)">Exact Match</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Default Sort Order</label>
                        <select class="form-control" id="defaultSort">
                            <option value="relevance">Relevance</option>
                            <option value="newest">Newest First</option>
                            <option value="price_low">Price: Low to High</option>
                            <option value="price_high">Price: High to Low</option>
                        </select>
                    </div>
                </div>

                <!-- Performance & Speed - Redesigned with Range Sliders -->
                <div class="settings-card">
                    <h3><i class="fas fa-tachometer-alt"></i> Speed & Optimization</h3>
                    <div class="range-group">
                        <label>Cache Duration (Hours) <span class="range-val" id="cacheExpiryVal">24h</span></label>
                        <input type="range" class="range-slider" min="1" max="168" value="24" oninput="document.getElementById('cacheExpiryVal').innerText = this.value + 'h'">
                    </div>
                    <div class="range-group">
                        <label>Image Optimization <span class="range-val" id="imgOptVal">85%</span></label>
                        <input type="range" class="range-slider" min="50" max="100" value="85" oninput="document.getElementById('imgOptVal').innerText = this.value + '%'">
                    </div>
                    <div class="btn-group">
                        <button class="btn-check active" onclick="selectBtn(this)">Optimize CSS</button>
                        <button class="btn-check active" onclick="selectBtn(this)">Optimize JS</button>
                    </div>
                </div>

                <!-- System Health - Redesigned with Action Cards -->
                <div class="settings-card">
                    <h3><i class="fas fa-tools"></i> System Health</h3>
                    <div class="maintenance-btn-grid">
                        <div class="action-card-btn" onclick="alert('Backup started...')">
                            <i class="fas fa-database"></i>
                            <span>DB BACKUP</span>
                        </div>
                        <div class="action-card-btn" onclick="confirm('Clear cache?') && alert('Cleared!')">
                            <i class="fas fa-bolt"></i>
                            <span>CLEAR CACHE</span>
                        </div>
                        <div class="action-card-btn" onclick="alert('Optimization started...')">
                            <i class="fas fa-magic"></i>
                            <span>OPTIMIZE</span>
                        </div>
                        <div class="action-card-btn" style="border-color: #fee2e2;">
                            <i class="fas fa-sync-alt" style="color: #ef4444;"></i>
                            <span style="color: #ef4444;">RESET SITE</span>
                        </div>
                    </div>
                </div>

                <!-- User Experience - Redesigned with Button Selection & Sliders -->
                <div class="settings-card">
                    <h3><i class="fas fa-user-cog"></i> User Experience</h3>
                    <div class="form-group">
                        <label>Items Per Page</label>
                        <div class="btn-group">
                            <button class="btn-check" onclick="selectBtn(this)">10</button>
                            <button class="btn-check active" onclick="selectBtn(this)">20</button>
                            <button class="btn-check" onclick="selectBtn(this)">50</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Image Quality (%) <span class="range-val" id="imgQualityVal">90%</span></label>
                        <input type="range" class="range-slider" min="50" max="100" value="90" oninput="document.getElementById('imgQualityVal').innerText = this.value + '%'">
                    </div>
                </div>

                <!-- Legal & Compliance - Better structure -->
                <div class="settings-card">
                    <h3><i class="fas fa-gavel"></i> Legal & Compliance</h3>
                    <div class="form-group">
                        <label>GST Number</label>
                        <input type="text" class="form-control" id="gstNumber" value="27AAAAA0000A1Z5">
                    </div>
                    <div class="form-group">
                        <label>Return Policy (Days)</label>
                        <select class="form-control" id="returnDays">
                            <option value="7">7 Days</option>
                            <option value="10" selected>10 Days</option>
                            <option value="15">15 Days</option>
                            <option value="30">30 Days</option>
                        </select>
                    </div>
                </div>

                <!-- Internationalization -->
                <div class="settings-card">
                    <h3><i class="fas fa-language"></i> Localization</h3>
                    <div class="form-group">
                        <label>Default Language</label>
                        <select class="form-control" id="defaultLanguage">
                            <option value="en">English</option>
                            <option value="hi">Hindi</option>
                        </select>
                    </div>
                    <div class="toggle-group">
                        <span class="toggle-label">Multi-Currency Support</span>
                        <label class="switch">
                            <input type="checkbox" id="multiCurrency">
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="form-group" style="margin-top: 10px;">
                        <label>Timezone</label>
                        <select class="form-control" id="siteTimezone">
                            <option value="Asia/Kolkata">(GMT+05:30) India</option>
                            <option value="UTC">UTC</option>
                        </select>
                    </div>
                </div>

                <!-- Media & Uploads -->
                <div class="settings-card">
                    <h3><i class="fas fa-images"></i> Media & Uploads</h3>
                    <div class="form-group">
                        <label>Max Upload Size (MB)</label>
                        <input type="number" class="form-control" id="maxUpload" value="5">
                    </div>
                    <div class="form-group">
                        <label>Allowed Extensions</label>
                        <input type="text" class="form-control" id="allowedExt" value="jpg, jpeg, png, webp">
                    </div>
                    <div class="toggle-group">
                        <span class="toggle-label">Image Watermark</span>
                        <label class="switch">
                            <input type="checkbox" id="enableWatermark">
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>

                <!-- Newsletter & Marketing - New Redesigned Section -->
                <div class="settings-card">
                    <h3><i class="fas fa-bullhorn"></i> Marketing & Newsletter</h3>
                    <div class="form-group">
                        <label>Newsletter Popup Delay (s) <span class="range-val" id="popupDelayVal">5s</span></label>
                        <input type="range" class="range-slider" min="0" max="30" value="5" oninput="document.getElementById('popupDelayVal').innerText = this.value + 's'">
                    </div>
                    <div class="btn-group">
                        <button class="btn-check active" onclick="selectBtn(this)">Enable Popup</button>
                        <button class="btn-check" onclick="selectBtn(this)">Disable</button>
                    </div>
                    <div class="form-group" style="margin-top: 15px;">
                        <label>Mailchimp API Key</label>
                        <input type="password" class="form-control" id="mailchimpKey" value="*************">
                    </div>
                </div>

                <!-- Advanced Order Settings - New Redesigned Section -->
                <div class="settings-card">
                    <h3><i class="fas fa-file-invoice"></i> Order & Invoice</h3>
                    <div class="form-group">
                        <label>Order ID Prefix</label>
                        <input type="text" class="form-control" id="orderPrefix" value="ORD-">
                    </div>
                    <div class="form-group">
                        <label>Invoice Theme</label>
                        <div class="btn-group">
                            <button class="btn-check active" onclick="selectBtn(this)">Classic</button>
                            <button class="btn-check" onclick="selectBtn(this)">Modern</button>
                            <button class="btn-check" onclick="selectBtn(this)">Minimal</button>
                        </div>
                    </div>
                </div>

                <!-- Developer Tools - New Redesigned Section -->
                <div class="settings-card">
                    <h3><i class="fas fa-code"></i> Developer Tools</h3>
                    <div class="form-group">
                        <label>Custom Header Code (JS/CSS)</label>
                        <textarea class="form-control" id="headerCode" rows="3" style="font-family: monospace; font-size: 12px; background: #f8f9fa;" placeholder="<script>...</script>"></textarea>
                    </div>
                    <div class="btn-group">
                        <button class="btn-check active" onclick="selectBtn(this)">Debug Off</button>
                        <button class="btn-check" onclick="selectBtn(this)" style="color: #ef4444;">Debug On</button>
                    </div>
                </div>

                <!-- Admin Account Quick Settings - New Redesigned Section -->
                <div class="settings-card">
                    <h3><i class="fas fa-user-lock"></i> Admin Account</h3>
                    <div class="form-group">
                        <label>Login Email</label>
                        <input type="email" class="form-control" id="adminLoginEmail" value="admin@ecart.com">
                    </div>
                    <div class="maintenance-btn-grid" style="grid-template-columns: 1fr;">
                        <div class="action-card-btn" onclick="alert('Password reset link sent!')">
                            <i class="fas fa-key"></i>
                            <span>CHANGE PASSWORD</span>
                        </div>
                    </div>
                </div>

                <!-- Data & Reporting - New Diverse Section -->
                <div class="settings-card">
                    <h3><i class="fas fa-chart-pie"></i> Reporting & Data</h3>
                    <div class="form-group">
                        <label>Default Chart Type</label>
                        <div class="btn-group">
                            <button class="btn-check active" onclick="selectBtn(this)">Bar</button>
                            <button class="btn-check" onclick="selectBtn(this)">Line</button>
                            <button class="btn-check" onclick="selectBtn(this)">Pie</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Export Format</label>
                        <select class="form-control" id="exportFormat">
                            <option value="csv">CSV Spreadsheet</option>
                            <option value="pdf">PDF Document</option>
                            <option value="excel">Excel (.xlsx)</option>
                        </select>
                    </div>
                </div>

                <!-- Shop Availability - New Section -->
                <div class="settings-card">
                    <h3><i class="fas fa-clock"></i> Shop Availability</h3>
                    <div class="btn-group">
                        <button class="btn-check active" onclick="selectBtn(this)">Always Open</button>
                        <button class="btn-check" onclick="selectBtn(this)">Custom Hours</button>
                    </div>
                    <div class="toggle-group" style="margin-top: 15px;">
                        <span class="toggle-label"><i class="fas fa-umbrella-beach"></i> Holiday Mode</span>
                        <label class="switch">
                            <input type="checkbox" id="holidayMode">
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="form-group" style="margin-top: 10px;">
                        <label>Auto-Reply Message</label>
                        <input type="text" class="form-control" id="autoReply" value="We are currently on holiday. Orders will be processed soon.">
                    </div>
                </div>

                <!-- Search & Discovery - New diverse section -->
                <div class="settings-card">
                    <h3><i class="fas fa-search-plus"></i> Search & Discovery</h3>
                    <div class="form-group">
                        <label>Search Logic</label>
                        <div class="btn-group">
                            <button class="btn-check active" onclick="selectBtn(this)">Fuzzy</button>
                            <button class="btn-check" onclick="selectBtn(this)">Exact</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Default Sort Order</label>
                        <select class="form-control" id="defaultSort">
                            <option value="relevance">Relevance</option>
                            <option value="newest">Newest First</option>
                            <option value="price_low">Price: Low to High</option>
                <!-- Performance Optimization - New diverse section -->
                <div class="settings-card">
                    <h3><i class="fas fa-tachometer-alt"></i> Speed & Performance</h3>
                    <div class="form-group">
                        <label>Cache Expiry (Hours) <span class="range-val" id="cacheExpiryVal">24h</span></label>
                        <input type="range" class="range-slider" min="1" max="168" value="24" oninput="document.getElementById('cacheExpiryVal').innerText = this.value + 'h'">
                    </div>
                    <div class="btn-group">
                        <button class="btn-check active" onclick="selectBtn(this)">Basic Cache</button>
                        <button class="btn-check" onclick="selectBtn(this)">Full Page</button>
                    </div>
                </div>

                <!-- Newsletter & Marketing - Redesigned Section -->
                <div class="settings-card">
                    <h3><i class="fas fa-bullhorn"></i> Marketing</h3>
                    <div class="form-group">
                        <label>Popup Delay (Seconds) <span class="range-val" id="popupDelayVal">5s</span></label>
                        <input type="range" class="range-slider" min="0" max="30" value="5" oninput="document.getElementById('popupDelayVal').innerText = this.value + 's'">
                    </div>
                    <div class="btn-group">
                        <button class="btn-check active" onclick="selectBtn(this)">Enable Popup</button>
                        <button class="btn-check" onclick="selectBtn(this)">Disable</button>
                    </div>
                </div>

                <!-- Advanced Order Settings - Diverse Section -->
                <div class="settings-card">
                    <h3><i class="fas fa-file-invoice"></i> Order Prefix</h3>
                    <div class="form-group">
                        <input type="text" class="form-control" id="orderPrefix" value="ORD-" placeholder="Enter prefix (e.g. ORD-)">
                    </div>
                    <label style="font-size: 13px; color: #666;">Invoice Style</label>
                    <div class="btn-group">
                        <button class="btn-check active" onclick="selectBtn(this)">Modern</button>
                        <button class="btn-check" onclick="selectBtn(this)">Classic</button>
                    </div>
                </div>

                <!-- Developer & Admin Tools - Action Cards -->
                <div class="settings-card">
                    <h3><i class="fas fa-user-shield"></i> Admin Tools</h3>
                    <div class="maintenance-btn-grid">
                        <div class="action-card-btn" onclick="alert('Password reset link sent!')">
                            <i class="fas fa-key"></i>
                            <span>CHANGE PWD</span>
                        </div>
                        <div class="action-card-btn" onclick="alert('Debug mode toggled!')">
                            <i class="fas fa-bug"></i>
                            <span>DEBUG MODE</span>
                        </div>
                    </div>
                    <div class="form-group" style="margin-top: 15px;">
                        <label>Custom Header Code</label>
                        <textarea class="form-control" id="headerCode" rows="2" style="font-family: monospace; font-size: 12px; background: #f8f9fa;" placeholder="<script>...</script>"></textarea>
                    </div>
                </div>
            </div>

            <button class="btn-save-all" id="saveAllSettings">
                <i class="fas fa-save"></i> Save All Changes
            </button>
        </main>
    </div>

    <script>
        // Sidebar Toggle
        const navbarToggle = document.getElementById('navbarToggle');
        const sidebarNav = document.getElementById('sidebarNav');
        if (navbarToggle && sidebarNav) {
            navbarToggle.addEventListener('click', () => {
                sidebarNav.classList.toggle('open');
            });
        }

        // Save All Functionality
        document.getElementById('saveAllSettings').addEventListener('click', function() {
            const btn = this;
            const originalContent = btn.innerHTML;
            
            // Basic Validation
            const siteName = document.getElementById('siteName');
            if(!siteName.value.trim()) {
                siteName.classList.add('input-error');
                document.getElementById('siteNameError').style.display = 'block';
                document.getElementById('siteNameError').textContent = 'Site name is required!';
                return;
            } else {
                siteName.classList.remove('input-error');
                document.getElementById('siteNameError').style.display = 'none';
            }

            // Simulate Saving
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            btn.disabled = true;

            setTimeout(() => {
                btn.innerHTML = '<i class="fas fa-check"></i> Changes Saved!';
                btn.style.backgroundColor = '#4caf50';
                
                // Reset button after 2 seconds
                setTimeout(() => {
                    btn.innerHTML = originalContent;
                    btn.style.backgroundColor = '#ff9800';
                    btn.disabled = false;
                    alert('Global settings updated successfully! Your website and admin panel will reflect these changes.');
                }, 2000);
            }, 1500);
        });

        // Toggle Active Class for Buttons
        window.selectBtn = function(btn) {
            const group = btn.parentElement;
            group.querySelectorAll('.btn-check').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        }
    </script>
</body>

</html>