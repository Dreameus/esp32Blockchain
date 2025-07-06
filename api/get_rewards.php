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

$user_id = $_SESSION['user_id'];
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

try {
    $conn = getDBConnection();
    
    // Get rewards sent by user
    $sql = "SELECT r.*, u.username as receiver_name, t.timestamp 
            FROM rewards r 
            JOIN users u ON r.receiver_id = u.id 
            JOIN transactions t ON r.transaction_id = t.id 
            WHERE r.sender_id = ? 
            ORDER BY t.timestamp DESC 
            LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iii', $user_id, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $sent_rewards = [];
    while ($row = $result->fetch_assoc()) {
        $sent_rewards[] = [
            'id' => (int)$row['id'],
            'transaction_id' => (int)$row['transaction_id'],
            'receiver_name' => $row['receiver_name'],
            'amount' => (int)$row['amount'],
            'reason' => $row['reason'],
            'timestamp' => $row['timestamp']
        ];
    }
    
    // Get rewards received by user
    $sql = "SELECT r.*, u.username as sender_name, t.timestamp 
            FROM rewards r 
            JOIN users u ON r.sender_id = u.id 
            JOIN transactions t ON r.transaction_id = t.id 
            WHERE r.receiver_id = ? 
            ORDER BY t.timestamp DESC 
            LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iii', $user_id, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $received_rewards = [];
    while ($row = $result->fetch_assoc()) {
        $received_rewards[] = [
            'id' => (int)$row['id'],
            'transaction_id' => (int)$row['transaction_id'],
            'sender_name' => $row['sender_name'],
            'amount' => (int)$row['amount'],
            'reason' => $row['reason'],
            'timestamp' => $row['timestamp']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'sent_rewards' => $sent_rewards,
        'received_rewards' => $received_rewards,
        'sent_count' => count($sent_rewards),
        'received_count' => count($received_rewards)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
} finally {
    if (isset($conn)) $conn->close();
}
?> 