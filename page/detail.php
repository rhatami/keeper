<?php
require_once '../asset/lib/config.php';
require_once '../asset/lib/jdf.php';

check_login();

$user_id = $_SESSION['user_id'];
$asset_id = $_GET['id'] ?? 0;

// Get asset details
$sql = "SELECT * FROM asset WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $asset_id);
$stmt->execute();
$asset = $stmt->get_result()->fetch_assoc();

if (!$asset) {
    header("Location: ../index.php");
    exit();
}

// Get balance details
$sql = "SELECT * FROM balance WHERE user = ? AND asset = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id , $asset_id);
$stmt->execute();
$balance = $stmt->get_result()->fetch_assoc();

if (!$balance) {
    header("Location: ../index.php");
    exit();
}

// Get current price
$current_price = get_current_price($asset['symbol']);
$current_price = $current_price == 0 ? $balance['average'] : $current_price;

// calculate total buy average price and total current price
$total_buy = $balance['balance'] * $balance['average'];
$total_current = $balance['balance'] * $current_price;
$total_loss_profit = $total_current - $total_buy;

// Get all transactions (both buy and sell) for this asset
$sql = "(SELECT 'buy' as type, id, buy_date as date, quantity, total_price, note FROM buy WHERE user = ? AND asset = ?)
        UNION ALL
        (SELECT 'sell' as type, id, sell_date as date, quantity, total_price, note FROM sell WHERE user = ? AND asset = ?)
        ORDER BY date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiii", $user_id, $asset_id, $user_id, $asset_id);
$stmt->execute();
$transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($asset['name']); ?></title>
    <link rel="stylesheet" href="../asset/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body>
    <div class="detail-container">
        <header class="detail-header">
            <h2><?php echo htmlspecialchars($asset['name']); ?></h2>
            <a href="../index.php" class="btn btn-back"><i class="fas fa-arrow-left"></i></a>
        </header>
        
        <section class="asset-summary">
            <div class="column column1">
                <img class="asset-icon-large" alt="Asset Icon" src=<?php echo "../asset/img/icon/" . $asset['symbol'] . ".png"; ?>>
                 <p class="quantity"><?php echo $balance['balance'] . ' ' . htmlspecialchars($asset['unit']); ?></p>
            </div>
            <div class="column column2">
                <div class="unit-price">
                     <label class="unit-label">قیمت هر <?php echo $asset['unit']; ?> : </label>
                     <span class="current-value"><?php echo format_price($current_price); ?></span>
                </div>
                <div class="average-value">
                    <label class="average-label">مجموع خرید: </label>
                    <span class="current-average-value"><?php echo format_price($total_buy); ?></span>
                </div>
                <div class="total-value">
                    <label class="current-label">ارزش فعلی: </label>
                    <span class="current-total-value"><?php echo format_price($total_current); ?></span>
                </div>
                <div class="loss-profit <?php echo ($total_loss_profit >= 0) ? 'profit' : 'loss'; ?>">
                     <span class="loss-profit-value"><?php echo format_price($total_loss_profit >= 0 ? $total_loss_profit : -$total_loss_profit); ?></span>
                </div>
            </div>
        </section>

        <nav class="action-buttons">
            <a href="transaction.php?type=buy&asset=<?php echo urlencode($asset['symbol']); ?>" class="btn btn-buy">خرید</a>
            <a href="transaction.php?type=sell&asset=<?php echo urlencode($asset['symbol']); ?>" class="btn btn-sell">فروش</a>
        </nav>
  
        <h2 class="transactions-label">تراکنش ها</h2>
        <main class="transactions">
            <?php foreach ($transactions as $transaction): 
                $quantity = floatval($transaction['quantity']);
                $total_price = floatval($transaction['total_price']);
                $unit_price = $quantity > 0 ? $total_price / $quantity : 0;
                $current_value = $current_price * $quantity;
                $benefit = $transaction['type'] == 'buy' ? $current_value - $total_price : $total_price - $current_value;
                $card_class = $transaction['type'] == 'buy' ? 'card-buy' : 'card-sell';
            ?>
                <div class="transaction-card <?php echo $card_class; ?>">
                    <div class="card-body">
                        <div class="row row1">
                            <div><i class="fas fa-coins"></i>
                                <span class="buy-sell"><?php echo $transaction['type'] == "buy" ? "خرید " : " فروش "; echo htmlspecialchars($quantity) . ' ' . htmlspecialchars($asset['unit']); ?></span>
                            </div>
                        </div>
                        <div class="row row2">
                            <p><i class="fas fa-money-bill-wave"></i><span class="total-buy-price"><?php echo format_price($total_price); ?></span></p>
                            <p><i class="fas fa-chart-line"></i><span class="total-profit-loss <?php echo $benefit >= 0 ? 'profit' : 'loss'; ?>">
                                <?php echo $benefit == 0 ? '0' : ($benefit > 0 ? format_price($benefit) : format_price(-$benefit)); ?>
                            </span></p>
                        </div>
                        <div class="row row3">
                            <p><i class="fas fa-dollar-sign"></i><span class="each-unit-price"><?php echo "هر " . htmlspecialchars($asset['unit']) . " " . format_price($total_price / $quantity,0); ?></span></p>
                        </div>
                        <?php if ($transaction['note']): ?>
                            <div class="row row4">
                               <p class="note"><i class="fas fa-sticky-note"></i><?php echo htmlspecialchars($transaction['note']); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <!-- Updated link for editing a transaction -->
                        <a href="transaction.php?id=<?php echo $transaction['id']; ?>&type=<?php echo $transaction['type']; ?>" class="btn btn-edit"><i class="fas fa-edit"></i></a>
                        <span class="transaction-date">
                            <?php
                            $gregorian_date = $transaction['date'];
                            $jalali_date = jdate('l d F Y', strtotime($gregorian_date));
                            echo htmlspecialchars($jalali_date);
                            ?>
                        </span> 
                        <button type="button" class="btn btn-delete delete-transaction" data-type="<?php echo $transaction['type']; ?>" data-id="<?php echo $transaction['id']; ?>"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
            <?php endforeach; ?>
        </main>
    </div>
</body>
</html>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.delete-transaction').forEach(function(button) {
            button.addEventListener('click', function() {
                var type = this.getAttribute('data-type');
                var id = this.getAttribute('data-id');
                if (confirm('Are you sure you want to delete this ' + type + ' transaction?')) {
                    fetch('delete.php?id=' + id + '&type=' + type, {
                        method: 'DELETE',
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            alert(data.message);
                            location.reload();
                        } else {
                            throw new Error(data.message || 'Error deleting ' + type + ' transaction');
                        }
                    })
                    .catch(error => {
                        alert('Error: ' + error.message);
                    });
                }
            });
        });
    });
    </script>
</body>
</html>

