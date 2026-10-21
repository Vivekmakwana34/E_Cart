<!DOCTYPE html>

<html lang="en">



<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

    <title>Admin Panel</title>

    <link rel="stylesheet" href="../admin/css/style.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

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

                <h1>Dashboard</h1>

            </header>

            <div class="dashboard-cards">

                <div class="card">

                    <h3>Total Users</h3>

                    <p>1,234</p>

                </div>

                <div class="card">

                    <h3>Total Orders</h3>

                    <p>567</p>

                </div>

                <div class="card">

                    <h3>Total Products</h3>

                    <p>89</p>

                </div>

                <div class="card">

                    <h3>Revenue</h3>

                    <p>₹12,345</p>

                </div>

            </div>



            <section class="dashboard-layout">

                <div class="dashboard-main-panel">

                    <div class="panel">

                        <div class="panel-header">

                            <h2>Recent Orders</h2>

                            <span class="panel-subtitle">Last 7 days</span>

                        </div>

                        <table class="table recent-orders-table">

                            <thead>

                                <tr>

                                    <th>Order ID</th>

                                    <th>Customer</th>

                                    <th>Status</th>

                                    <th>Amount</th>

                                </tr>

                            </thead>

                            <tbody>

                                <tr>

                                    <td>#ORD-1023</td>

                                    <td>Rahul Sharma</td>

                                    <td><span class="status-pill status-pill-paid">Paid</span></td>

                                    <td>₹2,450</td>

                                </tr>

                                <tr>

                                    <td>#ORD-1022</td>

                                    <td>Priya Verma</td>

                                    <td><span class="status-pill status-pill-pending">Pending</span></td>

                                    <td>₹1,180</td>

                                </tr>

                                <tr>

                                    <td>#ORD-1021</td>

                                    <td>Amit Patel</td>

                                    <td><span class="status-pill status-pill-paid">Paid</span></td>

                                    <td>₹3,720</td>

                                </tr>

                                <tr>

                                    <td>#ORD-1020</td>

                                    <td>Neha Singh</td>

                                    <td><span class="status-pill status-pill-refunded">Refunded</span></td>

                                    <td>₹650</td>

                                </tr>

                            </tbody>

                        </table>

                    </div>

                </div>



                <div class="dashboard-side-panel">

                    <div class="panel insights-panel">

                        <div class="panel-header">

                            <h2>Today's Overview</h2>

                        </div>

                        <ul class="insights-list">

                            <li>

                                <span class="insight-label">New Users</span>

                                <span class="insight-value">32</span>

                            </li>

                            <li>

                                <span class="insight-label">New Orders</span>

                                <span class="insight-value">78</span>

                            </li>

                            <li>

                                <span class="insight-label">Conversion Rate</span>

                                <span class="insight-value">3.4%</span>

                            </li>

                            <li>

                                <span class="insight-label">Refunds</span>

                                <span class="insight-value">5</span>

                            </li>

                        </ul>

                    </div>



                    <div class="panel quick-actions-panel">

                        <div class="panel-header">

                            <h2>Quick Actions</h2>

                        </div>

                        <div class="quick-actions">

                            <a href="users.html" class="quick-action-link">

                                <i class="fa-solid fa-user-plus"></i>

                                Add new user

                            </a>

                            <a href="products.html" class="quick-action-link">

                                <i class="fa-solid fa-box-open"></i>

                                Add new product

                            </a>

                            <a href="#" class="quick-action-link">

                                <i class="fa-solid fa-chart-line"></i>

                                View sales report

                            </a>

                        </div>

                    </div>

                </div>

            </section>



            <section class="dashboard-layout">

                <div class="dashboard-main-panel">

                    <div class="panel">

                        <div class="panel-header">

                            <h2>Sales by Channel</h2>

                            <span class="panel-subtitle">This month</span>

                        </div>
    
                        <table class="table">

                            <thead>

                                <tr>

                                    <th>Channel</th>

                                    <th>Orders</th>

                                    <th>Revenue</th>

                                </tr>

                            </thead>

                            <tbody>

                                <tr>

                                    <td>Website</td>

                                    <td>842</td>

                                    <td>₹7,25,000</td>

                                </tr>

                                <tr>

                                    <td>Mobile App</td>

                                    <td>516</td>

                                    <td>₹4,10,500</td>

                                </tr>

                                <tr>

                                    <td>Marketplace</td>

                                    <td>289</td>

                                    <td>₹2,35,800</td>

                                </tr>

                            </tbody>

                        </table>

                    </div>

                </div>



                <div class="dashboard-side-panel">

                    <div class="panel">

                        <div class="panel-header">

                            <h2>Channel Highlights</h2>

                        </div>

                        <ul class="insights-list">

                            <li>

                                <span class="insight-label">Top Channel</span>

                                <span class="insight-value">Website</span>

                            </li>

                            <li>

                                <span class="insight-label">Mobile Share</span>

                                <span class="insight-value">32%</span>

                            </li>

                            <li>

                                <span class="insight-label">Marketplace Share</span>

                                <span class="insight-value">18%</span>

                            </li>

                        </ul>

                    </div>

                </div>

            </section>



            <section class="dashboard-layout">

                <div class="dashboard-main-panel">

                    <div class="panel performance-panel">

                        <div class="panel-header">

                            <h2>Monthly Performance</h2>

                            <span class="panel-subtitle">vs target</span>

                        </div>

                        <div class="performance-metrics">

                            <div class="metric-row">

                                <div class="metric-text">

                                    <div class="metric-label">Revenue</div>

                                    <div class="metric-value">₹5,40,000</div>

                                    <div class="progress">

                                        <div class="progress-bar" style="width: 74%;"></div>

                                    </div>

                                </div>

                                <div class="metric-percent">74%</div>

                            </div>

                            <div class="metric-row">

                                <div class="metric-text">

                                    <div class="metric-label">Orders</div>

                                    <div class="metric-value">1,280</div>

                                    <div class="progress">

                                        <div class="progress-bar" style="width: 62%;"></div>

                                    </div>

                                </div>

                                <div class="metric-percent">62%</div>

                            </div>

                            <div class="metric-row">

                                <div class="metric-text">

                                    <div class="metric-label">New Customers</div>

                                    <div class="metric-value">412</div>

                                    <div class="progress">

                                        <div class="progress-bar" style="width: 81%;"></div>

                                    </div>

                                </div>

                                <div class="metric-percent">81%</div>

                            </div>

                            <div class="metric-row">

                                <div class="metric-text">

                                    <div class="metric-label">Avg. Order Value</div>

                                    <div class="metric-value">₹1,420</div>

                                    <div class="progress">

                                        <div class="progress-bar" style="width: 55%;"></div>

                                    </div>

                                </div>

                                <div class="metric-percent">55%</div>

                            </div>

                        </div>

                    </div>

                </div>



                <div class="dashboard-side-panel">

                    <div class="panel top-products-panel">

                        <div class="panel-header">

                            <h2>Top Products</h2>

                        </div>

                        <ul class="top-products-list">

                            <li>

                                <div class="product-main">

                                    <span class="product-name">Wireless Earbuds</span>

                                    <span class="product-category">Electronics</span>

                                </div>

                                <span class="product-sales">842 sales</span>

                            </li>

                            <li>

                                <div class="product-main">

                                    <span class="product-name">Running Shoes</span>

                                    <span class="product-category">Footwear</span>

                                </div>

                                <span class="product-sales">615 sales</span>

                            </li>

                            <li>

                                <div class="product-main">

                                    <span class="product-name">Smart Watch</span>

                                    <span class="product-category">Gadgets</span>

                                </div>

                                <span class="product-sales">497 sales</span>

                            </li>

                            <li>

                                <div class="product-main">

                                    <span class="product-name">Office Chair</span>

                                    <span class="product-category">Furniture</span>

                                </div>

                                <span class="product-sales">323 sales</span>

                            </li>

                        </ul>

            </section>

        </main>

    </div>

    <script>

        const navbar = document.getElementById('sidebarNav');

        const navbarToggle = document.getElementById('navbarToggle');



        if (navbar && navbarToggle) {

            navbarToggle.addEventListener('click', () => {

                navbar.classList.toggle('open');

            });



            // Close sidebar when clicking outside

            document.addEventListener('click', (e) => {

                if (navbar.classList.contains('open') && 

                    !navbar.contains(e.target) && 

                    !navbarToggle.contains(e.target)) {

                    navbar.classList.remove('open');

                    navbarToggle.classList.remove('open');

                }

            });



            // Close sidebar when clicking on a link (mobile)

            const navLinks = navbar.querySelectorAll('ul li a');

            navLinks.forEach(link => {

                link.addEventListener('click', () => {

                    if (window.innerWidth <= 768) {

                        navbar.classList.remove('open');

                        navbarToggle.classList.remove('open');

                    }

                });

            });

        }



        // Highlight current page in sidebar

        (function () {

            const sidebarNav = document.getElementById('sidebarNav');

            if (!sidebarNav) return;



            const links = sidebarNav.querySelectorAll('a');

            const currentPage = window.location.pathname.split('/').pop();



            for (let i = 0; i < links.length; i++) {

                const href = links[i].getAttribute('href');

                if (!href) continue;

                const hrefPage = href.split('/').pop();

                if (hrefPage === currentPage) {

                    links[i].classList.add('active');

                }

            }

        })();

    </script>



</body>



</html>