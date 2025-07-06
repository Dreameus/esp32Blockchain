<?php
session_start();
require_once 'config.php';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $name = trim($_POST['name'] ?? '');
    
    // Validate email
    if (empty($email)) {
        $message = 'Email is required!';
        $message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address!';
        $message_type = 'error';
    } else {
        try {
            $conn = getDBConnection();
            
            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM email_subscriptions WHERE email = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $message = 'This email is already subscribed!';
                $message_type = 'warning';
            } else {
                // Insert new subscription
                $stmt = $conn->prepare("INSERT INTO email_subscriptions (email, name, subscribed_at, status) VALUES (?, ?, NOW(), 'active')");
                $stmt->bind_param('ss', $email, $name);
                $stmt->execute();
                
                if ($stmt->affected_rows > 0) {
                    $message = 'Successfully subscribed! You will receive updates about our blockchain project.';
                    $message_type = 'success';
                    
                    // Clear form
                    $email = '';
                    $name = '';
                } else {
                    $message = 'Failed to subscribe. Please try again.';
                    $message_type = 'error';
                }
            }
            
            $conn->close();
            
        } catch (Exception $e) {
            $message = 'Database error: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscribe - ESP32 Blockchain</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 50%, #16213e 100%);
            min-height: 100vh;
            color: #00ff88;
            margin: 0;
            padding: 20px;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid #00ff88;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 0 30px rgba(0, 255, 136, 0.3);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            text-shadow: 0 0 20px #00ff88;
        }
        
        .header p {
            color: #888;
            font-size: 1.1em;
        }
        
        .subscription-form {
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #00ffff;
            font-weight: bold;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #00ff88;
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.7);
            color: #fff;
            font-family: 'Courier New', monospace;
            font-size: 16px;
            box-sizing: border-box;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #00ffff;
            box-shadow: 0 0 15px rgba(0, 255, 255, 0.5);
        }
        
        .subscribe-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(45deg, #00ff88, #00ffff);
            border: none;
            border-radius: 8px;
            color: #000;
            font-family: 'Courier New', monospace;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .subscribe-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0, 255, 136, 0.4);
        }
        
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
        }
        
        .message.success {
            background: rgba(0, 255, 136, 0.2);
            border: 1px solid #00ff88;
            color: #00ff88;
        }
        
        .message.error {
            background: rgba(255, 0, 136, 0.2);
            border: 1px solid #ff0088;
            color: #ff0088;
        }
        
        .message.warning {
            background: rgba(255, 136, 0, 0.2);
            border: 1px solid #ff8800;
            color: #ff8800;
        }
        
        .benefits {
            background: rgba(0, 255, 136, 0.1);
            border: 1px solid #00ff88;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .benefits h3 {
            color: #00ffff;
            margin-bottom: 15px;
        }
        
        .benefits ul {
            list-style: none;
            padding: 0;
        }
        
        .benefits li {
            padding: 8px 0;
            color: #ccc;
        }
        
        .benefits li:before {
            content: "✓ ";
            color: #00ff88;
            font-weight: bold;
        }
        
        .nav-links {
            text-align: center;
            margin-top: 30px;
        }
        
        .nav-links a {
            color: #00ffff;
            text-decoration: none;
            margin: 0 10px;
            padding: 8px 16px;
            border: 1px solid #00ffff;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        .nav-links a:hover {
            background: #00ffff;
            color: #000;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(0, 255, 136, 0.1);
            border: 1px solid #00ff88;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
        }
        
        .stat-value {
            font-size: 2em;
            font-weight: bold;
            color: #00ffff;
        }
        
        .stat-label {
            color: #888;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📧 Subscribe</h1>
            <p>Stay updated with the latest ESP32 Blockchain developments</p>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-value">
                    <?php
                    try {
                        $conn = getDBConnection();
                        $result = $conn->query("SELECT COUNT(*) as count FROM email_subscriptions WHERE status = 'active'");
                        $count = $result->fetch_assoc()['count'];
                        echo number_format($count);
                        $conn->close();
                    } catch (Exception $e) {
                        echo '0';
                    }
                    ?>
                </div>
                <div class="stat-label">Subscribers</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">
                    <?php
                    try {
                        $conn = getDBConnection();
                        $result = $conn->query("SELECT COUNT(*) as count FROM blocks WHERE status = 'confirmed'");
                        $count = $result->fetch_assoc()['count'];
                        echo number_format($count);
                        $conn->close();
                    } catch (Exception $e) {
                        echo '0';
                    }
                    ?>
                </div>
                <div class="stat-label">Blocks Mined</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">
                    <?php
                    try {
                        $conn = getDBConnection();
                        $result = $conn->query("SELECT COUNT(*) as count FROM users");
                        $count = $result->fetch_assoc()['count'];
                        echo number_format($count);
                        $conn->close();
                    } catch (Exception $e) {
                        echo '0';
                    }
                    ?>
                </div>
                <div class="stat-label">Users</div>
            </div>
        </div>
        
        <!-- Benefits -->
        <div class="benefits">
            <h3>🎯 What you'll receive:</h3>
            <ul>
                <li>Latest mining statistics and achievements</li>
                <li>New feature announcements and updates</li>
                <li>ESP32 hardware integration news</li>
                <li>Community highlights and success stories</li>
                <li>Technical tutorials and guides</li>
                <li>Exclusive early access to new features</li>
            </ul>
        </div>
        
        <!-- Subscription Form -->
        <form class="subscription-form" method="POST">
            <div class="form-group">
                <label for="name">Name (Optional):</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" placeholder="Your name">
            </div>
            
            <div class="form-group">
                <label for="email">Email Address:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" placeholder="your.email@example.com" required>
            </div>
            
            <button type="submit" class="subscribe-btn">📧 Subscribe Now</button>
        </form>
        
        <div class="nav-links">
            <a href="index.php">🏠 Home</a>
            <a href="dashboard.php">📊 Dashboard</a>
            <a href="mined_blocks.php">🔨 Mined Blocks</a>
            <a href="contest.php">🎯 Contest</a>
        </div>
    </div>
</body>
</html> 