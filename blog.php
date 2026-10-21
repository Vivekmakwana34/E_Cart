<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E_Cart Blog</title>
   
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/index.css">
</head>
<body>

<?php include 'Header.php'; ?>

<main class="page-main" style="padding: 40px 0; background:#f5f5f5;">
    <div class="page-container" style="max-width:1200px;margin:0 auto;padding:0 16px;">
        <div style="margin-bottom:30px;text-align:center;">
            <h1 style="font-size:32px;margin-bottom:10px;">E_Cart Blog</h1>
            <p style="color:#555;max-width:600px;margin:0 auto;">
                Latest updates, shopping tips, product guides and stories from the E_Cart team.
            </p>
        </div>

        <div class="blog-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:24px;">
            <article class="blog-card" style="background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.05);display:flex;flex-direction:column;">
                <div style="background:#e0e7ff;height:160px;display:flex;align-items:center;justify-content:center;color:#4f46e5;font-size:24px;font-weight:600;">
                    Shopping Tips
                </div>
                <div style="padding:18px 20px 20px;flex:1;display:flex;flex-direction:column;">
                    <h2 style="font-size:18px;margin:0 0 8px;">How to Save More on Your Next E_Cart Order</h2>
                    <p style="color:#666;font-size:14px;line-height:1.6;margin:0 0 12px;">
                        Learn how to combine offers, coupons and smart timing to get the best value on mobiles, fashion and electronics.
                    </p>
                    <a href="#" style="margin-top:auto;font-size:14px;color:#2563eb;text-decoration:none;font-weight:500;">Read More →</a>
                </div>
            </article>

            <article class="blog-card" style="background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.05);display:flex;flex-direction:column;">
                <div style="background:#fee2e2;height:160px;display:flex;align-items:center;justify-content:center;color:#b91c1c;font-size:24px;font-weight:600;">
                    New Arrivals
                </div>
                <div style="padding:18px 20px 20px;flex:1;display:flex;flex-direction:column;">
                    <h2 style="font-size:18px;margin:0 0 8px;">Trending Gadgets This Season</h2>
                    <p style="color:#666;font-size:14px;line-height:1.6;margin:0 0 12px;">
                        From smartwatches to wireless audio, explore the most popular gadgets customers are loving right now.
                    </p>
                    <a href="#" style="margin-top:auto;font-size:14px;color:#2563eb;text-decoration:none;font-weight:500;">Read More →</a>
                </div>
            </article>

            <article class="blog-card" style="background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.05);display:flex;flex-direction:column;">
                <div style="background:#dcfce7;height:160px;display:flex;align-items:center;justify-content:center;color:#15803d;font-size:24px;font-weight:600;">
                    E_Cart Stories
                </div>
                <div style="padding:18px 20px 20px;flex:1;display:flex;flex-direction:column;">
                    <h2 style="font-size:18px;margin:0 0 8px;">Behind the Scenes at E_Cart</h2>
                    <p style="color:#666;font-size:14px;line-height:1.6;margin:0 0 12px;">
                        Get a sneak peek into how we pack, ship and deliver thousands of orders every day with care.
                    </p>
                    <a href="#" style="margin-top:auto;font-size:14px;color:#2563eb;text-decoration:none;font-weight:500;">Read More →</a>
                </div>
            </article>
        </div>

        <!-- Featured Categories Section -->
        <section style="margin-top:40px;">
            <h2 style="font-size:24px;margin-bottom:20px;text-align:center;">Featured Categories</h2>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;">
                <div style="background:#fff;border-radius:8px;padding:20px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,0.05);">
                    <div style="font-size:32px;margin-bottom:10px;">📱</div>
                    <h3 style="font-size:16px;margin:0 0 6px;">Mobiles</h3>
                    <p style="font-size:13px;color:#666;margin:0;">Latest smartphones & accessories</p>
                </div>
                <div style="background:#fff;border-radius:8px;padding:20px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,0.05);">
                    <div style="font-size:32px;margin-bottom:10px;">👕</div>
                    <h3 style="font-size:16px;margin:0 0 6px;">Fashion</h3>
                    <p style="font-size:13px;color:#666;margin:0;">Trendy clothes & footwear</p>
                </div>
                <div style="background:#fff;border-radius:8px;padding:20px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,0.05);">
                    <div style="font-size:32px;margin-bottom:10px;">💻</div>
                    <h3 style="font-size:16px;margin:0 0 6px;">Electronics</h3>
                    <p style="font-size:13px;color:#666;margin:0;">Laptops, gadgets & more</p>
                </div>
                <div style="background:#fff;border-radius:8px;padding:20px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,0.05);">
                    <div style="font-size:32px;margin-bottom:10px;">🏠</div>
                    <h3 style="font-size:16px;margin:0 0 6px;">Home</h3>
                    <p style="font-size:13px;color:#666;margin:0;">Essentials for your home</p>
                </div>
            </div>
        </section>

        <!-- Popular Tags Section -->
        <section style="margin-top:40px;">
            <h2 style="font-size:24px;margin-bottom:20px;text-align:center;">Popular Tags</h2>
            <div style="display:flex;flex-wrap:wrap;gap:10px;justify-content:center;">
                <span style="background:#e3f2fd;color:#1976d2;padding:6px 14px;border-radius:20px;font-size:13px;">#ShoppingTips</span>
                <span style="background:#fce4ec;color:#c2185b;padding:6px 14px;border-radius:20px;font-size:13px;">#NewArrivals</span>
                <span style="background:#e8f5e8;color:#2e7d32;padding:6px 14px;border-radius:20px;font-size:13px;">#BestDeals</span>
                <span style="background:#fff3e0;color:#f57c00;padding:6px 14px;border-radius:20px;font-size:13px;">#TechReviews</span>
                <span style="background:#f3e5f5;color:#7b1fa2;padding:6px 14px;border-radius:20px;font-size:13px;">#FashionTrends</span>
                <span style="background:#e0f2f1;color:#00796b;padding:6px 14px;border-radius:20px;font-size:13px;">#HomeLiving</span>
                <span style="background:#fce4ec;color:#d32f2f;padding:6px 14px;border-radius:20px;font-size:13px;">#SaleAlert</span>
                <span style="background:#e1f5fe;color:#0277bd;padding:6px 14px;border-radius:20px;font-size:13px;">#CustomerStories</span>
            </div>
        </section>

        <!-- Newsletter Section -->
        <section style="margin-top:40px;">
            <div style="background:#fff;border-radius:10px;padding:30px;text-align:center;box-shadow:0 4px 12px rgba(0,0,0,0.05);">
                <h2 style="font-size:22px;margin:0 0 10px;">Stay Updated with E_Cart Blog</h2>
                <p style="color:#666;margin:0 0 20px;max-width:500px;margin-left:auto;margin-right:auto;">
                    Get the latest shopping tips, product reviews, and exclusive offers delivered to your inbox.
                </p>
                <form style="display:flex;gap:10px;max-width:400px;margin:0 auto;flex-wrap:wrap;justify-content:center;">
                    <input type="email" placeholder="Enter your email" style="flex:1;min-width:200px;padding:10px 15px;border:1px solid #ddd;border-radius:6px;font-size:14px;" required>
                    <button type="submit" style="background:#2563eb;color:#fff;border:none;padding:10px 20px;border-radius:6px;font-size:14px;cursor:pointer;">
                        Subscribe
                    </button>
                </form>
            </div>
        </section>

        <!-- Recent Comments Section -->
        <section style="margin-top:40px;">
            <h2 style="font-size:24px;margin-bottom:20px;text-align:center;">Recent Comments</h2>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;">
                <div style="background:#fff;border-radius:10px;padding:20px;box-shadow:0 4px 12px rgba(0,0,0,0.05);">
                    <div style="display:flex;align-items:center;margin-bottom:12px;">
                        <div style="width:40px;height:40px;background:#e3f2fd;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#1976d2;font-weight:bold;margin-right:12px;">
                            A
                        </div>
                        <div>
                            <div style="font-weight:600;font-size:14px;">Amit Kumar</div>
                            <div style="font-size:12px;color:#888;">2 hours ago</div>
                        </div>
                    </div>
                    <p style="font-size:14px;color:#555;margin:0;line-height:1.5;">
                        "Great tips on saving money! Already applied some coupon combinations and saved ₹500 on my last order."
                    </p>
                </div>
                <div style="background:#fff;border-radius:10px;padding:20px;box-shadow:0 4px 12px rgba(0,0,0,0.05);">
                    <div style="display:flex;align-items:center;margin-bottom:12px;">
                        <div style="width:40px;height:40px;background:#fce4ec;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#c2185b;font-weight:bold;margin-right:12px;">
                            P
                        </div>
                        <div>
                            <div style="font-weight:600;font-size:14px;">Priya Singh</div>
                            <div style="font-size:12px;color:#888;">5 hours ago</div>
                        </div>
                    </div>
                    <p style="font-size:14px;color:#555;margin:0;line-height:1.5;">
                        "The new arrivals section is amazing! Found exactly what I was looking for at a great price."
                    </p>
                </div>
                <div style="background:#fff;border-radius:10px;padding:20px;box-shadow:0 4px 12px rgba(0,0,0,0.05);">
                    <div style="display:flex;align-items:center;margin-bottom:12px;">
                        <div style="width:40px;height:40px;background:#e8f5e8;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#2e7d32;font-weight:bold;margin-right:12px;">
                            R
                        </div>
                        <div>
                            <div style="font-weight:600;font-size:14px;">Rahul Verma</div>
                            <div style="font-size:12px;color:#888;">1 day ago</div>
                        </div>
                    </div>
                    <p style="font-size:14px;color:#555;margin:0;line-height:1.5;">
                        "Behind the scenes article was really interesting. Love knowing more about how E_Cart works!"
                    </p>
                </div>
            </div>
        </section>
    </div>
</main>

     <?php include "Footer.php"; ?>

    <script src="assets/js/index.js"></script>



</body>
</html>