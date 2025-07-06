<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

try {
    $conn = getDBConnection();
    
    // Create contests table
    $sql = "CREATE TABLE IF NOT EXISTS contests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        start_time DATETIME NOT NULL,
        end_time DATETIME NOT NULL,
        prize_pool INT NOT NULL DEFAULT 1000,
        status ENUM('active', 'ended', 'cancelled') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "✅ Contests table created successfully<br>";
    } else {
        echo "❌ Error creating contests table: " . $conn->error . "<br>";
    }
    
    // Create contest_participants table
    $sql = "CREATE TABLE IF NOT EXISTS contest_participants (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        clicks INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user (user_id)
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "✅ Contest participants table created successfully<br>";
    } else {
        echo "❌ Error creating contest_participants table: " . $conn->error . "<br>";
    }
    
    // Create contest_winners table
    $sql = "CREATE TABLE IF NOT EXISTS contest_winners (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        contest_id INT NOT NULL,
        position INT NOT NULL,
        prize_amount INT NOT NULL,
        clicks INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (contest_id) REFERENCES contests(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "✅ Contest winners table created successfully<br>";
    } else {
        echo "❌ Error creating contest_winners table: " . $conn->error . "<br>";
    }
    
    // Check if admin user exists and create if not
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = 'admin' AND is_admin = 1");
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Create admin user with same username and password
        $admin_username = 'admin';
        $admin_password = 'admin';
        $password_hash = password_hash($admin_password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO users (username, password_hash, is_admin) VALUES (?, ?, 1)");
        $stmt->bind_param("ss", $admin_username, $password_hash);
        
        if ($stmt->execute()) {
            $admin_id = $conn->insert_id;
            
            // Create balance for admin
            $stmt = $conn->prepare("INSERT INTO balances (user_id, balance) VALUES (?, 1000000000)");
            $stmt->bind_param("i", $admin_id);
            $stmt->execute();
            
            echo "✅ Admin user created successfully (username: admin, password: admin)<br>";
        } else {
            echo "❌ Error creating admin user: " . $stmt->error . "<br>";
        }
    } else {
        echo "✅ Admin user already exists<br>";
    }
    
    $conn->close();
    
    echo "<br>🎉 Contest system setup completed!<br>";
    echo "<br>📋 Next steps:<br>";
    echo "1. <a href='admin_login.php'>Login as admin</a> (username: admin, password: admin)<br>";
    echo "2. <a href='admin_panel.php'>Go to admin panel</a> to start a contest<br>";
    echo "3. <a href='contest.php'>View contest page</a> (requires user login)<br>";
    echo "4. <a href='leaderboard_display.php'>View leaderboard display</a> for big screens<br>";
    
} catch (Exception $e) {
    echo "❌ Database connection error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ESP32 Blockchain - Contest Setup</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 50%, #16213e 100%);
            min-height: 100vh;
            color: #00ff88;
            padding: 20px;
            line-height: 1.6;
        }
        
        a {
            color: #ff0088;
            text-decoration: none;
        }
        
        a:hover {
            color: #ff6666;
            text-shadow: 0 0 10px #ff6666;
        }
    </style>
</head>
<body>
    <h1>🎯 Contest System Setup</h1>
    <p>Setting up contest tables and admin user...</p>
</body>
</html> 