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

if (!isset($input['receiver_id']) || !isset($input['amount']) || !isset($input['reason'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$sender_id = $_SESSION['user_id'];
$receiver_id = (int)$input['receiver_id'];
$amount = (int)$input['amount'];
$reason = trim($input['reason']);

// Validate amount
if ($amount <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid amount']);
    exit;
}

// Validate reason
if (strlen($reason) < 3 || strlen($reason) > 100) {
    http_response_code(400);
    echo json_encode(['error' => 'Reason must be between 3 and 100 characters']);
    exit;
}

// Check if sender and receiver are the same
if ($sender_id === $receiver_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Cannot send reward to yourself']);
    exit;
}

try {
    $conn = getDBConnection();
    
    // Verify receiver exists
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE id = ?");
    $stmt->bind_param('i', $receiver_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Receiver not found']);
        exit;
    }
    
    $receiver = $result->fetch_assoc();
    
    // Check sender balance
    $stmt = $conn->prepare("SELECT balance FROM balances WHERE user_id = ?");
    $stmt->bind_param('i', $sender_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $sender_balance = $result->fetch_assoc();
    
    if (!$sender_balance || $sender_balance['balance'] < $amount) {
        http_response_code(400);
        echo json_encode(['error' => 'Insufficient balance']);
        exit;
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    // Deduct from sender
    $stmt = $conn->prepare("UPDATE balances SET balance = balance - ? WHERE user_id = ?");
    $stmt->bind_param('ii', $amount, $sender_id);
    $stmt->execute();
    
    // Add to receiver
    $stmt = $conn->prepare("UPDATE balances SET balance = balance + ? WHERE user_id = ?");
    $stmt->bind_param('ii', $amount, $receiver_id);
    $stmt->execute();
    
    // Record the reward transaction
    $stmt = $conn->prepare("INSERT INTO transactions (sender_id, receiver_id, amount, timestamp, status) VALUES (?, ?, ?, NOW(), 'confirmed')");
    $stmt->bind_param('iii', $sender_id, $receiver_id, $amount);
    $stmt->execute();
    
    $transaction_id = $conn->insert_id;
    
    // Record reward details
    $stmt = $conn->prepare("INSERT INTO rewards (transaction_id, reason, sender_id, receiver_id, amount) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('isiii', $transaction_id, $reason, $sender_id, $receiver_id, $amount);
    $stmt->execute();
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'ESP32 token reward sent successfully!',
        'transaction_id' => $transaction_id,
        'receiver' => $receiver['username'],
        'amount' => $amount,
        'reason' => $reason
    ]);
    
} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
} finally {
    if (isset($conn)) $conn->close();
}
?> 