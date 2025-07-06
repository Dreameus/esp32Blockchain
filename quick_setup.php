<?php
// Suppress output to avoid header issues
ob_start();

require_once 'config.php';

try {
    $conn = getDBConnection();
    
    // Create users table if not exists
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        wallet_address VARCHAR(64) UNIQUE NOT NULL,
        is_admin BOOLEAN DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->query($sql);
    
    // Create balances table if not exists
    $sql = "CREATE TABLE IF NOT EXISTS balances (
        user_id INT PRIMARY KEY,
        balance BIGINT NOT NULL DEFAULT 0,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";
    $conn->query($sql);
    
    // Create blocks table if not exists
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
    $conn->query($sql);
    
    // Create mining_leaderboard table if not exists
    $sql = "CREATE TABLE IF NOT EXISTS mining_leaderboard (
        user_id INT PRIMARY KEY,
        blocks_mined INT DEFAULT 0,
        total_reward BIGINT DEFAULT 0,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";
    $conn->query($sql);
    
    // Check if admin user exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = 'admin'");
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Create admin user
        $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
        $admin_wallet = generateWalletAddress();
        
        $stmt = $conn->prepare("INSERT INTO users (username, password_hash, wallet_address, is_admin) VALUES (?, ?, ?, 1)");
        $stmt->bind_param('sss', 'admin', $admin_password, $admin_wallet);
        $stmt->execute();
        
        $admin_id = $conn->insert_id;
        
        // Give admin initial token supply
        $stmt = $conn->prepare("INSERT INTO balances (user_id, balance) VALUES (?, ?)");
        $total_supply_value = TOTAL_SUPPLY;
        $stmt->bind_param('ii', $admin_id, $total_supply_value);
        $stmt->execute();
        
        // Create mining leaderboard entry
        $stmt = $conn->prepare("INSERT INTO mining_leaderboard (user_id, blocks_mined, total_reward) VALUES (?, 0, 0)");
        $stmt->bind_param('i', $admin_id);
        $stmt->execute();
    }
    
    $conn->close();
    
    // Clear any output and redirect
    ob_end_clean();
    
    // Redirect to success page
    header('Location: setup_success.php');
    exit;
    
} catch (Exception $e) {
    // Clear any output and redirect to error page
    ob_end_clean();
    header('Location: setup_error.php?error=' . urlencode($e->getMessage()));
    exit;
}
?> 