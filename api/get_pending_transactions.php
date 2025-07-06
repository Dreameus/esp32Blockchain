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
    
    // Get pending transactions
    $sql = "SELECT id, sender_id, receiver_id, amount FROM pending_transactions WHERE status = 'pending' ORDER BY submitted_at ASC LIMIT 10";
    $result = $conn->query($sql);
    
    $transactions = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $transactions[] = [
                'id' => (int)$row['id'],
                'sender_id' => (int)$row['sender_id'],
                'receiver_id' => (int)$row['receiver_id'],
                'amount' => (int)$row['amount']
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'transactions' => $transactions,
        'count' => count($transactions)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
} finally {
    if (isset($conn)) $conn->close();
}
?> 