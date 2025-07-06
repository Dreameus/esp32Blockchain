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
    $conn = getDBConnection();
    
    // Get pending blocks
    $sql = "SELECT id, data FROM pending_blocks WHERE status = 'pending' ORDER BY submitted_at ASC LIMIT 10";
    $result = $conn->query($sql);
    
    $blocks = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $blocks[] = [
                'id' => (int)$row['id'],
                'data' => json_decode($row['data'], true)
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