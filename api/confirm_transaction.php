<?php
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

if (!isset($input['transaction_id']) || !isset($input['status'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$transaction_id = (int)$input['transaction_id'];
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
    
    // Update pending transaction status
    $sql = "UPDATE pending_transactions SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $status, $transaction_id);
    $stmt->execute();
    
    if ($status === 'confirmed') {
        // Get the transaction data
        $sql = "SELECT sender_id, receiver_id, amount FROM pending_transactions WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $transaction_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $transaction = $result->fetch_assoc();
        
        if ($transaction) {
            $sender_id = $transaction['sender_id'];
            $receiver_id = $transaction['receiver_id'];
            $amount = $transaction['amount'];
            
            // Check sender balance
            $sql = "SELECT balance FROM balances WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $sender_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $sender_balance = $result->fetch_assoc();
            
            if ($sender_balance && $sender_balance['balance'] >= $amount) {
                // Deduct from sender
                $sql = "UPDATE balances SET balance = balance - ? WHERE user_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('ii', $amount, $sender_id);
                $stmt->execute();
                
                // Add to receiver
                $sql = "UPDATE balances SET balance = balance + ? WHERE user_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('ii', $amount, $receiver_id);
                $stmt->execute();
                
                // Insert into confirmed transactions table
                $sql = "INSERT INTO transactions (sender_id, receiver_id, amount, timestamp, status) VALUES (?, ?, ?, NOW(), 'confirmed')";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('iii', $sender_id, $receiver_id, $amount);
                $stmt->execute();
            } else {
                // Insufficient balance, mark as rejected
                $sql = "UPDATE pending_transactions SET status = 'rejected' WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('i', $transaction_id);
                $stmt->execute();
                $status = 'rejected';
            }
        }
    }
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Transaction ' . $status,
        'transaction_id' => $transaction_id
    ]);
    
} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
} finally {
    if (isset($conn)) $conn->close();
}
?> 