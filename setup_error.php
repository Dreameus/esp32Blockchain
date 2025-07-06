<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ESP32 Blockchain - Setup Error</title>
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
            border: 2px solid #ff6666;
            border-radius: 15px;
            padding: 40px;
            width: 100%;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 0 30px rgba(255, 102, 102, 0.3);
        }

        .error-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #ff6666;
        }

        h1 {
            color: #ff6666;
            margin-bottom: 20px;
            text-shadow: 0 0 10px #ff6666;
        }

        .error-details {
            background: rgba(255, 102, 102, 0.1);
            border: 1px solid #ff6666;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            text-align: left;
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
        <div class="error-icon">❌</div>
        <h1>Setup Failed</h1>
        <p>There was an error during the ESP32 Blockchain setup.</p>
        
        <div class="error-details">
            <h3>Error Details:</h3>
            <p><?php echo htmlspecialchars($_GET['error'] ?? 'Unknown error'); ?></p>
        </div>
        
        <p>Please check your database configuration and try again.</p>
        
        <a href="debug.php" class="btn">Run Debug</a>
        <a href="create_tables.php" class="btn">Create Tables</a>
        <a href="index.php" class="btn">Go to Home</a>
    </div>
</body>
</html> 