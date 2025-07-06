-- ESP32 Blockchain Database Schema
-- Create database
CREATE DATABASE esp32_blockchain;
USE esp32_blockchain;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    wallet_address VARCHAR(64) UNIQUE NOT NULL,
    is_admin BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- User balances
CREATE TABLE balances (
    user_id INT PRIMARY KEY,
    balance BIGINT NOT NULL DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Blocks (confirmed)
CREATE TABLE blocks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hash VARCHAR(64) UNIQUE NOT NULL,
    previous_hash VARCHAR(64) NOT NULL,
    miner_id INT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    nonce BIGINT NOT NULL,
    reward BIGINT NOT NULL,
    status ENUM('pending', 'confirmed', 'rejected') DEFAULT 'confirmed',
    FOREIGN KEY (miner_id) REFERENCES users(id)
);

-- Pending blocks (awaiting ESP32 validation)
CREATE TABLE pending_blocks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    data JSON NOT NULL,
    submitted_by INT,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'confirmed', 'rejected') DEFAULT 'pending',
    FOREIGN KEY (submitted_by) REFERENCES users(id)
);

-- Transactions (confirmed)
CREATE TABLE transactions (
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
);

-- Pending transactions (awaiting ESP32 validation)
CREATE TABLE pending_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT,
    receiver_id INT,
    amount BIGINT NOT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'confirmed', 'rejected') DEFAULT 'pending',
    FOREIGN KEY (sender_id) REFERENCES users(id),
    FOREIGN KEY (receiver_id) REFERENCES users(id)
);

-- Mining leaderboard
CREATE TABLE mining_leaderboard (
    user_id INT PRIMARY KEY,
    blocks_mined INT DEFAULT 0,
    total_reward BIGINT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Rewards table (for user-to-user rewards)
CREATE TABLE rewards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    reason VARCHAR(100) NOT NULL,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    amount BIGINT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id),
    FOREIGN KEY (sender_id) REFERENCES users(id),
    FOREIGN KEY (receiver_id) REFERENCES users(id)
);

-- Indexes for better performance
CREATE INDEX idx_blocks_hash ON blocks(hash);
CREATE INDEX idx_blocks_miner ON blocks(miner_id);
CREATE INDEX idx_transactions_sender ON transactions(sender_id);
CREATE INDEX idx_transactions_receiver ON transactions(receiver_id);
CREATE INDEX idx_pending_blocks_status ON pending_blocks(status);
CREATE INDEX idx_pending_transactions_status ON pending_transactions(status);
CREATE INDEX idx_rewards_sender ON rewards(sender_id);
CREATE INDEX idx_rewards_receiver ON rewards(receiver_id); 