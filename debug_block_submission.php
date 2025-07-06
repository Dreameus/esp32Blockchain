<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

// Debug: Log all incoming data
$raw_input = file_get_contents('php://input');
error_log("DEBUG: Raw input received: " . $raw_input);
error_log("DEBUG: Raw input length: " . strlen($raw_input));
error_log("DEBUG: Raw input hex: " . bin2hex($raw_input));

// Get POST data
$input = json_decode($raw_input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("DEBUG: JSON decode error: " . json_last_error_msg());
    error_log("DEBUG: JSON error code: " . json_last_error());
    error_log("DEBUG: First 100 chars of raw input: " . substr($raw_input, 0, 100));
    
    // Try to identify the issue
    $cleaned_input = trim($raw_input);
    if (empty($cleaned_input)) {
        error_log("DEBUG: Input is empty");
        http_response_code(400);
        echo json_encode(['error' => 'Empty input received']);
        exit;
    }
    
    // Check for common JSON issues
    if (strpos($cleaned_input, 'undefined') !== false) {
        error_log("DEBUG: Input contains 'undefined'");
    }
    if (strpos($cleaned_input, 'null') !== false) {
        error_log("DEBUG: Input contains 'null'");
    }
    
    http_response_code(400);
    echo json_encode([
        'error' => 'Invalid JSON: ' . json_last_error_msg(),
        'raw_input' => substr($raw_input, 0, 200),
        'input_length' => strlen($raw_input)
    ]);
    exit;
}

error_log("DEBUG: Parsed input: " . json_encode($input));

if (!isset($input['index']) || !isset($input['timestamp']) || 
    !isset($input['previous_hash']) || !isset($input['nonce']) || 
    !isset($input['hash'])) {
    error_log("DEBUG: Missing required fields");
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$user_id = $_SESSION['user_id'] ?? 1; // Default to user 1 for testing
$index = (int)$input['index'];
$timestamp = (int)$input['timestamp'];
$previous_hash = $input['previous_hash'];
$nonce = (int)$input['nonce'];
$hash = $input['hash'];

error_log("DEBUG: Processed data - Index: $index, Timestamp: $timestamp, Nonce: $nonce, Hash: $hash");

// Recalculate the hash to verify
$blockString = $index . $timestamp . $previous_hash . $nonce;
$calculatedHash = hash('sha256', $blockString);

error_log("DEBUG: Block string: $blockString");
error_log("DEBUG: Calculated hash: $calculatedHash");
error_log("DEBUG: Received hash: $hash");
error_log("DEBUG: Hashes match: " . ($calculatedHash === $hash ? 'YES' : 'NO'));

// Check difficulty
$meets_difficulty = true;
for ($i = 0; $i < DIFFICULTY; $i++) {
    if ($calculatedHash[$i] !== '0') {
        $meets_difficulty = false;
        break;
    }
}

error_log("DEBUG: Meets difficulty " . DIFFICULTY . ": " . ($meets_difficulty ? 'YES' : 'NO'));

// Validate hash format
if (strlen($hash) !== 64) {
    error_log("DEBUG: Invalid hash length: " . strlen($hash));
    http_response_code(400);
    echo json_encode(['error' => 'Invalid hash format']);
    exit;
}

// Validate previous hash format
if (strlen($previous_hash) !== 64) {
    error_log("DEBUG: Invalid previous hash length: " . strlen($previous_hash));
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
    
    error_log("DEBUG: Block data to store: " . json_encode($blockData));
    
    // Insert into pending blocks
    $sql = "INSERT INTO pending_blocks (data, submitted_by, status) VALUES (?, ?, 'pending')";
    $stmt = $conn->prepare($sql);
    $jsonData = json_encode($blockData);
    $stmt->bind_param('si', $jsonData, $user_id);
    $stmt->execute();
    
    $block_id = $conn->insert_id;
    error_log("DEBUG: Block inserted with ID: $block_id");
    
    echo json_encode([
        'success' => true,
        'message' => 'Block submitted for validation',
        'block_id' => $block_id,
        'debug_info' => [
            'calculated_hash' => $calculatedHash,
            'received_hash' => $hash,
            'hashes_match' => $calculatedHash === $hash,
            'meets_difficulty' => $meets_difficulty,
            'block_string' => $blockString
        ]
    ]);
    
} catch (Exception $e) {
    error_log("DEBUG: Exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
} finally {
    if (isset($conn)) $conn->close();
}
?> 