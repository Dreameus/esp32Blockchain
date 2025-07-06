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
    
    // Get recent mined blocks with miner information
    $sql = "SELECT mb.*, u.username as miner_name 
            FROM mined_blocks mb 
            LEFT JOIN users u ON mb.miner_id = u.id 
            ORDER BY mb.confirmation_time DESC 
            LIMIT 20";
    
    $result = $conn->query($sql);
    $blocks = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $blocks[] = [
                'id' => (int)$row['block_id'],
                'hash' => $row['hash'],
                'previous_hash' => $row['previous_hash'],
                'miner' => $row['miner_username'],
                'nonce' => (int)$row['nonce'],
                'difficulty' => (int)$row['difficulty'],
                'reward' => (int)$row['reward'],
                'timestamp' => strtotime($row['confirmation_time']),
                'mined_at' => $row['confirmation_time']
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'blocks' => $blocks,
        'count' => count($blocks)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
} finally {
    if (isset($conn)) $conn->close();
}
?> 