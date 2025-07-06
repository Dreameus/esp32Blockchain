<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: login.php');
    exit;
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $test_email = trim($_POST['test_email'] ?? '');
    
    if (empty($subject) || empty($content)) {
        $message = 'Subject and content are required!';
        $message_type = 'error';
    } else {
        try {
            $conn = getDBConnection();
            
            // Get active subscribers
            $stmt = $conn->prepare("SELECT email, name, unsubscribe_token FROM email_subscriptions WHERE status = 'active'");
            $stmt->execute();
            $result = $stmt->get_result();
            $subscribers = $result->fetch_all(MYSQLI_ASSOC);
            
            if (empty($subscribers)) {
                $message = 'No active subscribers found!';
                $message_type = 'warning';
            } else {
                $sent_count = 0;
                $failed_count = 0;
                
                foreach ($subscribers as $subscriber) {
                    $email = $subscriber['email'];
                    $name = $subscriber['name'] ?: 'Subscriber';
                    $unsubscribe_token = $subscriber['unsubscribe_token'];
                    
                    // Create unsubscribe link
                    $unsubscribe_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/unsubscribe.php?token=" . $unsubscribe_token;
                    
                    // Create email content with unsubscribe link
                    $email_content = "
                        <html>
                        <head>
                            <style>
                                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                                .header { background: #00ff88; color: #000; padding: 20px; text-align: center; }
                                .content { padding: 20px; }
                                .footer { background: #f4f4f4; padding: 20px; text-align: center; font-size: 12px; color: #666; }
                                .unsubscribe { color: #ff0088; text-decoration: none; }
                            </style>
                        </head>
                        <body>
                            <div class='header'>
                                <h1>ESP32 Blockchain Newsletter</h1>
                            </div>
                            <div class='content'>
                                <p>Hello $name,</p>
                                " . nl2br(htmlspecialchars($content)) . "
                                <p>Best regards,<br>The ESP32 Blockchain Team</p>
                            </div>
                            <div class='footer'>
                                <p>You received this email because you subscribed to ESP32 Blockchain updates.</p>
                                <p><a href='$unsubscribe_link' class='unsubscribe'>Unsubscribe</a></p>
                            </div>
                        </body>
                        </html>
                    ";
                    
                    // Send email (using PHP mail function - you may want to use a proper email service)
                    $headers = "MIME-Version: 1.0\r\n";
                    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                    $headers .= "From: ESP32 Blockchain <noreply@esp32blockchain.com>\r\n";
                    $headers .= "Reply-To: noreply@esp32blockchain.com\r\n";
                    
                    if (mail($email, $subject, $email_content, $headers)) {
                        $sent_count++;
                        
                        // Update last email sent timestamp
                        $stmt = $conn->prepare("UPDATE email_subscriptions SET last_email_sent = NOW() WHERE email = ?");
                        $stmt->bind_param('s', $email);
                        $stmt->execute();
                    } else {
                        $failed_count++;
                    }
                }
                
                $message = "Newsletter sent! $sent_count emails sent successfully, $failed_count failed.";
                $message_type = 'success';
            }
            
            $conn->close();
            
        } catch (Exception $e) {
            $message = 'Database error: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Get subscription statistics
try {
    $conn = getDBConnection();
    
    $stats_sql = "SELECT 
                    COUNT(*) as total_subscribers,
                    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_subscribers,
                    COUNT(CASE WHEN status = 'unsubscribed' THEN 1 END) as unsubscribed,
                    COUNT(CASE WHEN status = 'bounced' THEN 1 END) as bounced
                  FROM email_subscriptions";
    
    $result = $conn->query($stats_sql);
    $stats = $result->fetch_assoc();
    
    $conn->close();
} catch (Exception $e) {
    $stats = ['total_subscribers' => 0, 'active_subscribers' => 0, 'unsubscribed' => 0, 'bounced' => 0];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Newsletter - ESP32 Blockchain</title>
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
            max-width: 800px;
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
        
        .stats-grid {
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
        
        .newsletter-form {
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
        
        .form-group input,
        .form-group textarea {
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
        
        .form-group textarea {
            height: 200px;
            resize: vertical;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #00ffff;
            box-shadow: 0 0 15px rgba(0, 255, 255, 0.5);
        }
        
        .send-btn {
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
        
        .send-btn:hover {
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
        
        .preview-section {
            background: rgba(0, 255, 136, 0.1);
            border: 1px solid #00ff88;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .preview-section h3 {
            color: #00ffff;
            margin-bottom: 15px;
        }
        
        .preview-content {
            background: #fff;
            color: #333;
            padding: 15px;
            border-radius: 5px;
            font-family: Arial, sans-serif;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📧 Send Newsletter</h1>
            <p>Send updates to all email subscribers</p>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($stats['total_subscribers']); ?></div>
                <div class="stat-label">Total Subscribers</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($stats['active_subscribers']); ?></div>
                <div class="stat-label">Active Subscribers</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($stats['unsubscribed']); ?></div>
                <div class="stat-label">Unsubscribed</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($stats['bounced']); ?></div>
                <div class="stat-label">Bounced</div>
            </div>
        </div>
        
        <!-- Newsletter Form -->
        <form class="newsletter-form" method="POST">
            <div class="form-group">
                <label for="subject">Subject:</label>
                <input type="text" id="subject" name="subject" value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>" placeholder="Newsletter subject" required>
            </div>
            
            <div class="form-group">
                <label for="content">Content:</label>
                <textarea id="content" name="content" placeholder="Write your newsletter content here..." required><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="test_email">Test Email (Optional):</label>
                <input type="email" id="test_email" name="test_email" value="<?php echo htmlspecialchars($_POST['test_email'] ?? ''); ?>" placeholder="Send test email to this address">
            </div>
            
            <button type="submit" class="send-btn">📧 Send Newsletter</button>
        </form>
        
        <!-- Preview Section -->
        <div class="preview-section">
            <h3>📋 Email Preview</h3>
            <div class="preview-content" id="preview">
                <p>Your newsletter preview will appear here...</p>
            </div>
        </div>
        
        <div class="nav-links">
            <a href="dashboard.php">📊 Dashboard</a>
            <a href="manage_subscriptions.php">👥 Manage Subscriptions</a>
            <a href="subscribe.php">📧 Subscribe</a>
            <a href="index.php">🏠 Home</a>
        </div>
    </div>
    
    <script>
        // Live preview functionality
        const subjectInput = document.getElementById('subject');
        const contentInput = document.getElementById('content');
        const preview = document.getElementById('preview');
        
        function updatePreview() {
            const subject = subjectInput.value || 'Newsletter Subject';
            const content = contentInput.value || 'Newsletter content...';
            
            preview.innerHTML = `
                <div style="background: #00ff88; color: #000; padding: 20px; text-align: center; margin-bottom: 20px;">
                    <h1>ESP32 Blockchain Newsletter</h1>
                </div>
                <div style="padding: 20px;">
                    <h2>${subject}</h2>
                    <p>Hello Subscriber,</p>
                    ${content.replace(/\n/g, '<br>')}
                    <p>Best regards,<br>The ESP32 Blockchain Team</p>
                </div>
                <div style="background: #f4f4f4; padding: 20px; text-align: center; font-size: 12px; color: #666;">
                    <p>You received this email because you subscribed to ESP32 Blockchain updates.</p>
                    <p><a href="#" style="color: #ff0088;">Unsubscribe</a></p>
                </div>
            `;
        }
        
        subjectInput.addEventListener('input', updatePreview);
        contentInput.addEventListener('input', updatePreview);
        
        // Initial preview
        updatePreview();
    </script>
</body>
</html> 