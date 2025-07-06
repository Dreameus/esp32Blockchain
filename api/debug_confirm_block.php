<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output
ini_set('log_errors', 1);
ini_set('error_log', '../debug_errors.log');

// Start output buffering to catch any unexpected output
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Test if config.php can be loaded
    if (!file_exists('../config.php')) {
        throw new Exception('config.php not found');
    }
    
    require_once '../config.php';
    
    // Test if ESP32_TOKEN is defined
    if (!defined('ESP32_TOKEN')) {
        throw new Exception('ESP32_TOKEN not defined in config.php');
    }
    
    // Check ESP32 token
    if (!isset($_GET['token']) || $_GET['token'] !== ESP32_TOKEN) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    // Get POST data
    $raw_input = file_get_contents('php://input');
    if (empty($raw_input)) {
        throw new Exception('No POST data received');
    }
    
    $input = json_decode($raw_input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON: ' . json_last_error_msg());
    }
    
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
    
    // Test database connection
    if (!function_exists('getDBConnection')) {
        throw new Exception('getDBConnection function not found');
    }
    
    $conn = getDBConnection();
    if (!$conn) {
        throw new Exception('Failed to connect to database');
    }
    
    // Simple test - just update the block status
    $sql = "UPDATE pending_blocks SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . $conn->error);
    }
    
    $stmt->bind_param('si', $status, $block_id);
    $stmt->execute();
    
    if ($stmt->affected_rows === 0) {
        throw new Exception("Block ID $block_id not found or already processed");
    }
    
    $conn->close();
    
    // Get any unexpected output
    $unexpected_output = ob_get_clean();
    if (!empty($unexpected_output)) {
        error_log("Unexpected output in debug_confirm_block.php: " . $unexpected_output);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Block ' . $status,
        'block_id' => $block_id,
        'details' => 'Debug version - simplified processing',
        'unexpected_output' => $unexpected_output
    ]);
    
} catch (Exception $e) {
    // Get any unexpected output
    $unexpected_output = ob_get_clean();
    
    error_log("debug_confirm_block.php error: " . $e->getMessage());
    if (!empty($unexpected_output)) {
        error_log("Unexpected output: " . $unexpected_output);
    }
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error: ' . $e->getMessage(),
        'unexpected_output' => $unexpected_output,
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?> 