<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config.php';

// Check ESP32 token
if (!isset($_GET['token']) || $_GET['token'] !== ESP32_TOKEN) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    echo json_encode([
        'success' => true,
        'difficulty' => DIFFICULTY,
        'message' => 'Current mining difficulty: ' . DIFFICULTY . ' leading zeros'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?> 