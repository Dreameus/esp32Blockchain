<?php
// Enable error reporting for debugging but don't display errors
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start output buffering to catch any unexpected output
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config.php';

// Check ESP32 token
if (!isset($_GET['token']) || $_GET['token'] !== ESP32_TOKEN) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['block_id']) || !isset($input['status'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$block_id = (int)$input['block_id'];
$status = $input['status'];
$reason = $input['reason'] ?? '';

if (!in_array($status, ['confirmed', 'rejected'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid status']);
    exit;
}

try {
    $conn = getDBConnection();
    $conn->begin_transaction();
    
    // First, check if the block exists
    $sql = "SELECT id, data, submitted_by, status FROM pending_blocks WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $block_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $block = $result->fetch_assoc();
    
    if (!$block) {
        throw new Exception("Block ID $block_id not found");
    }
    
    if ($block['status'] !== 'pending') {
        throw new Exception("Block ID $block_id is already " . $block['status']);
    }
    
    // Update pending block status
    $sql = "UPDATE pending_blocks SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $status, $block_id);
    $stmt->execute();
    
    if ($stmt->affected_rows === 0) {
        throw new Exception("Failed to update block status");
    }
    
    if ($status === 'confirmed') {
        $blockData = json_decode($block['data'], true);
        $miner_id = $block['submitted_by'];
        
        if (!$blockData) {
            throw new Exception("Invalid block data format");
        }
        
        // Validate required fields
        $required_fields = ['hash', 'previous_hash', 'timestamp', 'nonce'];
        foreach ($required_fields as $field) {
            if (!isset($blockData[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }
        
        // Insert into confirmed blocks table
        $sql = "INSERT INTO blocks (hash, previous_hash, miner_id, timestamp, nonce, reward, status) VALUES (?, ?, ?, ?, ?, ?, 'confirmed')";
        $stmt = $conn->prepare($sql);
        $hash = $blockData['hash'];
        $reward_amount_value = REWARD_AMOUNT;
        $stmt->bind_param('ssisii', $hash, $blockData['previous_hash'], $miner_id, $blockData['timestamp'], $blockData['nonce'], $reward_amount_value);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            throw new Exception("Failed to insert confirmed block");
        }
        
        // Award tokens to miner
        $sql = "UPDATE balances SET balance = balance + ? WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $reward_amount_value = REWARD_AMOUNT;
        $stmt->bind_param('ii', $reward_amount_value, $miner_id);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            throw new Exception("Failed to update miner balance");
        }
        
        // Update mining leaderboard
        $sql = "INSERT INTO mining_leaderboard (user_id, blocks_mined, total_reward) VALUES (?, 1, ?) 
                ON DUPLICATE KEY UPDATE blocks_mined = blocks_mined + 1, total_reward = total_reward + ?";
        $stmt = $conn->prepare($sql);
        $reward_amount_value = REWARD_AMOUNT;
        $stmt->bind_param('iii', $miner_id, $reward_amount_value, $reward_amount_value);
        $stmt->execute();
        
        // Log successful confirmation
        error_log("Block $block_id confirmed by ESP32. Miner: $miner_id, Hash: $hash");
    } else {
        // Log rejection
        error_log("Block $block_id rejected by ESP32. Reason: $reason");
    }
    
    $conn->commit();
    
    // Get any unexpected output
    $unexpected_output = ob_get_clean();
    if (!empty($unexpected_output)) {
        error_log("Unexpected output in confirm_block.php: " . $unexpected_output);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Block ' . $status,
        'block_id' => $block_id,
        'details' => $status === 'confirmed' ? 'Block added to blockchain and miner rewarded' : 'Block marked as rejected',
        'unexpected_output' => $unexpected_output
    ]);
    
} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    
    // Get any unexpected output
    $unexpected_output = ob_get_clean();
    
    // Log the error
    error_log("confirm_block.php error: " . $e->getMessage() . " for block_id: $block_id, status: $status");
    if (!empty($unexpected_output)) {
        error_log("Unexpected output: " . $unexpected_output);
    }
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error: ' . $e->getMessage(),
        'block_id' => $block_id,
        'status' => $status,
        'reason' => $reason,
        'unexpected_output' => $unexpected_output
    ]);
} finally {
    if (isset($conn)) $conn->close();
}
?> 