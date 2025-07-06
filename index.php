<?php
session_start();
require_once 'config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

// Handle registration
if (isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        try {
            $conn = getDBConnection();
            
            // Check if username exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->bind_param('s', $username);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $error = 'Username already exists';
            } else {
                // Create user
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $wallet_address = generateWalletAddress();
                
                $stmt = $conn->prepare("INSERT INTO users (username, password_hash, wallet_address) VALUES (?, ?, ?)");
                $stmt->bind_param('sss', $username, $password_hash, $wallet_address);
                $stmt->execute();
                
                $user_id = $conn->insert_id;
                
                // Create balance record
                $stmt = $conn->prepare("INSERT INTO balances (user_id, balance) VALUES (?, 0)");
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                
                $success = 'Registration successful! Please login.';
            }
        } catch (Exception $e) {
            $error = 'Registration failed: ' . $e->getMessage();
        }
    }
}

// Handle login
if (isset($_POST['login'])) {
    $username = trim($_POST['login_username']);
    $password = $_POST['login_password'];
    
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT id, username, password_hash FROM users WHERE username = ?");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Invalid password';
            }
        } else {
            $error = 'User not found';
        }
    } catch (Exception $e) {
        $error = 'Login failed: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ESP32 Blockchain - Decentralized Mining Network</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', monospace;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 50%, #16213e 100%);
            color: #00ff88;
            line-height: 1.6;
            overflow-x: hidden;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Navigation */
        .navbar {
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(10px);
            border-bottom: 2px solid #00ff88;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
        }

        .nav-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: #00ffff;
            text-shadow: 0 0 10px #00ffff;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
        }

        .nav-links a {
            color: #00ff88;
            text-decoration: none;
            transition: all 0.3s ease;
            padding: 0.5rem 1rem;
            border-radius: 5px;
        }

        .nav-links a:hover {
            background: rgba(0, 255, 136, 0.1);
            color: #00ffff;
            text-shadow: 0 0 10px #00ffff;
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: #00ff88;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
        }

        .mobile-menu {
            display: none;
            position: fixed;
            top: 80px;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 2px solid #00ff88;
            z-index: 999;
            padding: 1rem;
        }

        .mobile-menu.active {
            display: block;
        }

        .mobile-menu a {
            display: block;
            color: #00ff88;
            text-decoration: none;
            padding: 1rem;
            border-bottom: 1px solid rgba(0, 255, 136, 0.2);
            transition: all 0.3s ease;
        }

        .mobile-menu a:hover {
            background: rgba(0, 255, 136, 0.1);
            color: #00ffff;
        }

        .mobile-menu a:last-child {
            border-bottom: none;
        }

        /* Hero Section */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding-top: 80px;
        }

        .hero-content h1 {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            color: #00ffff;
            text-shadow: 0 0 20px #00ffff;
            animation: glow 2s ease-in-out infinite alternate;
        }

        @keyframes glow {
            from { text-shadow: 0 0 20px #00ffff; }
            to { text-shadow: 0 0 30px #00ffff, 0 0 40px #00ffff; }
        }

        .hero-content p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            color: #00ff88;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 1rem 2rem;
            border: 2px solid #00ff88;
            background: transparent;
            color: #00ff88;
            text-decoration: none;
            border-radius: 10px;
            font-weight: bold;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn:hover {
            background: #00ff88;
            color: #000;
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 255, 136, 0.3);
        }

        .btn-primary {
            background: linear-gradient(45deg, #00ff88, #00ffff);
            color: #000;
            border: none;
        }

        .btn-primary:hover {
            background: linear-gradient(45deg, #00ffff, #00ff88);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 255, 255, 0.3);
        }

        /* Features Section */
        .features {
            padding: 5rem 0;
            background: rgba(0, 0, 0, 0.3);
        }

        .section-title {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 3rem;
            color: #00ffff;
            text-shadow: 0 0 15px #00ffff;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .feature-card {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #00ff88;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 255, 136, 0.2);
            border-color: #00ffff;
        }

        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #00ffff;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #00ffff;
        }

        /* Stats Section */
        .stats {
            padding: 5rem 0;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            text-align: center;
        }

        .stat-item {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #00ff88;
            border-radius: 10px;
            padding: 2rem;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #00ffff;
            text-shadow: 0 0 10px #00ffff;
        }

        .stat-label {
            color: #00ff88;
            margin-top: 0.5rem;
        }

        /* Newsletter Section */
        .newsletter {
            padding: 5rem 0;
            background: rgba(0, 0, 0, 0.3);
            text-align: center;
        }

        .newsletter-form {
            max-width: 500px;
            margin: 0 auto;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .newsletter-input {
            flex: 1;
            min-width: 300px;
            padding: 1rem;
            border: 2px solid #00ff88;
            background: rgba(0, 0, 0, 0.7);
            color: #00ff88;
            border-radius: 10px;
            font-family: 'Courier New', monospace;
        }

        .newsletter-input:focus {
            outline: none;
            border-color: #00ffff;
            box-shadow: 0 0 20px rgba(0, 255, 255, 0.3);
        }

        /* Footer */
        .footer {
            background: rgba(0, 0, 0, 0.9);
            border-top: 2px solid #00ff88;
            padding: 2rem 0;
            text-align: center;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .social-links {
            display: flex;
            gap: 1rem;
        }

        .social-links a {
            color: #00ff88;
            font-size: 1.5rem;
            transition: all 0.3s ease;
        }

        .social-links a:hover {
            color: #00ffff;
            text-shadow: 0 0 10px #00ffff;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 2.5rem;
            }
            
            .nav-links {
                display: none;
            }
            
            .mobile-menu-btn {
                display: block;
            }
            
            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .newsletter-form {
                flex-direction: column;
            }
            
            .newsletter-input {
                min-width: auto;
            }
        }

        @media (max-width: 480px) {
            .hero-content h1 {
                font-size: 2rem;
            }
            
            .hero-content p {
                font-size: 1rem;
            }
            
            .section-title {
                font-size: 2rem;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
            
            .stat-number {
                font-size: 2rem;
            }
            
            .container {
                padding: 0 15px;
            }
            
            .nav-content {
                padding: 0.5rem 0;
            }
            
            .logo {
                font-size: 1.2rem;
            }
        }

        @media (max-width: 360px) {
            .hero-content h1 {
                font-size: 1.8rem;
            }
            
            .cta-buttons .btn {
                padding: 0.8rem 1.5rem;
                font-size: 14px;
            }
            
            .feature-card {
                padding: 1.5rem;
            }
            
            .feature-icon {
                font-size: 2.5rem;
            }
        }

        /* Animations */
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            animation: fadeIn 1s ease forwards;
        }

        @keyframes fadeIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .floating {
            animation: floating 3s ease-in-out infinite;
        }

        @keyframes floating {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <div class="nav-content">
                <div class="logo">ESP32 Blockchain</div>
                <div class="nav-links">
                    <a href="#features">Features</a>
                    <a href="#stats">Stats</a>
                    <a href="#newsletter">Newsletter</a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="contest.php">🎯 Contest</a>
                        <a href="mining_leaderboard.php">🏆 Leaderboard</a>
                        <a href="dashboard.php">Dashboard</a>
                        <a href="logout.php">Logout</a>
                    <?php else: ?>
                        <a href="login.php">Login</a>
                        <a href="register.php">Register</a>
                    <?php endif; ?>
                </div>
                <button class="mobile-menu-btn" onclick="toggleMobileMenu()">☰</button>
            </div>
        </div>
    </nav>

    <!-- Mobile Menu -->
    <div class="mobile-menu" id="mobileMenu">
        <a href="#features">Features</a>
        <a href="#stats">Stats</a>
        <a href="#newsletter">Newsletter</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="contest.php">🎯 Contest</a>
            <a href="mining_leaderboard.php">🏆 Leaderboard</a>
            <a href="dashboard.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        <?php endif; ?>
    </div>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content fade-in">
                <h1 class="floating">ESP32 Blockchain Network</h1>
                <p>Join the future of decentralized mining with our revolutionary ESP32-powered blockchain network. Mine cryptocurrency using real hardware, earn rewards, and be part of the next generation of blockchain technology.</p>
                <div class="cta-buttons">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                    <?php else: ?>
                        <a href="register.php" class="btn btn-primary">Start Mining</a>
                        <a href="login.php" class="btn">Login</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features">
        <div class="container">
            <h2 class="section-title fade-in">Why Choose ESP32 Blockchain?</h2>
            <div class="features-grid">
                <div class="feature-card fade-in">
                    <div class="feature-icon">🔧</div>
                    <h3>Real Hardware Mining</h3>
                    <p>Mine cryptocurrency using actual ESP32 microcontrollers, not just software simulations. Experience true decentralized mining.</p>
                </div>
                <div class="feature-card fade-in">
                    <div class="feature-icon">⚡</div>
                    <h3>Instant Rewards</h3>
                    <p>Earn ESP32 tokens immediately when you successfully mine a block. No waiting, no delays.</p>
                </div>
                <div class="feature-card fade-in">
                    <div class="feature-icon">🌐</div>
                    <h3>Global Network</h3>
                    <p>Join miners from around the world in a truly decentralized network powered by ESP32 devices.</p>
                </div>
                <div class="feature-card fade-in">
                    <div class="feature-icon">🔒</div>
                    <h3>Secure & Transparent</h3>
                    <p>All transactions are verified by the network and stored on an immutable blockchain ledger.</p>
                </div>
                <div class="feature-card fade-in">
                    <div class="feature-icon">📊</div>
                    <h3>Real-time Stats</h3>
                    <p>Monitor your mining performance, hash rates, and earnings in real-time through our intuitive dashboard.</p>
                </div>
                <div class="feature-card fade-in">
                    <div class="feature-icon">🚀</div>
                    <h3>Easy to Start</h3>
                    <p>No complex setup required. Just register, start mining, and begin earning rewards immediately.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section id="stats" class="stats">
        <div class="container">
            <h2 class="section-title fade-in">Network Statistics</h2>
            <div class="stats-grid">
                <div class="stat-item fade-in">
                    <div class="stat-number">1,000,000,000</div>
                    <div class="stat-label">Total ESP32 Tokens</div>
                </div>
                <div class="stat-item fade-in">
                    <div class="stat-number">100</div>
                    <div class="stat-label">Tokens per Block</div>
                </div>
                <div class="stat-item fade-in">
                    <div class="stat-number">2</div>
                    <div class="stat-label">Difficulty Level</div>
                </div>
                <div class="stat-item fade-in">
                    <div class="stat-number">24/7</div>
                    <div class="stat-label">Network Uptime</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Newsletter Section -->
    <section id="newsletter" class="newsletter">
        <div class="container">
            <h2 class="section-title fade-in">Stay Updated</h2>
            <p style="margin-bottom: 2rem; font-size: 1.1rem;">Subscribe to our newsletter for the latest updates, mining tips, and network announcements.</p>
            
            <?php if (isset($_GET['error'])): ?>
                <div style="background: rgba(255, 0, 0, 0.2); border: 1px solid #ff0000; color: #ff6666; padding: 10px; border-radius: 5px; margin-bottom: 20px; text-align: center;">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['success'])): ?>
                <div style="background: rgba(0, 255, 0, 0.2); border: 1px solid #00ff00; color: #00ff88; padding: 10px; border-radius: 5px; margin-bottom: 20px; text-align: center;">
                    <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>
            
            <form class="newsletter-form fade-in" method="POST" action="subscribe.php">
                <input type="email" name="email" class="newsletter-input" placeholder="Enter your email address" required>
                <button type="submit" class="btn btn-primary">Subscribe</button>
            </form>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div>
                    <p>&copy; 2024 ESP32 Blockchain Network. All rights reserved.</p>
                </div>
                <div class="social-links">
                    <a href="#" title="Twitter">📱</a>
                    <a href="#" title="Discord">💬</a>
                    <a href="#" title="GitHub">📦</a>
                    <a href="#" title="Telegram">📞</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Add fade-in animation to elements when they come into view
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animationDelay = '0.2s';
                    entry.target.classList.add('fade-in');
                }
            });
        }, observerOptions);

        // Observe all feature cards and stat items
        document.querySelectorAll('.feature-card, .stat-item').forEach(el => {
            observer.observe(el);
        });

        // Mobile menu toggle
        function toggleMobileMenu() {
            const mobileMenu = document.getElementById('mobileMenu');
            mobileMenu.classList.toggle('active');
        }

        // Close mobile menu when clicking on a link
        document.querySelectorAll('.mobile-menu a').forEach(link => {
            link.addEventListener('click', () => {
                document.getElementById('mobileMenu').classList.remove('active');
            });
        });

        // Smooth scrolling for navigation links
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