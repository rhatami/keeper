<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');

define('DB_HOST', 'localhost');
define('DB_NAME', 'YOUR_DB_NAME');
define('DB_USER', 'YOUR_DB_USER');
define('DB_PASS', 'YOUR_DB_PASS');
define('BASE_URL', 'YOUR_BASE_URL');
define('PRICE_API', BASE_URL . '/asset/lib/price.php');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

mysqli_set_charset($conn, "utf8");

// Helper function to format numbers in IRR
function format_price($price) {
    return number_format($price) . " تومان";
}

function convertPersianToEnglishNumbers($string) {
    $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    return str_replace($persian, $english, $string);
}

function check_login() {
    
    if (!isset($_SESSION['user_id'])) {
        
        header("Location: " . BASE_URL . "page/login.php");
        
        exit();
        
    }
}

// Function to get current price from price API
function get_current_price($asset,$type = "None") {
    if($asset == "Stock"){
        $url = PRICE_API . "?asset=Stock&type=" . urlencode($type) ;
    }
    else{
       $url = PRICE_API . "?asset=" . urlencode($asset) . "&type=None"; 
    }
    
    $response = file_get_contents($url);
    $data = json_decode($response, true);
    return $data['data']['price'] ?? 0;
}

function update_balance($user_id, $asset_id, $change, $total) {
    global $conn;
    try {
        $conn->begin_transaction();

        // Get the current balance and average
        $stmt = $conn->prepare("SELECT balance, average FROM balance WHERE user = ? AND asset = ?");
        $stmt->bind_param("ii", $user_id, $asset_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        // Extract current balance and average (default to 0 if no record exists)
        $current_balance = $row ? $row['balance'] : 0;
        $current_average = $row ? $row['average'] : 0;

        // Calculate new balance
        $new_balance = $current_balance + $change;

        // Prevent negative balances
        if ($new_balance < 0) {
            error_log("ERROR: Insufficient balance for user $user_id, asset $asset_id");
            return false;
        }

        if ($new_balance == 0) {
            // If balance is zero, delete the row
            $stmt = $conn->prepare("DELETE FROM balance WHERE user = ? AND asset = ?");
            $stmt->bind_param("ii", $user_id, $asset_id);
            $stmt->execute();
        } else {
            // Calculate current total and new total
            $current_total = $current_balance * $current_average;
            $new_total = $current_total + $total;

            // Calculate new average (avoid division by zero)
            if ($new_balance > 0) {
                $new_average = $new_total / $new_balance;
            } else {
                error_log("ERROR: Division by zero when calculating new average.");
                return false;
            }

            // Update existing balance or insert a new one
            $stmt = $conn->prepare("
                INSERT INTO balance (user, asset, balance, average) 
                VALUES (?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE balance = ?, average = ?
            ");
            // Bind parameters for both INSERT and UPDATE
            $stmt->bind_param("iidddd", 
                $user_id, 
                $asset_id, 
                $new_balance, 
                $new_average, 
                $new_balance, 
                $new_average
            );
            $stmt->execute();
        }

        // Commit the transaction
        $conn->commit();
        return true;

    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        error_log("ERROR updating balance: " . $e->getMessage());
        return false;
    }
}

function get_detail_page($asset_id){
    if($asset_id == 12)
        return 'stock.php';
    else
        return 'detail.php?id=' . $asset_id;
}
?>