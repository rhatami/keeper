<?php
require_once '../asset/lib/config.php';
check_login();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

try {
    $transaction_id = $_GET['id'] ?? null;
    $transaction_type = $_GET['type'] ?? null;

    if (!$transaction_id || !$transaction_type) {
        throw new Exception('Transaction ID and type are required');
    }

    if (!in_array($transaction_type, ['buy', 'sell'])) {
        throw new Exception('Invalid transaction type');
    }

    $conn->begin_transaction();

    // Get transaction details
    $table = ($transaction_type === 'buy') ? 'buy' : 'sell';
    $stmt = $conn->prepare("SELECT quantity,total_price, asset FROM $table WHERE id = ? AND user = ?");
    $stmt->bind_param("ii", $transaction_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $transaction = $result->fetch_assoc();

    if (!$transaction) {
        throw new Exception('Transaction not found or unauthorized');
    }

    // Delete the transaction
    $stmt = $conn->prepare("DELETE FROM $table WHERE id = ?");
    $stmt->bind_param("i", $transaction_id);
    $stmt->execute();

    // Update the balance
    $balance_change = ($transaction_type === 'buy') ? -$transaction['quantity'] : $transaction['quantity'];
    $price_change = ($transaction_type === 'buy') ? -$transaction['total_price'] : $transaction['total_price'];
    if (!update_balance($_SESSION['user_id'], $transaction['asset'], $balance_change,$price_change)) {
        throw new Exception('موجودی دارایی شما برای حذف، کافی نیست!');
    }

    $conn->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'تراکنش با موفقیت حذف گردید.'
    ]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
