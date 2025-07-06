<?php
session_start();
require_once 'config.php';

$message = '';
$message_type = '';
$email = '';

// Check if unsubscribe token is provided
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];
    
    try {
        $conn = getDBConnection();
        
        // Find subscriber by token
        $stmt = $conn->prepare("SELECT email, name FROM email_subscriptions WHERE unsubscribe_token = ? AND status = 'active'");
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $subscriber = $result->fetch_assoc();
            $email = $subscriber['email'];
            $name = $subscriber['name'] ?: 'Subscriber';
            
            // Handle unsubscribe confirmation
            if (isset($_POST['confirm_unsubscribe'])) {
                $stmt = $conn->prepare("UPDATE email_subscriptions SET status = 'unsubscribed' WHERE unsubscribe_token = ?");
                $stmt->bind_param('s', $token);
                $stmt->execute();
                
                if ($stmt->affected_rows > 0) {
                    $message = "You have been successfully unsubscribed from our newsletter.";
                    $message_type = 'success';
                } else {
                    $message = "Failed to unsubscribe. Please try again.";
                    $message_type = 'error';
                }
            }
        } else {
            $message = "Invalid or expired unsubscribe link.";
            $message_type = 'error';
        }
        
        $conn->close();
        
    } catch (Exception $e) {
        $message = 'Database error: ' . $e->getMessage();
        $message_type = 'error';
    }
} else {
    $message = "No unsubscribe token provided.";
    $message_type = 'error';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsubscribe - ESP32 Blockchain</title>
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
        
        .unsubscribe-form {
            background: rgba(255, 0, 136, 0.1);
            border: 1px solid #ff0088;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .unsubscribe-form h3 {
            color: #ff0088;
            margin-bottom: 15px;
        }
        
        .unsubscribe-btn {
            background: linear-gradient(45deg, #ff0088, #ff6666);
            border: none;
            color: white;
            padding: 15px 30px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .unsubscribe-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(255, 0, 136, 0.4);
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
        
        .info-box {
            background: rgba(0, 255, 136, 0.1);
            border: 1px solid #00ff88;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .info-box h3 {
            color: #00ffff;
            margin-bottom: 15px;
        }
        
        .info-box ul {
            list-style: none;
            padding: 0;
        }
        
        .info-box li {
            padding: 8px 0;
            color: #ccc;
        }
        
        .info-box li:before {
            content: "ℹ ";
            color: #00ffff;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📧 Unsubscribe</h1>
            <p>Manage your email subscription</p>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($message_type !== 'success' && !empty($email)): ?>
            <div class="unsubscribe-form">
                <h3>Confirm Unsubscribe</h3>
                <p>Are you sure you want to unsubscribe <strong><?php echo htmlspecialchars($email); ?></strong> from our newsletter?</p>
                <p>You will no longer receive updates about:</p>
                <ul style="text-align: left; display: inline-block;">
                    <li>Latest mining statistics</li>
                    <li>New feature announcements</li>
                    <li>ESP32 hardware updates</li>
                    <li>Community highlights</li>
                </ul>
                
                <form method="POST" style="margin-top: 20px;">
                    <button type="submit" name="confirm_unsubscribe" class="unsubscribe-btn">❌ Confirm Unsubscribe</button>
                </form>
            </div>
        <?php endif; ?>
        
        <?php if ($message_type === 'success'): ?>
            <div class="info-box">
                <h3>✅ Successfully Unsubscribed</h3>
                <p>You have been removed from our mailing list. We're sorry to see you go!</p>
                <p>If you change your mind, you can always <a href="subscribe.php" style="color: #00ffff;">resubscribe</a> at any time.</p>
            </div>
        <?php endif; ?>
        
        <div class="nav-links">
            <a href="subscribe.php">📧 Resubscribe</a>
            <a href="index.php">🏠 Home</a>
            <a href="dashboard.php">📊 Dashboard</a>
        </div>
    </div>
</body>
</html> 