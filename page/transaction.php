<?php
session_start();
require '../asset/lib/config.php';
check_login();

// Determine if it's a buy or sell transaction
$transaction_type = isset($_GET['type']) && $_GET['type'] === 'sell' ? 'sell' : 'buy';
$page_title = $transaction_type == "buy" ? "ثبت خرید" : "ثبت فروش";
$date_field = $transaction_type . '_date';
$table = $transaction_type === 'buy' ? 'buy' : 'sell';

// Check if we are in edit mode
$is_edit = isset($_GET['id']);
$transaction_id = $is_edit ? $_GET['id'] : null;
$user_id = $_SESSION['user_id'];

// Fetch available assets from the database
$assets = $conn->query("SELECT * FROM asset ORDER BY name");

// Initialize variables for edit mode
$transaction = null;
$defaut_asset_unit = '';
$redirect = '../index.php';

if ($is_edit) {
    // Fetch the existing transaction data
    $stmt = $conn->prepare("SELECT $table.*, asset.unit FROM $table JOIN asset ON $table.asset = asset.id WHERE $table.id = ? AND $table.user = ?");
    $stmt->bind_param("ii", $transaction_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        header("Location: ../index.php");
        exit();
    }

    $transaction = $result->fetch_assoc();
    $defaut_asset_unit = $transaction['unit'];
    $redirect = $transaction['asset'] == 12 ? "stock.php" : "detail.php?id=" . $transaction['asset'];
    $page_title = $transaction_type == "buy" ? "ویرایش خرید" : "ویرایش فروش";
} else {
    // Get the asset parameter from the URL, if it exists if not Gold is default
    $default_asset_symbol = isset($_GET['asset']) ? $_GET['asset'] : "XAU";
    $default_asset_id = 0;

    // Find the ID of default Symbol
    if ($default_asset_symbol) {
        // Loop through assets to find the matching symbol
        while ($asset = $assets->fetch_assoc()) {
            if ($asset['symbol'] === $default_asset_symbol) {
                $default_asset_id = $asset['id'];
                $defaut_asset_unit = $asset['unit'];
                break;
            }
        }

        // Reset the pointer back to the start of the result set for later use
        $assets->data_seek(0);
    }

    $redirect = $default_asset_id ? ( $default_asset_id == 12 ? "stock.php" : "detail.php?id=" . $default_asset_id) : "../index.php";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $asset_id = $_POST['asset'];
    $new_quantity = filter_var($_POST['quantity'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $transaction_date = convertPersianToEnglishNumbers($_POST[$date_field]);
    $total_price = $_POST['total_price'];
    $note = isset($_POST['note']) ? substr($_POST['note'], 0, 100) : ''; // Limit note to 100 characters
    $stock = ($asset_id == '12' && isset($_POST['stock'])) ? substr($_POST['stock'], 0, 100) : null;
    
    // Start transaction
    $conn->begin_transaction();

    try {
        if ($is_edit) {
            // Calculate the difference in quantity
            $quantity_difference = $new_quantity - $transaction['quantity'];
            $price_difference = $total_price - $transaction['total_price'];

            // Update the transaction
            if ($stock !== null) {
                $stmt = $conn->prepare("UPDATE $table SET asset = ?, quantity = ?, $date_field = ?, total_price = ?, note = ?, type = ? WHERE id = ? AND user = ?");
                $stmt->bind_param("idsdsssi", $asset_id, $new_quantity, $transaction_date, $total_price, $note, $stock, $transaction_id, $user_id);
            } else {
                $stmt = $conn->prepare("UPDATE $table SET asset = ?, quantity = ?, $date_field = ?, total_price = ?, note = ?, type = NULL WHERE id = ? AND user = ?");
                $stmt->bind_param("idsdssi", $asset_id, $new_quantity, $transaction_date, $total_price, $note, $transaction_id, $user_id);
            }
            $stmt->execute();

            // Update the balance
            if ($transaction_type === 'buy') {
                $balance_change = $quantity_difference;
                $price_change = $price_difference;
            } else { // sell
                $balance_change = -$quantity_difference;
                $price_change = -$price_difference;
            }
        } else {
            if ($transaction_type === 'sell') {
                // Check if user has enough balance
                $stmt = $conn->prepare("SELECT balance FROM balance WHERE user = ? AND asset = ?");
                $stmt->bind_param("ii", $user_id, $asset_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $current_balance = $result->fetch_assoc()['balance'] ?? 0;

                if ($current_balance < $new_quantity) {
                    throw new Exception("Insufficient balance. Current balance: $current_balance, Attempted to sell: $new_quantity");
                }
            }

            // Insert into transaction table
            if ($stock !== null) {
                $stmt = $conn->prepare("INSERT INTO $table (user, asset, quantity, {$date_field}, total_price, note, type) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iidsdss", $user_id, $asset_id, $new_quantity, $transaction_date, $total_price, $note, $stock);
            } else {
                $stmt = $conn->prepare("INSERT INTO $table (user, asset, quantity, {$date_field}, total_price, note) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iidsds", $user_id, $asset_id, $new_quantity, $transaction_date, $total_price, $note);
            }
            $stmt->execute();

            // Update balance using the update_balance function
            $balance_change = $transaction_type === 'buy' ? $new_quantity : -$new_quantity;
            $price_change = $transaction_type === 'buy' ? $total_price : -$total_price;
        }

        $result = update_balance($user_id, $asset_id, $balance_change, $price_change);

        if ($result === false) {
            throw new Exception("Failed to update balance");
        }

        // Commit transaction
        $conn->commit();

        header("Location: " . $redirect);
        exit();
    } catch (Exception $e) {
        // An error occurred, rollback the transaction
        $conn->rollback();
        die("An error occurred: " . $e->getMessage());
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="../asset/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="../asset/css/persian-datepicker.min.css">
</head>
<body>
    <div class="transaction-container">
        <header class="transaction-header">
            <h1><?php echo $page_title; ?></h1>
            <a href="<?php echo $redirect; ?>" class="btn btn-back"><i class="fas fa-arrow-left"></i></a>
        </header>
        <main class="form-container">
            <form method="POST" action="" class="transaction-form">
                <div class="form-group">
                    <label for="asset"><i class="fas fa-chart-pie"></i></label>
                    <select name="asset" id="asset" required>
                        <?php
                        $assets->data_seek(0);
                        while ($asset = $assets->fetch_assoc()):
                            $selected = ($is_edit && $asset['id'] == $transaction['asset']) || (!$is_edit && $asset['symbol'] === $default_asset_symbol) ? 'selected' : '';
                            $unit = htmlspecialchars($asset['unit']);
                            echo '<option value="' . htmlspecialchars($asset['id']) . '" data-unit="' . $unit . '" ' . $selected . '>';
                            echo htmlspecialchars($asset['name']);
                            echo '</option>';
                        endwhile;
                        ?>
                    </select>
                </div>

                <div class="form-group label-wrapper" id="stock-wrapper">
                    <label for="stock"><i class="fas fas fa-tag"></i>نام نماد</label>
                    <input type="text" name="stock" id="stock" value="<?php echo $is_edit ? htmlspecialchars($transaction['type']) : ''; ?>" required>
                </div>
                
                <div class="form-group label-wrapper">
                    <label for="quantity"><i class="fas fa-balance-scale"></i>مقدار</label>
                    <input type="text" name="quantity" id="quantity" required
                           value="<?php echo $is_edit ? htmlspecialchars($transaction['quantity']) : ''; ?>"
                           pattern="^\d+(\.\d{1,3})?$"
                           title="Enter a positive number with up to 3 decimal places"
                           oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');">
                    <label id="unit-label"><?php echo $is_edit ? htmlspecialchars($defaut_asset_unit) : htmlspecialchars($defaut_asset_unit); ?></label>
                </div>

                <div class="form-group label-wrapper">
                    <label for="total_price"><i class="fas fa-money-bill-wave"></i>مبلغ کل</label>
                    <input type="number" name="total_price" id="total_price" value="<?php echo $is_edit ? htmlspecialchars($transaction['total_price']) : ''; ?>" required>
                    <label>تومان</label>
                </div>

                <div class="form-group">
                    <label for="<?php echo $date_field; ?>"><i class="fas fa-calendar-alt"></i></label>
                    <input type="text" name="<?php echo $date_field; ?>_display" id="<?php echo $date_field; ?>_display" required readonly>
                    <input type="hidden" name="<?php echo $date_field; ?>" id="<?php echo $date_field; ?>" value="<?php echo $is_edit ? htmlspecialchars($transaction[$date_field]) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="note"><i class="fas fa-sticky-note"></i>یادداشت</label>
                    <textarea name="note" id="note" maxlength="100" rows="3" placeholder="ثبت یادداشت اختیاری است."><?php echo $is_edit ? htmlspecialchars($transaction['note']) : ''; ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary <?php echo ($transaction_type == "buy" ? "btn-buy" : "btn-sell"); ?>">
                        <?php echo $is_edit ? 'به روز رسانی ' . ($transaction_type == "buy" ? "خرید" : "فروش") : $page_title; ?></button>
                </div>
            </form>
        </main>
    </div>

    <script src="../asset/js/jquery-3.6.0.min.js"></script>
    <script src="../asset/js/persian-date.min.js"></script>
    <script src="../asset/js/persian-datepicker.min.js"></script>
    <script>
        var dateFieldId = '<?php echo $date_field; ?>';
        var isEdit = <?php echo $is_edit ? 'true' : 'false'; ?>;
        var initialDate = '<?php echo $transaction[$date_field]; ?>';
    </script>
    <script src="../asset/js/transaction.js"></script>
</body>
</html>
