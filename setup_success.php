<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ESP32 Blockchain - Setup Complete</title>
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
        }

        .container {
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid #00ff88;
            border-radius: 15px;
            padding: 40px;
            width: 100%;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 0 30px rgba(0, 255, 136, 0.3);
        }

        .success-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #00ff88;
        }

        h1 {
            color: #00ffff;
            margin-bottom: 20px;
            text-shadow: 0 0 10px #00ffff;
        }

        .admin-info {
            background: rgba(0, 255, 136, 0.1);
            border: 1px solid #00ff88;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }

        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(45deg, #00ff88, #00ffff);
            color: #000;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            margin: 10px;
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0, 255, 136, 0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-icon">✅</div>
        <h1>Setup Complete!</h1>
        <p>ESP32 Blockchain database has been successfully configured.</p>
        
        <div class="admin-info">
            <h3>Admin Account Created</h3>
            <p><strong>Username:</strong> admin</p>
            <p><strong>Password:</strong> admin123</p>
            <p><strong>Initial Balance:</strong> 1,000,000,000 ESP32 tokens</p>
        </div>
        
        <p>You can now:</p>
        <a href="login.php" class="btn">Login as Admin</a>
        <a href="register.php" class="btn">Create New Account</a>
        <a href="index.php" class="btn">Go to Home</a>
    </div>
</body>
</html> 