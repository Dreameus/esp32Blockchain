<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['index']) || !isset($input['timestamp']) || 
    !isset($input['previous_hash']) || !isset($input['nonce']) || 
    !isset($input['hash'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$user_id = $_SESSION['user_id'];
$index = (int)$input['index'];
$timestamp = (int)$input['timestamp'];
$previous_hash = $input['previous_hash'];
$nonce = (int)$input['nonce'];
$hash = $input['hash'];

// Validate hash format
if (strlen($hash) !== 64) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid hash format']);
    exit;
}

// Validate previous hash format
if (strlen($previous_hash) !== 64) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid previous hash format']);
    exit;
}

try {
    $conn = getDBConnection();
    
    // Create block data
    $blockData = [
        'index' => $index,
        'timestamp' => $timestamp,
        'previous_hash' => $previous_hash,
        'nonce' => $nonce,
        'hash' => $hash,
        'miner_id' => $user_id
    ];
    
    // Insert into pending blocks
    $sql = "INSERT INTO pending_blocks (data, submitted_by, status) VALUES (?, ?, 'pending')";
    $stmt = $conn->prepare($sql);
    $jsonData = json_encode($blockData);
    $stmt->bind_param('si', $jsonData, $user_id);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'Block submitted for validation',
        'block_id' => $conn->insert_id
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
} finally {
    if (isset($conn)) $conn->close();
}
?> 