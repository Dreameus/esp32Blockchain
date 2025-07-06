<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Test basic JSON output
echo json_encode([
    'success' => true,
    'message' => 'Test confirm_block.php working',
    'test' => 'Basic JSON output test'
]);
exit;
?> 