-- Create admin user and initial balance
-- Run this in your MySQL database if setup.php continues to have issues

-- First, check if admin user exists
SELECT 'Checking if admin user exists...' as status;

-- Create admin user (replace 'your_hashed_password' with actual hash)
INSERT INTO users (username, password_hash, wallet_address, is_admin) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin_wallet_0000000000000000000000000000000000000000000000000000000000000000', 1)
ON DUPLICATE KEY UPDATE id=id;

-- Get admin user ID
SET @admin_id = (SELECT id FROM users WHERE username = 'admin' LIMIT 1);

-- Create initial balance for admin (1 billion tokens)
INSERT INTO balances (user_id, balance) 
VALUES (@admin_id, 1000000000)
ON DUPLICATE KEY UPDATE balance = 1000000000;

-- Create genesis block if it doesn't exist
INSERT INTO blocks (hash, previous_hash, miner_id, timestamp, nonce, reward, status)
VALUES (
    '0000000000000000000000000000000000000000000000000000000000000000',
    '0000000000000000000000000000000000000000000000000000000000000000',
    @admin_id,
    UNIX_TIMESTAMP(),
    0,
    0,
    'confirmed'
)
ON DUPLICATE KEY UPDATE id=id;

-- Show results
SELECT 'Admin user created successfully!' as status;
SELECT username, wallet_address, is_admin FROM users WHERE username = 'admin';
SELECT user_id, balance FROM balances WHERE user_id = @admin_id;
SELECT COUNT(*) as total_blocks FROM blocks; 