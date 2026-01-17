<?php
session_start();
require_once 'db.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SewaHub – Local Services, On Demand</title>

    <link rel="stylesheet" href="css/styles.css">
</head>

<body>

    <!-- NAVBAR -->
    <header class="navbar">
        <div class="logo"><span style="color: #1c034f;">Sewa</span><span style="color:#B8860B" ;>Hub</span></div>
        <nav>
            <a href="#services">Services</a>
            <a href="#how">How It Works</a>

            <button class="open-login">Login</button>



        </nav>
    </header>

    <!-- HERO -->
    <section class="hero">
        <div class="hero-content">
            <h1>Trusted Local Services At Your Doorstep</h1>
            <p>Book verified professionals for home services, repairs, cleaning, and more.</p>
            <div class="hero-buttons">
                <button class="open-user">Get Started</button>
                <button class="open-vendor">Become Vendor</button>


            </div>
        </div>
    </section>
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Why Choose <span class="gradient-text"><span style="color: #150339;">Sewa</span><span style="color: #a87b09;">Hub</span></h2>
            <p class="section-subtitle">
              <strong>  Sewa Hub is more than just a marketplace — it's a trusted ecosystem where
                quality service meets convenience. We connect you with skilled professionals
                for all your service needs.</strong>
            </p>
        </div>

        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                    </svg>
                </div>
                <h3 class="feature-title">Verified Providers</h3>
                <p class="feature-description">All service providers undergo thorough background checks and skill
                    verification.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                        <circle cx="9" cy="7" r="4" />
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                        <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg>
                </div>
                <h3 class="feature-title">Growing Community</h3>
                <p class="feature-description">Join thousands of satisfied customers and trusted service professionals.
                </p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="8" r="7" />
                        <polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88" />
                    </svg>
                </div>
                <h3 class="feature-title">Quality Guaranteed</h3>
                <p class="feature-description">We ensure top-notch service quality with our rating and review system.
                </p>
            </div>
        </div>
    </div>
    <!-- SERVICES -->
<section id="services" class="services">
    <h2>Our Popular Services</h2>

    <div class="services-slider-wrapper">

        <!-- LEFT ARROW -->
        <button class="slider-arrow left" id="servicePrev">&#10094;</button>

        <!-- SERVICES SLIDER -->
        <div class="services-slider" id="servicesSlider">
            <?php
            $sql = "SELECT category,image FROM services ORDER BY service_id ASC";
            $result = $conn->query($sql);

            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $serviceName = trim($row['category']);
                    $image = !empty($row['image']) ? $row['image'] : 'default.jpg';
            ?>
                <div class="service-item"
                     onclick="location.href='service.php?service=<?php echo urlencode($serviceName); ?>'">
                    <div class="service-image">
                        <img src="/sewahub/uploads/services/<?php echo $image; ?>"
                             alt="<?php echo htmlspecialchars($serviceName); ?>"
                             onerror="this.src='/sewahub/uploads/services/default.jpg'">
                    </div>
                    <p class="service-name"><?php echo htmlspecialchars($serviceName); ?></p>
                </div>
            <?php
                }
            }
            ?>
        </div>

        <!-- RIGHT ARROW -->
        <button class="slider-arrow right" id="serviceNext">&#10095;</button>

    </div>
</section>

    <!-- HOW IT WORKS -->
    <section id="how" class="how">
        <h2>How SewaHub Works</h2>
        <div class="steps">
            <div class="step"><span>1</span>
                <p>Search service by location & time</p>
            </div>
            <div class="step"><span>2</span>
                <p>Book available vendor</p>
            </div>
            <div class="step"><span>3</span>
                <p>Service completed</p>
            </div>
            <div class="step"><span>4</span>
                <p>Pay & leave review</p>
            </div>
        </div>
    </section>

    <!-- TESTIMONIALS -->
    <section class="testimonials">
        <h2>What Our Customers Say</h2>

        <div class="testimonial-grid">
            <div class="testimonial-card">
                <div class="testimonial-header">
                    <div class="avatar">A</div>
                    <div>
                        <strong>Karan Jha.</strong>
                        <div class="stars">★★★★★</div>
                    </div>
                </div>
                <p>"Fast and professional service! Highly recommend SewaHub."</p>
            </div>

            <div class="testimonial-card">
                <div class="testimonial-header">
                    <div class="avatar">P</div>
                    <div>
                        <strong>Raunak Nepal.</strong>
                        <div class="stars">★★★★☆</div>
                    </div>
                </div>
                <p>"The plumber arrived on time and solved everything quickly."</p>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="footer">
        <div class="footer-container">

            <div class="footer-brand">
                <h3>
                    <span style="color: #1c034f;">Sewa</span><span style="color:#B8860B;">Hub</span>
                </h3>
                <p>Your trusted local service booking platform.</p>
            </div>

            <div class="footer-links">
                <h4>Quick Links</h4>
                <a href="#services">Services</a>
                <a href="#how">How It Works</a>
                <a href="#">Become a Vendor</a>
                <a href="#">Support</a>
            </div>

            <div class="footer-links">
                <h4>Legal</h4>
                <a href="#">Terms of Service</a>
                <a href="#">Privacy Policy</a>
                <a href="#">Refund Policy</a>
            </div>

        </div>

        <div class="footer-bottom">
            © 2026 SewaHub. All rights reserved.
        </div>
    </footer>

    <!-- AUTH MODAL -->
    <div id="authModal" class="modal">
        <div class="modal-box">
            <span class="close-btn">&times;</span>

            <!-- USER REGISTER -->
            <form id="userForm" class="auth-form" action="user_register.php" method="POST" style="display:none;">
                <h2>User Registration</h2>
                <input type="text" name="first_name" placeholder="First Name" required>
                <input type="text" name="last_name" placeholder="Last Name" required>
                <input type="text" name="address" placeholder="Address" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="phone" name="phone" placeholder="Phone" required>
                <input type="password" name="password" placeholder="Password" required>
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                <button type="submit">Register</button>
            </form>

            <!-- VENDOR REGISTER -->
            <form id="vendorForm" class="auth-form" action="vendor_register.php" method="POST" style="display:none;">
                <h2>Vendor Registration</h2>
                <input type="text" name="Name" placeholder="Name" required>
                <input type="text" name="skills" placeholder="Skills" required>
                <input type="text" name="location" placeholder="Location" required>
                <input type="text" name="experience" placeholder="Experience" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="phone" name="phone" placeholder="Phone" required>
                <input type="password" name="password" placeholder="Password" required>
                <input type="text" name="confirm_password" placeholder="Confirm Password" required>
                <button type="submit">Apply as Vendor</button>
            </form>

            <!-- USER LOGIN -->
            <form id="userLoginForm" class="auth-form" action="login.php" method="POST" style="display:none;">
                <h2>Login</h2>
                <input type="hidden" name="role" value="user">
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit">Login</button>
                <p class="forgot-password"><a href="forgot_password.php?role=user">Forgot Password?</a></p>
            </form>

            <!-- VENDOR LOGIN -->
            <form id="vendorLoginForm" class="auth-form" action="login.php" method="POST" style="display:none;">
                <h2>Vendor Login</h2>
                <input type="hidden" name="role" value="vendor">
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit">Login</button>
                <p class="forgot-password"><a href="forgot_password.php?role=vendor">Forgot Password?</a></p>
            </form>

            <!-- ADMIN LOGIN -->
            <form id="adminLoginForm" class="auth-form" action="login.php" method="POST" style="display:none;">
                <h2>Admin Login</h2>
                <input type="hidden" name="role" value="admin">
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit">Login</button>
                <p class="forgot-password"><a href="forgot_password.php?role=admin">Forgot Password?</a></p>
            </form>
            <!-- FORGOT PASSWORD FORM -->
            <form id="forgotPasswordForm" class="auth-form" style="display:none;">
                <h2>Forgot Password</h2>
                <input type="hidden" name="role" id="forgotRole" value="user">
                <input type="email" name="email" placeholder="Enter your email" required>
                <button type="submit">Send Reset Link</button>
                <p class="back-to-login"><a href="#">Back to Login</a></p>
            </form>

        </div>
    </div>

    <script src="js/form.js"></script>
    <script>
    window.IS_LOGGED_IN = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
    </script>
<script>
const slider = document.getElementById("servicesSlider");
const nextBtn = document.getElementById("serviceNext");
const prevBtn = document.getElementById("servicePrev");

const scrollAmount = 300; // slide distance

nextBtn.addEventListener("click", () => {
    slider.scrollLeft += scrollAmount;
});

prevBtn.addEventListener("click", () => {
    slider.scrollLeft -= scrollAmount;
});
</script>

</body>



</html>