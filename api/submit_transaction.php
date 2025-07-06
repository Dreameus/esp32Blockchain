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

if (!isset($input['receiver_address']) || !isset($input['amount'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$sender_id = $_SESSION['user_id'];
$receiver_address = $input['receiver_address'];
$amount = (int)$input['amount'];

// Validate amount
if ($amount <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid amount']);
    exit;
}

try {
    $conn = getDBConnection();
    
    // Get receiver user ID
    $stmt = $conn->prepare("SELECT id FROM users WHERE wallet_address = ?");
    $stmt->bind_param('s', $receiver_address);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Receiver wallet address not found']);
        exit;
    }
    
    $receiver = $result->fetch_assoc();
    $receiver_id = $receiver['id'];
    
    // Check if sender and receiver are the same
    if ($sender_id === $receiver_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Cannot send tokens to yourself']);
        exit;
    }
    
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
    
    // Insert into pending transactions
    $sql = "INSERT INTO pending_transactions (sender_id, receiver_id, amount, status) VALUES (?, ?, ?, 'pending')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iii', $sender_id, $receiver_id, $amount);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'Transaction submitted for validation',
        'transaction_id' => $conn->insert_id
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
} finally {
    if (isset($conn)) $conn->close();
}
?> 