<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

try {
    $conn = getDBConnection();
    
    // Get the latest confirmed block
    $sql = "SELECT * FROM blocks WHERE status = 'confirmed' ORDER BY id DESC LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $block = $result->fetch_assoc();
        echo json_encode([
            'success' => true,
            'index' => (int)$block['id'],
            'hash' => $block['hash'],
            'previous_hash' => $block['previous_hash'],
            'timestamp' => (int)$block['timestamp'],
            'nonce' => (int)$block['nonce'],
            'miner_id' => (int)$block['miner_id']
        ]);
    } else {
        // No blocks found, return genesis block info
        echo json_encode([
            'success' => true,
            'index' => 0,
            'hash' => '0000000000000000000000000000000000000000000000000000000000000000',
            'previous_hash' => '0000000000000000000000000000000000000000000000000000000000000000',
            'timestamp' => time(),
            'nonce' => 0,
            'miner_id' => 0
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
} finally {
    if (isset($conn)) $conn->close();
}
?> 