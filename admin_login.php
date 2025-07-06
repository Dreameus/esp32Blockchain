<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        try {
            $conn = getDBConnection();
            
            $stmt = $conn->prepare("SELECT id, username, password_hash FROM users WHERE username = ? AND is_admin = 1");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password_hash'])) {
                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['admin_username'] = $user['username'];
                    $conn->close();
                    
                    // Use JavaScript redirect instead of header to avoid "headers already sent" error
                    echo "<script>window.location.href = 'admin_panel.php';</script>";
                    exit;
                } else {
                    $error = 'Invalid admin credentials';
                }
            } else {
                $error = 'Invalid admin credentials';
            }
            
            $conn->close();
            
        } catch (Exception $e) {
            $error = 'Database connection error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ESP32 Blockchain - Admin Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', monospace;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 50%, #16213e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #00ff88;
            overflow: hidden;
        }

        .container {
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid #ff0088;
            border-radius: 15px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 0 30px rgba(255, 0, 136, 0.3);
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }

        .container::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, #ff0088, #ff6666, #ff0088, #ff6666);
            border-radius: 15px;
            z-index: -1;
            animation: borderGlow 3s ease-in-out infinite;
        }

        @keyframes borderGlow {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.5em;
            font-weight: bold;
            text-shadow: 0 0 20px #ff0088;
            animation: logoGlow 2s ease-in-out infinite alternate;
        }

        @keyframes logoGlow {
            from { text-shadow: 0 0 20px #ff0088; }
            to { text-shadow: 0 0 30px #ff0088, 0 0 40px #ff0088; }
        }

        .admin-badge {
            text-align: center;
            margin-bottom: 20px;
            padding: 10px;
            background: rgba(255, 0, 136, 0.1);
            border: 1px solid #ff0088;
            border-radius: 8px;
            color: #ff0088;
            font-weight: bold;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            text-shadow: 0 0 10px #ff0088;
        }

        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #ff0088;
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.7);
            color: #ff0088;
            font-family: 'Courier New', monospace;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        input[type="text"]:focus, input[type="password"]:focus {
            outline: none;
            border-color: #ff6666;
            box-shadow: 0 0 20px rgba(255, 102, 102, 0.5);
        }

        .btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            background: linear-gradient(45deg, #ff0088, #ff6666);
            color: white;
            font-family: 'Courier New', monospace;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(255, 0, 136, 0.4);
        }

        .btn:active {
            transform: translateY(0);
        }

        .links {
            text-align: center;
            margin-top: 20px;
        }

        .links a {
            color: #ff6666;
            text-decoration: none;
            margin: 0 10px;
            transition: all 0.3s ease;
        }

        .links a:hover {
            color: #ff0088;
            text-shadow: 0 0 10px #ff0088;
        }

        .error {
            background: rgba(255, 0, 0, 0.2);
            border: 1px solid #ff0000;
            color: #ff6666;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .success {
            background: rgba(0, 255, 0, 0.2);
            border: 1px solid #00ff00;
            color: #00ff88;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }

        .particle {
            position: absolute;
            width: 2px;
            height: 2px;
            background: #ff0088;
            border-radius: 50%;
            animation: float 6s infinite linear;
        }

        @keyframes float {
            0% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100px) rotate(360deg);
                opacity: 0;
            }
        }

        .back-link {
            position: absolute;
            top: 20px;
            left: 20px;
            color: #ff0088;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            color: #ff6666;
            text-shadow: 0 0 10px #ff6666;
        }

        /* Mobile Responsive */
        @media (max-width: 480px) {
            .container {
                padding: 30px 20px;
                margin: 20px;
                max-width: none;
            }
            
            .logo {
                font-size: 2em;
            }
            
            input[type="text"], input[type="password"] {
                padding: 15px;
                font-size: 16px;
            }
            
            .btn {
                padding: 15px;
                font-size: 16px;
            }
            
            .back-link {
                top: 15px;
                left: 15px;
                font-size: 12px;
            }
        }

        @media (max-width: 360px) {
            .container {
                padding: 25px 15px;
                margin: 15px;
            }
            
            .logo {
                font-size: 1.8em;
            }
            
            .form-group {
                margin-bottom: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="particles" id="particles"></div>
    
    <a href="index.php" class="back-link">← Back to Home</a>
    
    <div class="container">
        <div class="logo">ADMIN</div>
        <div class="admin-badge">🔐 Administrator Access</div>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Admin Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Admin Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn">Admin Login</button>
        </form>
        
        <div class="links">
            <a href="index.php">Back to Home</a>
            <a href="login.php">User Login</a>
        </div>
    </div>

    <script>
        // Create floating particles
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 50;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 6 + 's';
                particle.style.animationDuration = (Math.random() * 3 + 3) + 's';
                particlesContainer.appendChild(particle);
            }
        }

        createParticles();
    </script>
</body>
</html> 