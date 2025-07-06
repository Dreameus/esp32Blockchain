<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'cp93267_block');
define('DB_PASS', 'Razmikaren1@');
define('DB_NAME', 'cp93267_block');

// ESP32 authentication token
define('ESP32_TOKEN', 'esp32_secret_token_2024');

// Blockchain settings
define('DIFFICULTY', 4);
define('REWARD_AMOUNT', 100);
define('TOTAL_SUPPLY', 1000000000);

// Database connection function
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

// Helper function to generate wallet address
function generateWalletAddress() {
    return 'ESP32_' . bin2hex(random_bytes(16));
}

// Helper function to hash data
function sha256($data) {
    return hash('sha256', $data);
}

// Helper function to check if hash meets difficulty
function meetsDifficulty($hash) {
    for ($i = 0; $i < DIFFICULTY; $i++) {
        if ($hash[$i] !== '0') return false;
    }
    return true;
} 