<?php
require_once 'config.php';

echo "<h2>Creating Database Tables</h2>";

try {
    $conn = getDBConnection();
    
    // Create users table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        wallet_address VARCHAR(64) UNIQUE NOT NULL,
        is_admin BOOLEAN DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color: #00ff88;'>✓ Users table created successfully!</p>";
    } else {
        echo "<p style='color: #ff6666;'>✗ Error creating users table: " . $conn->error . "</p>";
    }
    
    // Create balances table
    $sql = "CREATE TABLE IF NOT EXISTS balances (
        user_id INT PRIMARY KEY,
        balance BIGINT NOT NULL DEFAULT 0,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color: #00ff88;'>✓ Balances table created successfully!</p>";
    } else {
        echo "<p style='color: #ff6666;'>✗ Error creating balances table: " . $conn->error . "</p>";
    }
    
    // Create blocks table
    $sql = "CREATE TABLE IF NOT EXISTS blocks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        hash VARCHAR(64) UNIQUE NOT NULL,
        previous_hash VARCHAR(64) NOT NULL,
        miner_id INT,
        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        nonce BIGINT NOT NULL,
        reward BIGINT NOT NULL,
        status ENUM('pending', 'confirmed', 'rejected') DEFAULT 'confirmed',
        FOREIGN KEY (miner_id) REFERENCES users(id)
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color: #00ff88;'>✓ Blocks table created successfully!</p>";
    } else {
        echo "<p style='color: #ff6666;'>✗ Error creating blocks table: " . $conn->error . "</p>";
    }
    
    // Create pending_blocks table
    $sql = "CREATE TABLE IF NOT EXISTS pending_blocks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        data JSON NOT NULL,
        submitted_by INT,
        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('pending', 'confirmed', 'rejected') DEFAULT 'pending',
        FOREIGN KEY (submitted_by) REFERENCES users(id)
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color: #00ff88;'>✓ Pending blocks table created successfully!</p>";
    } else {
        echo "<p style='color: #ff6666;'>✗ Error creating pending blocks table: " . $conn->error . "</p>";
    }
    
    // Create transactions table
    $sql = "CREATE TABLE IF NOT EXISTS transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT,
        receiver_id INT,
        amount BIGINT NOT NULL,
        block_id INT,
        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('pending', 'confirmed', 'rejected') DEFAULT 'confirmed',
        FOREIGN KEY (sender_id) REFERENCES users(id),
        FOREIGN KEY (receiver_id) REFERENCES users(id),
        FOREIGN KEY (block_id) REFERENCES blocks(id)
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color: #00ff88;'>✓ Transactions table created successfully!</p>";
    } else {
        echo "<p style='color: #ff6666;'>✗ Error creating transactions table: " . $conn->error . "</p>";
    }
    
    // Create pending_transactions table
    $sql = "CREATE TABLE IF NOT EXISTS pending_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT,
        receiver_id INT,
        amount BIGINT NOT NULL,
        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('pending', 'confirmed', 'rejected') DEFAULT 'pending',
        FOREIGN KEY (sender_id) REFERENCES users(id),
        FOREIGN KEY (receiver_id) REFERENCES users(id)
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color: #00ff88;'>✓ Pending transactions table created successfully!</p>";
    } else {
        echo "<p style='color: #ff6666;'>✗ Error creating pending transactions table: " . $conn->error . "</p>";
    }
    
    // Create mining_leaderboard table
    $sql = "CREATE TABLE IF NOT EXISTS mining_leaderboard (
        user_id INT PRIMARY KEY,
        blocks_mined INT DEFAULT 0,
        total_reward BIGINT DEFAULT 0,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color: #00ff88;'>✓ Mining leaderboard table created successfully!</p>";
    } else {
        echo "<p style='color: #ff6666;'>✗ Error creating mining leaderboard table: " . $conn->error . "</p>";
    }
    
    // Create rewards table
    $sql = "CREATE TABLE IF NOT EXISTS rewards (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT,
        receiver_id INT,
        amount BIGINT NOT NULL,
        reason VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sender_id) REFERENCES users(id),
        FOREIGN KEY (receiver_id) REFERENCES users(id)
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color: #00ff88;'>✓ Rewards table created successfully!</p>";
    } else {
        echo "<p style='color: #ff6666;'>✗ Error creating rewards table: " . $conn->error . "</p>";
    }
    
    echo "<h3>Database Setup Complete!</h3>";
    echo "<p><a href='setup.php' style='color: #00ffff;'>← Run Full Setup</a></p>";
    echo "<p><a href='index.php' style='color: #00ffff;'>← Back to Home</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: #ff6666;'>✗ Database setup failed: " . $e->getMessage() . "</p>";
} finally {
    if (isset($conn)) $conn->close();
}
?>

<style>
body {
    font-family: 'Courier New', monospace;
    background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 50%, #16213e 100%);
    color: #00ff88;
    padding: 20px;
    line-height: 1.6;
}

h2, h3 {
    color: #00ffff;
    text-shadow: 0 0 10px #00ffff;
}

p {
    margin: 10px 0;
}

a {
    color: #00ffff;
    text-decoration: none;
}

a:hover {
    text-shadow: 0 0 10px #00ffff;
}
</style> 